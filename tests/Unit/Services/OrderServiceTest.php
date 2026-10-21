<?php

namespace Tests\Unit\Services;

use App\Model\Cashier;
use App\Model\CompanyInfo;
use App\Model\Item;
use App\Model\Order;
use App\Model\OrderItem;
use App\Services\OrderService;
use Carbon\Carbon;
use CompanyInfoTableSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SimulatesDatabaseError;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;
    use SimulatesDatabaseError;

    /** @var \App\Services\OrderService */
    private $service;

    /** @var \App\Model\Item */
    private $cottonCandy;

    /** @var \App\Model\Item */
    private $potato;

    protected function setUp(): void
    {
        parent::setUp();

        // 会社の現金（初期値 32,000円）
        $this->seed(CompanyInfoTableSeeder::class);

        $this->service = $this->app->make(OrderService::class);
        $this->cottonCandy = factory(Item::class)->create(['price' => 300, 'stock' => 50]);
        $this->potato = factory(Item::class)->create(['price' => 150, 'stock' => 100]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 注文の入力を作成する
     *
     * @param  array  $overrides  上書きする項目
     * @return array
     */
    private function orderData(array $overrides = [])
    {
        return array_merge([
            'num_of_people' => 2,
            'paid'          => 1000,
            'change'        => 250,
            'remarks'       => '備考',
        ], $overrides);
    }

    /**
     * 注文明細の入力を作成する（OrderRequest::orderItems() の形）
     *
     * @param  \App\Model\Item  $item
     * @param  int   $price
     * @param  int   $quantity
     * @param  bool  $delete
     * @return array
     */
    private function orderItem(Item $item, $price, $quantity, $delete = false)
    {
        return [
            'item_id'  => (string) $item->id,
            'price'    => (string) $price,
            'quantity' => (string) $quantity,
            'delete'   => $delete,
        ];
    }

    /**
     * 会社の現金を取得する
     *
     * @return int
     */
    private function companyCash()
    {
        return (int) CompanyInfo::first()->cash;
    }

    public function testRegisterCreatesOrderAndRelatedRecords()
    {
        Carbon::setTestNow('2019-11-30 12:00:00');

        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 2),
            $this->orderItem($this->potato, 150, 1),
        ]);

        $order = $order->fresh();
        // 注文日時を指定しない場合は、注文日時・作成日時ともに現在日時
        $this->assertSame('2019-11-30 12:00:00', $order->order_at->format('Y-m-d H:i:s'));
        $this->assertSame('2019-11-30 12:00:00', $order->created_at->format('Y-m-d H:i:s'));
        // お釣りは 支払金額 − 合計金額（750円）で計算し直す
        $this->assertSame(250, (int) $order->change);
        $this->assertSame(2, OrderItem::where('order_id', $order->id)->count());
        $this->assertSame(48, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(99, (int) $this->potato->fresh()->stock);
        $this->assertDatabaseHas('cashier', [
            'order_id'         => $order->id,
            'cashier_type'     => 'income',
            'income_amount'    => 1000,
            'payment_amount'   => 250,
            'deduction_amount' => 750,
            'remarks'          => '備考',
            'transaction_time' => '2019-11-30 12:00:00',
        ]);
        $this->assertSame(32000 + 750, $this->companyCash());
    }

    public function testRegisterUsesGivenOrderAt()
    {
        Carbon::setTestNow('2026-10-19 14:00:00');

        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ], '2019-12-01 10:15:00');

        $order = $order->fresh();
        $this->assertSame('2019-12-01 10:15:00', $order->order_at->format('Y-m-d H:i:s'));
        // 作成日時は、注文日時ではなく実際に登録した日時
        $this->assertSame('2026-10-19 14:00:00', $order->created_at->format('Y-m-d H:i:s'));
        // 収入の出納記録の取引日時は、注文日時と同じ
        $this->assertSame('2019-12-01 10:15:00', Cashier::where('order_id', $order->id)->first()->transaction_time);
    }

    public function testRegisterTreatsEmptyPriceAndQuantityAsZero()
    {
        $order = $this->service->register($this->orderData(), [
            ['item_id' => (string) $this->cottonCandy->id, 'price' => null, 'quantity' => null, 'delete' => false],
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'item_id' => $this->cottonCandy->id, 'price' => 0, 'quantity' => 0,
        ]);
        $this->assertSame(50, (int) $this->cottonCandy->fresh()->stock);
    }

    public function testRegisterSkipsItemMarkedAsDeleted()
    {
        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
            $this->orderItem($this->potato, 150, 1, true),
        ]);

        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id, 'item_id' => $this->potato->id]);
        $this->assertSame(100, (int) $this->potato->fresh()->stock);
    }

    public function testRegisterExcludesItemMarkedAsDeletedFromTotal()
    {
        $order = $this->service->register($this->orderData(['paid' => 1000]), [
            $this->orderItem($this->cottonCandy, 300, 1),
            $this->orderItem($this->potato, 150, 1, true),
        ]);

        // 合計金額は削除チェックのない綿菓子の 300円だけ
        $this->assertSame(700, (int) $order->fresh()->change);
        $this->assertSame(32000 + 300, $this->companyCash());
    }

    public function testUpdateCreatesIncomeWhenPaidIsEnteredLater()
    {
        // 支払 0円で登録した注文には、収入の記録がなく、会社の現金も増えない
        $order = $this->service->register($this->orderData(['paid' => 0, 'change' => 0]), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ], '2019-11-30 10:15:00');
        $this->assertSame(0, Cashier::count());
        $this->assertSame(32000, $this->companyCash());

        $this->service->update($order->id, $this->orderData(['paid' => 1000, 'change' => 700]), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        $this->assertDatabaseHas('cashier', [
            'order_id'         => $order->id,
            'cashier_type'     => 'income',
            'income_amount'    => 1000,
            'payment_amount'   => 700,
            'deduction_amount' => 300,
            'transaction_time' => '2019-11-30 10:15:00',
        ]);
        $this->assertSame(32000 + 300, $this->companyCash());
    }

    public function testUpdateWithoutIncomeKeepsCashWhenPaidStaysZero()
    {
        $order = $this->service->register($this->orderData(['paid' => 0, 'change' => 0]), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        $this->service->update($order->id, $this->orderData(['paid' => 0, 'change' => 0]), [
            $this->orderItem($this->cottonCandy, 300, 2),
        ]);

        $this->assertSame(0, Cashier::count());
        $this->assertSame(32000, $this->companyCash());
    }

    public function testRegisterWithZeroPaidDoesNotCreateCashier()
    {
        $this->service->register($this->orderData(['paid' => 0, 'change' => 0]), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        $this->assertSame(0, Cashier::count());
        $this->assertSame(32000, $this->companyCash());
    }

    public function testGetOrderDetailCalculatesTotalAndChange()
    {
        $order = $this->service->register($this->orderData(['paid' => 1000]), [
            $this->orderItem($this->cottonCandy, 300, 2),
            $this->orderItem($this->potato, 150, 1),
        ]);

        $detail = $this->service->getOrderDetail($order->id);

        $this->assertSame($order->id, $detail['order']->id);
        $this->assertSame(750, $detail['order']->order_total);
        $this->assertSame(250, $detail['order']->change);
        $this->assertCount(2, $detail['order_items']);
    }

    public function testGetOrderDetailThrowsWhenOrderIsMissing()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->service->getOrderDetail(999999);
    }

    public function testUpdateChangesQuantityAndRecalculates()
    {
        $order = $this->service->register($this->orderData(['paid' => 500, 'change' => 200]), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        $this->service->update($order->id, $this->orderData(['paid' => 1000, 'change' => 100, 'remarks' => '変更後']), [
            $this->orderItem($this->cottonCandy, 300, 3),
        ]);

        $order = $order->fresh();
        $this->assertSame(1000, (int) $order->paid);
        $this->assertSame(100, (int) $order->change);
        $this->assertSame('変更後', $order->remarks);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'item_id' => $this->cottonCandy->id, 'quantity' => 3,
        ]);
        // 在庫は元の数量（1）を戻してから、新しい数量（3）を引く
        $this->assertSame(47, (int) $this->cottonCandy->fresh()->stock);
        $this->assertDatabaseHas('cashier', [
            'order_id'         => $order->id,
            'income_amount'    => 1000,
            'payment_amount'   => 100,
            'deduction_amount' => 900,
            'remarks'          => '変更後',
        ]);
        $this->assertSame(32000 + 900, $this->companyCash());
    }

    public function testUpdateAddsNewItem()
    {
        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        $this->service->update($order->id, $this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
            $this->orderItem($this->potato, 150, 2),
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'item_id' => $this->potato->id, 'quantity' => 2,
        ]);
        $this->assertSame(49, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(98, (int) $this->potato->fresh()->stock);
    }

    public function testUpdateRemovesItemMarkedAsDeleted()
    {
        $order = $this->service->register($this->orderData(['paid' => 1000]), [
            $this->orderItem($this->cottonCandy, 300, 2),
            $this->orderItem($this->potato, 150, 1),
        ]);
        $this->assertSame(32000 + 750, $this->companyCash());

        $this->service->update($order->id, $this->orderData(['paid' => 1000]), [
            $this->orderItem($this->cottonCandy, 300, 2),
            $this->orderItem($this->potato, 150, 1, true),
        ]);

        $this->assertSame(1, OrderItem::where('order_id', $order->id)->count());
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $order->id, 'item_id' => $this->potato->id, 'deleted_at' => null,
        ]);
        // 削除した明細の在庫が戻り、残りの明細の在庫はそのまま
        $this->assertSame(100, (int) $this->potato->fresh()->stock);
        $this->assertSame(48, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(400, (int) $order->fresh()->change);
        $this->assertSame(32000 + 600, $this->companyCash());
    }

    public function testUpdateReplacesItemWhenRowItemIsChanged()
    {
        $order = $this->service->register($this->orderData(['paid' => 1000]), [
            $this->orderItem($this->cottonCandy, 300, 2),
        ]);
        $this->assertSame(32000 + 600, $this->companyCash());

        // 画面で、1行目のアイテムを綿菓子からポテトに変えた場合
        $this->service->update($order->id, $this->orderData(['paid' => 1000]), [
            $this->orderItem($this->potato, 150, 1),
        ]);

        $this->assertSame(
            [$this->potato->id],
            OrderItem::where('order_id', $order->id)->pluck('item_id')->map(function ($id) {
                return (int) $id;
            })->all()
        );
        // 綿菓子の在庫は戻り、ポテトの在庫が減る
        $this->assertSame(50, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(99, (int) $this->potato->fresh()->stock);
        $this->assertSame(850, (int) $order->fresh()->change);
        $this->assertSame(32000 + 150, $this->companyCash());
    }

    public function testUpdateIgnoresItemMarkedAsDeletedThatWasNotSaved()
    {
        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        // 画面で追加した行に、保存前に削除チェックを付けた場合
        $this->service->update($order->id, $this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
            $this->orderItem($this->potato, 150, 1, true),
        ]);

        $this->assertSame(1, OrderItem::where('order_id', $order->id)->count());
        $this->assertSame(100, (int) $this->potato->fresh()->stock);
    }

    public function testUpdateAdjustsCashByPriceTimesQuantity()
    {
        $order = $this->service->register($this->orderData(['paid' => 1000]), [
            $this->orderItem($this->cottonCandy, 300, 3),
        ]);
        $this->assertSame(32000 + 900, $this->companyCash());

        $this->service->update($order->id, $this->orderData(['paid' => 1000]), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        // 変更前の 900円（300円 × 3個）を引いてから、変更後の 300円を足す
        $this->assertSame(32000 + 300, $this->companyCash());
    }

    public function testUpdateKeepsOrderAtWhenNotGiven()
    {
        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ], '2019-11-30 10:00:00');

        $this->service->update($order->id, $this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        $this->assertSame('2019-11-30 10:00:00', $order->fresh()->order_at->format('Y-m-d H:i:s'));
        $this->assertSame('2019-11-30 10:00:00', Cashier::where('order_id', $order->id)->first()->transaction_time);
    }

    public function testUpdateChangesOrderAtWhenGiven()
    {
        Carbon::setTestNow('2026-10-19 14:00:00');
        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ], '2019-11-30 10:00:00');
        Carbon::setTestNow('2026-10-20 09:00:00');

        $this->service->update($order->id, $this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ], '2019-12-01 14:30:00');

        $updated = $order->fresh();
        $this->assertSame('2019-12-01 14:30:00', $updated->order_at->format('Y-m-d H:i:s'));
        // 作成日時は、登録したときのまま変えない
        $this->assertSame('2026-10-19 14:00:00', $updated->created_at->format('Y-m-d H:i:s'));
        // 収入の出納記録の取引日時も、新しい注文日時にそろえる
        $this->assertSame('2019-12-01 14:30:00', Cashier::where('order_id', $order->id)->first()->transaction_time);
    }

    public function testDeleteSoftDeletesOrderAndRestoresStock()
    {
        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 2),
            $this->orderItem($this->potato, 150, 1),
        ]);

        $this->service->delete($order->id);

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
        $this->assertSame(0, OrderItem::where('order_id', $order->id)->count());
        $this->assertSame(0, Cashier::where('order_id', $order->id)->count());
        $this->assertSame(50, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(100, (int) $this->potato->fresh()->stock);
    }

    public function testDeleteDecreasesCompanyCash()
    {
        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 2),
            $this->orderItem($this->potato, 150, 1),
        ]);
        $this->assertSame(32000 + 750, $this->companyCash());

        $this->service->delete($order->id);

        $this->assertSame(32000, $this->companyCash());
    }

    public function testDeleteOrderWithoutIncomeKeepsCompanyCash()
    {
        // 支払 0円の注文は、登録時に出納記録も現金の加算もない
        $order = $this->service->register($this->orderData(['paid' => 0, 'change' => 0]), [
            $this->orderItem($this->cottonCandy, 300, 1),
        ]);

        $this->service->delete($order->id);

        $this->assertSame(32000, $this->companyCash());
    }

    public function testDeleteRollsBackAndRethrowsOriginalException()
    {
        $order = $this->service->register($this->orderData(), [
            $this->orderItem($this->cottonCandy, 300, 2),
        ]);
        // 在庫を戻し、明細と出納記録を論理削除した後の、最後の保存で DB エラーが起きたことにする
        $this->failOnSaving(Order::class);

        $this->assertThrowsSimulatedDatabaseError(function () use ($order) {
            $this->service->delete($order->id);
        });

        // すべての変更が元に戻っている
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'deleted_at' => null]);
        $this->assertSame(1, OrderItem::where('order_id', $order->id)->count());
        $this->assertSame(1, Cashier::where('order_id', $order->id)->count());
        $this->assertSame(48, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(32000 + 600, $this->companyCash());
    }

    public function testDeleteThrowsWhenOrderIsMissing()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->service->delete(999999);
    }

    public function testPaginateExcludesDeletedOrders()
    {
        factory(Order::class, 2)->create();
        factory(Order::class)->create()->delete();

        $result = $this->service->paginate(10);

        $this->assertSame(2, $result['total_count']);
        $this->assertCount(2, $result['records']);
    }
}

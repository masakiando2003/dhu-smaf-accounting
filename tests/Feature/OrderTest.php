<?php

namespace Tests\Feature;

use App\Model\Cashier;
use App\Model\CompanyInfo;
use App\Model\Item;
use App\Model\Order;
use App\Model\OrderItem;
use CompanyInfoTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @var \App\Model\Item */
    private $cottonCandy;

    /** @var \App\Model\Item */
    private $potato;

    protected function setUp(): void
    {
        parent::setUp();

        // 会社の現金（初期値 32,000円）
        $this->seed(CompanyInfoTableSeeder::class);

        $this->cottonCandy = factory(Item::class)->create(['name' => '綿菓子', 'price' => 300, 'stock' => 50]);
        $this->potato = factory(Item::class)->create(['name' => 'ポテト', 'price' => 150, 'stock' => 100]);
    }

    protected function tearDown(): void
    {
        $_POST = [];

        parent::tearDown();
    }

    /**
     * 注文フォームの入力値を作成する
     *
     * @param  array  $items      [[アイテム, 単価, 数量], ...]
     * @param  array  $overrides  上書きする項目
     * @return array
     */
    private function orderInput(array $items, array $overrides = [])
    {
        $input = [
            'num_of_people'       => '2',
            'paid'                => '1000',
            'change'              => '0',
            'remarks'             => '備考',
            'transactionTimeType' => 'auto',
            'order_item_count'    => (string) count($items),
        ];
        foreach (array_values($items) as $i => [$item, $price, $quantity]) {
            $n = $i + 1;
            $input['item_id_'.$n] = (string) $item->id;
            $input['price_'.$n] = (string) $price;
            $input['quantity_'.$n] = (string) $quantity;
        }

        return array_merge($input, $overrides);
    }

    /**
     * 注文フォームを送信する
     *
     * 注文明細はコントローラーが $_POST から直接読むため、$_POST にも同じ値を入れる.
     *
     * @param  string  $uri
     * @param  array   $input
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    private function postOrder(string $uri, array $input)
    {
        $_POST = $input;

        return $this->post($uri, $input);
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

    public function testIndexListsOrders()
    {
        $order = factory(Order::class)->create();

        $this->get('/admin/orders')
             ->assertOk()
             ->assertSee('/admin/orders/detail/'.$order->id);
    }

    public function testCreatePageListsItems()
    {
        $this->get('/admin/orders/create')
             ->assertOk()
             ->assertSee('新規注文作成');
    }

    public function testDetailPageShowsOrderItems()
    {
        $order = factory(Order::class)->create(['paid' => 1000]);
        factory(OrderItem::class)->create([
            'order_id' => $order->id, 'item_id' => $this->cottonCandy->id, 'price' => 300, 'quantity' => 2,
        ]);

        $this->get('/admin/orders/detail/'.$order->id)
             ->assertOk()
             ->assertSee('注文詳細')
             ->assertSee('綿菓子');
    }

    public function testRegisterCreatesOrderWithItemsStockAndCashier()
    {
        $input = $this->orderInput(
            [[$this->cottonCandy, 300, 2], [$this->potato, 150, 1]],
            ['paid' => '1000', 'change' => '250']
        );

        $this->postOrder('/admin/orders/register', $input)
             ->assertRedirect('/admin/orders')
             ->assertSessionHas('success_msg');

        $order = Order::first();
        $this->assertSame(2, (int) $order->num_of_people);
        // お釣りはサーバー側で 支払金額 − 合計金額（750円）として計算し直す
        $this->assertSame(250, (int) $order->change);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'item_id' => $this->cottonCandy->id, 'price' => 300, 'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'item_id' => $this->potato->id, 'price' => 150, 'quantity' => 1,
        ]);

        // 在庫が注文数だけ減る
        $this->assertSame(48, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(99, (int) $this->potato->fresh()->stock);

        // 「収入」の出納記録が作られる
        $this->assertDatabaseHas('cashier', [
            'order_id'         => $order->id,
            'cashier_type'     => 'income',
            'description'      => '注文ID: '.$order->id.'に支払する',
            'income_amount'    => 1000,
            'payment_amount'   => 250,
            'deduction_amount' => 750,
        ]);

        // 会社の現金が合計金額だけ増える
        $this->assertSame(32000 + 750, $this->companyCash());
    }

    public function testRegisterWithMultipleCreateFlagRedirectsToCreatePage()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], ['multiple_create_flag' => '1']);

        $this->postOrder('/admin/orders/register', $input)
             ->assertRedirect('/admin/orders/create');
    }

    public function testRegisterWithManualTimeUsesInputTime()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], [
            'transactionTimeType' => 'manual',
            'transactionDate'     => '2019/11/30',
            'transactionTime'     => '10:15:00',
        ]);

        $this->postOrder('/admin/orders/register', $input);

        $this->assertSame('2019-11-30 10:15:00', Order::first()->created_at->format('Y-m-d H:i:s'));
    }

    public function testRegisterWithZeroPaidDoesNotCreateCashier()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], ['paid' => '0']);

        $this->postOrder('/admin/orders/register', $input);

        $this->assertSame(1, Order::count());
        $this->assertSame(0, Cashier::count());
        $this->assertSame(32000, $this->companyCash());
    }

    public function testRegisterRejectsMissingPaid()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], ['paid' => '']);

        $this->from('/admin/orders/create')
             ->postOrder('/admin/orders/register', $input)
             ->assertRedirect('/admin/orders/create')
             ->assertSessionHasErrors(['paid' => '支払金額が指定されていません']);

        $this->assertSame(0, Order::count());
    }

    public function testRegisterRejectsNonNumericNumOfPeople()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], ['num_of_people' => '二人']);

        $this->from('/admin/orders/create')
             ->postOrder('/admin/orders/register', $input)
             ->assertSessionHasErrors(['num_of_people' => '人数に数字以外が試用されています']);

        $this->assertSame(0, Order::count());
    }

    public function testEditUpdatesQuantityStockCashierAndCash()
    {
        $this->postOrder('/admin/orders/register', $this->orderInput(
            [[$this->cottonCandy, 300, 1]],
            ['paid' => '500', 'change' => '200']
        ));
        $order = Order::first();
        $this->assertSame(49, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(32000 + 300, $this->companyCash());

        $this->postOrder('/admin/orders/edit', $this->orderInput(
            [[$this->cottonCandy, 300, 3]],
            ['order_id' => (string) $order->id, 'paid' => '1000', 'change' => '100', 'remarks' => '変更後']
        ))
             ->assertRedirect('/admin/orders')
             ->assertSessionHas('success_msg', '注文ID: "'.$order->id.'"を更新しました');

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'item_id' => $this->cottonCandy->id, 'quantity' => 3,
        ]);
        $this->assertSame(1000, (int) $order->fresh()->paid);
        $this->assertSame(100, (int) $order->fresh()->change);

        // 在庫は元の数量を戻してから、新しい数量を引く
        $this->assertSame(47, (int) $this->cottonCandy->fresh()->stock);

        $this->assertDatabaseHas('cashier', [
            'order_id'         => $order->id,
            'income_amount'    => 1000,
            'payment_amount'   => 100,
            'deduction_amount' => 900,
            'remarks'          => '変更後',
        ]);

        // 会社の現金は 300円 → 900円 に差し替わる
        $this->assertSame(32000 + 900, $this->companyCash());
    }

    public function testEditAddsNewOrderItem()
    {
        $this->postOrder('/admin/orders/register', $this->orderInput([[$this->cottonCandy, 300, 1]]));
        $order = Order::first();

        $this->postOrder('/admin/orders/edit', $this->orderInput(
            [[$this->cottonCandy, 300, 1], [$this->potato, 150, 2]],
            ['order_id' => (string) $order->id]
        ));

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'item_id' => $this->potato->id, 'price' => 150, 'quantity' => 2,
        ]);
        $this->assertSame(49, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(98, (int) $this->potato->fresh()->stock);
    }

    public function testEditWithoutOrderIdRedirectsToIndex()
    {
        $this->postOrder('/admin/orders/edit', $this->orderInput([[$this->cottonCandy, 300, 1]]))
             ->assertRedirect('/admin/orders');
    }

    public function testDeleteSoftDeletesOrderAndRestoresStock()
    {
        $this->postOrder('/admin/orders/register', $this->orderInput([[$this->cottonCandy, 300, 2]]));
        $order = Order::first();
        $this->assertSame(48, (int) $this->cottonCandy->fresh()->stock);

        $this->delete('/admin/orders/delete/', ['del_id' => $order->id])
             ->assertRedirect('/admin/orders')
             ->assertSessionHas('success_msg', '注文ID: "'.$order->id.'"を削除しました');

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
        $this->assertSoftDeleted('order_items', ['order_id' => $order->id]);
        $this->assertSoftDeleted('cashier', ['order_id' => $order->id]);
        $this->assertSame(50, (int) $this->cottonCandy->fresh()->stock);
    }
}

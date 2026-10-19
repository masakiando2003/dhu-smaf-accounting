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

    public function testIndexShowsPaginationLinksWhenOrdersExceedOnePage()
    {
        factory(Order::class, 11)->create();

        $this->get('/admin/orders')
             ->assertOk()
             ->assertSee('page=2');
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

    public function testDetailPageNumbersEachOrderItemRow()
    {
        $order = factory(Order::class)->create(['paid' => 1000]);
        factory(OrderItem::class)->create([
            'order_id' => $order->id, 'item_id' => $this->cottonCandy->id, 'price' => 300, 'quantity' => 2,
        ]);
        factory(OrderItem::class)->create([
            'order_id' => $order->id, 'item_id' => $this->potato->id, 'price' => 150, 'quantity' => 1,
        ]);

        $html = $this->get('/admin/orders/detail/'.$order->id)->assertOk()->getContent();

        // 明細ごとに 1, 2 と行番号が振られる
        $this->assertStringContainsString('id="item_id_1" value="'.$this->cottonCandy->id.'"', $html);
        $this->assertStringContainsString('id="item_id_2" value="'.$this->potato->id.'"', $html);
        $this->assertStringContainsString('id="quantity_1" value="2"', $html);
        $this->assertStringContainsString('id="quantity_2" value="1"', $html);
        // 明細の数は1か所だけ
        $this->assertSame(1, substr_count($html, 'name="order_item_count"'));
        $this->assertStringContainsString('id="order_item_count" value="2"', $html);
        // ドロップダウンで、各行の実際のアイテムが選ばれている
        $this->assertSame(2, preg_match_all('/<option value="(\d+)" data-price="\d+"\s+selected\s*>/', $html, $matches));
        $this->assertSame([(string) $this->cottonCandy->id, (string) $this->potato->id], $matches[1]);
    }

    public function testDetailPageShowsOrderIdAndOrderTimeLabel()
    {
        $order = factory(Order::class)->create(['order_at' => '2019-11-30 10:15:00']);

        $this->get('/admin/orders/detail/'.$order->id)
             ->assertOk()
             ->assertSeeInOrder(['注文ID', (string) $order->id, '注文時間', 'value="2019/11/30"', 'value="10:15:00"']);
    }

    public function testDetailPageEditsOrderTimeWithoutAutoOption()
    {
        $order = factory(Order::class)->create(['order_at' => '2019-11-30 10:15:00']);

        $html = $this->get('/admin/orders/detail/'.$order->id)->assertOk()->getContent();

        // 自動採番は選べず、注文時間の入力欄が最初から表示される
        $this->assertStringNotContainsString('id="transaction_auto"', $html);
        $this->assertStringNotContainsString('type="radio"', $html);
        $this->assertStringContainsString('<input type="hidden" name="transactionTimeType" id="transaction_manual" value="manual" />', $html);
        $this->assertRegExp('/<div id="time_input" class="col-sm-4" >/', $html);
    }

    public function testCreatePageShowsOrderTimeLabelWithoutOrderId()
    {
        $html = $this->get('/admin/orders/create')
                     ->assertOk()
                     ->assertSeeInOrder(['注文時間', '自動採番', '入力'])
                     ->assertDontSee('注文ID')
                     ->getContent();

        // 新規作成では自動採番が選ばれていて、入力欄は隠れている
        $this->assertRegExp('/id="transaction_auto" value="auto"\s+checked/', $html);
        $this->assertStringContainsString('<div id="time_input" class="col-sm-4"  style="display:none;" >', $html);
    }

    public function testEditKeepsOrderTimeWhenNotChanged()
    {
        $this->post('/admin/orders/register', $this->orderInput([[$this->cottonCandy, 300, 1]], [
            'transactionTimeType' => 'manual',
            'transactionDate'     => '2019/11/30',
            'transactionTime'     => '10:15:00',
        ]));
        $order = Order::first();

        // 編集画面は、表示された注文時間をそのまま送る
        $this->post('/admin/orders/edit', $this->orderInput([[$this->cottonCandy, 300, 2]], [
            'order_id'            => (string) $order->id,
            'transactionTimeType' => 'manual',
            'transactionDate'     => '2019/11/30',
            'transactionTime'     => '10:15:00',
        ]))->assertRedirect('/admin/orders');

        $this->assertSame('2019-11-30 10:15:00', $order->fresh()->order_at->format('Y-m-d H:i:s'));
    }

    public function testDetailPageKeepsEnteredOrderTimeAfterValidationError()
    {
        $order = factory(Order::class)->create(['order_at' => '2019-11-30 10:15:00']);

        $this->from('/admin/orders/detail/'.$order->id)
             ->followingRedirects()
             ->post('/admin/orders/edit', $this->orderInput([[$this->cottonCandy, 300, 1]], [
                'order_id'            => (string) $order->id,
                'paid'                => '',
                'transactionTimeType' => 'manual',
                'transactionDate'     => '2019/12/01',
                'transactionTime'     => '13:00:00',
             ]))
             ->assertOk()
             ->assertSee('支払金額が指定されていません')
             ->assertSee('value="2019/12/01"', false)
             ->assertSee('value="13:00:00"', false);
    }

    public function testManualOrderTimeIsReflectedInReportTimePeriod()
    {
        $this->post('/admin/orders/register', $this->orderInput([[$this->cottonCandy, 300, 1]]));
        $order = Order::first();

        // 注文詳細で、注文時間を学園祭の 9:59:11 に手入力して更新する
        $this->post('/admin/orders/edit', $this->orderInput([[$this->cottonCandy, 300, 1]], [
            'order_id'            => (string) $order->id,
            'transactionTimeType' => 'manual',
            'transactionDate'     => '2019/11/30',
            'transactionTime'     => '09:59:11',
        ]))->assertRedirect('/admin/orders');

        $text = preg_replace('/\s+/u', ' ', strip_tags($this->get('/admin/report')->assertOk()->getContent()));

        // 2019-11-30 の時間帯別の表で、9:00 - 9:59 の綿菓子（1列目）が 1個になる
        $this->assertRegExp('/2019-11-30 .*?9:00 - 9:59 1個 0個/u', $text);
    }

    public function testOrderPagesDoNotUseAutocomplete()
    {
        $this->get('/admin/orders/create')
             ->assertOk()
             ->assertDontSee('item_autocomplete')
             ->assertDontSee('item_search');
    }

    public function testRegisterRejectsSameItemInMultipleRows()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1], [$this->cottonCandy, 300, 2]]);

        $this->from('/admin/orders/create')
             ->post('/admin/orders/register', $input)
             ->assertRedirect('/admin/orders/create')
             ->assertSessionHasErrors(['order_items' => '同じアイテムが複数の行で選択されています。数量でまとめてください。']);

        $this->assertSame(0, Order::count());
        $this->assertSame(50, (int) $this->cottonCandy->fresh()->stock);
    }

    public function testRegisterAllowsSameItemWhenOneRowIsMarkedDeleted()
    {
        $input = $this->orderInput(
            [[$this->cottonCandy, 300, 1], [$this->cottonCandy, 300, 2]],
            ['delete_item_2' => 'on']
        );

        $this->post('/admin/orders/register', $input)
             ->assertRedirect('/admin/orders')
             ->assertSessionHasNoErrors();

        $this->assertSame(49, (int) $this->cottonCandy->fresh()->stock);
    }

    public function testEditRejectsSameItemInMultipleRows()
    {
        $this->post('/admin/orders/register', $this->orderInput([[$this->cottonCandy, 300, 1]]));
        $order = Order::first();

        $this->from('/admin/orders/detail/'.$order->id)
             ->post('/admin/orders/edit', $this->orderInput(
                [[$this->cottonCandy, 300, 1], [$this->cottonCandy, 300, 3]],
                ['order_id' => (string) $order->id]
             ))
             ->assertRedirect('/admin/orders/detail/'.$order->id)
             ->assertSessionHasErrors('order_items');

        $this->assertSame(49, (int) $this->cottonCandy->fresh()->stock);
    }

    public function testRegisterCreatesOrderWithItemsStockAndCashier()
    {
        $input = $this->orderInput(
            [[$this->cottonCandy, 300, 2], [$this->potato, 150, 1]],
            ['paid' => '1000', 'change' => '250']
        );

        $this->post('/admin/orders/register', $input)
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

        $this->post('/admin/orders/register', $input)
             ->assertRedirect('/admin/orders/create');
    }

    public function testRegisterWithManualTimeUsesInputTime()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], [
            'transactionTimeType' => 'manual',
            'transactionDate'     => '2019/11/30',
            'transactionTime'     => '10:15:00',
        ]);

        $this->post('/admin/orders/register', $input);

        $this->assertSame('2019-11-30 10:15:00', Order::first()->order_at->format('Y-m-d H:i:s'));
    }

    public function testRegisterWithZeroPaidDoesNotCreateCashier()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], ['paid' => '0']);

        $this->post('/admin/orders/register', $input);

        $this->assertSame(1, Order::count());
        $this->assertSame(0, Cashier::count());
        $this->assertSame(32000, $this->companyCash());
    }

    public function testRegisterRejectsMissingPaid()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], ['paid' => '']);

        $this->from('/admin/orders/create')
             ->post('/admin/orders/register', $input)
             ->assertRedirect('/admin/orders/create')
             ->assertSessionHasErrors(['paid' => '支払金額が指定されていません']);

        $this->assertSame(0, Order::count());
    }

    public function testRegisterRejectsNonNumericNumOfPeople()
    {
        $input = $this->orderInput([[$this->cottonCandy, 300, 1]], ['num_of_people' => '二人']);

        $this->from('/admin/orders/create')
             ->post('/admin/orders/register', $input)
             ->assertSessionHasErrors(['num_of_people' => '人数に数字以外が試用されています']);

        $this->assertSame(0, Order::count());
    }

    public function testEditUpdatesQuantityStockCashierAndCash()
    {
        $this->post('/admin/orders/register', $this->orderInput(
            [[$this->cottonCandy, 300, 1]],
            ['paid' => '500', 'change' => '200']
        ));
        $order = Order::first();
        $this->assertSame(49, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(32000 + 300, $this->companyCash());

        $this->post('/admin/orders/edit', $this->orderInput(
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
        $this->post('/admin/orders/register', $this->orderInput([[$this->cottonCandy, 300, 1]]));
        $order = Order::first();

        $this->post('/admin/orders/edit', $this->orderInput(
            [[$this->cottonCandy, 300, 1], [$this->potato, 150, 2]],
            ['order_id' => (string) $order->id]
        ));

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'item_id' => $this->potato->id, 'price' => 150, 'quantity' => 2,
        ]);
        $this->assertSame(49, (int) $this->cottonCandy->fresh()->stock);
        $this->assertSame(98, (int) $this->potato->fresh()->stock);
    }

    public function testEditRejectsMissingPaid()
    {
        $this->post('/admin/orders/register', $this->orderInput([[$this->cottonCandy, 300, 1]], ['paid' => '500']));
        $order = Order::first();

        $this->from('/admin/orders/detail/'.$order->id)
             ->post('/admin/orders/edit', $this->orderInput(
                [[$this->cottonCandy, 300, 3]],
                ['order_id' => (string) $order->id, 'paid' => '']
             ))
             ->assertRedirect('/admin/orders/detail/'.$order->id)
             ->assertSessionHasErrors(['paid' => '支払金額が指定されていません']);

        $this->assertSame(500, (int) $order->fresh()->paid);
        $this->assertSame(49, (int) $this->cottonCandy->fresh()->stock);
    }

    public function testEditWithoutOrderIdRedirectsToIndex()
    {
        $this->post('/admin/orders/edit', $this->orderInput([[$this->cottonCandy, 300, 1]]))
             ->assertRedirect('/admin/orders');
    }

    public function testDeleteRedirectsBackToSamePage()
    {
        $order = factory(Order::class)->create();

        $this->delete('/admin/orders/delete/', ['del_id' => $order->id, 'display_items' => '5', 'page' => '2'])
             ->assertRedirect('/admin/orders?display_items=5&page=2');
    }

    public function testIndexShowsRecordRangeOnSecondPage()
    {
        factory(Order::class, 12)->create();

        $this->get('/admin/orders?display_items=5&page=2')
             ->assertOk()
             ->assertSee('6 - 10 件表示 / 12件');
    }

    public function testDeleteSoftDeletesOrderAndRestoresStock()
    {
        $this->post('/admin/orders/register', $this->orderInput([[$this->cottonCandy, 300, 2]]));
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

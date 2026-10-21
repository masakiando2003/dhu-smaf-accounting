<?php

namespace Tests\Unit\Models;

use App\Model\Item;
use App\Model\Order;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @var \App\Model\Order */
    private $orders;

    /** @var \App\Services\OrderService */
    private $orderService;

    /** @var \App\Model\Item */
    private $cottonCandy;

    /** @var \App\Model\Item */
    private $potato;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orders = new Order();
        $this->orderService = $this->app->make(OrderService::class);
        $this->cottonCandy = factory(Item::class)->create(['price' => 300, 'stock' => 50]);
        $this->potato = factory(Item::class)->create(['price' => 150, 'stock' => 100]);
    }

    /**
     * 注文の入力を作成する
     *
     * @return array
     */
    private function orderData(int $num_of_people = 1)
    {
        return ['num_of_people' => $num_of_people, 'paid' => 0, 'change' => 0, 'remarks' => null];
    }

    /**
     * 注文明細の入力を作成する（OrderRequest::orderItems() の形）
     *
     * @param  array  $items  [[アイテム, 単価, 数量, 削除チェック], ...]
     * @return array
     */
    private function orderItems(array $items)
    {
        $order_items = [];
        foreach ($items as $row) {
            [$item, $price, $quantity] = $row;
            $order_items[] = [
                'item_id'  => (string) $item->id,
                'price'    => (string) $price,
                'quantity' => (string) $quantity,
                'delete'   => $row[3] ?? false,
            ];
        }

        return $order_items;
    }

    /**
     * 注文を登録する
     *
     * @param  array   $items       [[アイテム, 単価, 数量], ...]
     * @param  string  $order_at  注文日時
     * @return \App\Model\Order
     */
    private function registerOrder(array $items, string $order_at, int $num_of_people = 1)
    {
        return $this->orderService->register($this->orderData($num_of_people), $this->orderItems($items), $order_at);
    }

    public function testSellAmountByDateMultipliesPriceByQuantity()
    {
        $this->registerOrder([[$this->cottonCandy, 300, 3], [$this->potato, 150, 2]], '2019-11-30 10:15:00');
        $this->registerOrder([[$this->cottonCandy, 300, 1]], '2019-12-01 11:00:00');

        $this->assertSame(1200, $this->orders->GetOrderItemsSellAmountByDate('2019-11-30'));
        $this->assertSame(900, $this->orders->GetOrderItemsSellAmountByDate('2019-11-30', $this->cottonCandy->id));
        $this->assertSame(300, $this->orders->GetOrderItemsSellAmountByDate('2019-12-01'));
        $this->assertSame(1500, $this->orders->GetOrderItemsTotalSellAmount(['2019-11-30', '2019-12-01']));
    }

    public function testSellAmountExcludesDeletedOrders()
    {
        $this->registerOrder([[$this->cottonCandy, 300, 1]], '2019-11-30 10:15:00');
        $deleted = $this->registerOrder([[$this->cottonCandy, 300, 2]], '2019-11-30 11:00:00');
        $this->orderService->delete($deleted->id);

        $this->assertSame(300, $this->orders->GetOrderItemsSellAmountByDate('2019-11-30'));
        $this->assertSame(1, $this->orders->GetOrderItemsCountByDate('2019-11-30', $this->cottonCandy->id));
    }

    public function testReportExcludesOrderLinesDeletedOnEdit()
    {
        $order = $this->registerOrder([[$this->cottonCandy, 300, 1], [$this->potato, 150, 2]], '2019-11-30 10:15:00');
        $this->orderService->update($order->id, $this->orderData(), $this->orderItems([
            [$this->cottonCandy, 300, 1],
            [$this->potato, 150, 2, true],
        ]));

        $this->assertSame(300, $this->orders->GetOrderItemsSellAmountByDate('2019-11-30'));
        $this->assertSame(0, $this->orders->GetOrderItemsCountByDate('2019-11-30', $this->potato->id));
        $this->assertSame(1, $this->orders->GetOrderItemsTotalCount(['2019-11-30']));
        $this->assertSame(0, $this->orders->GetOrdersCountByItemAndTimePeriod($this->potato->id, '2019-11-30', '10:00 - 10:59'));
    }

    public function testTimePeriodIncludesSecondsOfLastMinute()
    {
        $this->registerOrder([[$this->cottonCandy, 300, 1]], '2019-11-30 09:00:00');
        $this->registerOrder([[$this->cottonCandy, 300, 1]], '2019-11-30 09:59:30');
        // 次の時間帯の始まりは含めない
        $this->registerOrder([[$this->cottonCandy, 300, 1]], '2019-11-30 10:00:00');

        $this->assertSame(2, $this->orders->GetOrdersCountByItemAndTimePeriod($this->cottonCandy->id, '2019-11-30', '9:00 - 9:59'));
        $this->assertSame(1, $this->orders->GetOrdersCountByItemAndTimePeriod($this->cottonCandy->id, '2019-11-30', '10:00 - 10:59'));
    }

    public function testDayIncludesLastSecondAndExcludesNextDay()
    {
        $this->registerOrder([[$this->cottonCandy, 300, 1]], '2019-11-30 23:59:59');
        $this->registerOrder([[$this->cottonCandy, 300, 2]], '2019-12-01 00:00:00');

        $this->assertSame(1, $this->orders->GetOrdersCountByDate('2019-11-30'));
        $this->assertSame(300, $this->orders->GetOrderItemsSellAmountByDate('2019-11-30'));
        $this->assertSame(600, $this->orders->GetOrderItemsSellAmountByDate('2019-12-01'));
        $this->assertSame(900, $this->orders->GetOrderItemsTotalSellAmount(['2019-11-30', '2019-12-01']));
    }

    public function testReportUsesOrderAtInsteadOfCreatedAt()
    {
        // 学園祭の後に、当日の注文を入力した場合
        $order = $this->registerOrder([[$this->cottonCandy, 300, 1]], '2019-11-30 10:15:00');
        $order->created_at = '2019-12-05 20:00:00';
        $order->save();

        $this->assertSame(1, $this->orders->GetOrdersCountByDate('2019-11-30'));
        $this->assertSame(300, $this->orders->GetOrderItemsSellAmountByDate('2019-11-30'));
    }

    public function testVisitorCountsSumNumOfPeople()
    {
        $this->registerOrder([[$this->cottonCandy, 300, 1]], '2019-11-30 09:10:00', 2);
        $this->registerOrder([[$this->cottonCandy, 300, 2]], '2019-11-30 09:59:30', 3);
        $this->registerOrder([[$this->potato, 150, 1]], '2019-11-30 10:00:00', 1);
        $this->registerOrder([[$this->potato, 150, 1]], '2019-12-01 12:00:00', 4);
        $deleted = $this->registerOrder([[$this->potato, 150, 1]], '2019-11-30 09:30:00', 10);
        $this->orderService->delete($deleted->id);

        $this->assertSame(5, $this->orders->GetVisitorCountByTimePeriod('2019-11-30', '9:00 - 9:59'));
        $this->assertSame(1, $this->orders->GetVisitorCountByTimePeriod('2019-11-30', '10:00 - 10:59'));
        $this->assertSame(6, $this->orders->GetVisitorCountByDate('2019-11-30'));
        $this->assertSame(10, $this->orders->GetVisitorTotalCount(['2019-11-30', '2019-12-01']));
    }

    public function testAverageSpendPerVisitorRoundsDown()
    {
        // 売上 1,050円（300×3 + 150）÷ 来客数 4人 = 262.5円 → 262円
        $this->registerOrder([[$this->cottonCandy, 300, 3]], '2019-11-30 10:00:00', 3);
        $this->registerOrder([[$this->potato, 150, 1]], '2019-11-30 11:00:00', 1);

        $this->assertSame(262, $this->orders->GetAverageSpendPerVisitorByDate('2019-11-30'));
        $this->assertSame(262, $this->orders->GetAverageSpendPerVisitor(['2019-11-30', '2019-12-01']));
    }

    public function testAverageSpendPerVisitorIsZeroWithoutVisitors()
    {
        $this->assertSame(0, $this->orders->GetAverageSpendPerVisitorByDate('2019-11-30'));
        $this->assertSame(0, $this->orders->GetAverageSpendPerVisitor(['2019-11-30', '2019-12-01']));
    }
}

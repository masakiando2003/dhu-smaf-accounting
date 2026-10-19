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
    private function orderData()
    {
        return ['num_of_people' => 1, 'paid' => 0, 'change' => 0, 'remarks' => null];
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
     * @param  string  $created_at  注文日時
     * @return \App\Model\Order
     */
    private function registerOrder(array $items, string $created_at)
    {
        return $this->orderService->register($this->orderData(), $this->orderItems($items), $created_at);
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
}

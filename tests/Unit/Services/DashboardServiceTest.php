<?php

namespace Tests\Unit\Services;

use App\Model\Cashier;
use App\Model\Item;
use App\Model\Order;
use App\Model\OrderItem;
use App\Services\DashboardService;
use CompanyInfoTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TestDataSeeder;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var \App\Services\DashboardService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DashboardService();
    }

    /**
     * 集計結果（DB からは文字列で返ることがある）を整数の配列にする
     *
     * @param  \Illuminate\Support\Collection  $values
     * @return int[]
     */
    private function toIntegers($values)
    {
        return $values->map(function ($value) {
            return (int) $value;
        })->all();
    }

    public function testSummaryIsZeroWithoutData()
    {
        $summary = $this->service->getSummary();

        $this->assertNull($summary['company_info']);
        $this->assertSame(0, $summary['sales']);
        $this->assertSame(0, $summary['order_count']);
        $this->assertSame(0, $summary['visitor_count']);
        $this->assertSame(0, $summary['expenditure']);
        $this->assertSame(0, $summary['profit']);
        $this->assertSame(0, $summary['cash_balance']);
        $this->assertCount(0, $summary['low_stock_items']);
        $this->assertCount(0, $summary['best_selling_items']);
        $this->assertCount(0, $summary['recent_orders']);
    }

    public function testSummaryOfTestData()
    {
        $this->seed(CompanyInfoTableSeeder::class);
        $this->seed(TestDataSeeder::class);

        $summary = $this->service->getSummary();

        // 削除済みの注文（綿菓子 1個）は集計に含まれない
        $this->assertSame(1200, $summary['sales']);
        $this->assertSame(2, $summary['order_count']);
        $this->assertSame(3, $summary['visitor_count']);
        $this->assertSame(400, $summary['expenditure']);
        $this->assertSame(800, $summary['profit']);
        $this->assertSame(32000 + 1200 - 400, $summary['cash_balance']);
        $this->assertSame(['綿菓子'], $summary['low_stock_items']->pluck('name')->all());
        $this->assertSame(['綿菓子', 'ポテト'], $summary['best_selling_items']->pluck('name')->all());
        $this->assertSame([3, 2], $this->toIntegers($summary['best_selling_items']->pluck('total_quantity')));
        $this->assertSame([900, 300], $this->toIntegers($summary['best_selling_items']->pluck('total_sales')));
    }

    public function testSalesMultipliesPriceByQuantity()
    {
        $item = factory(Item::class)->create();
        $order = factory(Order::class)->create();
        factory(OrderItem::class)->create(['order_id' => $order->id, 'item_id' => $item->id, 'price' => 300, 'quantity' => 3]);

        $this->assertSame(900, $this->service->getSummary()['sales']);
    }

    public function testProfitCanBeNegative()
    {
        factory(Cashier::class)->create(['cashier_type' => 'payment', 'payment_amount' => 500]);
        // 収入の記録は経費に含まれない
        factory(Cashier::class)->create(['cashier_type' => 'income', 'payment_amount' => 100]);

        $summary = $this->service->getSummary();

        $this->assertSame(500, $summary['expenditure']);
        $this->assertSame(-500, $summary['profit']);
    }

    public function testLowStockUsesRatioOfInitialStock()
    {
        factory(Item::class)->create(['name' => 'ちょうど2割', 'init_stock' => 50, 'stock' => 10]);
        factory(Item::class)->create(['name' => '2割より多い', 'init_stock' => 50, 'stock' => 11]);
        factory(Item::class)->create(['name' => '売り切れ', 'init_stock' => 50, 'stock' => 0]);
        factory(Item::class)->create(['name' => '初期在庫0', 'init_stock' => 0, 'stock' => 0]);

        $names = $this->service->getSummary()['low_stock_items']->pluck('name')->all();

        // 在庫の少ない順
        $this->assertSame(['売り切れ', 'ちょうど2割'], $names);
    }

    public function testBestSellingItemsAreLimitedToFive()
    {
        $order = factory(Order::class)->create();
        foreach (range(1, 6) as $quantity) {
            factory(OrderItem::class)->create([
                'order_id' => $order->id,
                'item_id'  => factory(Item::class)->create()->id,
                'quantity' => $quantity,
            ]);
        }

        $quantities = $this->toIntegers($this->service->getSummary()['best_selling_items']->pluck('total_quantity'));

        $this->assertSame([6, 5, 4, 3, 2], $quantities);
    }

    public function testRecentOrdersAreNewestFiveWithoutDeleted()
    {
        foreach (range(1, 6) as $day) {
            factory(Order::class)->create(['created_at' => "2019-11-0{$day} 10:00:00"]);
        }
        factory(Order::class)->create(['created_at' => '2019-11-30 10:00:00'])->delete();

        $dates = $this->service->getSummary()['recent_orders']
                      ->map(function ($order) {
                          return $order->created_at->format('Y-m-d');
                      })->all();

        $this->assertSame(['2019-11-06', '2019-11-05', '2019-11-04', '2019-11-03', '2019-11-02'], $dates);
    }
}

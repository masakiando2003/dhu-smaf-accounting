<?php

namespace Tests\Unit\Services;

use App\Model\Cashier;
use App\Model\Item;
use App\Model\Order;
use App\Services\ItemService;
use App\Services\ReportService;
use CompanyInfoTableSeeder;
use CompanyMembersTableSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TestDataSeeder;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testReportDataContainsCompanyItemsAndExpenses()
    {
        $this->seed(CompanyInfoTableSeeder::class);
        $this->seed(CompanyMembersTableSeeder::class);
        $this->seed(TestDataSeeder::class);

        $data = $this->app->make(ReportService::class)->getReportData();

        $this->assertSame('株式会社SmaF', $data['company_info']->company_name);
        $this->assertGreaterThan(0, $data['company_members']->count());
        $this->assertInstanceOf(Order::class, $data['orders']);
        $this->assertSame(['綿菓子', 'ポテト', 'ポップコーン'], $data['items']->pluck('name')->all());
    }

    public function testItemsAreTakenFromInjectedItemService()
    {
        $items = new Collection([new Item(['name' => 'モックのアイテム'])]);
        $this->mock(ItemService::class, function ($mock) use ($items) {
            $mock->shouldReceive('all')->once()->andReturn($items);
        });

        $data = $this->app->make(ReportService::class)->getReportData();

        $this->assertSame($items, $data['items']);
    }

    public function testExpensesContainOnlyPayments()
    {
        factory(Cashier::class)->create(['cashier_type' => 'payment', 'description' => '材料費']);
        factory(Cashier::class)->create(['cashier_type' => 'income', 'description' => '売上']);
        factory(Cashier::class)->create(['cashier_type' => 'payment', 'description' => '削除済み'])->delete();

        $expenses = $this->app->make(ReportService::class)->getReportData()['expenses'];

        $this->assertSame(['材料費'], $expenses->pluck('description')->all());
    }
}

<?php

namespace Tests\Unit\Services;

use App\Model\Cashier;
use App\Model\Order;
use App\Services\ReportService;
use CompanyInfoTableSeeder;
use CompanyMembersTableSeeder;
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

        $data = (new ReportService())->getReportData();

        $this->assertSame('株式会社SmaF', $data['company_info']->company_name);
        $this->assertGreaterThan(0, $data['company_members']->count());
        $this->assertInstanceOf(Order::class, $data['orders']);
        $this->assertSame(['綿菓子', 'ポテト', 'ポップコーン'], $data['items']->pluck('name')->all());
    }

    public function testExpensesContainOnlyPayments()
    {
        factory(Cashier::class)->create(['cashier_type' => 'payment', 'description' => '材料費']);
        factory(Cashier::class)->create(['cashier_type' => 'income', 'description' => '売上']);
        factory(Cashier::class)->create(['cashier_type' => 'payment', 'description' => '削除済み'])->delete();

        $expenses = (new ReportService())->getReportData()['expenses'];

        $this->assertSame(['材料費'], $expenses->pluck('description')->all());
    }
}

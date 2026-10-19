<?php

namespace Tests\Feature;

use CompanyInfoTableSeeder;
use CompanyMembersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TestDataSeeder;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanyInfoTableSeeder::class);
        $this->seed(CompanyMembersTableSeeder::class);
    }

    public function testReportIsShownWithoutOrders()
    {
        $this->get('/admin/report')
             ->assertOk()
             ->assertSee('貸借対照表')
             ->assertSee('株式会社SmaF');
    }

    public function testReportIsShownWithTestData()
    {
        $this->seed(TestDataSeeder::class);

        $this->get('/admin/report')
             ->assertOk()
             ->assertSee('綿菓子')
             ->assertSee('ポテト')
             ->assertSee('材料費');
    }

    public function testReportCountsTestDataOnFestivalDays()
    {
        // テストデータの注文日時は、学園祭の 2019-11-30・2019-12-01
        $this->seed(TestDataSeeder::class);

        $this->get('/admin/report')
             ->assertOk()
             // 期間の売上合計（削除済みの注文を除く 750円 + 450円）
             ->assertSee('1200円')
             // 損益計算書：売上 1200円 − 材料費 400円
             ->assertSee('800円');
    }
}

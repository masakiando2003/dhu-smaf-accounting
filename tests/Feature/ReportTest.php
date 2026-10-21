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

    public function testReportShowsVisitorCountsAndAverageSpend()
    {
        // テストデータ：2019-11-30 10:15 に 2人（750円）、2019-12-01 13:59:30 に 1人（450円）
        // 2019-11-30 の 3人の注文は削除済み
        $this->seed(TestDataSeeder::class);

        $text = preg_replace('/\s+/u', ' ', strip_tags($this->get('/admin/report')->assertOk()->getContent()));

        // 時間帯別の表の来客数（最後の列）
        $this->assertRegExp('/2019-11-30 .*?10:00 - 10:59 (\d+個 ){3}2人/u', $text);
        $this->assertRegExp('/2019-12-01 .*?13:00 - 13:59 (\d+個 ){3}1人/u', $text);
        // 売上報告（日別）の来客数と客単価
        $this->assertRegExp('/合計注文数: 1個 来客数: 2人 客単価: 375円/u', $text);
        $this->assertRegExp('/合計注文数: 1個 来客数: 1人 客単価: 450円/u', $text);
        // 売上報告（期間合計）
        $this->assertStringContainsString('2019-11-30から2019-12-01までの合計来客数: 3人', $text);
        $this->assertStringContainsString('2019-11-30から2019-12-01までの客単価: 400円', $text);
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

<?php

namespace Tests\Feature;

use App\Model\Item;
use CompanyInfoTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TestDataSeeder;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function testTopPageRedirectsToDashboard()
    {
        $this->get('/')->assertRedirect('admin/home');
    }

    public function testDashboardShowsEmptyMessagesWithoutData()
    {
        $this->get('/admin/home')
             ->assertOk()
             ->assertSee('ダッシュボード')
             ->assertSee('在庫が少ない商品はありません')
             ->assertSee('まだ販売実績がありません')
             ->assertSee('まだ注文がありません');
    }

    public function testDashboardShowsSummaryOfTestData()
    {
        $this->seed(CompanyInfoTableSeeder::class);
        $this->seed(TestDataSeeder::class);

        $response = $this->get('/admin/home')->assertOk();

        // 削除済みの注文は集計に含まれない
        $response->assertSeeInOrder(['1,200', '売上合計']);
        $response->assertSeeInOrder(['2', '注文数（来客数 3人）']);
        $response->assertSeeInOrder(['32,800', '現金残高']);
        $response->assertSeeInOrder(['800', '利益（経費 400円）']);
        $response->assertSee('2019/11/02 〜 2019/12/04');
    }

    public function testDashboardListsOnlyLowStockItems()
    {
        factory(Item::class)->create(['name' => '残りわずか', 'init_stock' => 50, 'stock' => 10]);
        factory(Item::class)->create(['name' => '在庫たっぷり', 'init_stock' => 50, 'stock' => 11]);

        $this->get('/admin/home')
             ->assertSee('残りわずか')
             ->assertDontSee('在庫たっぷり');
    }

    public function testDashboardRanksBestSellingItemsByQuantity()
    {
        $this->seed(TestDataSeeder::class);

        // 綿菓子 3個（900円）、ポテト 2個（300円）の順
        $this->get('/admin/home')
             ->assertSeeInOrder(['売れ筋ランキング', '綿菓子', '3', '900円', 'ポテト', '2', '300円']);
    }
}

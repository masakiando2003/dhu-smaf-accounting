<?php

namespace Tests\Feature;

use App\Model\Cashier;
use App\Model\Item;
use App\Model\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundTest extends TestCase
{
    use RefreshDatabase;

    /** 存在しない ID */
    const MISSING_ID = 999999;

    public function testUnknownUrlShowsJapaneseNotFoundPage()
    {
        $this->get('/no/such/page')
             ->assertNotFound()
             ->assertSee('ページが見つかりません')
             ->assertSee('ダッシュボードへ戻る');
    }

    public function testMissingItemDetailIsNotFound()
    {
        $this->get('/admin/items/detail/'.self::MISSING_ID)
             ->assertNotFound()
             ->assertSee('ページが見つかりません');
    }

    public function testMissingOrderDetailIsNotFound()
    {
        $this->get('/admin/orders/detail/'.self::MISSING_ID)
             ->assertNotFound()
             ->assertSee('ページが見つかりません');
    }

    public function testMissingCashierDetailIsNotFound()
    {
        $this->get('/admin/cashier/detail/'.self::MISSING_ID)
             ->assertNotFound()
             ->assertSee('ページが見つかりません');
    }

    public function testDeletedItemDetailIsNotFound()
    {
        $item = factory(Item::class)->create();
        $item->delete();

        $this->get('/admin/items/detail/'.$item->id)->assertNotFound();
    }

    public function testDeletingMissingItemIsNotFound()
    {
        $item = factory(Item::class)->create();

        $this->delete('/admin/items/delete/', ['del_id' => self::MISSING_ID, 'del_name' => 'なし'])
             ->assertNotFound();

        $this->assertDatabaseHas('items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function testDeletingItemTwiceIsNotFound()
    {
        $item = factory(Item::class)->create();

        $this->delete('/admin/items/delete/', ['del_id' => $item->id, 'del_name' => $item->name])
             ->assertRedirect('/admin/items');
        $this->delete('/admin/items/delete/', ['del_id' => $item->id, 'del_name' => $item->name])
             ->assertNotFound();
    }

    public function testDeletingMissingOrderIsNotFound()
    {
        $order = factory(Order::class)->create();

        $this->delete('/admin/orders/delete/', ['del_id' => self::MISSING_ID])
             ->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'deleted_at' => null]);
    }

    public function testDeletingMissingCashierIsNotFound()
    {
        $cashier = factory(Cashier::class)->create();

        $this->delete('/admin/cashier/delete/', ['del_id' => self::MISSING_ID])
             ->assertNotFound();

        $this->assertDatabaseHas('cashier', ['id' => $cashier->id, 'deleted_at' => null]);
    }

    public function testMissingItemDetailReturnsJsonForJsonRequest()
    {
        $this->getJson('/admin/items/detail/'.self::MISSING_ID)
             ->assertNotFound()
             ->assertJsonStructure(['message']);
    }
}

<?php

namespace Tests\Feature;

use App\Model\Cashier;
use App\Model\Item;
use App\Model\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteConstraintTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID に数字以外が続く URL（MySQL では先頭の数字だけで一致してしまう）
     *
     * @param  int  $id
     * @return string
     */
    private function idWithSuffix($id)
    {
        return $id.'abc';
    }

    public function testItemDetailRejectsIdWithNonNumericSuffix()
    {
        $item = factory(Item::class)->create(['name' => '綿菓子']);

        $this->get('/admin/items/detail/'.$this->idWithSuffix($item->id))
             ->assertNotFound()
             ->assertDontSee('綿菓子');
    }

    public function testOrderDetailRejectsIdWithNonNumericSuffix()
    {
        $order = factory(Order::class)->create();

        $this->get('/admin/orders/detail/'.$this->idWithSuffix($order->id))
             ->assertNotFound();
    }

    public function testCashierDetailRejectsIdWithNonNumericSuffix()
    {
        $cashier = factory(Cashier::class)->create(['description' => '材料費']);

        $this->get('/admin/cashier/detail/'.$this->idWithSuffix($cashier->id))
             ->assertNotFound()
             ->assertDontSee('材料費');
    }

    public function testCashierEditRejectsIdWithNonNumericSuffix()
    {
        $cashier = factory(Cashier::class)->create(['description' => '材料費']);

        $this->post('/admin/cashier/edit/'.$this->idWithSuffix($cashier->id), [
                'cashier_id'       => (string) $cashier->id,
                'description'      => '変更後',
                'income_amount'    => '0',
                'payment_amount'   => '400',
                'deduction_amount' => '-400',
             ])
             ->assertNotFound();

        $this->assertSame('材料費', $cashier->fresh()->description);
    }

    public function testRemovedAutocompleteApisAreNotFound()
    {
        $this->getJson('/item_autocomplete?term=a')->assertNotFound();
        $this->getJson('/item_getprice?itemName=a')->assertNotFound();
    }

    public function testDetailRejectsNonIntegerIds()
    {
        foreach (['abc', '1.5', '-1'] as $id) {
            $this->get('/admin/items/detail/'.$id)->assertNotFound();
        }
    }

    public function testDetailAcceptsNumericId()
    {
        $item = factory(Item::class)->create(['name' => '綿菓子']);

        $this->get('/admin/items/detail/'.$item->id)
             ->assertOk()
             ->assertSee('綿菓子');
    }
}

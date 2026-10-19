<?php

namespace Tests\Feature;

use App\Model\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * アイテム登録フォームの入力値を作成する
     *
     * @param  array  $overrides  上書きする項目
     * @return array
     */
    private function itemInput(array $overrides = [])
    {
        return array_merge([
            'name'        => '綿菓子',
            'description' => 'ふわふわの綿菓子',
            'price'       => '300',
            'size'        => 'M',
            'weight'      => '30',
            'weight_unit' => 'g',
            'init_stock'  => '50',
            'stock'       => '50',
            'remarks'     => '備考',
        ], $overrides);
    }

    public function testIndexListsItems()
    {
        factory(Item::class)->create(['name' => '綿菓子']);
        factory(Item::class)->create(['name' => 'ポテト']);

        $this->get('/admin/items')
             ->assertOk()
             ->assertSee('綿菓子')
             ->assertSee('ポテト');
    }

    public function testCreatePageIsShown()
    {
        $this->get('/admin/items/create')
             ->assertOk()
             ->assertSee('新規アイテム作成');
    }

    public function testDetailPageShowsItem()
    {
        $item = factory(Item::class)->create(['name' => '綿菓子']);

        $this->get('/admin/items/detail/'.$item->id)
             ->assertOk()
             ->assertSee('アイテム詳細')
             ->assertSee('綿菓子');
    }

    public function testRegisterCreatesItem()
    {
        $this->post('/admin/items/register', $this->itemInput())
             ->assertRedirect('/admin/items')
             ->assertSessionHas('success_msg', '新規アイテム"綿菓子"を作成しました');

        $this->assertDatabaseHas('items', [
            'name'       => '綿菓子',
            'price'      => 300,
            'init_stock' => 50,
            'stock'      => 50,
            'remarks'    => '備考',
        ]);
    }

    public function testRegisterRejectsMissingName()
    {
        $this->from('/admin/items/create')
             ->post('/admin/items/register', $this->itemInput(['name' => '']))
             ->assertRedirect('/admin/items/create')
             ->assertSessionHasErrors(['name' => 'アイテム名称が指定されていません']);

        $this->assertDatabaseMissing('items', ['description' => 'ふわふわの綿菓子']);
    }

    public function testRegisterRejectsNonNumericPrice()
    {
        $this->from('/admin/items/create')
             ->post('/admin/items/register', $this->itemInput(['price' => '三百']))
             ->assertRedirect('/admin/items/create')
             ->assertSessionHasErrors(['price' => '価格に数字以外が試用されています']);

        $this->assertDatabaseMissing('items', ['name' => '綿菓子']);
    }

    public function testRegisterRejectsDuplicateName()
    {
        factory(Item::class)->create(['name' => '綿菓子']);

        $this->from('/admin/items/create')
             ->post('/admin/items/register', $this->itemInput())
             ->assertRedirect('/admin/items/create')
             ->assertSessionHasErrors();

        $this->assertSame(1, Item::where('name', '綿菓子')->count());
    }

    public function testEditUpdatesItemWithSameName()
    {
        $item = factory(Item::class)->create(['name' => '綿菓子', 'price' => 300, 'stock' => 50]);

        $this->post('/admin/items/edit', $this->itemInput([
                'item_id'  => $item->id,
                'ori_name' => '綿菓子',
                'price'    => '350',
                'stock'    => '40',
             ]))
             ->assertRedirect('/admin/items')
             ->assertSessionHas('success_msg', 'アイテム"綿菓子"を更新しました');

        $this->assertDatabaseHas('items', ['id' => $item->id, 'price' => 350, 'stock' => 40]);
    }

    public function testEditWithoutItemIdRedirectsToIndex()
    {
        $this->post('/admin/items/edit', $this->itemInput())
             ->assertRedirect('/admin/items');
    }

    public function testDeleteSoftDeletesItem()
    {
        $item = factory(Item::class)->create(['name' => '綿菓子']);

        $this->delete('/admin/items/delete/', ['del_id' => $item->id, 'del_name' => '綿菓子'])
             ->assertRedirect('/admin/items')
             ->assertSessionHas('success_msg', 'アイテム"綿菓子"を削除しました');

        $this->assertSoftDeleted('items', ['id' => $item->id]);
    }

    public function testAutocompleteSearchesByPartialName()
    {
        factory(Item::class)->create(['name' => '綿菓子']);
        factory(Item::class)->create(['name' => 'ポテト']);

        $this->getJson('/item_autocomplete?term='.urlencode('菓'))
             ->assertOk()
             ->assertJsonCount(1)
             ->assertJsonFragment(['name' => '綿菓子']);
    }

    public function testGetPriceReturnsItemWithExactName()
    {
        factory(Item::class)->create(['name' => '綿菓子', 'price' => 300]);

        $this->getJson('/item_getprice?itemName='.urlencode('綿菓子'))
             ->assertOk()
             ->assertJsonCount(1)
             ->assertJsonFragment(['name' => '綿菓子', 'price' => 300]);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Model\Item;
use App\Services\ItemService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Tests\Concerns\SimulatesDatabaseError;
use Tests\TestCase;

class ItemServiceTest extends TestCase
{
    use RefreshDatabase;
    use SimulatesDatabaseError;

    /** @var \App\Services\ItemService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(ItemService::class);
    }

    /**
     * アイテム登録の入力を作成する
     *
     * @param  array  $overrides  上書きする項目
     * @return array
     */
    private function itemData(array $overrides = [])
    {
        return array_merge([
            'name'        => '綿菓子',
            'description' => 'ふわふわの綿菓子',
            'price'       => 300,
            'size'        => 'M',
            'weight'      => 30,
            'weight_unit' => 'g',
            'init_stock'  => 50,
            'stock'       => 50,
            'remarks'     => '備考',
        ], $overrides);
    }

    public function testPaginateReturnsRecordsInIdOrder()
    {
        $items = factory(Item::class, 3)->create();

        $result = $this->service->paginate(2);

        $this->assertSame(3, $result['total_count']);
        $this->assertFalse($result['is_first_page_fallback']);
        $this->assertSame([$items[0]->id, $items[1]->id], $result['records']->pluck('id')->all());
    }

    public function testPaginateFallsBackToFirstPageWhenPageIsEmpty()
    {
        $items = factory(Item::class, 3)->create();
        Paginator::currentPageResolver(function () {
            return 5;
        });

        $result = $this->service->paginate(2);

        $this->assertTrue($result['is_first_page_fallback']);
        $this->assertSame(1, $result['records']->currentPage());
        $this->assertSame([$items[0]->id, $items[1]->id], $result['records']->pluck('id')->all());
    }

    public function testFindReturnsItem()
    {
        $item = factory(Item::class)->create(['name' => '綿菓子']);

        $this->assertSame('綿菓子', $this->service->find($item->id)->name);
    }

    public function testFindThrowsWhenItemIsDeleted()
    {
        $item = factory(Item::class)->create();
        $item->delete();

        $this->expectException(ModelNotFoundException::class);
        $this->service->find($item->id);
    }

    public function testAllReturnsItemsWithoutDeleted()
    {
        factory(Item::class, 2)->create();
        factory(Item::class)->create()->delete();

        $this->assertCount(2, $this->service->all());
    }

    public function testExistsByName()
    {
        factory(Item::class)->create(['name' => '綿菓子']);
        factory(Item::class)->create(['name' => 'ポテト'])->delete();

        $this->assertTrue($this->service->existsByName('綿菓子'));
        $this->assertFalse($this->service->existsByName('ポップコーン'));
        // 削除済みのアイテムの名称は、再び使える
        $this->assertFalse($this->service->existsByName('ポテト'));
    }

    public function testCreateSavesAllFields()
    {
        $item = $this->service->create($this->itemData());

        $this->assertDatabaseHas('items', ['id' => $item->id] + $this->itemData());
    }

    public function testUpdateChangesFieldsExceptRemarks()
    {
        $item = $this->service->create($this->itemData());

        $this->service->update($item->id, $this->itemData([
            'name'    => '綿菓子（大）',
            'price'   => 400,
            'stock'   => 40,
            'remarks' => '変更しない',
        ]));

        $this->assertDatabaseHas('items', [
            'id'      => $item->id,
            'name'    => '綿菓子（大）',
            'price'   => 400,
            'stock'   => 40,
            'remarks' => '備考',
        ]);
    }

    public function testDeleteSoftDeletesItem()
    {
        $item = factory(Item::class)->create();

        $this->service->delete($item->id);

        $this->assertSoftDeleted('items', ['id' => $item->id]);
    }

    public function testDeleteRollsBackAndRethrowsOriginalException()
    {
        $item = factory(Item::class)->create();
        // 論理削除の後の保存で DB エラーが起きたことにする
        $this->failOnSaving(Item::class);

        $this->assertThrowsSimulatedDatabaseError(function () use ($item) {
            $this->service->delete($item->id);
        });

        $this->assertDatabaseHas('items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function testDeleteThrowsWhenItemIsMissing()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->service->delete(999999);
    }
}

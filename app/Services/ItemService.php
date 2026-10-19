<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Model\Item;
use App\Services\Concerns\PaginatesRecords;

/**
 * アイテム（商品）のデータ操作
 */
class ItemService
{
    use PaginatesRecords;

    /**
     * アイテム一覧をページに分けて取得する
     *
     * @param  int  $per_page  1ページの表示件数
     * @return array{records: \Illuminate\Contracts\Pagination\LengthAwarePaginator, total_count: int, is_first_page_fallback: bool}
     */
    public function paginate(int $per_page)
    {
        return $this->paginateWithFallback(Item::orderBy('id', 'asc'), $per_page);
    }

    /**
     * すべてのアイテムを取得する
     *
     * @return \Illuminate\Database\Eloquent\Collection|\App\Model\Item[]
     */
    public function all()
    {
        return Item::get();
    }

    /**
     * アイテムを取得する
     *
     * @param  int  $id  アイテムID
     * @return \App\Model\Item|null
     */
    public function find($id)
    {
        return Item::find($id);
    }

    /**
     * 指定した名称のアイテムが存在するかを確認する
     *
     * @param  string  $name  アイテム名称
     * @return bool
     */
    public function existsByName($name)
    {
        return Item::where('name', $name)->count() > 0;
    }

    /**
     * 名称の部分一致でアイテムを検索する
     *
     * @param  string|null  $search  検索文字列
     * @return \Illuminate\Database\Eloquent\Collection|\App\Model\Item[]
     */
    public function searchByName($search)
    {
        return Item::where('name', 'LIKE', '%'. $search. '%')->get();
    }

    /**
     * 名称が完全一致するアイテムを取得する
     *
     * @param  string|null  $name  アイテム名称
     * @return \Illuminate\Database\Eloquent\Collection|\App\Model\Item[]
     */
    public function findByExactName($name)
    {
        return Item::where('name', $name)->get();
    }

    /**
     * アイテムを新規登録する
     *
     * @param  array  $data  name, description, price, size, weight, weight_unit, init_stock, stock, remarks
     * @return \App\Model\Item
     */
    public function create(array $data)
    {
        $new_item = new Item();
        $new_item->name = $data['name'];
        $new_item->description = $data['description'];
        $new_item->price = $data['price'];
        $new_item->size = $data['size'];
        $new_item->weight = $data['weight'];
        $new_item->weight_unit = $data['weight_unit'];
        $new_item->init_stock = $data['init_stock'];
        $new_item->stock = $data['stock'];
        $new_item->remarks = $data['remarks'];
        $new_item->save();

        return $new_item;
    }

    /**
     * アイテムを更新する
     *
     * 備考（remarks）は更新しない.
     *
     * @param  int    $id    アイテムID
     * @param  array  $data  name, description, price, size, weight, weight_unit, init_stock, stock
     * @return void
     */
    public function update($id, array $data)
    {
        Item::where('id', $id)
             ->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'size' => $data['size'],
                'weight' => $data['weight'],
                'weight_unit' => $data['weight_unit'],
                'init_stock' => $data['init_stock'],
                'stock' => $data['stock'],
             ]);
    }

    /**
     * アイテムを削除（論理削除）する
     *
     * @param  int  $id  アイテムID
     * @return void
     */
    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $item = Item::find($id);
            $item->delete();
            $item->save();
            DB::commit();
        } catch (\PDOException $e){
            DB::rollBack();
            throw e;
        }
    }
}

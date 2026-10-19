<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Model\Item;

/**
 * 注文明細
 *
 * @property int $id
 * @property int $order_id 注文ID
 * @property int $item_id アイテムID
 * @property int $price 注文時の単価
 * @property int $quantity 数量
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class OrderItem extends Model
{
    use SoftDeletes;

    protected $table        = 'order_items';
    public    $tableType    = 'master';

    protected $primaryKey   = 'id';
    protected $keyType      = 'bigint';
    public    $incrementing = true;

    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['order_id', 'item_id', 'quantity'];

    // created_at, updated_at は DB の機能で自動更新する.
    public $timestamps = false;

    
    /**
     * アイテム名称を取得する
     *
     * @param  int  $item_id  アイテムID
     * @return string
     */
    public function GetItemName(int $item_id){
        $item_detail = Item::where('id', $item_id)->get()->first();
        return $item_detail->name;
    }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Model\Item;

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

    
    public function GetItemName(int $item_id){
        $item_detail = Item::where('id', $item_id)->get()->first();
        return $item_detail->name;
    }
}

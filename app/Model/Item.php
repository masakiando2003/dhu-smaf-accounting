<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * アイテム（商品）
 *
 * @property int $id
 * @property string $name 名称
 * @property string|null $description 説明
 * @property int $price 単価
 * @property string $size サイズ
 * @property int $weight 重量
 * @property string $weight_unit 重量の単位
 * @property int $init_stock 初期在庫数
 * @property int $stock 在庫数
 * @property string|null $remarks 備考
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Item extends Model
{
    use SoftDeletes;

    protected $table        = 'items';
    public    $tableType    = 'master';

    protected $primaryKey   = 'id';
    protected $keyType      = 'bigint';
    public    $incrementing = true;

    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['name', 'description', 'price', 'size', 'weight', 'weight_unit', 
                           'init_stock', 'stock', 'remarks'];

    // created_at, updated_at は DB の機能で自動更新する.
    public $timestamps = false;
}

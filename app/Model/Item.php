<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $table        = 'orders';
    public    $tableType    = 'master';

    protected $primaryKey   = 'id';
    protected $keyType      = 'bigint';
    public    $incrementing = true;

    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['num_of_people', 'paid', 'change', 'remarks'];

    // created_at, updated_at は DB の機能で自動更新する.
    public $timestamps = false;
}

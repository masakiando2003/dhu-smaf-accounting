<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Model\OrderItem;

Use DB;

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

    public function GetOrderTotal(int $order_id){
        $order_total = 0;
        $orderItems = OrderItem::where('order_id', $order_id)->get();
        foreach($orderItems as $orderItem){
            $order_total += ($orderItem->price * $orderItem->quantity);
        }

        return $order_total;
    }
}

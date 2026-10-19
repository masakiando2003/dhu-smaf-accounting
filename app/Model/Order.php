<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Model\OrderItem;

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

    public function GetOrdersCountByItemAndTimePeriod(int $item_id, string $date, string $time_period)
    {
        $time_arr = explode(" - ", $time_period);
        $start_time = $date." ".$time_arr[0];
        $end_time = $date." ".$time_arr[1];

        $order_count = Order::join('order_items', function($join){
            $join->on('orders.id', '=', 'order_items.order_id');
        })
        ->where('order_items.item_id', $item_id)
        ->where('orders.created_at', '>=', $start_time)
        ->where('orders.created_at', '<=', $end_time)
        ->count();

        return $order_count;
    }

    public function GetOrdersCountByDate(string $date)
    {
        $order_count = Order::where('orders.created_at', '>=', $date." 00:00:00")
                            ->where('orders.created_at', '<=', $date." 23:59:59")
                            ->count();

        return $order_count;
    }

    public function GetOrderItemsCountByDate(string $date, int $item_id)
    {
        $orderitems_count = Order::join('order_items', function($join){
                            $join->on('orders.id', '=', 'order_items.order_id');
                       })
                       ->where('order_items.item_id', $item_id)
                       ->where('orders.created_at', '>=', $date." 00:00:00")
                       ->where('orders.created_at', '<=', $date." 23:59:59")
                       ->count();

        return $orderitems_count;
    }

    public function GetOrderItemsTotalCount($dates)
    {
        $start_date = $dates[0];
        $end_date = $dates[count($dates)-1];
        $orderitems_count = Order::join('order_items', function($join){
                            $join->on('orders.id', '=', 'order_items.order_id');
                       })
                       ->where('orders.created_at', '>=', $start_date." 00:00:00")
                       ->where('orders.created_at', '<=', $end_date." 23:59:59")
                       ->count();
        return $orderitems_count;
    }

    public function GetOrderItemsSellAmountByDate(string $date, int $item_id=0)
    {
        $order_query = Order::join('order_items', function($join){
                        $join->on('orders.id', '=', 'order_items.order_id');
                       })
                       ->where('orders.created_at', '>=', $date." 00:00:00")
                       ->where('orders.created_at', '<=', $date." 23:59:59");
        if($item_id!=0){
            $order_query->where('order_items.item_id', $item_id);
        }
        $order_total = $order_query->sum('order_items.price', 'order_items.quantity');

        return $order_total;
    }

    public function GetOrderItemsTotalSellAmount($dates)
    {
        $start_date = $dates[0];
        $end_date = $dates[count($dates)-1];
        $order_total = Order::join('order_items', function($join){
                        $join->on('orders.id', '=', 'order_items.order_id');
                       })
                       ->where('orders.created_at', '>=', $start_date." 00:00:00")
                       ->where('orders.created_at', '<=', $end_date." 23:59:59")
                       ->sum('order_items.price', 'order_items.quantity');
        return $order_total;
    }
}

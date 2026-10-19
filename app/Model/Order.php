<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 注文
 *
 * @property int $id
 * @property int $num_of_people 人数
 * @property int $paid 支払金額
 * @property int|null $change お釣り
 * @property string|null $remarks 備考
 * @property \Illuminate\Support\Carbon|null $created_at 注文日時
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
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

    /**
     * 注文の合計金額（単価 × 数量 の合計）を取得する
     *
     * @param  int  $order_id  注文ID
     * @return int
     */
    public function GetOrderTotal(int $order_id){
        $order_total = 0;
        $orderItems = OrderItem::where('order_id', $order_id)->get();
        foreach($orderItems as $orderItem){
            $order_total += ($orderItem->price * $orderItem->quantity);
        }

        return $order_total;
    }

    /**
     * 指定した日・時間帯に、指定したアイテムを含む注文明細の件数を取得する
     *
     * @param  int     $item_id      アイテムID
     * @param  string  $date         日付（Y-m-d）
     * @param  string  $time_period  時間帯（例: "9:00 - 9:59"）
     * @return int
     */
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

    /**
     * 指定した日の注文件数を取得する
     *
     * @param  string  $date  日付（Y-m-d）
     * @return int
     */
    public function GetOrdersCountByDate(string $date)
    {
        $order_count = Order::where('orders.created_at', '>=', $date." 00:00:00")
                            ->where('orders.created_at', '<=', $date." 23:59:59")
                            ->count();

        return $order_count;
    }

    /**
     * 指定した日に、指定したアイテムを含む注文明細の件数を取得する
     *
     * @param  string  $date     日付（Y-m-d）
     * @param  int     $item_id  アイテムID
     * @return int
     */
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

    /**
     * 指定した期間の注文明細の件数を取得する
     *
     * @param  string[]  $dates  日付（Y-m-d）の配列. 最初と最後の日付を期間として使う
     * @return int
     */
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

    /**
     * 指定した日の売上金額を取得する
     *
     * ※ 現状は数量を掛けずに、注文明細の単価だけを合計している.
     *
     * @param  string  $date     日付（Y-m-d）
     * @param  int     $item_id  アイテムID. 0 のときは全アイテムが対象
     * @return int|string
     */
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

    /**
     * 指定した期間の売上金額を取得する
     *
     * ※ 現状は数量を掛けずに、注文明細の単価だけを合計している.
     *
     * @param  string[]  $dates  日付（Y-m-d）の配列. 最初と最後の日付を期間として使う
     * @return int|string
     */
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

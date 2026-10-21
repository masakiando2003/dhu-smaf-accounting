<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 注文
 *
 * @property int $id
 * @property int $num_of_people 人数
 * @property int $paid 支払金額
 * @property int|null $change お釣り
 * @property string|null $remarks 備考
 * @property \Illuminate\Support\Carbon $order_at 注文日時
 * @property \Illuminate\Support\Carbon|null $created_at レコードを作成した日時
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

    protected $dates = ['order_at', 'created_at', 'updated_at'];
    protected $fillable = ['num_of_people', 'paid', 'change', 'remarks', 'order_at'];

    // created_at, updated_at は DB の機能で自動更新する.
    public $timestamps = false;

    /**
     * 注文日時が指定した範囲（開始以上・終了未満）の注文に絞り込む
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTimeInterface|string  $start  開始日時（この日時を含む）
     * @param  \DateTimeInterface|string  $end    終了日時（この日時を含まない）
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedBetween($query, $start, $end)
    {
        return $query->where('orders.order_at', '>=', $start)
                     ->where('orders.order_at', '<', $end);
    }

    /**
     * 指定した日の時間帯の範囲を返す
     *
     * 終わりの時刻（例: 9:59）は、その1分間（9:59:59 まで）を含める.
     *
     * @param  string  $date         日付（Y-m-d）
     * @param  string  $time_period  時間帯（例: "9:00 - 9:59"）
     * @return \Carbon\Carbon[]  [開始日時, 終了日時（含まない）]
     */
    private static function timePeriodRange(string $date, string $time_period)
    {
        $time_arr = explode(" - ", $time_period);

        return [
            Carbon::parse($date." ".$time_arr[0]),
            Carbon::parse($date." ".$time_arr[1])->addMinute(),
        ];
    }

    /**
     * 開始日の 0時から、終了日の翌日 0時までの範囲を返す
     *
     * @param  string  $start_date  開始日（Y-m-d）
     * @param  string  $end_date    終了日（Y-m-d）
     * @return \Carbon\Carbon[]  [開始日時, 終了日時（含まない）]
     */
    private static function dayRange(string $start_date, string $end_date)
    {
        return [
            Carbon::parse($start_date)->startOfDay(),
            Carbon::parse($end_date)->startOfDay()->addDay(),
        ];
    }

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
        [$start_time, $end_time] = self::timePeriodRange($date, $time_period);

        $order_count = Order::join('order_items', function($join){
            $join->on('orders.id', '=', 'order_items.order_id');
            $join->whereNull('order_items.deleted_at');
        })
        ->where('order_items.item_id', $item_id)
        ->orderedBetween($start_time, $end_time)
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
        $order_count = Order::orderedBetween(...self::dayRange($date, $date))
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
            $join->whereNull('order_items.deleted_at');
                       })
                       ->where('order_items.item_id', $item_id)
                       ->orderedBetween(...self::dayRange($date, $date))
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
            $join->whereNull('order_items.deleted_at');
                       })
                       ->orderedBetween(...self::dayRange($start_date, $end_date))
                       ->count();
        return $orderitems_count;
    }

    /**
     * 指定した日の売上金額を取得する
     *
     * 売上金額は 単価 × 数量 の合計. 削除された注文・注文明細は含めない.
     *
     * @param  string  $date     日付（Y-m-d）
     * @param  int     $item_id  アイテムID. 0 のときは全アイテムが対象
     * @return int
     */
    public function GetOrderItemsSellAmountByDate(string $date, int $item_id=0)
    {
        $order_query = Order::join('order_items', function($join){
                        $join->on('orders.id', '=', 'order_items.order_id');
            $join->whereNull('order_items.deleted_at');
                       })
                       ->orderedBetween(...self::dayRange($date, $date));
        if($item_id!=0){
            $order_query->where('order_items.item_id', $item_id);
        }
        $order_total = $order_query->sum(DB::raw('order_items.price * order_items.quantity'));

        return (int) $order_total;
    }

    /**
     * 指定した期間の売上金額を取得する
     *
     * 売上金額は 単価 × 数量 の合計. 削除された注文・注文明細は含めない.
     *
     * @param  string[]  $dates  日付（Y-m-d）の配列. 最初と最後の日付を期間として使う
     * @return int
     */
    public function GetOrderItemsTotalSellAmount($dates)
    {
        $start_date = $dates[0];
        $end_date = $dates[count($dates)-1];
        $order_total = Order::join('order_items', function($join){
                        $join->on('orders.id', '=', 'order_items.order_id');
            $join->whereNull('order_items.deleted_at');
                       })
                       ->orderedBetween(...self::dayRange($start_date, $end_date))
                       ->sum(DB::raw('order_items.price * order_items.quantity'));
        return (int) $order_total;
    }

    /**
     * 指定した日・時間帯の来客数（注文の人数の合計）を取得する
     *
     * @param  string  $date         日付（Y-m-d）
     * @param  string  $time_period  時間帯（例: "9:00 - 9:59"）
     * @return int
     */
    public function GetVisitorCountByTimePeriod(string $date, string $time_period)
    {
        return (int) Order::orderedBetween(...self::timePeriodRange($date, $time_period))
                          ->sum('num_of_people');
    }

    /**
     * 指定した日の来客数（注文の人数の合計）を取得する
     *
     * @param  string  $date  日付（Y-m-d）
     * @return int
     */
    public function GetVisitorCountByDate(string $date)
    {
        return (int) Order::orderedBetween(...self::dayRange($date, $date))
                          ->sum('num_of_people');
    }

    /**
     * 指定した期間の来客数（注文の人数の合計）を取得する
     *
     * @param  string[]  $dates  日付（Y-m-d）の配列. 最初と最後の日付を期間として使う
     * @return int
     */
    public function GetVisitorTotalCount($dates)
    {
        return (int) Order::orderedBetween(...self::dayRange($dates[0], $dates[count($dates)-1]))
                          ->sum('num_of_people');
    }

    /**
     * 指定した日の客単価（売上高 ÷ 来客数、1円未満は切り捨て）を取得する
     *
     * @param  string  $date  日付（Y-m-d）
     * @return int  来客数が 0人の場合は 0
     */
    public function GetAverageSpendPerVisitorByDate(string $date)
    {
        return self::averageSpend($this->GetOrderItemsSellAmountByDate($date), $this->GetVisitorCountByDate($date));
    }

    /**
     * 指定した期間の客単価（売上高 ÷ 来客数、1円未満は切り捨て）を取得する
     *
     * @param  string[]  $dates  日付（Y-m-d）の配列. 最初と最後の日付を期間として使う
     * @return int  来客数が 0人の場合は 0
     */
    public function GetAverageSpendPerVisitor($dates)
    {
        return self::averageSpend($this->GetOrderItemsTotalSellAmount($dates), $this->GetVisitorTotalCount($dates));
    }

    /**
     * 客単価を計算する
     *
     * @param  int  $sell_amount    売上高
     * @param  int  $visitor_count  来客数
     * @return int  来客数が 0人の場合は 0
     */
    private static function averageSpend(int $sell_amount, int $visitor_count)
    {
        if($visitor_count <= 0){
            return 0;
        }
        return intdiv($sell_amount, $visitor_count);
    }
}

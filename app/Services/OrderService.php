<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;

use App\Model\Item;
use App\Model\Order;
use App\Model\OrderItem;
use App\Model\Cashier;
use App\Model\CompanyInfo;
use App\Services\Concerns\PaginatesRecords;

use Carbon\Carbon;

/**
 * 注文のデータ操作
 *
 * 注文の登録・更新・削除にあわせて、在庫数・現金出納帳・会社の現金も更新する.
 */
class OrderService
{
    use PaginatesRecords;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $db;

    /**
     * @param  \Illuminate\Database\ConnectionInterface  $db  DB 接続（削除処理のトランザクションと、金額を計算する SQL 式に使う）
     */
    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * 注文一覧をページに分けて取得する
     *
     * @param  int  $per_page  1ページの表示件数
     * @return array{records: \Illuminate\Contracts\Pagination\LengthAwarePaginator, total_count: int, is_first_page_fallback: bool}
     */
    public function paginate(int $per_page)
    {
        return $this->paginateWithFallback(Order::orderBy('id', 'asc'), $per_page);
    }

    /**
     * 注文と注文明細を取得する
     *
     * 注文には、合計金額（order_total）と、支払金額から計算したお釣り（change）を設定する.
     *
     * @param  int  $id  注文ID
     * @return array{order: \App\Model\Order, order_items: \Illuminate\Database\Eloquent\Collection}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  注文が存在しない（削除済みを含む）場合
     */
    public function getOrderDetail($id)
    {
        $orderDetail = Order::findOrFail($id);
        $orderItemDetail = OrderItem::where('order_id', $id)->get();
        $order_total = 0;
        foreach($orderItemDetail as $orderItem){
            $order_total += ($orderItem->price * $orderItem->quantity);
        }
        $orderDetail->order_total = $order_total;
        $orderDetail->change = $orderDetail->paid - $order_total;

        return [
            'order'       => $orderDetail,
            'order_items' => $orderItemDetail,
        ];
    }

    /**
     * 注文を新規登録する
     *
     * 注文明細の登録、在庫数の減算、「収入」の出納記録の作成、会社の現金の加算もあわせて行う.
     *
     * @param  array        $data         num_of_people, paid, change, remarks
     * @param  array        $order_items  注文明細（\App\Http\Requests\OrderRequest::orderItems() の形）
     * @param  string|null  $created_at   注文日時（Y-m-d H:i:s）. null のときは現在日時
     * @return \App\Model\Order
     */
    public function register(array $data, array $order_items, $created_at = null)
    {
        $new_order = new Order();
        $new_order->num_of_people = $data['num_of_people'];
        $new_order->paid = $data['paid'];
        $new_order->change = $data['change'];
        $new_order->remarks = $data['remarks'];
        $new_order->created_at = $created_at ?? Carbon::now();
        $new_order->save();
        $order_id = $new_order->id;

        $order_item_total = 0;
        foreach($order_items as $order_item){
            $item_id = $order_item['item_id'];
            $new_order_item = new OrderItem();
            $new_order_item->order_id = $order_id;
            $new_order_item->item_id = $item_id;
            $order_item_price = ($order_item['price'] !='') ? $order_item['price'] : 0;
            $order_item_quantity = ($order_item['quantity'] !='') ? $order_item['quantity'] : 0;
            $new_order_item->price = $order_item_price;
            $new_order_item->quantity = $order_item_quantity;
            $new_order_item->created_at = Carbon::now();
            if(!$order_item['delete']){
                $order_item_total += $order_item_price * $order_item_quantity;
                $new_order_item->save();
                //在庫数更新
                Item::where('id', $item_id)->decrement('stock', $order_item_quantity);
            }
        }

        $change = $data['paid'] - $order_item_total;
        Order::where('id', $order_id)
             ->update([
                'change' => $change
        ]);

        if($data['paid'] > 0){
            // 直接出納記録を作成する
            $new_cashier = new Cashier();
            $new_cashier->transaction_time = date('Y-m-d H:i:s');
            $new_cashier->cashier_type = 'income';
            $new_cashier->order_id = $order_id;
            $new_cashier->description = '注文ID: '.$order_id.'に支払する';
            $new_cashier->income_amount = $data['paid'];
            $new_cashier->payment_amount = $data['change'];
            $new_cashier->deduction_amount = $data['paid'] - $data['change'];
            $new_cashier->remarks = $data['remarks'];
            $new_cashier->created_at = Carbon::now();
            $new_cashier->save();

            //会社の現金を更新する
            CompanyInfo::increment('cash', $order_item_total);
        }

        return $new_order;
    }

    /**
     * 注文を更新する
     *
     * 注文明細の追加・変更・削除にあわせて在庫数を戻してから再計算し、
     * 出納記録と会社の現金も更新する.
     *
     * 画面の明細にないアイテム（行のアイテムを別のアイテムに変えた場合など）の明細は削除する.
     *
     * @param  int          $order_id     注文ID
     * @param  array        $data         num_of_people, paid, change, remarks
     * @param  array        $order_items  注文明細（\App\Http\Requests\OrderRequest::orderItems() の形）
     * @param  string|null  $created_at   注文日時（Y-m-d H:i:s）. null のときは変更しない
     * @return void
     */
    public function update($order_id, array $data, array $order_items, $created_at = null)
    {
        // 更新.
        Order::where('id', $order_id)
             ->update([
                'num_of_people' => $data['num_of_people'],
                'paid'          => $data['paid'],
                'change'        => $data['change'],
                'remarks'       => $data['remarks'],
                'updated_at'    => Carbon::now()
             ]);

        $old_order_item_total = (int) OrderItem::where('order_id', $order_id)
                                ->sum($this->db->raw('price * quantity'));

        $order_item_total = 0;
        foreach($order_items as $order_item){
            if($order_item['delete']){
                $item_id = intval($order_item['item_id']);
                // 在庫数更新
                $order_item_info = OrderItem::where('order_id', $order_id)
                                            ->where('item_id', $item_id)->first();
                if($order_item_info !== null){
                    Item::where('id', $item_id)->increment('stock', $order_item_info->quantity);
                    OrderItem::where('order_id', $order_id)->where('item_id', $item_id)
                              ->delete();
                }
            } else {
                $item_id = intval($order_item['item_id']);
                $order_item_price = ($order_item['price'] !='') ? intval($order_item['price']) : 0;
                $order_item_quantity = ($order_item['quantity'] !='') ? intval($order_item['quantity']) : 0;
                $order_item_total += $order_item_price * $order_item_quantity;
                $order_item_exist_count = OrderItem::where('order_id', $order_id)->where('item_id', $item_id)->count();

                if($order_item_exist_count > 0){
                    // 在庫数更新
                    $order_item_info = OrderItem::where('order_id', $order_id)
                                                ->where('item_id', $item_id)->get()->first();
                    $old_order_item_quantity = $order_item_info->quantity;
                    Item::where('id', $item_id)->increment('stock', $old_order_item_quantity);
                    OrderItem::where('order_id', $order_id)->where('item_id', $item_id)
                             ->update([
                                'item_id'       => $item_id,
                                'price'         => $order_item_price,
                                'quantity'      => $order_item_quantity,
                                'updated_at'    => Carbon::now()
                    ]);
                    Item::where('id', $item_id)->decrement('stock', $order_item_quantity);
                } else {
                    $new_order_item = new OrderItem();
                    $new_order_item->order_id = $order_id;
                    $new_order_item->item_id = $item_id;
                    $new_order_item->price = $order_item_price;
                    $new_order_item->quantity = $order_item_quantity;
                    $new_order_item->save();
                    // 在庫数更新
                    Item::where('id', $item_id)->decrement('stock', $order_item_quantity);
                }
            }
        }

        // 画面の明細にないアイテム（行のアイテムを別のアイテムに変えた場合など）の明細を削除し、在庫を戻す
        $kept_item_ids = [];
        foreach($order_items as $order_item){
            if(!$order_item['delete']){
                $kept_item_ids[] = intval($order_item['item_id']);
            }
        }
        $removed_order_items = OrderItem::where('order_id', $order_id)
                                        ->whereNotIn('item_id', $kept_item_ids)->get();
        foreach($removed_order_items as $removed_order_item){
            Item::where('id', $removed_order_item->item_id)->increment('stock', $removed_order_item->quantity);
            $removed_order_item->delete();
        }

        $change = $data['paid'] - $order_item_total;
        Order::where('id', $order_id)
             ->update([
                'change' => $change
        ]);

        if($created_at !== null){
            Order::where('id', $order_id)
             ->update([
                'created_at' => $created_at
             ]);
        }

        Cashier::where('order_id', $order_id)
                ->update([
                'income_amount'     => $data['paid'],
                'payment_amount'    => $data['change'],
                'deduction_amount'  => $data['paid'] - $data['change'],
                'remarks'           => $data['remarks'],
                'updated_at'        => Carbon::now(),
            ]);

        //会社の現金を更新する
        CompanyInfo::decrement('cash', $old_order_item_total);
        CompanyInfo::increment('cash', $order_item_total);
    }

    /**
     * 注文を削除（論理削除）する
     *
     * 注文明細と出納記録も論理削除し、在庫数と会社の現金を戻す.
     *
     * 途中でエラーが起きた場合は、それまでの変更をすべて元に戻して、元の例外を投げる.
     *
     * @param  int  $order_id  注文ID
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  注文が存在しない（削除済みを含む）場合
     * @throws \Throwable  削除中に DB エラーなどが起きた場合
     */
    public function delete($order_id)
    {
        $this->db->transaction(function () use ($order_id) {
            $order = Order::findOrFail($order_id);
            $order->delete();

            // 在庫数更新
            $order_items = OrderItem::where('order_id', $order_id)->get();
            $order_item_total = 0;
            foreach($order_items as $order_item){
                Item::where('id', $order_item->item_id)->increment('stock', $order_item->quantity);
                $order_item_total += $order_item->price * $order_item->quantity;
            }
            OrderItem::where('order_id', $order_id)->delete();

            // 会社の現金を更新する（登録時に収入を記録した注文だけ、現金に加算されている）
            if(Cashier::where('order_id', $order_id)->exists()){
                CompanyInfo::decrement('cash', $order_item_total);
            }
            Cashier::where('order_id', $order_id)->delete();

            $order->save();
        });
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Model\Item;
use App\Model\Order;
use App\Model\OrderItem;
use App\Model\Cashier;
use App\Model\CompanyInfo;

use Carbon\Carbon;

class OrderController extends Controller
{
    function index(Request $request)
    {
        if(isset($request->display_items)){
            $paginate = $request->display_items;
        }
        else {
            $paginate = 10;
        }
        if(isset($request->page)){
            $page = $request->page;
        } else {
            $page = 1;
        }
        $display_items = $paginate;
        $query = Order::orderBy('id', 'asc');
        $total_count = $query->count();
        $orders = $query->paginate($paginate);
        if(count($orders)<=0){
            $orders = $query->paginate($paginate, ['*'], 'page', 1);
            $page = 1;
        }
        $start_record = ($page - 1) * $paginate + 1;
        $end_record = ($page * $paginate >= count($orders) ) ? count($orders) : $page * $paginate;

        $pagination_params = [
            "display_items" => $display_items,
        ];

        return view('admin/orders/index', compact('orders', 'start_record', 'end_record', 'total_count',
                                                  'display_items', 'pagination_params'));
    }
    
    function CreateOrder()
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 新規注文作成";
        $page['content_header'] = "新規注文作成";
        $page['action'] = "/admin/orders/register";
        $page['submit'] = '作成';
        return view('admin/orders/detail')->with('page', $page);
    }

    function ShowOrderDetail($id)
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 注文詳細";
        $page['content_header'] = "注文詳細";
        $page['action'] = "/admin/orders/edit";
        $page['submit'] = '更新';

        $orderDetail = Order::find($id);
        $orderItemDetail = OrderItem::where('order_id', $id)->get();
        $order_total = 0;
        foreach($orderItemDetail as $orderItem){
            $order_total += ($orderItem->price * $orderItem->quantity);
        }
        $orderDetail->order_total = $order_total;
        $orderDetail->change = $orderDetail->paid - $order_total;
        return view('admin/orders/detail', compact('page', 'orderDetail', 'orderItemDetail'));
    }

    function GetOrderValidationErrorMessage() {
        return [
            "num_of_people.required"       => "人数が指定されていません",
            "num_of_people.regex"          => "人数に数字以外が試用されています",
            "paid.required"                => "支払金額が指定されていません",
            "paid.regex"                   => "支払金額に数字以外が試用されています",
        ];
      }
  
      function GetOrderValidator(Request $request) {
        return Validator::make($request->all(), [
                    'num_of_people'         => 'required|regex:/^[0-9]+$/',
                    'paid'         => 'required|regex:/^[0-9]+$/',
                ], $this->GetOrderValidationErrorMessage());
      }

    function RegisterOrder(Request $request)
    {
        $validator = $this->GetOrderValidator($request);
        if ($validator->fails()) {
          return redirect()
                 ->back()
                 ->withInput()
                 ->withErrors($validator);
        }

        $new_order = new Order();
        $new_order->num_of_people = $request->num_of_people;
        $new_order->paid = $request->paid;
        $new_order->change = $request->change;
        $new_order->remarks = $request->remarks;
        $new_order->created_at = Carbon::now();
        $new_order->save();
        $order_id = $new_order->id;

        $order_item_count = $request->order_item_count;
        $order_item_total = 0;
        for($i = 1; $i <= $order_item_count; $i++){
            if(isset($_POST['item_id_'.$i]) && $_POST['item_id_'.$i]!=''){
                $item_id = $_POST['item_id_'.$i];
                $new_order_item = new OrderItem();
                $new_order_item->order_id = $order_id;
                $new_order_item->item_id = $item_id;
                $order_item_price = ($_POST['price_'.$i] !='') ? $_POST['price_'.$i] : 0;
                $order_item_quantity = ($_POST['quantity_'.$i] !='') ? $_POST['quantity_'.$i] : 0;
                $new_order_item->price = $order_item_price;
                $new_order_item->quantity = $order_item_quantity;
                $order_item_total += $order_item_price * $order_item_quantity;
                $new_order_item->created_at = Carbon::now();
                if(!isset($_POST['delete_item_'.$i])){
                    $new_order_item->save();
                    //在庫数更新
                    Item::where('id', $item_id)->decrement('stock', $order_item_quantity);
                }
            }
        }

        $change = $request->paid - $order_item_total;
        Order::where('id', $order_id)
             ->update([
                'change' => $change
        ]);

        if($request->paid > 0){
            // 直接出納記録を作成する
            $new_cashier = new Cashier();
            $new_cashier->transaction_time = date('Y-m-d H:i:s');
            $new_cashier->cashier_type = 'income';
            $new_cashier->order_id = $order_id;
            $new_cashier->description = '注文ID: '.$order_id.'に支払する';
            $new_cashier->income_amount = $request->paid;
            $new_cashier->payment_amount = $request->change;
            $new_cashier->deduction_amount = $request->paid - $request->change;
            $new_cashier->remarks = $request->remarks;
            $new_cashier->created_at = Carbon::now();
            $new_cashier->save();

            //会社の現金を更新する
            CompanyInfo::increment('cash', $order_item_total);
        }

        $msg="新規注文ID: \"$order_id\"を作成しました";
        return redirect('/admin/orders')->with('success_msg', $msg);
    }

    function EditOrder(Request $request)
    {
        if($request->order_id == null){
            return redirect('/admin/orders');
            exit;
        }

        $order_id = $request->order_id;

        $validator = $this->GetOrderValidator($request);
        if ($validator->fails()) {
          return redirect()
                 ->back()
                 ->withInput()
                 ->withErrors($validator);
        }

        // 更新.
        Order::where('id', $order_id)
             ->update([
                'num_of_people' => $request->num_of_people,
                'paid'          => $request->paid,
                'change'        => $request->change,
                'remarks'       => $request->remarks,
                'updated_at'    => Carbon::now()
             ]);

        $old_order_item_total = OrderItem::where('order_id', $order_id)
                                ->sum('price', 'quantity');

        $order_item_count = $request->order_item_count;
        $order_item_total = 0;
        for($i = 1; $i <= $order_item_count; $i++){
            if(isset($_POST['item_id_'.$i]) && $_POST['item_id_'.$i]!=''){
                if(isset($_POST['delete_item_'.$i]) && $_POST['delete_item_'.$i]!=''){
                    $item_id = intval($_POST['item_id_'.$i]);
                    // 在庫数更新
                    $order_item_info = OrderItem::where('order_id', $order_id)
                                                ->where('item_id', $item_id)->get();
                    $old_order_item_quantity = $order_item_info->quantity;
                    Item::where('id', $item_id)->increment('stock', $old_order_item_quantity_count);
                    OrderItem::where('order_id', $order_id)->where('item_id', $item_id)
                              ->delete();
                } else {
                    $item_id = intval($_POST['item_id_'.$i]);
                    $order_item_price = ($_POST['price_'.$i] !='') ? intval($_POST['price_'.$i]) : 0;
                    $order_item_quantity = ($_POST['quantity_'.$i] !='') ? intval($_POST['quantity_'.$i]) : 0;
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
        }

        $change = $request->paid - $order_item_total;
        Order::where('id', $order_id)
             ->update([
                'change' => $change
        ]);

        Cashier::where('order_id', $order_id)
                ->update([
                'income_amount'     => $request->paid,
                'payment_amount'    => $request->change,
                'deduction_amount'  => $request->paid - $request->change,
                'remarks'           => $request->remarks,
                'updated_at'        => Carbon::now(),
            ]);

        //会社の現金を更新する
        CompanyInfo::decrement('cash', $old_order_item_total);
        CompanyInfo::increment('cash', $order_item_total);
        
        $msg = "注文ID: \"$order_id\"を更新しました";
        return redirect('/admin/orders')->with('success_msg', $msg);
    }

    function DeleteOrder(Request $request)
    {
        $order_id = $request->del_id;

        DB::beginTransaction();
        try {
            $order = Order::find($order_id);
            $order->delete();

            // 在庫数更新
            $order_items = OrderItem::where('order_id', $order_id)->get();
            foreach($order_items as $order_item){
                Item::where('id', $order_item->item_id)->increment('stock', $order_item->quantity);
            }
            OrderItem::where('order_id', $order_id)->delete();

            Cashier::where('order_id', $order_id)->delete();

            $order->save();
            
            DB::commit();
        } catch (\PDOException $e){
            DB::rollBack();
            throw e;
        }

        $msg = "注文ID: \"$order_id\"を削除しました";
        if(isset($request->display_items) && isset($request->page)){
            $display_items = $request->display_items;
            $page = $request->page;
            $pagination_params = [
                "display_items" => $display_items,
                "page" => $page,
            ];
            return redirect('/admin/orders?display_items'.$display_items.'&page='.$page)
                   ->with([
                            'success_msg' => $msg,
                            'pagination_params' => $pagination_params
                    ]);
        }
        else{
            return redirect('/admin/orders')->with('success_msg', $msg);
        }
    }
}

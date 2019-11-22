<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Model\Order;
use App\Model\OrderItem;
use App\Model\Cashier;

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
        $orders = $query->paginate($paginate);
        if(count($orders)<=0){
            $orders = $query->paginate($paginate, ['*'], 'page', 1);
            $page = 1;
        }

        $pagination_params = [
            "display_items" => $display_items,
        ];

        return view('admin/orders/index', compact('orders', 'display_items', 'pagination_params'));
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
        $orderDetail->change = $order_total - $orderDetail->paid;
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
        $new_order->save();
        $order_id = $new_order->id;

        $order_item_count = $request->order_item_count;
        $order_item_total = 0;
        for($i = 1; $i <= $order_item_count; $i++){
            $new_order_item = new OrderItem();
            if(isset($_POST['item_id_'.$i]) && $_POST['item_id_'.$i]!=''){
                $new_order_item->order_id = $order_id;
                $new_order_item->item_id = $_POST['item_id_'.$i];
                $order_item_price = ($_POST['price_'.$i] !='') ? $_POST['price_'.$i] : 0;
                $new_order_item->price = $order_item_price;
                $order_item_quantity = ($_POST['quantity_'.$i] !='') ? $_POST['quantity_'.$i] : 0;
                $new_order_item->quantity = $order_item_quantity;
                $order_item_total += ($order_item_price * $order_item_quantity);
                $new_order_item->save();
            }
        }

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
            $new_cashier->save();
        }

        $msg="新規注文ID: \"$order_id\"を作成しました";
        return redirect('/admin/orders')->with('success_msg', $msg);
    }

    function EditOrder(Request $request)
    {
        if($request->id === null){
            return redirect('/admin/orders');
            exit;
        }

        $validator = $this->GetOrderValidator($request);
        if ($validator->fails()) {
          return redirect()
                 ->back()
                 ->withInput()
                 ->withErrors($validator);
        }

        // 更新.
        Order::where('id', $request->id)
             ->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'size' => $request->size,
                'weight' => $request->weight,
                'weight_unit' => $request->weight_unit,
                'init_stock' => $request->init_stock,
                'stock' => $request->stock,
             ]);

        
        $msg = "注文ID: \"$request->id\"を更新しました";
        return redirect('/admin/items')->with('success_msg', $msg);
    }

    function DeleteOrder(Request $request)
    {
        $order_id = $request->del_id;

        DB::beginTransaction();
        try {
            $order = Order::find($order_id);
            $order->delete();

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

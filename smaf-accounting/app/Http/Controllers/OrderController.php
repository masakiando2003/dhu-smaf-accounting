<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Model\Order;
use App\Model\OrderItem;

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
        return view('admin/orders/detail', compact('page', 'orderDetail'));
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
        $validator = $this->GetItemValidator($request);
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
        $order_id = $new_order->insert_id;

        $order_item_count = $request->order_item_count;
        for($i = 1; $i <= $order_item_count; $i++){
            $new_order_item = new OrderItem();
            $new_order_item->order_id = $order_id;
            //$new_order_item->item_id = $order_id;
            $new_order_item->save();
        }

        $msg="新規注文ID: \"$order_id\"を作成しました";
        return redirect('/admin/items')->with('success_msg', $msg);
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

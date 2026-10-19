<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\OrderRequest;
use App\Services\ItemService;
use App\Services\OrderService;

/**
 * 注文の一覧・作成・編集・削除を扱うコントローラー
 *
 * 注文の登録・更新・削除にあわせて、在庫数・現金出納帳・会社の現金も更新する.
 */
class OrderController extends Controller
{
    /**
     * @var \App\Services\OrderService
     */
    private $orderService;

    /**
     * @var \App\Services\ItemService
     */
    private $itemService;

    /**
     * @param  \App\Services\OrderService  $orderService
     * @param  \App\Services\ItemService   $itemService
     */
    public function __construct(OrderService $orderService, ItemService $itemService)
    {
        $this->orderService = $orderService;
        $this->itemService = $itemService;
    }

    /**
     * 注文一覧を表示する
     *
     * @param  \Illuminate\Http\Request  $request  display_items（1ページの表示件数）、page（ページ番号）
     * @return \Illuminate\View\View
     */
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
        $result = $this->orderService->paginate($paginate);
        $orders = $result['records'];
        $total_count = $result['total_count'];
        if($result['is_first_page_fallback']){
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
    
    /**
     * 注文の新規作成画面を表示する
     *
     * @return \Illuminate\View\View
     */
    function CreateOrder()
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 新規注文作成";
        $page['content_header'] = "新規注文作成";
        $page['action'] = "/admin/orders/register";
        $page['submit'] = '作成';
        $items = $this->itemService->all();
        return view('admin/orders/detail', compact('page', 'items'));
    }

    /**
     * 注文の詳細（編集）画面を表示する
     *
     * @param  int  $id  注文ID
     * @return \Illuminate\View\View
     */
    function ShowOrderDetail($id)
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 注文詳細";
        $page['content_header'] = "注文詳細";
        $page['action'] = "/admin/orders/edit";
        $page['submit'] = '更新';

        $order_detail = $this->orderService->getOrderDetail($id);
        $orderDetail = $order_detail['order'];
        $orderItemDetail = $order_detail['order_items'];
        $items = $this->itemService->all();
        return view('admin/orders/detail', compact('page', 'orderDetail', 'orderItemDetail', 'items'));
    }

    /**
     * 注文を新規登録する
     *
     * 注文明細の登録、在庫数の減算、「収入」の出納記録の作成、会社の現金の加算もあわせて行う.
     * 注文明細は item_id_N / price_N / quantity_N（N は 1〜order_item_count）で受け取る.
     *
     * @param  \App\Http\Requests\OrderRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function RegisterOrder(OrderRequest $request)
    {
        $new_order = $this->orderService->register(
            $this->orderData($request),
            $request->orderItems(),
            $request->manualTransactionTime()
        );
        $order_id = $new_order->id;

        $msg="新規注文ID: \"$order_id\"を作成しました";
        if($request->multiple_create_flag == 1)
        {
            return redirect('/admin/orders/create')->with('success_msg', $msg);
        } 
        else 
        {
            return redirect('/admin/orders')->with('success_msg', $msg);
        }
    }

    /**
     * 注文を更新する
     *
     * 注文明細の追加・変更・削除にあわせて在庫数を戻してから再計算し、
     * 出納記録と会社の現金も更新する.
     *
     * @param  \App\Http\Requests\OrderRequest  $request  order_id（更新する注文ID）ほか
     * @return \Illuminate\Http\RedirectResponse
     */
    function EditOrder(OrderRequest $request)
    {
        if($request->order_id == null){
            return redirect('/admin/orders');
            exit;
        }

        $order_id = $request->order_id;

        $this->orderService->update(
            $order_id,
            $this->orderData($request),
            $request->orderItems(),
            $request->manualTransactionTime()
        );

        $msg = "注文ID: \"$order_id\"を更新しました";
        return redirect('/admin/orders')->with('success_msg', $msg);
    }

    /**
     * 注文を削除（論理削除）する
     *
     * 注文明細と出納記録も論理削除し、在庫数を戻す.
     *
     * @param  \Illuminate\Http\Request  $request  del_id（削除する注文ID）
     * @return \Illuminate\Http\RedirectResponse
     */
    function DeleteOrder(Request $request)
    {
        $order_id = $request->del_id;

        $this->orderService->delete($order_id);

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

    /**
     * フォームの入力から、登録・更新する注文の項目を取り出す
     *
     * @param  \App\Http\Requests\OrderRequest  $request
     * @return array
     */
    private function orderData(OrderRequest $request)
    {
        return [
            'num_of_people' => $request->num_of_people,
            'paid'          => $request->paid,
            'change'        => $request->change,
            'remarks'       => $request->remarks,
        ];
    }
}

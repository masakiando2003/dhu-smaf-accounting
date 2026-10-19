<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\ItemRequest;
use App\Services\ItemService;

/**
 * アイテム（商品）の一覧・作成・編集・削除を扱うコントローラー
 */
class ItemController extends Controller
{
    /**
     * @var \App\Services\ItemService
     */
    private $itemService;

    /**
     * @param  \App\Services\ItemService  $itemService
     */
    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
    }

    /**
     * アイテム一覧を表示する
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
        $result = $this->itemService->paginate($paginate);
        $items = $result['records'];
        $total_count = $result['total_count'];
        if($result['is_first_page_fallback']){
            $page = 1;
        }
        $start_record = ($page - 1) * $paginate + 1;
        $end_record = ($page * $paginate >= count($items) ) ? count($items) : $page * $paginate;

        $pagination_params = [
            "display_items" => $display_items,
        ];

        return view('admin/items/index', compact('items', 'start_record', 'end_record', 'total_count',
                                                 'display_items', 'pagination_params'));
    }
    
    /**
     * アイテムの新規作成画面を表示する
     *
     * @return \Illuminate\View\View
     */
    function CreateItem()
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 新規アイテム作成";
        $page['content_header'] = "新規アイテム作成";
        $page['action'] = "/admin/items/register";
        $page['submit'] = '作成';
        return view('admin/items/detail')->with('page', $page);
    }

    /**
     * アイテムの詳細（編集）画面を表示する
     *
     * @param  int  $id  アイテムID
     * @return \Illuminate\View\View
     */
    function ShowItemDetail($id)
    {
        $page = array();
        $page['title'] = "SmaF会計システム - アイテム詳細";
        $page['content_header'] = "アイテム詳細";
        $page['action'] = "/admin/items/edit";
        $page['submit'] = '更新';

        $itemDetail = $this->itemService->find($id);
        return view('admin/items/detail', compact('page', 'itemDetail'));
    }

    /**
     * アイテムを新規登録する
     *
     * 同じ名称のアイテムがすでにある場合は、エラーにして入力画面に戻す.
     *
     * @param  \App\Http\Requests\ItemRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function RegisterItem(ItemRequest $request)
    {
        if($this->itemService->existsByName($request->name)){
            $err_msg = "入力したアイテム名称\"$request->name\"は既にデータベース存在しています。";
            return redirect()
                       ->back()
                       ->withInput()
                       ->withErrors($err_msg);
        }

        $new_item = $this->itemService->create($this->itemData($request) + [
            'remarks' => $request->remarks,
        ]);

        $msg="新規アイテム\"$new_item->name\"を作成しました";
        return redirect('/admin/items')->with('success_msg', $msg);
    }

    /**
     * アイテムを更新する
     *
     * @param  \App\Http\Requests\ItemRequest  $request  item_id（更新するアイテムID）、ori_name（変更前の名称）ほか
     * @return \Illuminate\Http\RedirectResponse
     */
    function EditItem(ItemRequest $request)
    {
        if($request->item_id === null){
            return redirect('/admin/items');
            exit;
        }

        // 重複チェック.
        $item_name = $request->name;
        $ori_item_name = $request->ori_name;
        if($item_name != $ori_item_name){
            if($this->CheckEventIsNotExist($item_name) == "false"){
                $err_msg = "入力したアイテム名称\"$item_name\"は既にデータベース存在します。";
                return redirect()
                       ->back()
                       ->withInput()
                       ->withErrors($err_msg);
            }
        }

        // 更新.
        $this->itemService->update($request->item_id, $this->itemData($request));

        $msg = "アイテム\"$request->name\"を更新しました";
        return redirect('/admin/items')->with('success_msg', $msg);
    }

    /**
     * アイテムを削除（論理削除）する
     *
     * @param  \Illuminate\Http\Request  $request  del_id（削除するアイテムID）、del_name（アイテム名称）
     * @return \Illuminate\Http\RedirectResponse
     */
    function DeleteItem(Request $request)
    {
        $this->itemService->delete($request->del_id);

        $msg = "アイテム\"$request->del_name\"を削除しました";
        if(isset($request->display_items) && isset($request->page)){
            $display_items = $request->display_items;
            $page = $request->page;
            $pagination_params = [
                "display_items" => $display_items,
                "page" => $page,
            ];
            return redirect('/admin/items?display_items'.$display_items.'&page='.$page)
                   ->with([
                            'success_msg' => $msg,
                            'pagination_params' => $pagination_params
                    ]);
        }
        else{
            return redirect('/admin/items')->with('success_msg', $msg);
        }
    }

    /**
     * フォームの入力から、登録・更新するアイテムの項目を取り出す
     *
     * @param  \App\Http\Requests\ItemRequest  $request
     * @return array
     */
    private function itemData(ItemRequest $request)
    {
        return [
            'name'        => $request->name,
            'description' => $request->description,
            'price'       => $request->price,
            'size'        => $request->size,
            'weight'      => $request->weight,
            'weight_unit' => $request->weight_unit,
            'init_stock'  => $request->init_stock,
            'stock'       => $request->stock,
        ];
    }
}

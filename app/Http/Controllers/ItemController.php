<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Model\Item;

class ItemController extends Controller
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
        $query = Item::orderBy('id', 'asc');
        $total_count = $query->count();
        $items = $query->paginate($paginate);
        if(count($items)<=0){
            $items = $query->paginate($paginate, ['*'], 'page', 1);
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
    
    function CreateItem()
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 新規アイテム作成";
        $page['content_header'] = "新規アイテム作成";
        $page['action'] = "/admin/items/register";
        $page['submit'] = '作成';
        return view('admin/items/detail')->with('page', $page);
    }

    function ShowItemDetail($id)
    {
        $page = array();
        $page['title'] = "SmaF会計システム - アイテム詳細";
        $page['content_header'] = "アイテム詳細";
        $page['action'] = "/admin/items/edit";
        $page['submit'] = '更新';

        $itemDetail = Item::find($id);
        return view('admin/items/detail', compact('page', 'itemDetail'));
    }

    function GetItemValidationErrorMessage() {
        return [
            "name.required"                 => "アイテム名称が指定されていません",
            "name.max"                      => "アイテム名称が255字を超えています",
            "price.required"                => "価格が指定されていません",
            "price.regex"                   => "価格に数字以外が試用されています",
            "weight.required"               => "重量が指定されていません",
            "weight.regex"                  => "重量に数字以外が試用されています",
            "init_stock.required"           => "初期在庫数が指定されていません",
            "init_stock.regex"              => "初期在庫数に数字以外が試用されています",
            "stock.required"                => "在庫数が指定されていません",
            "stock.regex"                   => "在庫数に数字以外が試用されています",
        ];
      }
  
      function GetItemValidator(Request $request) {
        return Validator::make($request->all(), [
                    'name'          => 'required|max:255',
                    'price'         => 'required|regex:/^[0-9]+$/',
                    'weight'        => 'required|regex:/^[0-9]+$/',
                    'init_stock'    => 'required|regex:/^[0-9]+$/',
                    'stock'         => 'required|regex:/^[0-9]+$/',
                ], $this->GetItemValidationErrorMessage());
      }

    function RegisterItem(Request $request)
    {
        $validator = $this->GetItemValidator($request);
        if ($validator->fails()) {
          return redirect()
                 ->back()
                 ->withInput()
                 ->withErrors($validator);
        }

        if($this->CheckItemNameIsNotExist($request->name) == "false"){
            $err_msg = "入力したアイテム名称\"$request->name\"は既にデータベース存在しています。";
            return redirect()
                       ->back()
                       ->withInput()
                       ->withErrors($err_msg);
        }

        $new_item = new Item();
        $new_item->name = $request->name;
        $new_item->description = $request->description;
        $new_item->price = $request->price;
        $new_item->size = $request->size;
        $new_item->weight = $request->weight;
        $new_item->weight_unit = $request->weight_unit;
        $new_item->init_stock = $request->init_stock;
        $new_item->stock = $request->stock;
        $new_item->remarks = $request->remarks;
        $new_item->save();

        $msg="新規アイテム\"$new_item->name\"を作成しました";
        return redirect('/admin/items')->with('success_msg', $msg);
    }

    function EditItem(Request $request)
    {
        if($request->item_id === null){
            return redirect('/admin/items');
            exit;
        }

        $validator = $this->GetItemValidator($request);
        if ($validator->fails()) {
          return redirect()
                 ->back()
                 ->withInput()
                 ->withErrors($validator);
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
        Item::where('id', $request->item_id)
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

        
        $msg = "アイテム\"$request->name\"を更新しました";
        return redirect('/admin/items')->with('success_msg', $msg);
    }

    function DeleteItem(Request $request)
    {
        $item_id = $request->del_id;

        DB::beginTransaction();
        try {
            $item = Item::find($item_id);
            $item->delete();
            $item->save();
            DB::commit();
        } catch (\PDOException $e){
            DB::rollBack();
            throw e;
        }

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

    function CheckItemNameIsNotExist($name)
    {
        $item_count = Item::where('name', $name)->count();
        if($item_count > 0){
            return "false";
        }
        return "true";
    }
}

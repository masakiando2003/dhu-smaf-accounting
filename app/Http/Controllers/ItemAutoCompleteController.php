<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Model\Item;
 
/**
 * 注文入力画面のアイテム検索（オートコンプリート）用の API を扱うコントローラー
 */
class ItemAutoCompleteController extends Controller
{
    /**
     * 名称の部分一致でアイテムを検索する
     *
     * @param  \Illuminate\Http\Request  $request  term（検索文字列）
     * @return \Illuminate\Http\JsonResponse  一致したアイテムの一覧
     */
    public function searchItem(Request $request)
    {
          $search = $request->get('term');
      
          $result = Item::where('name', 'LIKE', '%'. $search. '%')->get();
 
          return response()->json($result);
            
    } 

    /**
     * 名称が完全一致するアイテムを取得する（単価の取得に使う）
     *
     * @param  \Illuminate\Http\Request  $request  itemName（アイテム名称）
     * @return \Illuminate\Http\JsonResponse  一致したアイテムの一覧
     */
    public function getItemPrice(Request $request)
    {
          $item_name = $request->get('itemName');
      
          $result = Item::where('name', $item_name)->get();
 
          return response()->json($result);
            
    }
}
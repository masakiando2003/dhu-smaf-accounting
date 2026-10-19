<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Services\ItemService;
 
/**
 * 注文入力画面のアイテム検索（オートコンプリート）用の API を扱うコントローラー
 */
class ItemAutoCompleteController extends Controller
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
     * 名称の部分一致でアイテムを検索する
     *
     * @param  \Illuminate\Http\Request  $request  term（検索文字列）
     * @return \Illuminate\Http\JsonResponse  一致したアイテムの一覧
     */
    public function searchItem(Request $request)
    {
          $search = $request->get('term');
      
          $result = $this->itemService->searchByName($search);
 
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
      
          $result = $this->itemService->findByExactName($item_name);
 
          return response()->json($result);
            
    }
}
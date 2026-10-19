<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Model\Item;
 
class ItemAutoCompleteController extends Controller
{
    public function searchItem(Request $request)
    {
          $search = $request->get('term');
      
          $result = Item::where('name', 'LIKE', '%'. $search. '%')->get();
 
          return response()->json($result);
            
    } 

    public function getItemPrice(Request $request)
    {
          $item_name = $request->get('itemName');
      
          $result = Item::where('name', $item_name)->get();
 
          return response()->json($result);
            
    }
}
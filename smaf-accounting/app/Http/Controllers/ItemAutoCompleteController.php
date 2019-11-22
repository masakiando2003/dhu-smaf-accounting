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
          $item_id = $request->get('id');
      
          $result = Item::where('id', $item_id)->get();
 
          return response()->json($result);
            
    }
}
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
}
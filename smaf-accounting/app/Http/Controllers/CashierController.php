<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Model\Cashier;

class CashierController extends Controller
{
    function index()
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
        $query = Cashier::orderBy('id', 'asc');
        $total_count = $query->count();
        $cashier = $query->paginate($paginate);
        if(count($cashier)<=0){
            $cashier = $query->paginate($paginate, ['*'], 'page', 1);
            $page = 1;
        }
        $start_record = ($page - 1) * $paginate + 1;
        $end_record = ($page * $paginate >= count($cashier) ) ? count($cashier) : $page * $paginate;

        $pagination_params = [
            "display_items" => $display_items,
        ];

    return view('admin/cashier/index', compact('cashier', 'start_record', 'end_record', 'total_count',
                                               'display_items', 'pagination_params'));
    }
    
    function CreateCashier()
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 新規出納記録作成";
        $page['content_header'] = "新規出納記録作成";
        $page['action'] = "/admin/cashier/register";
        $page['submit'] = '作成';
        $transactionTimeType = 'auto';
        return view('admin/cashier/detail', compact('page', 'transactionTimeType'));
    }

    function ShowCashierDetail($id)
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 出納記録編集";
        $page['content_header'] = "出納記録編集";
        $page['action'] = "/admin/cashier/edit/".$id;
        $page['submit'] = '更新';

        $cashierDetail = Cashier::find($id);
        $transaction_time_arr = explode(" ", $cashierDetail->transaction_time);
        $cashierDetail->transactionDate = date('Y/m/d', strtotime($transaction_time_arr[0]));
        $cashierDetail->transactionTime = $transaction_time_arr[1];
        return view('admin/cashier/detail', compact('page', 'cashierDetail'));
    }

    function GetCashierValidationErrorMessage() {
        return [
            "income_amount.regex"                   => "収入金額に数字以外が試用されています",
            "payment_amount.regex"                  => "支払金額に数字以外が試用されています",
        ];
    }
  
    function GetCashierValidator(Request $request) {
        return Validator::make($request->all(), [
                    'income_amount'         => 'required|regex:/^[0-9]+$/',
                    'payment_amount'        => 'required|regex:/^[0-9]+$/',
                ], $this->GetCashierValidationErrorMessage());
    }
      
    function RegisterCashier(Request $request)
    {
        $validator = $this->GetCashierValidator($request);
        if ($validator->fails()) {
          return redirect()
                 ->back()
                 ->withInput()
                 ->withErrors($validator);
        }

        $new_cashier = new Cashier();
        if($request->transactionTimeType == 'manual')
        {
            $formatted_date = date('Y-m-d', strtotime($request->transactionDate));
            $new_cashier->transaction_time = $formatted_date.' '.$request->transactionTime;
        }
        else
        {
            $new_cashier->transaction_time = Carbon::now();
        }
        $new_cashier->cashier_type = $request->type;
        $new_cashier->description = $request->description;
        $new_cashier->income_amount = $request->income_amount;
        $new_cashier->payment_amount = $request->payment_amount;
        $new_cashier->deduction_amount = $request->deduction_amount;
        $new_cashier->remarks = $request->remarks;
        $new_cashier->save();

        $msg="新規出納記録(ID: ".$new_cashier->id.")を作成しました";
        return redirect('/admin/cashier')->with('success_msg', $msg);
    }

    function EditCashier(Request $request)
    {
        $cashier_id = $request->cashier_id;

        if($cashier_id == NULL){
            return redirect('/admin/cashier');
        }

        $validator = $this->GetCashierValidator($request);
        if ($validator->fails()) {
          return redirect()
                 ->back()
                 ->withInput()
                 ->withErrors($validator);
        }

        // 更新.
        Cashier::where('id', $cashier_id)
             ->update([
                'description' => $request->description,
                'income_amount' => $request->income_amount,
                'payment_amount' => $request->payment_amount,
                'deduction_amount' => $request->deduction_amount,
                'remarks' => $request->remarks,
             ]);

        if($request->transactionTimeType == 'manual'){
            $formatted_date = date('Y-m-d', strtotime($request->transactionDate));
            $request->transaction_time = $formatted_date.' '.$request->transactionTime;
            Cashier::where('id', $cashier_id)
             ->update([
                'transaction_time' => $request->transaction_time
             ]);
        }
        
        $msg = "出納記録(ID: ".$cashier_id.")を更新しました";
        return redirect('/admin/cashier')->with('success_msg', $msg);
    }

    function DeleteCashier(Request $request)
    {
        $cashier_id = $request->del_id;

        DB::beginTransaction();
        try {
            $cashier = Cashier::find($cashier_id);
            $cashier->delete();
            $cashier->save();
            DB::commit();
        } catch (\PDOException $e){
            DB::rollBack();
            throw e;
        }

        $msg = "出納記録(ID: ".$cashier_id.")を削除しました";
        if(isset($request->display_items) && isset($request->page)){
            $display_items = $request->display_items;
            $page = $request->page;
            $pagination_params = [
                "display_items" => $display_items,
                "page" => $page,
            ];
            return redirect('/admin/cashier?display_items'.$display_items.'&page='.$page)
                   ->with([
                            'success_msg' => $msg,
                            'pagination_params' => $pagination_params
                    ]);
        }
        else{
            return redirect('/admin/cashier')->with('success_msg', $msg);
        }
    }
}

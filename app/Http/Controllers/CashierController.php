<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\CashierRequest;
use App\Services\CashierService;

/**
 * 現金出納帳の一覧・作成・編集・削除を扱うコントローラー
 */
class CashierController extends Controller
{
    /**
     * @var \App\Services\CashierService
     */
    private $cashierService;

    /**
     * @param  \App\Services\CashierService  $cashierService
     */
    public function __construct(CashierService $cashierService)
    {
        $this->cashierService = $cashierService;
    }

    /**
     * 現金出納帳の一覧を表示する
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
        $display_items = $paginate;
        $result = $this->cashierService->paginate($paginate);
        $cashier = $result['records'];
        $total_count = $result['total_count'];
        $start_record = $cashier->firstItem() ?? 0;
        $end_record = $cashier->lastItem() ?? 0;

        $pagination_params = [
            "display_items" => $display_items,
        ];

    return view('admin/cashier/index', compact('cashier', 'start_record', 'end_record', 'total_count',
                                               'display_items', 'pagination_params'));
    }
    
    /**
     * 出納記録の新規作成画面を表示する
     *
     * @return \Illuminate\View\View
     */
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

    /**
     * 出納記録の編集画面を表示する
     *
     * @param  int  $id  出納記録ID
     * @return \Illuminate\View\View
     */
    function ShowCashierDetail($id)
    {
        $page = array();
        $page['title'] = "SmaF会計システム - 出納記録編集";
        $page['content_header'] = "出納記録編集";
        $page['action'] = "/admin/cashier/edit/".$id;
        $page['submit'] = '更新';

        $cashierDetail = $this->cashierService->find($id);
        $transaction_time_arr = explode(" ", $cashierDetail->transaction_time);
        $cashierDetail->transactionDate = date('Y/m/d', strtotime($transaction_time_arr[0]));
        $cashierDetail->transactionTime = $transaction_time_arr[1];
        return view('admin/cashier/detail', compact('page', 'cashierDetail'));
    }
      
    /**
     * 出納記録を新規登録する
     *
     * transactionTimeType が "manual" のときは入力された日時、それ以外は現在日時で登録する.
     *
     * @param  \App\Http\Requests\CashierRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function RegisterCashier(CashierRequest $request)
    {
        $new_cashier = $this->cashierService->create(
            $this->cashierData($request) + ['cashier_type' => $request->type],
            $request->manualTransactionTime()
        );

        $msg="新規出納記録(ID: ".$new_cashier->id.")を作成しました";
        return redirect('/admin/cashier')->with('success_msg', $msg);
    }

    /**
     * 出納記録を更新する
     *
     * @param  \App\Http\Requests\CashierRequest  $request  cashier_id（更新する出納記録ID）ほか
     * @return \Illuminate\Http\RedirectResponse
     */
    function EditCashier(CashierRequest $request)
    {
        $cashier_id = $request->cashier_id;

        if($cashier_id == NULL){
            return redirect('/admin/cashier');
        }

        // 更新.
        $this->cashierService->update($cashier_id, $this->cashierData($request), $request->manualTransactionTime());

        $msg = "出納記録(ID: ".$cashier_id.")を更新しました";
        return redirect('/admin/cashier')->with('success_msg', $msg);
    }

    /**
     * 出納記録を削除（論理削除）する
     *
     * @param  \Illuminate\Http\Request  $request  del_id（削除する出納記録ID）
     * @return \Illuminate\Http\RedirectResponse
     */
    function DeleteCashier(Request $request)
    {
        $cashier_id = $request->del_id;

        $this->cashierService->delete($cashier_id);

        $msg = "出納記録(ID: ".$cashier_id.")を削除しました";
        if(isset($request->display_items) && isset($request->page)){
            $display_items = $request->display_items;
            $page = $request->page;
            $pagination_params = [
                "display_items" => $display_items,
                "page" => $page,
            ];
            return redirect('/admin/cashier?display_items='.$display_items.'&page='.$page)
                   ->with([
                            'success_msg' => $msg,
                            'pagination_params' => $pagination_params
                    ]);
        }
        else{
            return redirect('/admin/cashier')->with('success_msg', $msg);
        }
    }

    /**
     * フォームの入力から、登録・更新する出納記録の項目を取り出す
     *
     * @param  \App\Http\Requests\CashierRequest  $request
     * @return array
     */
    private function cashierData(CashierRequest $request)
    {
        return [
            'description'      => $request->description,
            'income_amount'    => $request->income_amount,
            'payment_amount'   => $request->payment_amount,
            'deduction_amount' => $request->deduction_amount,
            'remarks'          => $request->remarks,
        ];
    }
}

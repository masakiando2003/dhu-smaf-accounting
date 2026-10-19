<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Model\Cashier;
use App\Services\Concerns\PaginatesRecords;

use Carbon\Carbon;

/**
 * 現金出納帳のデータ操作
 */
class CashierService
{
    use PaginatesRecords;

    /**
     * 出納記録の一覧をページに分けて取得する
     *
     * @param  int  $per_page  1ページの表示件数
     * @return array{records: \Illuminate\Contracts\Pagination\LengthAwarePaginator, total_count: int, is_first_page_fallback: bool}
     */
    public function paginate(int $per_page)
    {
        return $this->paginateWithFallback(Cashier::orderBy('id', 'asc'), $per_page);
    }

    /**
     * 出納記録を取得する
     *
     * @param  int  $id  出納記録ID
     * @return \App\Model\Cashier
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  出納記録が存在しない（削除済みを含む）場合
     */
    public function find($id)
    {
        return Cashier::findOrFail($id);
    }

    /**
     * 出納記録を新規登録する
     *
     * @param  array        $data              cashier_type, description, income_amount, payment_amount, deduction_amount, remarks
     * @param  string|null  $transaction_time  取引日時（Y-m-d H:i:s）. null のときは現在日時
     * @return \App\Model\Cashier
     */
    public function create(array $data, $transaction_time = null)
    {
        $new_cashier = new Cashier();
        $new_cashier->transaction_time = $transaction_time ?? Carbon::now();
        $new_cashier->cashier_type = $data['cashier_type'];
        $new_cashier->description = $data['description'];
        $new_cashier->income_amount = $data['income_amount'];
        $new_cashier->payment_amount = $data['payment_amount'];
        $new_cashier->deduction_amount = $data['deduction_amount'];
        $new_cashier->remarks = $data['remarks'];
        $new_cashier->save();

        return $new_cashier;
    }

    /**
     * 出納記録を更新する
     *
     * 種別（cashier_type）は更新しない.
     *
     * @param  int          $id                出納記録ID
     * @param  array        $data              description, income_amount, payment_amount, deduction_amount, remarks
     * @param  string|null  $transaction_time  取引日時（Y-m-d H:i:s）. null のときは変更しない
     * @return void
     */
    public function update($id, array $data, $transaction_time = null)
    {
        Cashier::where('id', $id)
             ->update([
                'description' => $data['description'],
                'income_amount' => $data['income_amount'],
                'payment_amount' => $data['payment_amount'],
                'deduction_amount' => $data['deduction_amount'],
                'remarks' => $data['remarks'],
             ]);

        if($transaction_time !== null){
            Cashier::where('id', $id)
             ->update([
                'transaction_time' => $transaction_time
             ]);
        }
    }

    /**
     * 出納記録を削除（論理削除）する
     *
     * @param  int  $id  出納記録ID
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  出納記録が存在しない（削除済みを含む）場合
     */
    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $cashier = Cashier::findOrFail($id);
            $cashier->delete();
            $cashier->save();
            DB::commit();
        } catch (\PDOException $e){
            DB::rollBack();
            throw e;
        }
    }
}

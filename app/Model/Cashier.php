<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 現金出納帳の記録
 *
 * @property int $id
 * @property string $transaction_time 取引日時
 * @property string $cashier_type 種別（"income": 収入、"payment": 支払）
 * @property int|null $order_id 注文ID（注文から作られた記録のみ）
 * @property string|null $description 摘要
 * @property int $income_amount 収入金額
 * @property int $payment_amount 支払金額
 * @property int $deduction_amount 差引金額
 * @property string|null $remarks 備考
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Cashier extends Model
{
    use SoftDeletes;

    protected $table        = 'cashier';
    public    $tableType    = 'master';

    protected $primaryKey   = 'id';
    protected $keyType      = 'bigint';
    public    $incrementing = true;

    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['transaction_time', 'cashier_type', 'description', 
                           'income_amount', 'payment_amount', 'deduction_amount', 'remarks'];

    // created_at, updated_at は DB の機能で自動更新する.
    public $timestamps = false;

    /**
     * 経費の合計金額を取得する
     *
     * ※ 現状はクラス名の誤り（Casher）があり、呼び出すとエラーになる. どこからも使われていない.
     *
     * @param  string|null  $description  摘要の部分一致で絞り込む文字列
     * @return int
     */
    public function GetExpenses(string $description=NULL)
    {
        $expense = 0;
        
        $expenses = Casher::where('cashier_type', 2);
        if($expenses->count() > 0)
        {
            if($description != "" && $description != NULL)
            {
                $expenses->where('description', 'LIKE', '%'. $description. '%');
            }
            $expense_info = $expenses->get();
            $expense += $expense_info->payment_amount;
        }

        return $expense;
    }
}

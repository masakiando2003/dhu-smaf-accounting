<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function GetExpenses(string $description="")
    {
        $expense = 0;
        
        $expenses = Casher::where('cashier_type', 2);
        if($expenses->count() > 0)
        {
            if($description != "")
            {
                $expenses->where('description', 'LIKE', '%'. $description. '%');
            }
            $expense_info = $expenses->get();
            $expense += $expense_info->payment_amount;
        }

        return $expense;
    }
}

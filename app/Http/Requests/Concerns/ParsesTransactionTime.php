<?php

namespace App\Http\Requests\Concerns;

/**
 * 取引日時（注文日時）の入力の変換
 */
trait ParsesTransactionTime
{
    /**
     * 手入力された取引日時を取得する
     *
     * transactionTimeType が "manual" のときだけ、transactionDate（Y/m/d）と
     * transactionTime（H:i:s）を "Y-m-d H:i:s" の形にして返す.
     *
     * @return string|null  手入力でない場合は null
     */
    public function manualTransactionTime()
    {
        if($this->transactionTimeType != 'manual'){
            return null;
        }

        $formatted_date = date('Y-m-d', strtotime($this->transactionDate));
        return $formatted_date.' '.$this->transactionTime;
    }
}

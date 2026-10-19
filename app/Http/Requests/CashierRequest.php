<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ParsesTransactionTime;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 出納記録の登録・更新フォームの入力チェック
 */
class CashierRequest extends FormRequest
{
    use ParsesTransactionTime;

    /**
     * このリクエストを実行してよいかを判定する
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 入力チェックのルールを返す
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'income_amount'         => 'required|regex:/^[0-9]+$/',
            'payment_amount'        => 'required|regex:/^[0-9]+$/',
        ];
    }

    /**
     * 入力チェックのエラーメッセージを返す
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            "income_amount.regex"                   => "収入金額に数字以外が試用されています",
            "payment_amount.regex"                  => "支払金額に数字以外が試用されています",
        ];
    }
}

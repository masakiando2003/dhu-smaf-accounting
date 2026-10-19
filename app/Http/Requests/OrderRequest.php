<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 注文の登録・更新フォームの入力チェック
 */
class OrderRequest extends FormRequest
{
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
            'num_of_people'         => 'required|regex:/^[0-9]+$/',
            'paid'                  => 'required|regex:/^[0-9]+$/',
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
            "num_of_people.required"       => "人数が指定されていません",
            "num_of_people.regex"          => "人数に数字以外が試用されています",
            "paid.required"                => "支払金額が指定されていません",
            "paid.regex"                   => "支払金額に数字以外が試用されています",
        ];
    }
}

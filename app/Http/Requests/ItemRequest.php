<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * アイテムの登録・更新フォームの入力チェック
 */
class ItemRequest extends FormRequest
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
            'name'          => 'required|max:255',
            'price'         => 'required|regex:/^[0-9]+$/',
            'weight'        => 'required|regex:/^[0-9]+$/',
            'init_stock'    => 'required|regex:/^[0-9]+$/',
            'stock'         => 'required|regex:/^[0-9]+$/',
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
            "name.required"                 => "アイテム名称が指定されていません",
            "name.max"                      => "アイテム名称が255字を超えています",
            "price.required"                => "価格が指定されていません",
            "price.regex"                   => "価格に数字以外が試用されています",
            "weight.required"               => "重量が指定されていません",
            "weight.regex"                  => "重量に数字以外が試用されています",
            "init_stock.required"           => "初期在庫数が指定されていません",
            "init_stock.regex"              => "初期在庫数に数字以外が試用されています",
            "stock.required"                => "在庫数が指定されていません",
            "stock.regex"                   => "在庫数に数字以外が試用されています",
        ];
    }
}

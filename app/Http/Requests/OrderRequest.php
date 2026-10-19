<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ParsesTransactionTime;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 注文の登録・更新フォームの入力チェック
 */
class OrderRequest extends FormRequest
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

    /**
     * 入力された注文明細を取得する
     *
     * 注文明細は item_id_N / price_N / quantity_N / delete_item_N（N は 1〜order_item_count）で受け取る.
     * アイテムが選ばれていない行（item_id_N が空）は含めない.
     *
     * @return array<int, array{item_id: string, price: string|null, quantity: string|null, delete: bool}>
     */
    public function orderItems()
    {
        $order_items = [];
        $order_item_count = $this->order_item_count;
        for($i = 1; $i <= $order_item_count; $i++){
            $item_id = $this->input('item_id_'.$i);
            if($item_id === null || $item_id === ''){
                continue;
            }
            $order_items[] = [
                'item_id'  => $item_id,
                'price'    => $this->input('price_'.$i),
                'quantity' => $this->input('quantity_'.$i),
                'delete'   => $this->input('delete_item_'.$i) !== null && $this->input('delete_item_'.$i) !== '',
            ];
        }

        return $order_items;
    }
}

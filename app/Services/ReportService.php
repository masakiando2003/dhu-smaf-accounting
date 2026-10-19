<?php

namespace App\Services;

use App\Model\CompanyInfo;
use App\Model\CompanyMembers;
use App\Model\Order;
use App\Model\Item;
use App\Model\Cashier;

/**
 * レポートに表示するデータの取得
 */
class ReportService
{
    /**
     * レポートの表示に使うデータを取得する
     *
     * orders は、レポート画面で日別・時間帯別の集計メソッドを呼ぶための Order のインスタンス.
     *
     * @return array{company_info: \App\Model\CompanyInfo|null, company_members: \Illuminate\Database\Eloquent\Collection, orders: \App\Model\Order, items: \Illuminate\Database\Eloquent\Collection, expenses: \Illuminate\Database\Eloquent\Collection}
     */
    public function getReportData()
    {
        return [
            'company_info'    => CompanyInfo::get()->first(),
            'company_members' => CompanyMembers::get(),
            'orders'          => new Order(),
            'items'           => Item::get(),
            'expenses'        => Cashier::where('cashier_type', 'payment')->get(),
        ];
    }
}

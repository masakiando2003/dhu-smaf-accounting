<?php

namespace App\Http\Controllers;

use App\Services\ReportService;

/**
 * レポート（販売実績・損益計算書・貸借対照表など）を扱うコントローラー
 */
class ReportController extends Controller
{
    /**
     * @var \App\Services\ReportService
     */
    private $reportService;

    /**
     * @param  \App\Services\ReportService  $reportService
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * レポートを表示する
     *
     * @return \Illuminate\View\View
     */
    function index()
    {
        $sell_dates = ['2019-11-30','2019-12-01'];
        $time_periods = [
            '9:00 - 9:59', 
            '10:00 - 10:59', 
            '11:00 - 11:59', 
            '12:00 - 12:59', 
            '13:00 - 13:59', 
            '14:00 - 14:59', 
            '15:00 - 15:59', 
            '16:00 - 16:59'
        ];
        $profit = 0;
        $income = 0;
        $expenditure = 0;
        return view('admin/report/index',
                    $this->reportService->getReportData() +
                    compact('sell_dates', 'time_periods', 'profit', 'income', 'expenditure'));
    }
}

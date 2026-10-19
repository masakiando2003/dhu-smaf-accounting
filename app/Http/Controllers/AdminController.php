<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

/**
 * 管理画面のトップ（ダッシュボード）を扱うコントローラー
 */
class AdminController extends Controller
{
    /**
     * @var \App\Services\DashboardService
     */
    private $dashboardService;

    /**
     * @param  \App\Services\DashboardService  $dashboardService
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * ダッシュボードを表示する
     *
     * 売上・注文数・現金残高・利益の集計、在庫が少ない商品、
     * 売れ筋ランキング、最近の注文を表示する.
     *
     * @return \Illuminate\View\View
     */
    function index()
    {
        return view('admin/dashboard', $this->dashboardService->getSummary());
    }

    /**
     * トップページからダッシュボードへリダイレクトする
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    function RedirectToDashboard()
    {
        return redirect('admin/home');
    }
}

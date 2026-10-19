<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;

use App\Model\Item;
use App\Model\Order;
use App\Model\OrderItem;
use App\Model\Cashier;
use App\Model\CompanyInfo;

/**
 * ダッシュボードに表示する集計
 */
class DashboardService
{
    // 在庫が初期在庫のこの割合以下になったら「在庫が少ない」とする
    const LOW_STOCK_RATIO = 0.2;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $db;

    /**
     * @param  \Illuminate\Database\ConnectionInterface  $db  DB 接続（集計用の SQL 式の作成に使う）
     */
    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * ダッシュボードの表示内容を取得する
     *
     * 売上・注文数・現金残高・利益の集計、在庫が少ない商品、
     * 売れ筋ランキング（上位5件）、最近の注文（5件）を返す.
     *
     * @return array{company_info: \App\Model\CompanyInfo|null, sales: int, order_count: int, visitor_count: int, expenditure: int, profit: int, cash_balance: int, low_stock_items: \Illuminate\Database\Eloquent\Collection, best_selling_items: \Illuminate\Database\Eloquent\Collection, recent_orders: \Illuminate\Database\Eloquent\Collection}
     */
    public function getSummary()
    {
        $company_info = CompanyInfo::get()->first();

        // 削除されていない注文の明細だけを集計する
        $order_items = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
                                ->whereNull('orders.deleted_at');

        $sales = (int) (clone $order_items)->sum($this->db->raw('order_items.price * order_items.quantity'));
        $order_count = Order::count();
        $visitor_count = (int) Order::sum('num_of_people');
        $expenditure = (int) Cashier::where('cashier_type', 'payment')->sum('payment_amount');
        $profit = $sales - $expenditure;
        $initial_capital = $company_info->initial_captial ?? 0;
        $cash_balance = $initial_capital + $sales - $expenditure;

        $low_stock_items = Item::where('init_stock', '>', 0)
                               ->whereRaw('stock <= init_stock * ?', [self::LOW_STOCK_RATIO])
                               ->orderBy('stock')
                               ->get();

        $best_selling_items = (clone $order_items)
                                ->join('items', 'items.id', '=', 'order_items.item_id')
                                ->select('items.id', 'items.name',
                                         $this->db->raw('SUM(order_items.quantity) as total_quantity'),
                                         $this->db->raw('SUM(order_items.price * order_items.quantity) as total_sales'))
                                ->groupBy('items.id', 'items.name')
                                ->orderByDesc('total_quantity')
                                ->limit(5)
                                ->get();

        $recent_orders = Order::orderByDesc('order_at')->limit(5)->get();

        return compact('company_info', 'sales', 'order_count', 'visitor_count',
                       'expenditure', 'profit', 'cash_balance',
                       'low_stock_items', 'best_selling_items', 'recent_orders');
    }
}

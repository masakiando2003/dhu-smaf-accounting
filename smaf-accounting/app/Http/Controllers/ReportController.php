<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Model\CompanyInfo;
use App\Model\CompanyMembers;
use App\Model\Order;
use App\Model\Item;
use App\Model\Cashier;

class ReportController extends Controller
{
    function index()
    {
        $company_info = CompanyInfo::get()->first();
        $company_members = CompanyMembers::get();

        $orders = new Order();
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
        $items = Item::get();
        $profit = 0;
        $income = 0;
        $expenditure = 0;
        $expenses = Cashier::get();
        return view('admin/report/index', 
                    compact('company_info', 'company_members', 'orders', 
                            'items', 'sell_dates', 'time_periods', 
                            'profit', 'income', 'expenditure', 'expenses'));
    }
}

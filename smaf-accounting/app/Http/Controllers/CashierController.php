<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CashierController extends Controller
{
    function index()
    {
        return view('admin/cashier/index');
    }
    
    function Create()
    {
        return view('admin/cashier/detail');
    }

    function Show()
    {
        return view('admin/cashier/detail');
    }
}

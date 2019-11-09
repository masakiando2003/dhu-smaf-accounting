<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    function index()
    {
        return view('admin/orders/index');
    }
    
    function Create()
    {
        return view('admin/orders/detail');
    }

    function Show()
    {
        return view('admin/orders/detail');
    }
}

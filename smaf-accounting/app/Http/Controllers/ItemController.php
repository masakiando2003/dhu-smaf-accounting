<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ItemController extends Controller
{
    function index()
    {
        return view('admin/items/index');
    }
    
    function Create()
    {
        return view('admin/items/detail');
    }

    function Show()
    {
        return view('admin/items/detail');
    }
}

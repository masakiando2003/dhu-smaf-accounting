<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    function index()
    {
        return view('admin/report/index');
    }
}

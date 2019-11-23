<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Model\CompanyInfo;
use App\Model\CompanyMembers;

class ReportController extends Controller
{
    function index()
    {
        $company_info = CompanyInfo::get()->first();
        $company_members = CompanyMembers::get();
        return view('admin/report/index', compact('company_info', 'company_members'));
    }
}

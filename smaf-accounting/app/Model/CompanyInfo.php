<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CompanyInfo extends Model
{
    protected $table        = 'company_info';
    public    $tableType    = 'master';

    protected $dates = ['setup_date', 'end_date'];
}

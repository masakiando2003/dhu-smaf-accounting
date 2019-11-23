<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CompanyMembers extends Model
{
    protected $table        = 'company_members';
    public    $tableType    = 'master';

    protected $primaryKey   = 'id';
    protected $keyType      = 'bigint';
    public    $incrementing = true;

    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['name', 'email', 'role', 'position', 'captial', 
                           'num_of_share', 'share_percentage', 'status'];

    // created_at, updated_at は DB の機能で自動更新する.
    public $timestamps = false;
    
}

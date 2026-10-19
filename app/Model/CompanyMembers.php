<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * 会社のメンバー
 *
 * @property int $id
 * @property string $name 氏名
 * @property string|null $email メールアドレス
 * @property int $role 役割
 * @property string|null $position 役職
 * @property int $capital 出資額
 * @property int $num_of_share 株数
 * @property int $share_percentage 持株比率
 * @property int $status 状態
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompanyMembers extends Model
{
    protected $table        = 'company_members';
    public    $tableType    = 'master';

    protected $primaryKey   = 'id';
    protected $keyType      = 'bigint';
    public    $incrementing = true;

    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['name', 'email', 'role', 'position', 'capital', 
                           'num_of_share', 'share_percentage', 'status'];

    // created_at, updated_at は DB の機能で自動更新する.
    public $timestamps = false;
    
}

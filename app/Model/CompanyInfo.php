<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * 会社情報
 *
 * @property string $company_name 会社名
 * @property string|null $description 事業内容
 * @property \Illuminate\Support\Carbon $setup_date 設立日（営業開始日）
 * @property \Illuminate\Support\Carbon|null $end_date 営業終了日
 * @property int $initial_captial 初期資本金
 * @property int $cash 現金
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompanyInfo extends Model
{
    protected $table        = 'company_info';
    public    $tableType    = 'master';

    protected $dates = ['setup_date', 'end_date'];
}

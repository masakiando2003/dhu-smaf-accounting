<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * 一覧画面のページ送り
 */
trait PaginatesRecords
{
    /**
     * クエリの結果をページに分けて取得する
     *
     * 指定したページにレコードがない場合は、1ページ目を取得する.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $per_page  1ページの表示件数
     * @return array{records: \Illuminate\Contracts\Pagination\LengthAwarePaginator, total_count: int, is_first_page_fallback: bool}
     */
    protected function paginateWithFallback(Builder $query, int $per_page)
    {
        $total_count = $query->count();
        $records = $query->paginate($per_page);
        $is_first_page_fallback = false;
        if(count($records)<=0){
            $records = $query->paginate($per_page, ['*'], 'page', 1);
            $is_first_page_fallback = true;
        }

        return [
            'records'                => $records,
            'total_count'            => $total_count,
            'is_first_page_fallback' => $is_first_page_fallback,
        ];
    }
}

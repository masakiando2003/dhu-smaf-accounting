{{-- resources/views/errors/404.blade.php --}}

@extends('adminlte::page')

@section('title', 'SmaF会計システム - ページが見つかりません')

@section('content_header')
    <h1>ページが見つかりません</h1>
@stop

@section('content')
<div class="card card-warning card-outline">
  <div class="card-body">
    <h3 class="text-warning mb-3"><i class="fas fa-exclamation-triangle"></i> 404</h3>
    <p>お探しのページ、またはデータが見つかりませんでした。</p>
    <p class="text-muted">すでに削除されたか、URL が間違っている可能性があります。</p>
    <a href="/admin/home" class="btn btn-primary mr-2 mb-2">
      <i class="fas fa-home"></i> ダッシュボードへ戻る
    </a>
    <a href="javascript:history.back()" class="btn btn-secondary mb-2">
      <i class="fas fa-arrow-left"></i> 前のページへ戻る
    </a>
  </div>
</div>
@stop

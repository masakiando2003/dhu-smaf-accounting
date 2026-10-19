{{-- resources/views/admin/dashboard.blade.php --}}

@extends('adminlte::page')

@section('title', 'SmaF会計システム - ダッシュボード')

@section('content_header')
    <h1>ダッシュボード</h1>
    @if($company_info)
        <p class="text-muted mb-0">
            {{ $company_info->company_name }}
            （営業期間: {{ $company_info->setup_date->format('Y/m/d') }} 〜 {{ $company_info->end_date->format('Y/m/d') }}）
        </p>
    @endif
@stop

@section('content')

<div class="mb-3">
  <a href="/admin/orders/create" class="btn btn-primary mr-2 mb-2">
    <i class="fas fa-plus"></i> 新規注文
  </a>
  <a href="/admin/cashier/create" class="btn btn-success mr-2 mb-2">
    <i class="fas fa-cash-register"></i> 現金出納帳に記入
  </a>
  <a href="/admin/report" class="btn btn-secondary mb-2">
    <i class="fas fa-chart-bar"></i> レポートを見る
  </a>
</div>

<div class="row">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-info">
      <div class="inner">
        <h3>{{ number_format($sales) }}<small>円</small></h3>
        <p>売上合計</p>
      </div>
      <div class="icon"><i class="fas fa-yen-sign"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-success">
      <div class="inner">
        <h3>{{ number_format($order_count) }}<small>件</small></h3>
        <p>注文数（来客数 {{ number_format($visitor_count) }}人）</p>
      </div>
      <div class="icon"><i class="fas fa-receipt"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box bg-warning">
      <div class="inner">
        <h3>{{ number_format($cash_balance) }}<small>円</small></h3>
        <p>現金残高</p>
      </div>
      <div class="icon"><i class="fas fa-wallet"></i></div>
    </div>
  </div>
  <div class="col-lg-3 col-6">
    <div class="small-box {{ $profit < 0 ? 'bg-danger' : 'bg-primary' }}">
      <div class="inner">
        <h3>{{ number_format($profit) }}<small>円</small></h3>
        <p>利益（経費 {{ number_format($expenditure) }}円）</p>
      </div>
      <div class="icon"><i class="fas fa-chart-line"></i></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-6">
    <div class="card card-danger card-outline">
      <div class="card-header">
        <h3 class="card-title">在庫が少ない商品</h3>
      </div>
      <div class="card-body p-0">
        @if($low_stock_items->isEmpty())
          <p class="p-3 mb-0 text-muted">在庫が少ない商品はありません</p>
        @else
          <table class="table table-sm mb-0">
            <thead>
              <tr><th>商品名</th><th class="text-right">残り在庫</th><th class="text-right">初期在庫</th></tr>
            </thead>
            <tbody>
              @foreach($low_stock_items as $item)
                <tr>
                  <td><a href="/admin/items/detail/{{ $item->id }}">{{ $item->name }}</a></td>
                  <td class="text-right {{ $item->stock <= 0 ? 'text-danger font-weight-bold' : '' }}">{{ $item->stock }}</td>
                  <td class="text-right">{{ $item->init_stock }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card card-info card-outline">
      <div class="card-header">
        <h3 class="card-title">売れ筋ランキング</h3>
      </div>
      <div class="card-body p-0">
        @if($best_selling_items->isEmpty())
          <p class="p-3 mb-0 text-muted">まだ販売実績がありません</p>
        @else
          <table class="table table-sm mb-0">
            <thead>
              <tr><th>順位</th><th>商品名</th><th class="text-right">販売数</th><th class="text-right">売上</th></tr>
            </thead>
            <tbody>
              @foreach($best_selling_items as $rank => $item)
                <tr>
                  <td>{{ $rank + 1 }}</td>
                  <td><a href="/admin/items/detail/{{ $item->id }}">{{ $item->name }}</a></td>
                  <td class="text-right">{{ number_format($item->total_quantity) }}</td>
                  <td class="text-right">{{ number_format($item->total_sales) }}円</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="card card-success card-outline">
  <div class="card-header">
    <h3 class="card-title">最近の注文</h3>
    <div class="card-tools">
      <a href="/admin/orders" class="btn btn-tool">すべて見る</a>
    </div>
  </div>
  <div class="card-body p-0">
    @if($recent_orders->isEmpty())
      <p class="p-3 mb-0 text-muted">まだ注文がありません</p>
    @else
      <table class="table table-sm mb-0">
        <thead>
          <tr><th>注文ID</th><th>日時</th><th class="text-right">人数</th><th class="text-right">合計金額</th></tr>
        </thead>
        <tbody>
          @foreach($recent_orders as $order)
            <tr>
              <td><a href="/admin/orders/detail/{{ $order->id }}">{{ $order->id }}</a></td>
              <td>{{ $order->created_at->format('Y/m/d H:i:s') }}</td>
              <td class="text-right">{{ $order->num_of_people }}</td>
              <td class="text-right">{{ number_format($order->GetOrderTotal($order->id)) }}円</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>

@stop

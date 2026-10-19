{{-- resources/views/admin/cashier/index.blade.php --}}

@extends('adminlte::page')

@section('title', 'SmaF会計システム - 現金出納帳')

@section('content_header')
    <h1>現金出納帳</h1>
@stop

@section('content')

  @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error_msg)
                {{ $error_msg }}<br/>
            @endforeach
        </div>
    @endif
    @if(session('success_msg'))
        <div class="alert alert-success">
            {{ session('success_msg') }}
        </div>
    @endif

<div class="float-right py-3">
  <a href="/admin/cashier/create" class="btn btn-primary">
    <span class="glyphicon glyphicon-plus">新規作成</span>
  </a>
</div>

<form action="/admin/cashier/delete" method="POST">
{{ csrf_field() }}
<table class="table">
  <thead class="thead-light">
    <tr>
      <th scope="col">ID</th>
      <th scope="col">時間</th>
      <th scope="col">種類</th>
      <th scope="col">摘要</th>
      <th scope="col">収入金額</th>
      <th scope="col">支払金額</th>
      <th scope="col">差引金額</th>
      <th scope="col">アクション</th>
    </tr>
  </thead>
  <tbody>
    @foreach($cashier as $cashier_item)
      <tr>
        <th scope="row">{{ $cashier_item->id }}</th>
        <td>{{ $cashier_item->transaction_time }}</td>
        <td>{{ ($cashier_item->cashier_type == 'income') ? '収入' : '支払' }}</td>
        <td>
          @if(preg_match('/注文/', $cashier_item->description))
            <a href="/admin/orders/detail/{{$cashier_item->order_id}}" target="_blank">{{ $cashier_item->description }}</a>
          @else
            {{ $cashier_item->description }}
          @endif
        </td>
        <td>{{ $cashier_item->income_amount }}</td>
        <td>{{ $cashier_item->payment_amount }}</td>
        <td>{{ $cashier_item->deduction_amount }}</td>
        <td>
          <a href="/admin/cashier/detail/{{ $cashier_item->id }}" class="btn btn-success">編集</a>
          <input type="button" class="btn btn-danger" name="btnDel" id="btnDel" value="削除" 
                 onclick="ConfirmDelete('{{ $cashier_item->id }}')" />
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="row">
    <div class="col-sm-9 text-left">
        @if($start_record < $end_record)
            {{ $start_record }} - {{ $end_record }} 件表示 / {{ $total_count }}件
        @else
            {{ $end_record }} 件表示 / {{ $total_count }}件
        @endif
    </div>
    <div class="col-sm-3 text-right">
        {{-- paginate --}}
        @if($cashier->hasPages())
            {!! $cashier->appends($pagination_params)->links('pagination.default') !!}
        @else
            <div class="g_pager">
                <ul class="pagination text-right">
                    <li class="page-item active">
                        <a class="page-link" href="#">1</a>
                    </li>
                </ul>
            </div>
        @endif
        {{-- / paginate --}}
    </div>
  </div>
  <input type="hidden" name="del_id" id="del_id" />
  <input type="hidden" name="del_name" id="del_name" />
  <input type="hidden" name="_method" id="_method">
  <input type="hidden" name="display_items" id="display_items" value="{{ $display_items ?? '' }}" />
  <input type="hidden" name="page" id="page" value="{{ $page ?? ''}}" />
  </form>
@stop

@section('js')
    <script type="text/javascript">
        function ConfirmDelete(cashier_id){
            var flag = false;
            if(confirm('出納記録#"'+cashier_id+'"を削除しても宜しいでしょうか?') == true){
                return true;
            }
            return flag;
        }
    </script>
@stop
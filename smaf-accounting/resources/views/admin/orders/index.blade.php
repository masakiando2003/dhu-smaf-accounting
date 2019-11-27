{{-- resources/views/admin/order/index.blade.php --}}

@extends('adminlte::page')

@section('title', 'SmaF会計システム - 注文一覧')

@section('content_header')
    <h1>注文一覧</h1>
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
  <a href="/admin/orders/create" class="btn btn-primary">
    <span class="glyphicon glyphicon-plus">新規作成</span>
  </a>
</div>

<form action="/admin/orders/delete" method="POST">
{{ csrf_field() }}
<table class="table">
  <thead class="thead-light">
    <tr>
      <th scope="col">ID</th>
      <th scope="col">人数</th>
      <th scope="col">合計金額</th>
      <th scope="col">支払金額</th>
      <th scope="col">お釣り</th>
      <th scope="col">時間</th>
      <th scope="col">アクション</th>
    </tr>
  </thead>
  <tbody>
    @foreach($orders as $order)
    <tr>
      <th scope="row">{{ $order->id }}</th>
      <td>{{ $order->num_of_people }}</td>
      <td>{{ $order->GetOrderTotal($order->id) }}</td>
      <td>{{ $order->paid }}</td>
      <td>{{ $order->change }}</td>
      <td>{{ $order->created_at->format('Y/m/d H:i:s') }}</td>
      <td>
      <a href="/admin/orders/detail/{{ $order->id }}" class="btn btn-success">編集</a>
        <input type="submit" class="btn btn-danger" name="btnEdit" value="削除" onclick="return ConfirmDelete('{{ $order->id }}')" />
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
              @if($orders->hasPages())
                  {!! $orders->appends($pagination_params)->links('pagination.default') !!}
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
    <input type="hidden" name="_method" id="_method" />
    <input type="hidden" name="display_items" id="display_items" value="{{ $display_items ?? '' }}" />
    <input type="hidden" name="page" id="page" value="{{ $page ?? ''}}" />
  </form>
@stop

@section('js')
    <script type="text/javascript">
        function ConfirmDelete(order_id){
            var flag = false;
            if(confirm('注文#'+order_id+'を削除しても宜しいでしょうか?') == true){
                document.getElementById('del_id').value = order_id;
                document.getElementById('_method').value = "delete";
                return true;
            }
            return flag;
        }
    </script>
@stop
{{-- resources/views/admin/order/index.blade.php --}}

@extends('adminlte::page')

@section('title', 'SmaF会計システム - 注文一覧')

@section('content_header')
    <h1>注文一覧</h1>
@stop

@section('content')

<div class="float-right py-3">
  <a href="#" class="btn btn-primary">
    <span class="glyphicon glyphicon-plus">新規作成</span>
  </a>
</div>

<table class="table">
  <thead class="thead-light">
    <tr>
      <th scope="col">ID</th>
      <th scope="col">人数</th>
      <th scope="col">購入個数</th>
      <th scope="col">合計</th>
      <th scope="col">支払額</th>
      <th scope="col">お釣り</th>
      <th scope="col">時間</th>
      <th scope="col">アクション</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">1</th>
      <td>1</td>
      <td>1</td>
      <td>140</td>
      <td>150</td>
      <td>10</td>
      <td>2019/11/09 14:00:00</td>
      <td>
        <input type="button" class="btn btn-success" name="btnEdit" value="編集" />
        <input type="button" class="btn btn-danger" name="btnEdit" value="削除" onclick="ConfirmDelete(1)" />
      </td>
    </tr>
    <tr>
      <th scope="row">2</th>
      <td>1</td>
      <td>2</td>
      <td>400</td>
      <td>500</td>
      <td>100</td>
      <td>2019/11/09 15:00:00</td>
      <td>
        <input type="button" class="btn btn-success" name="btnEdit" value="編集" />
        <input type="button" class="btn btn-danger" name="btnEdit" value="削除" onclick="ConfirmDelete(2)" />
      </td>
    </tr>
    <tr>
      <th scope="row">3</th>
      <td>1</td>
      <td>1</td>
      <td>200</td>
      <td>200</td>
      <td>0</td>
      <td>2019/11/10 12:00:00</td>
      <td>
        <input type="button" class="btn btn-success" name="btnEdit" value="編集" />
        <input type="button" class="btn btn-danger" name="btnEdit" value="削除" onclick="ConfirmDelete(3)" />
      </td>
    </tr>
  </tbody>
</table>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script type="text/javascript">
        function ConfirmDelete(order_id){
            var flag = false;
            if(confirm('注文#'+order_id+'を削除しても宜しいでしょうか?') == true){
                return true;
            }
            return flag;
        }
    </script>
@stop
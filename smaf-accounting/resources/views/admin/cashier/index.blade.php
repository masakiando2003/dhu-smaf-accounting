{{-- resources/views/admin/order/index.blade.php --}}

@extends('adminlte::page')

@section('title', 'SmaF会計システム - 現金出納帳')

@section('content_header')
    <h1>現金出納帳</h1>
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
      <th scope="col">日付</th>
      <th scope="col">摘要</th>
      <th scope="col">収入金額</th>
      <th scope="col">支払金額</th>
      <th scope="col">差引金額</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">1</th>
      <td>ポップコーン(小)</td>
      <td>140</td>
      <td>S</td>
      <td>75</td>
      <td>g</td>
      <td>2019/11/09</td>
      <td>
        <input type="button" class="btn btn-success" name="btnEdit" value="編集" />
        <input type="button" class="btn btn-danger" name="btnEdit" value="削除" onclick="ConfirmDelete('ポップコーン(小)')" />
      </td>
    </tr>
    <tr>
      <th scope="row">2</th>
      <td>ポップコーン(大)</td>
      <td>200</td>
      <td>S</td>
      <td>75</td>
      <td>g</td>
      <td>2019/11/09</td>
      <td>
        <input type="button" class="btn btn-success" name="btnEdit" value="編集" />
        <input type="button" class="btn btn-danger" name="btnEdit" value="削除" onclick="ConfirmDelete('ポップコーン(大)')" />
      </td>
    </tr>
    <tr>
      <th scope="row">3</th>
      <td>ポテト(小)</td>
      <td>100</td>
      <td>L</td>
      <td>150</td>
      <td>g</td>
      <td>2019/11/09</td>
      <td>
        <input type="button" class="btn btn-success" name="btnEdit" value="編集" />
        <input type="button" class="btn btn-danger" name="btnEdit" value="削除" onclick="ConfirmDelete('ポテト(小)')" />
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
        function ConfirmDelete(item_name){
            var flag = false;
            if(confirm('アイテム"'+item_name+'"を削除しても宜しいでしょうか?') == true){
                return true;
            }
            return flag;
        }
    </script>
@stop
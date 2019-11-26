{{-- resources/views/admin/items/index.blade.php --}}

@extends('adminlte::page')

@section('title', 'SmaF会計システム - アイテム一覧')

@section('content_header')
    <h1>アイテム一覧</h1>
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
  <a href="/admin/items/create" class="btn btn-primary">
    <span class="glyphicon glyphicon-plus">新規作成</span>
  </a>
</div>

<form action="/admin/items/delete" method="POST">
{{ csrf_field() }}
<table class="table table-bordered table-hover dataTable">
  <thead class="thead-light">
    <tr>
      <th scope="col">ID</th>
      <th scope="col">名称</th>
      <th scope="col">価格</th>
      <th scope="col">サイズ</th>
      <th scope="col">重量</th>
      <th scope="col">重量単位</th>
      <th scope="col">在庫数</th>
      <th scope="col">最終更新日付</th>
      <th scope="col">アクション</th>
    </tr>
  </thead>
  <tbody>
    @foreach($items as $item)
    <tr>
      <th scope="row">{{ $item->id }}</th>
      <td>{{ $item->name }}</td>
      <td>{{ $item->price }}</td>
      <td>{{ $item->size }}</td>
      <td>{{ $item->weight }}</td>
      <td>{{ $item->weight_unit }}</td>
      <td>{{ $item->stock }}</td>
      <td>{{ $item->updated_at->format('Y/m/d') }}</td>
      <td>
        <a href="/admin/items/detail/{{ $item->id }}" class="btn btn-success">編集</a>
        <input type="submit" class="btn btn-danger" name="btnDel" id="btnDel" value="削除" onclick="return ConfirmDelete('{{ $item->id }}', '{{ $item->name }}')" />
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
<input type="hidden" name="del_id" id="del_id" />
<input type="hidden" name="del_name" id="del_name" />
<input type="hidden" name="_method" id="_method">
<input type="hidden" name="display_items" id="display_items" value="{{ $display_items ?? '' }}" />
<input type="hidden" name="page" id="page" value="{{ $page ?? ''}}" />
</form>
@stop

@section('js')
    <script type="text/javascript">
        function ConfirmDelete(id, item_name){
            var flag = false;
            if(confirm('アイテム"'+item_name+'"を削除しても宜しいでしょうか?') == true){
                document.getElementById('del_id').value = id;
                document.getElementById('del_name').value = item_name;
                document.getElementById('_method').value = "delete";
                return true;
            }
            return flag;
        }
    </script>
@stop
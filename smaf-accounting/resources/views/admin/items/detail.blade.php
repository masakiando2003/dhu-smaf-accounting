{{-- resources/views/admin/items/detail.blade.php --}}

@extends('adminlte::page')

@section('title', $page['title'])

@section('content_header')
    <h1>{{ $page['content_header'] }}</h1>
@stop

@section('content')
    <div class="box">
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error_msg)
                    {!! nl2br($error_msg) !!}<br/>
                @endforeach
            </div>
        @endif

        <div class="box-body">
            <form action="{{ $page['action'] }}" id="item" name="item"
            method='post'>
            @isset($itemDetail->id)
                <input type="hidden" name="item_id" id="item_id"
                       value="{{ $itemDetail->id ?? '' }}" />
            @endisset

            {{ csrf_field() }}

            <div class="row">
                <div class="col-sm-2"><span class="required">*</span>アイテム名称</div>
                <div class="col-sm-4">
                    <input type="text" name="name" id="name"
                       class="form-control" placeholder="アイテム名称"
                       value="@if(isset($itemDetail->name)){{ $itemDetail->name ?? '' }}@else{{old('name') ?? ''}}@endif" />
                </div>
                <input type="hidden" name="ori_name" id="ori_name"
                           value="{{ $itemDetail->name ?? '' }}" />
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">アイテム詳細</div>
                <div class="col-sm-4">
                    <input type="text" name="description" id="description" 
                       class="form-control" placeholder="アイテム詳細 (255文字以内)"
                       value="@if(isset($itemDetail->description)){{ $itemDetail->description ?? '' }}@else{{old('description') ?? ''}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2"><span class="required">*</span>価格</div>
                <div class="col-sm-4">
                    <input type="text" name="price" id="price" 
                       class="form-control" placeholder="価格"
                       value="@if(isset($itemDetail->price)){{ $itemDetail->price ?? '' }}@else{{old('price') ?? ''}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">サイズ</div>
                <div class="col-sm-4">
                    <select name="size" id="size" class="form-control">
                        <option value="S">S</option>
                        <option value="L">L</option>
                    </select>       
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2"><span class="required">*</span>重量</div>
                <div class="col-sm-4">
                    <input type="text" name="weight" id="weight" 
                    class="form-control" placeholder="重量"
                    value="@if(isset($itemDetail->weight)){{ $itemDetail->weight ?? '' }}@else{{old('weight') ?? ''}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">単位</div>
                <div class="col-sm-4">
                    <select name="weight_unit" id="weight_unit" class="form-control">
                        <option value="g">g</option>
                        <option value="ml">ml</option>
                    </select>       
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2"><span class="required">*</span>初期在庫数</div>
                <div class="col-sm-4">
                    <input type="text" name="init_stock" id="init_stock" 
                    class="form-control" placeholder="初期在庫数"
                    value="@if(isset($itemDetail->init_stock)){{ $itemDetail->init_stock ?? '' }}@else{{old('init_stock') ?? ''}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2"><span class="required">*</span>在庫数</div>
                <div class="col-sm-4">
                    <input type="text" name="stock" id="stock" 
                    class="form-control" placeholder="在庫数"
                    value="@if(isset($itemDetail->stock)){{ $itemDetail->stock ?? '' }}@else{{old('stock') ?? ''}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">備考</div>
                <div class="col-sm-6">
                    <textarea class="form-control" style="resize: none;"
                              name="remarks" id="remarks" cols=80 rows=10>@if(isset($itemDeailDetail->remarks)){{ $itemDeailDetail->remarks ?? '' }}@else{{ old('remarks') ?? '' }}@endif</textarea>
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">&nbsp;</div>
                <div class="col-sm-4">
                    <input type="submit" class="btn btn-primary" 
                           name="btnSubmit" id="btnSubmit" value="{{ $page['submit'] }}"
                           onclick="return CheckInput()"/>
                    &nbsp;
                    <a href="{{ url('/admin/items') }}" class="btn btn-primary">戻る</a>
                    &nbsp;
                    @isset($itemDetail->id)
                        <input type="submit"
                               name="btnDelete"
                               id="btnDelete"
                               onclick="return ConfirmDelete(
                                '{{$itemDetail->id}}',
                                '{{$itemDetail->name}}'
                               )" 
                               class="btn btn-danger"
                               value="アイテム削除" /> 
                    @endisset
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <input type="hidden" name="del_id" id="del_id" />
            <input type="hidden" name="del_name" id="del_name" />
            <input type="hidden" name="_method" id="_method">
            </form>
        </div>
    </div>
</form>
@stop

@section('js')
    <script type="text/javascript">
    function CheckInput(){
        var flag = true;
        
        if(document.getElementById('name').value == ''){
            alert('アイテム名称を入力してください。');
            flag = false;
        }
        if(document.getElementById('price').value == ''){
            alert('価格を入力してください。');
            flag = false;
        }
        if(document.getElementById('weight').value == ''){
            alert('重量を入力してください。');
            flag = false;
        }
        if(document.getElementById('init_stock').value == ''){
            alert('初期在庫数を入力してください。');
            flag = false;
        }
        if(document.getElementById('stock').value == ''){
            alert('在庫数を入力してください。');
            flag = false;
        }
        return flag;
    }

    function ConfirmDelete(id, name){
        var flag = false;
        if(confirm('本当に'+name+'を削除しますか?') == true){
            flag = true;
            document.getElementById('del_id').value = id;
            document.getElementById('del_name').value = name;
            document.getElementById('_method').value = "delete";
            document.getElementById('item').action = '/admin/items/delete/'+id;
        }
        return flag;
    }
    </script>
@stop
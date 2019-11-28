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
            <form action="{{ $page['action'] }}" id="orders" name="orders"
            method='post'>
            {{ csrf_field() }}

            @isset($orderDetail->id)
                <input type="hidden" name="order_id" id="order_id"
                       value="{{ $orderDetail->id ?? '' }}" />
            @endisset

            <div class="row">
                <div class="col-sm-2">
                    <input type="radio" name="transactionTimeType" id="transaction_auto" value="auto" checked
                    onclick="ChangeTransactionType()" />自動採番
                </div>
                <div class="col-sm-2">
                    <input type="radio" name="transactionTimeType" id="transaction_manual" value="manual"
                    onclick="ChangeTransactionType()" />入力
                </div>
                <div id="time_label" class="col-sm-4" @if(!isset($orderDetail->id)) style="display:none;" @endif>
                    <div>@isset($orderDetail->created_at){{ $orderDetail->created_at->format('Y/m/d H:i:s') ?? '' }}@endisset</div>
                </div>
                <div id="time_input" class="col-sm-4" style="display:none;">
                    <div class="row">
                        <div class="col-sm-6">
                            <input type="text" name="transactionDate" id="transactionDate"
                            class="form-control datepicker" placeholder="年/月/日"
                            value="@if(isset($orderDetail->created_at)){{ $orderDetail->created_at->format('Y/m/d') ?? '' }}@else{{old('transactionDate') ?? ''}}@endif" />
                        </div>
                        <div class="col-sm-6">
                            <input type="text" name="transactionTime" id="transactionTime"
                            class="form-control timepicker" placeholder="時間:分:秒"
                            value="@if(isset($orderDetail->created_at)){{ $orderDetail->created_at->format('H:i:s') ?? '' }}@else{{old('transactionTime') ?? ''}}@endif" />
                        </div>
                    </div>
                </div>
            </div>
            
            <!--スペースをあげる-->
            <!--<div class="row">&nbsp;</div>-->

            <!--<div class="row">
                <div class="col-sm-2">人数<span class="required">*</span></div>
                <div class="col-sm-4">
                    <input type="text" name="num_of_people" id="num_of_people"
                       class="form-control" placeholder="人数"
                       value="@if(isset($orderDetail->num_of_people)){{ $orderDetail->num_of_people ?? '' }}@else{{old('num_of_people') ?? ''}}@endif" />
                </div>
            </div>-->
            <input type="hidden" name="num_of_people" id="num_of_people" value="1" />

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row pl-3">
                <button type="button" class="btn btn-primary" onclick="AddRow()">+</button>&nbsp;
                <button type="button" class="btn btn-primary" onclick="DeleteRow()">-</button>
            </div>
            
            <div class="row">&nbsp;</div>

            <table class="table">
            <thead class="thead-light">
                <tr>
                    <th scope="col" width="35%">アイテム</th>
                    <th scope="col" width="20%">価格</th>
                    <th scope="col" width="20%">数量</th>
                    <th scope="col" colspan=2>削除</th>
                </tr>
            </thead>
            <tbody id="order-item-list">
                @if(isset($orderDetail->id) && isset($orderItemDetail) && count($orderItemDetail) > 0)
                    @foreach($orderItemDetail as $orderItem)
                        @php
                            $count = 1;
                        @endphp
                        <tr id="order_item_row_{{ $count }}">
                            <td>
                                <input type="hidden" name="item_id_{{ $count }}" id="item_id_{{ $count }}" value="{{ $orderItem->item_id }}" />
                                <input type="text" class="form-control item_search" name="item_name_{{ $count }}" id="item_name_{{ $count }}" value="{{ $orderItem->GetItemName($orderItem->item_id) ?? '' }}" placeholder='少なくとも一つ文字を入力してください' onchange="AutoFillQuantity('{{ $count }}');GetItemIdAndPrice('{{ $count }}');" />
                            </td>
                            <td><input type="text" class="form-control" name="price_{{ $count }}" id="price_{{ $count }}" value="{{ $orderItem->price ?? '' }}" onchange="OrderItemValidation(this.id);CalculateOrderTotal()" placeholder="数字" /></td>
                            <td><input type="text" class="form-control" name="quantity_{{ $count }}" id="quantity_{{ $count }}" value="{{ $orderItem->quantity ?? '' }}" onchange="OrderItemValidation(this.id);CalculateOrderTotal()" placeholder="数字" /></td>
                            <td><input type="checkbox" class="form-control" name="delete_item_{{ $count }}" id="delete_item_{{ $count }}" /></td>
                            <td><button class="btn btn-primary" name="btnClear" id="btnClear" onclick="ClearFilledItem('{{ $count }}')">クリア</button></td>
                        </tr>
                        <input type="hidden" name="order_item_count" id="order_item_count" value="{{ count($orderItemDetail) }}" />
                        @php
                            $count++;
                        @endphp
                    @endforeach
                @else
                    <tr id="order_item_row_1">
                        <td>
                            <input type="hidden" name="item_id_1" id="item_id_1" />
                            <input type="text" class="form-control item_search" name="item_name_1" id="item_name_1" placeholder='少なくとも一つ文字を入力してください' onchange="AutoFillQuantity('1');GetItemIdAndPrice('1');" />
                        </td>
                        <td><input type="text" class="form-control" name="price_1" id="price_1" onchange="OrderItemValidation(this.id);CalculateOrderTotal()" placeholder="数字" /></td>
                        <td><input type="text" class="form-control" name="quantity_1" id="quantity_1" onchange="OrderItemValidation(this.id);CalculateOrderTotal()" placeholder="数字" /></td>
                        <td><input type="checkbox" class="form-control" name="delete_item_1" id="delete_item_1" /></td>
                        <td><button class="btn btn-primary" name="btnClear" id="btnClear" onclick="ClearFilledItem('1')">クリア</button></td>
                    </tr>
                    <input type="hidden" name="order_item_count" id="order_item_count" value="1" />
                @endif
            </tbody>
            </table>

            <div class="row">
                <div class="col-sm-2">合計金額</div>
                <div class="col-sm-4" id="order_total">@if(isset($orderDetail->order_total)) {{ $orderDetail->order_total ?? 0 }} @else 0 @endif</div>
                <input type="hidden" name="order_total_hidden" id="order_total_hidden" value="@if(isset($orderDetail->order_total)) {{ $orderDetail->order_total ?? 0 }} @else 0 @endif" />
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">支払金額</div>
                <div class="col-sm-4">
                    <input type="text" name="paid" id="paid" 
                       class="form-control" placeholder="支払金額" onchange="CalculateChange()"
                       value="@if(isset($orderDetail->paid)){{ $orderDetail->paid ?? '' }}@else{{old('paid') ?? ''}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">お釣り</div>
                <div class="col-sm-4">
                    <input type="text" name="change" id="change" 
                       class="form-control" placeholder="お釣り" readonly
                       value="@if(isset($orderDetail->change)){{ $orderDetail->change ?? '' }}@else{{old('change') ?? ''}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">備考</div>
                <div class="col-sm-6">
                    <textarea class="form-control" style="resize: none;"
                              name="remarks" id="remarks" cols=80 rows=10>@if(isset($orderDetail->remarks)){{ $orderDetail->remarks ?? '' }}@else{{ old('remarks') ?? '' }}@endif</textarea>
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
                    <a href="{{ url('/admin/orders') }}" class="btn btn-primary">戻る</a>
                    &nbsp;
                    @isset($orderDetail->id)
                        <input type="submit"
                               name="btnDelete"
                               id="btnDelete"
                               onclick="return ConfirmDelete(
                                '{{$orderDetail->id}}'
                               )" 
                               class="btn btn-danger"
                               value="注文削除" /> 
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

@section('css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/js/bootstrap-datetimepicker.min.js"></script>
    <script type="text/javascript">

    $('.datepicker').datepicker({ dateFormat: 'yy/mm/dd' });
    $('.timepicker').datetimepicker({ format: 'HH:mm:ss' });

    function ChangeTransactionType()
    {
        if(document.getElementById('transaction_auto').checked == true)
        {
            document.getElementById('time_label').style.display = "";
            document.getElementById('time_input').style.display = "none";
        }
        else if(document.getElementById('transaction_manual').checked == true)
        {
            document.getElementById('time_label').style.display = "none";
            document.getElementById('time_input').style.display = "";
        }
    }

    $( ".item_search" ).autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "{{url('item_autocomplete')}}",
                data: {
                        term : request.term
                },
                dataType: "json",
                success: function(data){
                    var resp = $.map(data,function(obj){
                        return obj.name;
                    }); 
                    response(resp);
                },
            });
        },
        minLength: 0
    });

    function GetItemIdAndPrice(index){
        const item_name = document.getElementById('item_name_'+index).value;
        $.ajax({
            url: "{{url('item_getprice')}}",
            data: {
                itemName : item_name
            },
            dataType: "json",
            success: function(data){
                $.map(data,function(obj){
                    document.getElementById('price_'+index).value = obj.price;
                    document.getElementById('item_id_'+index).value = obj.id;
                    CalculateOrderTotal();
                });
            },
        });
    }

    function ClearFilledItem(index){
        document.getElementById('item_id_'+index).value = '';
        document.getElementById('price_'+index).value = '';
        document.getElementById('quantity_'+index).value = '';
    }

    function AutoFillQuantity(index){
        document.getElementById('quantity_'+index).value = 1;
    }

    function OrderItemValidation(id){
        if(!document.getElementById(id).value.match(/^[0-9]+$/)){
            alert('数字を入力してください。');
            document.getElementById(id).value = 1;
        } else if(document.getElementById(id).value <= 0 ){
            alert('0より大きな数字を入力してください。');
            document.getElementById(id).value = 1;
        }
        
    }

    function CalculateChange(){
        var order_total = parseInt(document.getElementById('order_total_hidden').value);
        var paid = parseInt(document.getElementById('paid').value);
        if(!isNaN(paid)){
            if(paid >= order_total){
                document.getElementById('change').value = paid - order_total;
            } else {
                alert('支払金額に合計金額より大きな数字を入力してください。');
                document.getElementById('paid').value = '';
                document.getElementById('change').value = 0;
            }
        } else {
            //alert('支払金額に数字を入力してください。');
            document.getElementById('paid').value = '';
            document.getElementById('change').value = 0;
        }
    }

    function CheckInput(){
        var flag = true;
        
        if(document.getElementById('num_of_people').value == ''){
            alert('人数を入力してください。');
            flag = false;
        }
        if(document.getElementById('paid').value == ''){
            alert('支払金額を入力してください。');
            flag = false;
        }

        return flag;
    }

    function ConfirmDelete(id)
    {
        var flag = false;
        if(confirm('本当にこの注文を削除しますか?') == true){
            flag = true;
            document.getElementById('del_id').value = id;
            document.getElementById('_method').value = "delete";
            document.getElementById('orders').action = '/admin/orders/delete/';
        }
        return flag;
    }

    function CalculateOrderTotal()
    {
        var order_total = 0;
        var order_item_count = document.getElementById('order_item_count').value;
        for(var i = 1; i <= order_item_count; i++){
            const price = document.getElementById('price_'+i).value;
            const quantity = document.getElementById('quantity_'+i).value;
            order_total += (price * quantity);
        }
        document.getElementById('order_total').innerHTML = order_total;
        document.getElementById('order_total_hidden').value = order_total;

        CalculateChange();
    }

    function AddRow(){
        var order_item_count = document.getElementById('order_item_count').value;
        order_item_count++;
        var table = document.getElementById("order-item-list");
        var row = table.insertRow(-1);
        row.setAttribute("id", "order_item_row_"+order_item_count);

        var item_input = row.insertCell(0);
        var price_input = row.insertCell(1);
        var quantity_input = row.insertCell(2);
        var delete_item_checkbox = row.insertCell(3);
        var clear_button= row.insertCell(4);

        item_input.innerHTML = "<input type='hidden' name='item_id_"+order_item_count+"' id='item_id_"+order_item_count+"' />";
        item_input.innerHTML += "<input type='text' class='form-control' name='item_name_"+order_item_count+"' id='item_name_"+order_item_count+"' onchange='AutoFillQuantity('"+order_item_count+"');GetItemIdAndPrice('"+order_item_count+"');' placeholder='少なくとも一つ文字を入力してください' />";
        price_input.innerHTML = "<input type='text' class='form-control' name='price_"+order_item_count+"' id='price_"+order_item_count+"' onchange='CalculateOrderTotal()' placeholder='数字' />";
        quantity_input.innerHTML = "<input type='text' class='form-control' name='quantity_"+order_item_count+"' id='quantity_"+order_item_count+"' onchange='CalculateOrderTotal()' placeholder='数字' />";
        delete_item_checkbox.innerHTML = "<input type='checkbox' class='form-control' name='delete_item_"+order_item_count+"' id='delete_item_"+order_item_count+"' />";
        clear_button.innerHTML = "<button class='btn btn-primary' name='btnClear' id='btnClear' onclick=\"ClearFilledItem('"+order_item_count+"')\"";
        document.getElementById('order_item_count').value = order_item_count;
    }

    function DeleteRow(){
        var order_item_count = document.getElementById('order_item_count').value;
        if(order_item_count > 1 && document.getElementById('item_name_'+order_item_count).value == ''){
            document.getElementById('order_item_row_'+order_item_count).remove();
            order_item_count--;
            document.getElementById('order_item_count').value = order_item_count;
        }
    }
    </script>
@stop
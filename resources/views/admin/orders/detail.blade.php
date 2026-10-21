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
        @if(session('success_msg'))
        <div class="alert alert-success">
            {{ session('success_msg') }}
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

            @isset($orderDetail->id)
            <div class="row">
                <div class="col-sm-2">注文ID</div>
                <div class="col-sm-4" id="order_id_label">{{ $orderDetail->id }}</div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>
            @endisset

            <div class="row">
                <div class="col-sm-2">注文時間</div>
                @isset($orderDetail->id)
                    {{-- 編集では、注文時間を直接変更する（自動採番は選べない） --}}
                    <input type="hidden" name="transactionTimeType" id="transaction_manual" value="manual" />
                @else
                    <div class="col-sm-2">
                        <label class="mb-0 font-weight-normal">
                            <input type="radio" name="transactionTimeType" id="transaction_auto" value="auto"
                            @if(old('transactionTimeType', 'auto') != 'manual') checked @endif
                            onclick="ChangeTransactionType()" />自動採番
                        </label>
                    </div>
                    <div class="col-sm-2">
                        <label class="mb-0 font-weight-normal">
                            <input type="radio" name="transactionTimeType" id="transaction_manual" value="manual"
                            @if(old('transactionTimeType') == 'manual') checked @endif
                            onclick="ChangeTransactionType()" />入力
                        </label>
                    </div>
                @endisset
                <div id="time_input" class="col-sm-4" @if(!isset($orderDetail->id) && old('transactionTimeType') != 'manual') style="display:none;" @endif>
                    <div class="row">
                        <div class="col-sm-6">
                            <input type="text" name="transactionDate" id="transactionDate"
                            class="form-control datepicker" placeholder="年/月/日"
                            value="{{ old('transactionDate', isset($orderDetail->order_at) ? $orderDetail->order_at->format('Y/m/d') : '') }}" />
                        </div>
                        <div class="col-sm-6">
                            <input type="text" name="transactionTime" id="transactionTime"
                            class="form-control timepicker" placeholder="時間:分:秒"
                            value="{{ old('transactionTime', isset($orderDetail->order_at) ? $orderDetail->order_at->format('H:i:s') : '') }}" />
                        </div>
                    </div>
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">人数</div>
                <div class="col-sm-2">
                    <input type="number" name="num_of_people" id="num_of_people"
                           class="form-control" min="1" step="1" placeholder="人数"
                           value="{{ old('num_of_people', $orderDetail->num_of_people ?? 1) }}" />
                </div>
            </div>

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
                @php
                    // 既存の明細がなければ、空の行を1行表示する
                    $rows = (isset($orderDetail->id) && isset($orderItemDetail) && count($orderItemDetail) > 0)
                            ? $orderItemDetail : [null];
                @endphp
                @foreach($rows as $index => $orderItem)
                    @php
                        $count = $index + 1;
                    @endphp
                    <tr id="order_item_row_{{ $count }}">
                        <td>
                            <input type="hidden" name="item_id_{{ $count }}" id="item_id_{{ $count }}" value="{{ $orderItem->item_id ?? '' }}" />
                            <select class="form-control item_select" id="item_select_{{ $count }}" onchange="SelectItem('{{ $count }}');">
                                <option value="">選択してください</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-price="{{ $item->price }}" @if(isset($orderItem) && $item->id == $orderItem->item_id) selected @endif>{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="form-control" name="price_{{ $count }}" id="price_{{ $count }}" value="{{ $orderItem->price ?? '' }}" onchange="OrderItemValidation(this.id);CalculateOrderTotal()" placeholder="数字" /></td>
                        <td><input type="text" class="form-control" name="quantity_{{ $count }}" id="quantity_{{ $count }}" value="{{ $orderItem->quantity ?? '' }}" onchange="OrderItemValidation(this.id);CalculateOrderTotal()" placeholder="数字" /></td>
                        <td><input type="checkbox" class="form-control" name="delete_item_{{ $count }}" id="delete_item_{{ $count }}" /></td>
                        <td><button type="button" class="btn btn-primary" onclick="ClearFilledItem('{{ $count }}')">クリア</button></td>
                    </tr>
                @endforeach
            </tbody>
            </table>
            <input type="hidden" name="order_item_count" id="order_item_count" value="{{ count($rows) }}" />

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
                       class="form-control" placeholder="支払金額" onchange="CalculateChange();MultipleCreateFocus();"
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
                    <input type="hidden" name="multiple_create_flag" id="multiple_create_flag" value="0"/>
                    @empty($orderDetail->id)
                    <input type="submit" class="btn btn-primary" 
                           name="btnSubmitAndCreate" id="btnSubmitAndCreate" 
                           value="連続作成"
                           onclick="MultipleCreate();return CheckInput()"/>
                    @endif
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

    // 新規作成で、注文時間の「自動採番」「入力」を切り替える
    function ChangeTransactionType()
    {
        if(document.getElementById('transaction_manual').checked == true)
        {
            document.getElementById('time_input').style.display = "";
        }
        else
        {
            document.getElementById('time_input').style.display = "none";
        }
    }

    function PaidFocus()
    {
        document.getElementById('paid').focus();
    }

    function MultipleCreate(){
        document.getElementById('multiple_create_flag').value = 1;
    }

    function MultipleCreateFocus()
    {
        if(document.getElementById('btnSubmitAndCreate') != null)
        {
            document.getElementById('btnSubmitAndCreate').focus();
        }
    }

    // ほかの行ですでに選択されているアイテムかを確認する
    function IsItemSelectedInOtherRow(index, item_id){
        var order_item_count = document.getElementById('order_item_count').value;
        for(var i = 1; i <= order_item_count; i++){
            if(String(i) === String(index)){
                continue;
            }
            var item_id_input = document.getElementById('item_id_'+i);
            if(item_id_input != null && item_id_input.value == item_id){
                return true;
            }
        }
        return false;
    }

    // アイテムを選んだら、アイテムID・単価・数量（1）を入れる
    function SelectItem(index){
        var select = document.getElementById('item_select_'+index);
        var item_id = select.value;
        if(item_id != '' && IsItemSelectedInOtherRow(index, item_id)){
            alert('このアイテムはすでに選択されています。数量を変更してください。');
            select.value = document.getElementById('item_id_'+index).value;
            return;
        }
        if(item_id == ''){
            ClearFilledItem(index);
            return;
        }
        document.getElementById('item_id_'+index).value = item_id;
        document.getElementById('price_'+index).value = select.options[select.selectedIndex].dataset.price;
        document.getElementById('quantity_'+index).value = 1;
        CalculateOrderTotal();
        PaidFocus();
    }

    function ClearFilledItem(index){
        document.getElementById('item_select_'+index).value = '';
        document.getElementById('item_id_'+index).value = '';
        document.getElementById('price_'+index).value = '';
        document.getElementById('quantity_'+index).value = '';
        CalculateOrderTotal();
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
            document.getElementById('paid').value = '';
            document.getElementById('change').value = 0;
        }
    }

    function CheckInput(){
        var flag = true;
        
        var num_of_people = document.getElementById('num_of_people').value;
        if(num_of_people == ''){
            alert('人数を入力してください。');
            flag = false;
        } else if(!num_of_people.match(/^[0-9]+$/) || parseInt(num_of_people) < 1){
            alert('人数は1以上の数字を入力してください。');
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
            if(document.getElementById('price_'+i) == null){
                continue;
            }
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

        // 1行目のドロップダウンの選択肢をコピーして、未選択の状態にする
        var select = document.getElementById('item_select_1').cloneNode(true);
        select.id = 'item_select_'+order_item_count;
        select.value = '';
        select.setAttribute('onchange', "SelectItem('"+order_item_count+"');");

        item_input.innerHTML = "<input type='hidden' name='item_id_"+order_item_count+"' id='item_id_"+order_item_count+"' />";
        item_input.appendChild(select);
        price_input.innerHTML = "<input type='text' class='form-control' name='price_"+order_item_count+"' id='price_"+order_item_count+"' onchange='OrderItemValidation(this.id);CalculateOrderTotal()' placeholder='数字' />";
        quantity_input.innerHTML = "<input type='text' class='form-control' name='quantity_"+order_item_count+"' id='quantity_"+order_item_count+"' onchange='OrderItemValidation(this.id);CalculateOrderTotal()' placeholder='数字' />";
        delete_item_checkbox.innerHTML = "<input type='checkbox' class='form-control' name='delete_item_"+order_item_count+"' id='delete_item_"+order_item_count+"' />";
        clear_button.innerHTML = "<button type='button' class='btn btn-primary' onclick=\"ClearFilledItem('"+order_item_count+"')\">クリア</button>";
        document.getElementById('order_item_count').value = order_item_count;
    }

    function DeleteRow(){
        var order_item_count = document.getElementById('order_item_count').value;
        if(order_item_count > 1 && document.getElementById('item_select_'+order_item_count).value == ''){
            document.getElementById('order_item_row_'+order_item_count).remove();
            order_item_count--;
            document.getElementById('order_item_count').value = order_item_count;
        }
    }
    </script>
@stop
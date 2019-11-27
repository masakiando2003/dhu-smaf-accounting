{{-- resources/views/admin/cashier/detail.blade.php --}}

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
            <form action="{{ $page['action'] }}" id="cashier" name="cashier"
            method='post'>
            @isset($cashierDetail->id)
                <input type="hidden" name="cashier_id" id="cashier_id"
                       value="{{ $cashierDetail->id ?? '' }}" />
            @endisset

            {{ csrf_field() }}

            <div class="row">
                <div class="col-sm-2">時間</div>
                <div class="col-sm-10">
                    <div class="row">
                        <div class="col-sm-2">
                            <input type="radio" name="transactionTimeType" id="transaction_auto" value="auto" checked
                            onclick="ChangeTransactionType()" />自動採番
                        </div>
                        <div class="col-sm-2">
                            <input type="radio" name="transactionTimeType" id="transaction_manual" value="manual"
                            onclick="ChangeTransactionType()" />入力
                        </div>
                        <div id="time_label" class="col-sm-4" @if(!isset($cashierDetail->id)) style="display:none;" @endif>
                            <div>
                                @if(isset($cashierDetail->transactionDate)){{ $cashierDetail->transactionDate ?? '' }}@endif&nbsp;
                                @if(isset($cashierDetail->transactionTime)){{ $cashierDetail->transactionTime ?? '' }}@endif
                            </div>
                        </div>
                        <div id="time_input" class="col-sm-4" style="display:none">
                            <div class="row">
                                <div class="col-sm-6">
                                    <input type="text" name="transactionDate" id="transactionDate"
                                    class="form-control datepicker" placeholder="年/月/日"
                                    value="@if(isset($cashierDetail->transactionDate)){{ $cashierDetail->transactionDate ?? '' }}@else{{old('transactionDate') ?? ''}}@endif" />
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" name="transactionTime" id="transactionTime"
                                    class="form-control timepicker" placeholder="時間:分:秒"
                                    value="@if(isset($cashierDetail->transactionTime)){{ $cashierDetail->transactionTime ?? '' }}@else{{old('transactionTime') ?? ''}}@endif" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">種類</div>
                <div class="col-sm-4">
                    <select name="type" id="type" class="form-control">
                        <option value="income" @if(isset($cashierDetail->type) && $cashierDetail->type == "income") "SELECTED" @endif>収入</option>
                        <option value="payment" @if(isset($cashierDetail->type) && $cashierDetail->type == "payment") "SELECTED" @endif>支払</option>
                    </select>
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">摘要</div>
                <div class="col-sm-4">
                    <input type="text" name="description" id="description" 
                       class="form-control" placeholder="摘要"
                       value="@if(isset($cashierDetail->description)){{ $cashierDetail->description ?? '' }}@else{{old('description') ?? ''}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">収入金額</div>
                <div class="col-sm-4">
                    <input type="text" name="income_amount" id="income_amount" 
                    class="form-control" placeholder="収入金額"
                    value="@if(isset($cashierDetail->income_amount)){{ $cashierDetail->income_amount ?? '0' }}@else{{old('income_amount') ?? '0'}}@endif" 
                    onchange="CalculateDeductionAmount()"/>
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">支払金額</div>
                <div class="col-sm-4">
                    <input type="text" name="payment_amount" id="payment_amount" 
                    class="form-control" placeholder="支払金額"
                    value="@if(isset($cashierDetail->payment_amount)){{ $cashierDetail->payment_amount ?? '0' }}@else{{old('payment_amount') ?? '0'}}@endif"
                    onchange="CalculateDeductionAmount()" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">差引金額</div>
                <div class="col-sm-4">
                    <input type="text" name="deduction_amount" id="deduction_amount" 
                    class="form-control" readonly
                    value="@if(isset($cashierDetail->deduction_amount)){{ $cashierDetail->deduction_amount ?? '0' }}@else{{old('deduction_amount') ?? '0'}}@endif" />
                </div>
            </div>

            <!--スペースをあげる-->
            <div class="row">&nbsp;</div>

            <div class="row">
                <div class="col-sm-2">備考</div>
                <div class="col-sm-6">
                    <textarea class="form-control" style="resize: none;"
                              name="remarks" id="remarks" cols=80 rows=10>@if(isset($cashierDetail->remarks)){{ $cashierDetail->remarks ?? '' }}@else{{ old('remarks') ?? '' }}@endif</textarea>
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
                    <a href="{{ url('/admin/cashier') }}" class="btn btn-primary">戻る</a>
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
                               value="出納記録削除" /> 
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
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css">
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

    function CalculateDeductionAmount()
    {
        var income_amount = document.getElementById('income_amount').value;
        var payment_amount = document.getElementById('payment_amount').value;
        if(income_amount.match(/^[0-9]+$/) && income_amount.match(/^[0-9]+$/)){
            const deduction_amount = income_amount - payment_amount;
            document.getElementById('deduction_amount').value = deduction_amount;
        }
        else {
            if(!income_amount.match(/^[0-9]+$/)){
                alert('収入金額を数字で入力してください。');
                document.getElementById('income_amount').value = 0;
            }
            if(!payment_amount.match(/^[0-9]+$/)){
                alert('支払金額を数字で入力してください。');
                document.getElementById('payment_amount').value = 0;
            }
        }
    }

    function CheckInput(){
        var flag = true;

        if(document.getElementById('type').value == 'income' && 
                document.getElementById('income_amount').value <= 0){
            alert('収入金額を0より大きな数字を入力してください。');
            flag = false;
        }
        else if(document.getElementById('type').value == 'income' && 
                !document.getElementById('income_amount').value.match(/^[0-9]+$/)){
            alert('収入金額を数字で入力してください。');
            flag = false;
        }
        if(document.getElementById('type').value == 'payment' && 
                document.getElementById('payment_amount').value <= 0){
            alert('支払金額を0より大きな数字を入力してください。');
            flag = false;
        }
        else if(document.getElementById('type').value == 'payment' && 
                !document.getElementById('payment_amount').value.match(/^[0-9]+$/)){
            alert('支払金額を数字で入力してください。');
            flag = false;
        }
        return flag;
    }

    function ConfirmDelete(id, name){
        var flag = false;
        if(confirm('本当にこの出納記録を削除しますか?') == true){
            flag = true;
            document.getElementById('del_id').value = id;
            document.getElementById('_method').value = "delete";
            document.getElementById('cashier').action = '/admin/cashier/delete/'+id;
        }
        return flag;
    }
    </script>
@stop
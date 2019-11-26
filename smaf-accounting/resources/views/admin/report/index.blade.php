{{-- resources/views/admin/order/index.blade.php --}}

@extends('adminlte::page')

@section('title', 'SmaF会計システム - レポート')

@section('content_header')
    <h1>レポート</h1>
@stop

@section('content')
<ul id="report_tabs" class="nav nav-tabs">
    <li class="nav-item">
        <a href="#order_report" class="nav-link active">注文レポート</a>
    </li>
    <li class="nav-item">
        <a href="#sale_report" class="nav-link">売上報告</a>
    </li>
    <li class="nav-item">
        <a href="#company_info" class="nav-link">会社紹介</a>
    </li>
    <li class="nav-item">
        <a href="#company_members" class="nav-link">会社メンバー</a>
    </li>
</ul>
<div class="tab-content">
    <!--スペースをあげる-->
    <div class="row">&nbsp;</div>

    <div class="tab-pane fade show active" id="order_report">
        <div class="row">
            <div class="col-sm-12">
                @foreach($sell_dates as $sell_date)
                <div class="row">
                    <div class="col-sm-12 text-left"><strong>{{ $sell_date }}</strong></div>
                </div>

                <!--スペースをあげる-->
                <div class="row">
                    <div class="col-sm-12">&nbsp;</div>
                </div>

                @if(count($items) > 0)
                <div class="row">
                    <div class="col-sm-12 text-left">
                        <table class="table table-bordered dataTable">
                            <tr>
                                <th>&nbsp;</th>
                                @foreach($items as $item)
                                    <th>{{ $item->name }}</th>
                                @endforeach
                            </tr>
                            @foreach($time_periods as $time_period)
                                <tr>
                                    <th>{{ $time_period }}</th>
                                    @foreach($items as $item)
                                        <th>{{ $orders->GetOrdersCountByItemAndTimePeriod($item->id, $sell_date, $time_period) }}</th>
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
                @else
                <div class="row">
                    <div class="col-sm-12 text-left">
                        申し訳ございません、アイテムが存在しません。先にアイテムを作成してください。
                    </div>
                </div> 
                @endif

                @endforeach
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="sale_report">
    <div class="row">
            <div class="col-sm-6">
                @foreach($sell_dates as $sell_date)
                <div class="row">
                    <div class="col-sm-12 text-left"><strong>{{ $sell_date }}</strong></div>
                </div>

                <!--スペースをあげる-->
                <div class="row">
                    <div class="col-sm-12">&nbsp;</div>
                </div>

                @if(count($items) > 0)
                    @foreach($items as $item)
                    <div class="row">
                        <div class="col-sm-6 text-left">
                            <strong>{{ $item->name}}の注文数: </strong>
                        </div>
                        <div class="col-sm-6 text-left">
                            <strong>{{ $orders->GetOrderItemsCountByDate($sell_date, $item->id)}}</strong>
                        </div>
                    </div>
                    @endforeach

                    <!--スペースをあげる-->
                    <div class="row">
                        <div class="col-sm-12">&nbsp;</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 text-left">
                            <strong>合計注文数: </strong>
                        </div>
                        <div class="col-sm-6 text-left">
                            <strong>{{ $orders->GetOrdersCountByDate($sell_date)}}</strong>
                        </div>
                    </div>

                    <!--スペースをあげる-->
                    <div class="row">
                        <div class="col-sm-12">&nbsp;</div>
                    </div>
                @else
                    <div class="row">
                        <div class="col-sm-12 text-left">
                            申し訳ございません、アイテムが存在しません。先にアイテムを作成してください。
                        </div>
                    </div>
                @endif

                @endforeach
            </div>
            <div class="col-sm-6">
                @foreach($sell_dates as $sell_date)
                <div class="row">
                    <div class="col-sm-12 text-left">&nbsp;</div>
                </div>

                <!--スペースをあげる-->
                <div class="row">
                    <div class="col-sm-12">&nbsp;</div>
                </div>

                @if(count($items) > 0)
                    @foreach($items as $item)
                    <div class="row">
                        <div class="col-sm-6 text-left">
                            <strong>{{ $item->name}}の売上高: </strong>
                        </div>
                        <div class="col-sm-6 text-left">
                            <strong>{{ $orders->GetOrderItemsSellAmountByDate($sell_date, $item->id)}}</strong>
                        </div>
                    </div>
                    @endforeach

                    <!--スペースをあげる-->
                    <div class="row">
                        <div class="col-sm-12">&nbsp;</div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 text-left">
                            <strong>合計売上高: </strong>
                        </div>
                        <div class="col-sm-6 text-left">
                            <strong>{{ $orders->GetOrderItemsSellAmountByDate($sell_date)}}</strong>
                        </div>
                    </div>

                    <!--スペースをあげる-->
                    <div class="row">
                        <div class="col-sm-12">&nbsp;</div>
                    </div>
                @else
                    <div class="row">
                        <div class="col-sm-12 text-left">
                            申し訳ございません、アイテムが存在しません。先にアイテムを作成してください。
                        </div>
                    </div>
                @endif

                @endforeach
            </div>
        </div>
    </div>

    <!--会社紹介タブ開始-->
    <div class="tab-pane fade " id="company_info">
      <div class="row">
          <div class="col-sm-2">会社名</div>
          <div class="col-sm-4">{{ $company_info->company_name }}</div>
      </div>
      
      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">事業内容</div>
          <div class="col-sm-4">{{ $company_info->description }}</div>
      </div>
      
      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">設立日</div>
          <div class="col-sm-4">{{ $company_info->setup_date->format('Y年m月d日') }}</div>
      </div>

      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">資本金</div>
          <div class="col-sm-4">{{ $company_info->initial_captial }}円</div>
      </div>
      
      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">現金</div>
          <div class="col-sm-4">{{ $company_info->cash }}円</div>
      </div>

      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">解散日</div>
          <div class="col-sm-4">{{ $company_info->end_date->format('Y年m月d日') }}</div>
      </div>
    </div>
    <!--会社紹介タブ完了-->

    <!--会社メンバータブ開始-->
    <div class="tab-pane fade" id="company_members">
      @foreach($company_members as $company_member)
      <div class="row">
          <div class="col-sm-2">氏名</div>
          <div class="col-sm-4">{{ $company_member->name }}</div>
      </div>

      @if($company_member->email != '')
      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">メールアドレス</div>
          <div class="col-sm-4">{{ $company_member->email }}</div>
      </div> 
      @endif
      
      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">役</div>
          <div class="col-sm-4">{{ ($company_member->role == 1) ? "代表取締役" : "取締役" }}</div>
      </div>
      
      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">役割</div>
          <div class="col-sm-4">{{ $company_member->position }}</div>
      </div>

      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">資金</div>
          <div class="col-sm-4">{{ $company_member->capital }}円</div>
      </div>
      
      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">株数</div>
          <div class="col-sm-4">{{ $company_member->num_of_share }}({{ $company_member->share_percentage }}%)</div>
      </div>

      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <div class="row">
          <div class="col-sm-2">状態</div>
          <div class="col-sm-4">{{ ($company_member->status == 1) ? '活性' : '非活性' }}</div>
      </div>

      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>

      <!--スペースをあげる-->
      <div class="row">&nbsp;</div>
      @endforeach
    </div>
    <!--会社メンバータブ完了-->
</div>
@stop

@section('css')
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
@stop

@section('js')
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
  <script type="text/javascript">
    $(document).ready(function(){ 
        $("#report_tabs a").click(function(e){
            e.preventDefault();
            $(this).tab('show');
        });
    });
  </script>
@stop
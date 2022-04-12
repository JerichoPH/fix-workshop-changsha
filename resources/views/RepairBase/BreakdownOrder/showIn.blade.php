@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            故障修管理
            <small>扫码</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('repairBase/breakdownOrder') }}?direction=IN&page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>故障修管理</a></li>--}}
{{--            <li class="active">扫码</li>--}}
{{--        </ol>--}}
    </section>
    <div class="row">
        <div class="col-md-12">
            <section class="content">
                @include('Layout.alert')
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">故障修入所计划</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('/repairBase/breakdownOrder') }}?direction=IN&page={{ request('page',1) }}" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a href="{{ url('repairBase/breakdownOrder/mission') }}?page={{ request('page',1) }}&date={{ $breakdown_order->created_at->format('Y-m') }}" class="btn btn-flat btn-default"><i class="fa fa-wrench">&nbsp;</i>分配检修任务</a>
                            <a href="{{ url('repairBase/breakdownOrder/printLabel', $breakdown_order->serial_number) }}?direction=IN" class="btn btn-flat btn-default" target="_blank"><i class="fa fa-print">&nbsp;</i>打印唯一编号</a>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>更换时间</dt>
                            <dd>{{ $breakdown_order->created_at->format('Y-m') }}</dd>
                            <dt>任务总数</dt>
                            <dd><span id="spanPlanSum">{{ $plan_sum }}</span></dd>
                            <dt>入所总数</dt>
                            <dd><span id="spanWarehouseSum">{{ $warehouse_sum }}</span></dd>
                            <dt>状态</dt>
                            <dd><span id="spanStatus">{{ $breakdown_order->status }}</span></dd>
                        </dl>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>型号</th>
                                    <th>任务</th>
                                    <th>已入所</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyInScan">
                                @foreach($plan_count as $model_name => $aggregate)
                                    <tr>
                                        <td>{{ $model_name }}</td>
                                        <td>{{ $aggregate }}</td>
                                        <td>{{ @$warehouse_count[$model_name] ?: 0 }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if(array_flip(\App\Model\RepairBaseBreakdownOrder::$STATUSES)[$breakdown_order->status] === 'SATISFY')
                        <div class="box-footer">
                            <div class="btn-group btn-group-sm pull-right">
                                <a href="javascript:" onclick="fnDone('{{ $breakdown_order->serial_number }}')" class="btn btn-success btn-flat"><i class="fa fa-check">&nbsp;</i>确认完成</a>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

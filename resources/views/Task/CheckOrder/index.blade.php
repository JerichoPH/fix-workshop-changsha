@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修任务管理
            <small>列表</small>
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">检修任务列表</h3>
                    <!--右侧最小化按钮-->
                    <div class="pull-right btn-group">
                        <a href="{{url('task/checkPlan')}}" class="btn btn-flat btn-success">前往分配任务</a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>任务编号</th>
                            <th>标题</th>
                            <th>设备数量</th>
                            <th>单位</th>
                            <th>现场车间主任</th>
                            <th>现场工区职工</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($taskStationCheckOrders as $taskStationCheckOrder)
                            <tr>
                                <td>{{ $taskStationCheckOrder->serial_number }}</td>
                                <td>{{ $taskStationCheckOrder->title }}</td>
                                <td>{{ $taskStationCheckOrder->number }}</td>
                                <td>{{ $taskStationCheckOrder->unit }}</td>
                                <td>{{ $taskStationCheckOrder->PrincipalIdLevel3->nickname ?? '' }}</td>
                                <td>{{ $taskStationCheckOrder->PrincipalIdLevel5->nickname ?? '' }}</td>
                                <td>{{ $taskStationCheckOrder->status['value'] }}</td>
                                <td>
                                    @if($taskStationCheckOrder->status['code'] == 'UNDONE')
                                        <a href="{{ url('task/checkOrder/instance') }}?task_station_check_order_serial_number={{ $taskStationCheckOrder->serial_number }}" class="btn btn-default btn-flat">添加设备</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($taskStationCheckOrders->hasPages())
                    <div class="box-footer">
                        {{ $taskStationCheckOrders->links() }}
                    </div>
                @endif
            </div>
        </form>
    </section>
@endsection

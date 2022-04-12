@extends('Layout.index')
@section('style')
    <link rel="stylesheet" href="/EasyWeb/spa/assets/libs/layui/css/layui.css"/>
    <link rel="stylesheet" href="/EasyWeb/spa/assets/css/lite.css"/>
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修计划管理
            <small>列表</small>
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">检修计划列表</h3>
                    <!--右侧最小化按钮-->
                    <div class="pull-right btn-group">
                        <a href="{{url('task/checkPlan/create')}}" class="btn btn-flat btn-success"><i class="fa fa-plus-square"></i> 新建检修计划</a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>计划编号</th>
                            <th>项目类型</th>
                            <th>项目名称</th>
                            <th>车间</th>
                            <th>车站</th>
                            <th>设备数量</th>
                            <th>状态</th>
                            <th>操作人</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($checkPlans as $checkPlan)
                            <tr>
                                <td>{{ $checkPlan->serial_number }}</td>
                                <td>{{ $checkPlan->WithCheckProject->type['text'] ?? '' }}</td>
                                <td>{{ $checkPlan->WithCheckProject->name ?? '' }}</td>
                                <td>{{ $checkPlan->WithStation->Parent->name ?? '' }}</td>
                                <td>{{ $checkPlan->WithStation->name ?? '' }}</td>
                                <td>{{ $checkPlan->number }}</td>
                                <td>{{ $checkPlan->status['text'] }}</td>
                                <td>{{ $checkPlan->WithAccount->nickname ?? '' }}</td>
                                <td>
                                    @if($checkPlan->status['value'] == 0 || $checkPlan->status['value'] == 2)
                                        <a href="{{url('task/checkPlan/instance')}}?serial_number={{ $checkPlan->serial_number }}" class="btn btn-default btn-flat">添加设备</a>
                                        @if($checkPlan->number > 0)
                                            <a href="javascript:" onclick="fnStoreCheckOrder(`{{ $checkPlan->serial_number }}`)" class="btn btn-default btn-flat">分配任务</a>
                                        @else
                                            <a href="javascript:" disabled class="btn btn-default btn-flat">分配任务</a>
                                        @endif
                                    @else
                                        <a href="javascript:" disabled class="btn btn-default btn-flat">添加设备</a>
                                        <a href="javascript:" disabled class="btn btn-default btn-flat">分配任务</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($checkPlans->hasPages())
                    <div class="box-footer">
                        {{ $checkPlans->links() }}
                    </div>
                @endif
            </div>
        </form>
    </section>
    <div id="divCheckOrderShow"></div>
@endsection
@section('script')
    <script type="text/javascript" src="/EasyWeb/spa/assets/libs/layui/layui.js"></script>
    <script>

        function fnStoreCheckOrder(check_plan_serial_number) {
            $.ajax({
                url: `{{ url('task/checkOrder/create') }}`,
                type: 'get',
                data: {
                    check_plan_serial_number: check_plan_serial_number
                },
                async: true,
                success: response => {
                    $("#divCheckOrderShow").html(response);
                    $("#checkOrder").modal("show");
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                    location.reload();
                }
            });
        }

    </script>
@endsection

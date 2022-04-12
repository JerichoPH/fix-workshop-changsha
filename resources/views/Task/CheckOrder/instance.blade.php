@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修任务添加设备
            <small>
                计划：{{ $taskStationCheckOrder->WithCheckPlan->WithCheckProject->type['text'] ?? '' }}&emsp;{{ $taskStationCheckOrder->WithCheckPlan->WithCheckProject->name ?? '' }}&emsp;任务：{{ $taskStationCheckOrder->serial_number }}&emsp;{{ $taskStationCheckOrder->SceneWorkshop->name ?? '' }}&emsp;{{ $taskStationCheckOrder->MaintainStation->name ?? '' }}&emsp;{{ $taskStationCheckOrder->PrincipalIdLevel5->nickname }}&emsp;{{ date('Y-m-d',strtotime($taskStationCheckOrder->expiring_at)) }}
            </small>
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <form id="frmScreen">
            <div class="box box-solid">
                <div class="box-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h3 class="box-title">设备列表</h3>
                        </div>
                        <div class="col-md-6">
                            <div class="pull-right btn-group">
                                <div class="input-group">
                                    <div class="input-group-addon">道岔号</div>
                                    <input type="text" id="crossroad_number" class="form-control" value="{{request('crossroad_number')}}">
                                    <div class="input-group-btn">
                                        <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                                        <a href="{{url('task/checkPlan')}}" class="btn btn-flat btn-success">继续分配任务</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>选择</th>
                            <th>唯一编号</th>
                            <th>道岔号</th>
                            <th>种类型</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entireInstances as $entireInstance)
                            <tr>
                                <td>
                                    <input type="checkbox" id="{{$entireInstance->identity_code}}" @if(in_array($entireInstance->identity_code,$entireInstanceIdentityCodes)) checked @endif value="{{$entireInstance->identity_code}}" onchange="selInstance(this.value)">
                                </td>
                                <td>{{ $entireInstance->identity_code }}</td>
                                <td>{{ $entireInstance->crossroad_number }}</td>
                                <td>{{ $entireInstance->category_name ?? '' }}{{ $entireInstance->entire_model_name ?? '' }}{{ $entireInstance->sub_model_name ?? '' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($entireInstances->hasPages())
                    <div class="box-footer">
                        {{ $entireInstances->appends([
                            'page'=>request('page',1),
                            'task_station_check_order_serial_number'=>request('task_station_check_order_serial_number',1),
                        ])->links() }}
                    </div>
                @endif
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/layer/layer.js"></script>
    <script>
        let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select2();
        });

        /**
         * 选择道岔
         * @param identity_code
         */
        function selInstance(identity_code) {
            let loading = layer.load(2, {shade: false});
            let labelChecked = $(`[id="${identity_code}"]`).prop('checked');
            let url = '';
            let type = '';
            let disable = '';
            if (labelChecked) {
                url = `{{url('task/checkOrder/instance')}}`;
                type = 'post';
                disable = false;
            } else {
                url = `{{url('task/checkOrder/instance')}}`;
                type = 'delete';
                disable = true;
            }
            $.ajax({
                url: url,
                type: type,
                data: {
                    entire_instance_identity_code: identity_code,
                    task_station_check_order_serial_number: `{{ $task_station_check_order_serial_number }}`
                },
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    // layer.msg(response.message)
                    layer.close(loading);
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
        }


        /**
         * 查询
         */
        function fnScreen() {
            let task_station_check_order_serial_number = `{{ $task_station_check_order_serial_number }}`;
            let crossroad_number = $('#crossroad_number').val();
            location.href = `{{ url('task/checkOrder/instance') }}?task_station_check_order_serial_number=${task_station_check_order_serial_number}&crossroad_number=${crossroad_number}`
        }
    </script>
@endsection

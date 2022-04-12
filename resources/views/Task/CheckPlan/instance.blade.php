@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修计划添加设备
            <small>
                计划编号：{{ $checkPlan->serial_number }}&emsp;{{ $checkPlan->WithCheckProject->type['text'] ?? '' }}&emsp;{{ $checkPlan->WithCheckProject->name ?? '' }}&emsp;{{ $checkPlan->WithStation->Parent->name ?? '' }}&emsp;{{ $checkPlan->WithStation->name ?? '' }}&emsp;{{ date('Y-m',strtotime($checkPlan->expiring_at)) }}
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
                            <h3 class="box-title">道岔号列表</h3>
                        </div>
                        <div class="col-md-6">
                            <div class="pull-right btn-group">
                                <div class="input-group">
                                    <div class="input-group-addon">道岔号</div>
                                    <input type="text" id="crossroad_number" class="form-control" value="{{request('crossroad_number')}}">
                                    <div class="input-group-btn">
                                        <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                                        <a href="{{url('task/checkPlan')}}" class="btn btn-flat btn-success">添加成功前往分配任务</a>
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
                            <th>序号/选择</th>
                            <th>道岔号</th>
                            <th>设备数量</th>
                            <th>序号/选择</th>
                            <th>道岔号</th>
                            <th>设备数量</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entireInstances as $value)
                            <tr>
                                @foreach($value as $k=>$entireInstance)
                                    <td>
                                        {{ $k+1 }}&emsp;<input type="checkbox" id="{{$entireInstance->crossroad_number}}" @if(in_array($entireInstance->crossroad_number,$checkPlanEntireInstanceCrossroadNumbers)) checked @endif value="{{$entireInstance->crossroad_number}}" onchange="selInstance(this.value,`{{ $entireInstance->maintain_station_name }}`)">
                                    </td>
                                    <td>{{ $entireInstance->crossroad_number }}</td>
                                    <td>{{ $entireInstance->count }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
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
         * @param crossroad_number
         * @param maintain_station_name
         */
        function selInstance(crossroad_number, maintain_station_name) {
            let loading = layer.load(2, {shade: false});
            let labelChecked = $(`[id="${crossroad_number}"]`).prop('checked');
            let url = '';
            let type = '';
            let disable = '';
            if (labelChecked) {
                url = `{{url('task/checkPlan/instance')}}`;
                type = 'post';
                disable = false;
            } else {
                url = `{{url('task/checkPlan/instance')}}`;
                type = 'delete';
                disable = true;
            }
            $.ajax({
                url: url,
                type: type,
                data: {
                    crossroad_number: crossroad_number,
                    maintain_station_name: maintain_station_name,
                    serial_number: `{{ $serial_number }}`
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
            let serial_number = `{{ $serial_number }}`;
            let crossroad_number = $('#crossroad_number').val();
            location.href = `{{ url('task/checkPlan/instance') }}?serial_number=${serial_number}&crossroad_number=${crossroad_number}`
        }
    </script>
@endsection

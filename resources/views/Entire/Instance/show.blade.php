@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            整件管理
            <small>批量导入</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('entire/instance') }}"><i class="fa fa-users">&nbsp;</i>整件管理</a></li>--}}
{{--            <li class="active">批量导入</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
    @include('Layout.alert')

    <!--查询-->
        <form action="">
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h1 class="box-title">查询</h1>
                            <!--右侧最小化按钮-->
                            <div class="pull-right btn-group btn-group-sm">
                                <button class="btn btn-default btn-flat">查询</button>
                            </div>
                        </div>
                        <div class="box-body form-horizontal">
                            <form action="">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <div class="input-group-addon">状态</div>
                                            <select name="status" class="form-control select2" style="width:100%;">
                                                <option value="">全部</option>
                                                <option value="BUY_IN" {{ request('status') == 'BUY_IN' ? 'selected' : '' }}>新入所</option>
                                                <option value="INSTALLING" {{ request('status') == 'INSTALLING' ? 'selected' : '' }}>备品</option>
                                                <option value="INSTALLED" {{ request('status') == 'INSTALLED' ? 'selected' : '' }}>上道</option>
                                                <option value="FIXING" {{ request('status') == 'FIXING' ? 'selected' : '' }}>待修</option>
                                                <option value="FIXED" {{ request('status') == 'FIXED' ? 'selected' : '' }}>成品</option>
                                                <option value="RETURN_FACTORY" {{ request('status') == 'RETURN_FACTORY' ? 'selected' : '' }}>返厂维修</option>
                                                <option value="FACTORY_RETURN" {{ request('status') == 'FACTORY_RETURN' ? 'selected' : '' }}>返厂入所</option>
                                            </select>
                                            <div class="input-group-addon">时间类型</div>
                                            <select name="date_type" class="form-control select2" style="width:100%;">
                                                <option value="">不使用</option>
                                                <option value="create" {{ request('date_type') == 'create' ? 'selected' : '' }}>采购时间</option>
                                                <option value="in" {{ request('date_type') == 'in' ? 'selected' : '' }}>入所时间</option>
                                                <option value="out" {{ request('date_type') == 'out' ? 'selected' : '' }}>出所时间</option>
                                                <option value="fix" {{ request('date_type') == 'fix' ? 'selected' : '' }}>检修时间</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <div class="input-group-addon">时间段</div>
                                            <input name="date" type="text" class="form-control pull-right" id="date" value="{{ request('date',date("Y-m-d").'~'.date("Y-m-d")) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-1"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h1 class="box-title">设备列表</h1>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm">
                            <a href="{{ url('warehouse/report/scanInBatch') }}?page={{ request('page',1) }}" class="btn btn-default btn-flat">批量扫码</a>
                            <a href="{{ url('entire/instance/create') }}?page={{ request('page',1) }}&type=entire_model_unique_code&entire_model_unique_code={{ $entireModel->unique_code }}" class="btn btn-default btn-flat">新设备</a>
                        </div>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-hover table-condensed table-striped" id="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>唯一编号</th>
                                <th>型号</th>
                                <th>供应商</th>
                                <th>安装位置</th>
                                <th>安装时间</th>
                                <th>主/备用</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($entireInstances as $entireInstance)
                                <tr>
                                    <td>{{ $entireInstance->id}}</td>
                                    <td><a href="{{ url('search',$entireInstance->identity_code) }}" target="_blank">{{ $entireInstance->identity_code }}</a></td>
                                    <td>
                                        {{ $entireInstance->model_name}}
                                    </td>
                                    <td>
                                        {{ $entireInstance->factory_name }}
                                        {{ $entireInstance->factory_device_code }}
                                    </td>
                                    <td>
                                        {{ $entireInstance->maintain_station_name }}
                                        {{ $entireInstance->maintain_location_code }}
                                        {{ $entireInstance->crossroad_number }}
                                        {{ $entireInstance->traction }}
                                        {{ $entireInstance->line_name }}
                                        {{ $entireInstance->open_direction }}
                                        {{ $entireInstance->said_rod }}
                                    </td>
                                    <td>{{ $entireInstance->last_installed_time ? date('Y-m-d',$entireInstance->last_installed_time) : '' }}</td>
                                    <td>{{ $entireInstance->is_main ? '主用' : '备用' }}</td>
                                    <td>{{ $statuses[$entireInstance->status] }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ url('entire/instance',$entireInstance->identity_code) }}/edit?page={{ request('page',1) }}" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                            @if($entireInstance->fix_workflow_serial_number)
                                                <a href="{{ url('measurement/fixWorkflow',$entireInstance->fix_workflow_serial_number) }}/edit?page={{ request('page',1) }}" class="btn btn-warning btn-flat">查看检修单</a>
                                            @endif
                                            {{--@if($entireInstance->fw_status !== "FIXED")--}}
                                            {{--<!--新建检修单-->--}}
                                            {{--<a href="{{ url('measurement/fixWorkflow/create') }}?page={{ request('page',1) }}&type=FIX&identity_code={{ $entireInstance->identity_code }}" class="btn btn-warning btn-flat">新检修</a>--}}
                                            {{--@else--}}
                                            {{--<!--查看检修单-->--}}
                                            {{--<a href="{{ url('measurement/fixWorkflow',$entireInstance->fix_workflow_serial_number) }}/edit?page={{ request('page',1) }}" class="btn btn-warning btn-flat">检修</a>--}}
                                            {{--@endif--}}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($entireInstances->hasPages())
                        <div class="box-footer">
                            {{ $entireInstances->appends([
                            'categoryUniqueCode'=>request('categoryUniqueCode'),
                            'entireModelUniqueCode'=>request('entireModelUniqueCode'),
                            'updatedAt'=>request('updatedAt'),
                            'status'=>request('status'),
                            'date_type'=>request('date_type'),
                            'date'=>request('date'),
                            ])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="divModalEntireInstanceFixing"></div>
        <div id="divModalFixWorkflowInOnce"></div>
    </section>
@endsection
@section('script')
    <script>
        let originAt = '{{ $originAt }}';
        let finishAt = '{{ $finishAt }}';

        $(function () {
            $('.select2').select2();

            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
            //Date picker
            $('#date').daterangepicker({
                locale: {
                    format: "YYYY-MM-DD",
                    separator: "~",
                    daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                    monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    applyLabel: "确定",
                    cancelLabel: "取消",
                    fromLabel: "开始时间",
                    toLabel: "结束时间",
                    customRangeLabel: "自定义",
                    weekLabel: "W",
                },
                startDate: "{{ $originAt }}",
                endDate: "{{ $finishAt }}"
            });
        });

        /**
         * 报废
         * @param {string} identityCode 编号
         */
        function fnDelete (identityCode) {
            $.ajax({
                url: "{{ url('entire/instance/scrap') }}/" + identityCode,
                type: "post",
                data: {},
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 打开入所窗口
         * @param entireInstanceIdentityCode
         */
        function fnFixingIn (entireInstanceIdentityCode) {
            $.ajax({
                url: "{{ url('entire/instance/fixingIn') }}/" + entireInstanceIdentityCode,
                type: "get",
                data: {},
                async: true,
                success: function (response) {
                    console.log('success:', response);
                    // alert(response);
                    // location.reload();
                    $("#divModalFixWorkflowInOnce").html(response);
                    $("#modalFixWorkflowInOnce").modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }
    </script>
@endsection

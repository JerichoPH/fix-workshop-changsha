@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修单管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box box-solid">
                <div class="box-header">
                    <h1 class="box-title">检修单列表 数量{{$fixWorkflows->total()}}</h1>
                    <!--右侧最小化按钮-->
                    <div class="box-tools pull-right">
                        {{--<a href="{{url('fixWorkflow/create')}}" class="btn btn-box-tool"><i class="fa fa-plus-square">&nbsp;</i></a>--}}
                        <a href="{{ url('measurement/fixWorkflow/batchUploadFixerAndChecker') }}" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-upload">&nbsp;</i>批量补录检修人和验收人</a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>设备/器材唯一编号</th>
                            <th>时间</th>
                            <th>种类</th>
                            <th>型号</th>
                            <th>所编号</th>
                            <th>状态</th>
                            <th>阶段</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($fixWorkflows as $fixWorkflow)
                            <tr>
                                <td>{{ $fixWorkflow->entire_instance_identity_code }}</td>
                                <td>{{ $fixWorkflow->updated_at }}</td>
                                <td>{{ @$fixWorkflow->EntireInstance->Category->name }}</td>
                                <td>{{ @$fixWorkflow->EntireInstance->EntireModel->name }}</td>
                                <td>{{ $fixWorkflow->EntireInstance ? $fixWorkflow->EntireInstance->serial_number ?: '新设备' : '' }}</td>
                                <td>{{ $fixWorkflow->status }}</td>
                                <td>{{ $fixWorkflow->stage }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if(array_flip(\App\Model\FixWorkflow::$STAGE)[$fixWorkflow->stage] == 'WAIT_CHECK')
                                            {{--                                        <a href="{{url('measurement/fixWorkflow/create')}}?page={{request('page',1)}}&type=CHECK&identity_code={{$fixWorkflow->EntireInstance->identity_code}}" class="btn btn-warning btn-flat">验收</a>--}}
                                            <a href="{{url('measurement/fixWorkflow',$fixWorkflow->serial_number)}}/edit?page={{request('page',1)}}" class="btn btn-warning btn-flat">验收</a>
                                        @else
                                            @if(array_flip(\App\Model\FixWorkflow::$STAGE)[$fixWorkflow->stage] == 'FIXING')
                                                <a href="{{url('measurement/fixWorkflow',$fixWorkflow->serial_number)}}/edit?page={{request('page',1)}}" class="btn btn-primary btn-flat">检修</a>
                                            @else
                                                <a href="{{url('measurement/fixWorkflow',$fixWorkflow->serial_number)}}/edit?page={{request('page',1)}}" class="btn btn-primary btn-flat">详情</a>
                                            @endif
                                        @endif
                                        <a href="javascript:" onclick="fnDelete('{{$fixWorkflow->serial_number}}')" class="btn btn-danger btn-flat">删除</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($fixWorkflows->hasPages())
                    <div class="box-footer">
                        {{ $fixWorkflows->appends([
                            "status"=>request("status"),
                        ])->links() }}
                    </div>
                @endif
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        $(function () {
            $select2 = $('.select2');
            if ($select2) {
                $select2.select2();
            }
            // iCheck for checkbox and radio inputs
            if ($('input[type="checkbox"].minimal, input[type="radio"].minimal')) {
                $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                    checkboxClass: 'icheckbox_minimal-blue',
                    radioClass: 'iradio_minimal-blue'
                });
            }

            // 日期选择器
            let datepickerOption = {
                autoclose: true,
                todayHighlight: true,
                language: "cn",
                format: "yyyy-mm-dd",
                beforeShowDay: $.noop,
                calendarWeeks: false,
                clearBtn: true,
                daysOfWeekDisabled: [],
                endDate: Infinity,
                forceParse: true,
                keyboardNavigation: true,
                minViewMode: 0,
                orientation: "auto",
                rtl: false,
                startDate: -Infinity,
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#datepicker').datepicker(datepickerOption);
        });

        /**
         * 删除
         * @param {string} fixWorkflowSerialNumber 编号
         */
        fnDelete = function (fixWorkflowSerialNumber) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: "{{ url('measurement/fixWorkflow') }}/" + fixWorkflowSerialNumber,
                    type: "delete",
                    data: {},
                    success: function (response) {
                        console.log('success:', response);
                        // alert(response);
                        location.reload();
                    },
                    error: function (error) {
                        console.log('fail:', error);
                    }
                });
        };
    </script>
@endsection


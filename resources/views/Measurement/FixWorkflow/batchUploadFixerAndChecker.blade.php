@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修单管理
            <small>补录检修人和验收人</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('fixWorkflow') }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>检修单管理</a></li>--}}
{{--            <li class="active">补录检修人和验收人</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                @include('Layout.alert')
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">补录检修人和验收人</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('measurement/fixWorkflow/batchUploadFixerAndChecker') }}?download=1" class="btn btn-default btn-flat"><i class="fa fa-download">&nbsp;</i>下载模板</a>
                        </div>
                    </div>
                    <br>
                    <form class="form-horizontal" id="frmStore" action="" method="POST" enctype="multipart/form-data">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">选择文件：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input type="file" name="file" id="file">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">检修日期：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        type="text"
                                        class="form-control pull-right"
                                        name="fixed_at"
                                        id="dpFixedAt"
                                        value="{{ old('fixed_at', date('Y-m-d')) }}"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">验收日期：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        type="text"
                                        class="form-control pull-right"
                                        name="checked_at"
                                        id="dpCheckedAt"
                                        value="{{ old('checked_at', date('Y-m-d')) }}"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
{{--                            <a href="{{ url('measurement/fixWorkflow') }}?page={{ request('page',1) }}" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>
                            <button type="submit" class="btn btn-success btn-flat btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>确定</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        $(function () {
            if ($select2.length > 0) $('.select2').select2();

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
                forceParse: true,
                keyboardNavigation: true,
                minViewMode: 0,
                orientation: "auto",
                rtl: false,
                startDate: -Infinity,
                endDate: '{{ date('Y-m-d') }}',
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#dpFixedAt').datepicker(datepickerOption);
            $('#dpCheckedAt').datepicker(datepickerOption);
        });

    </script>
@endsection

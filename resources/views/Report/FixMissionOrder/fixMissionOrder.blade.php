@extends('Layout.index')
@section('content')
    <style>
        .change{
            position: absolute;
            overflow: hidden;
            top: 0;
            opacity: 0;
        }
    </style>
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修
            <small>任务分配</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-home"></i> 首页</a></li>--}}
{{--            <li class="active">检修任务分配</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
    @include('Layout.alert')
    <!--检修报表-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-4">
                                <h3 class="box-title">检修任务 {{ request('date') }}</h3>
                            </div>
                            <div class="col-md-8">
                                <div class="box-tools pull-right">
                                    <div class="input-group">
                                        <div class="input-group-addon">工区</div>
                                        <select
                                            name="workArea"
                                            id="selWorkArea"
                                            class="form-control select2"
                                            style="width: 100%;"
                                            onchange="fnSelectWorkArea(this.value)"
                                        >
                                        <option value="0" selected disabled>请选择</option>
                                        <option value="1" {{ request('workAreaId') == '1' ? 'selected' : '' }}>转辙机</option>
                                        <option value="2" {{ request('workAreaId') == '2' ? 'selected' : '' }}>继电器</option>
                                        <option value="3" {{ request('workAreaId') == '3' ? 'selected' : '' }}>综合</option>
                                        </select>
                                        <div class="input-group-addon">时间</div>
                                        <select
                                            name="dates"
                                            id="selDates"
                                            class="form-control select2"
                                            style="width: 100%;"
                                        >
                                        <option value="0" selected disabled>请选择</option>
                                        </select>
                                        <div class="input-group-btn">
                                            <a href="javascript:" onclick="fnSearch()" class="btn btn-default btn-flat">搜索</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div id="iframe" style="display: none"></div>
            <div class="col-md-4">
                <!--整件和部件-->
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">检修详情</h3>
                    </div>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>日期：</dt>
                            <dd>{{ @$fixMissionOrders[0]->serial_number }}</dd>
                            <dt>工区：</dt>
                            @if(@$fixMissionOrders[0]->work_area_id == 1)
                            <dd>转辙机</dd>
                            @elseif(@$fixMissionOrders[0]->work_area_id == 2)
                            <dd>继电器</dd>
                            @elseif(@$fixMissionOrders[0]->work_area_id == 3)
                            <dd>综合</dd>
                            @endif
                            <div>任务统计：</div>
                            <div class="box-header">
                                <table class="table table-hover table-condensed" id="tblEntireInstance">
                                    <thead>
                                    <tr>
                                        <th>型号</th>
                                        <th>数量</th>
                                        <th>完成数量</th>
                                    </tr>
                                    </thead>
                                    <tbody id="tbodyEntireInstance">
                                    @foreach(@$datas as $data)
                                        <tr>
                                            <td>{{ @$data->model_name }}</td>
                                            <td>{{ \Illuminate\Support\Facades\DB::table('fix_mission_order_entire_instances')->where('fix_mission_order_serial_number', $fixMissionOrders[0]->serial_number)->where('work_area_id', $fixMissionOrders[0]->work_area_id)->where('model_name', @$data->model_name)->count() }}</td>
                                            <td>{{ @$data->complete }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <!--任务详情-->
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">任务详情</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            @if(request('dates'))
                                <a href="javascript:;" class="btn btn-default btn-flat" onclick="fnDownloadExcel()"><i class="fa fa-cloud-download">&nbsp;</i>下载检修单</a>
{{--                                <form class="form-horizontal" id="frmStore" action="" method="POST" enctype="multipart/form-data">--}}
                                    <a href="javascript:;" class="btn btn-info btn-flat"><i class="fa fa-cloud-upload">&nbsp;</i>上传检修单
                                        <input type="file" id="upLoadExcel" name="upLoadExcel" class="change" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"/>
                                    </a>
                                    <a href="javascript:;" class="btn btn-primary btn-flat" onclick="fnUploadExcel()">确定上传</a>
{{--                                </form>--}}
                            @endif
                        </div>
                        <table class="table table-hover table-condensed" id="tblEntireInstance">
                            <thead>
                            <tr>
                                <th>设备编号</th>
                                <th>型号</th>
                                <th>检修人</th>
                                <th>截止日期</th>
                                <th>验收日期</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($fixMissionOrderEntireInstances as $fixMissionOrderEntireInstance)
                                    <tr>
                                        <td>{{ @$fixMissionOrderEntireInstance->entire_instance_identity_code }}</td>
                                        <td>{{ @$fixMissionOrderEntireInstance->model_name }}</td>
                                        <td>{{ \Illuminate\Support\Facades\DB::table('accounts')->where('id', $fixMissionOrderEntireInstance->account_id)->value('nickname') }}</td>
                                        <td>{{ @substr($fixMissionOrderEntireInstance->abort_date,0,10) }}</td>
                                        <td>{{ @substr($fixMissionOrderEntireInstance->acceptance_date,0,10) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <br>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $selDate = $('#selDate');
        let $selCategory = $('#selCategory');
        let selDates = $('#selDates');


        $(function () {
            $select2.select2();
        });

        /**
         * 上传Excel
         */
        function fnUploadExcel() {
            var formData = new FormData();
            var name = $("#upLoadExcel").val();
            formData.append('upLoadExcel',$('#upLoadExcel')[0].files[0]);
            formData.append('name',name);
            if (name == null || name == '') {
                alert('请先上传文件')
            }else {
                $.ajax({
                    url: "{{ url('fixMissionOrder/UploadExcel') }}",
                    type: 'post',
                    data: formData,
                    processData : false,
                    contentType : false,
                    async: true,
                    success: res => {
                        console.log(res);
                    },
                    error: err => {
                        console.log(`/fixMissionOrder/UploadExcel error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            }

        }

        /**
         * 下载Excel
         */
        function fnDownloadExcel() {
            $.ajax({
                url: "{{ url('/fixMissionOrder/DownloadExcel') }}",
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(res);
                    window.open(`/fixMissionOrder/DownloadExcel?workAreaId={{ request('workAreaId') }}&dates={{ request('dates') }}&download=1`, '_blank');
                },
                error: err => {
                    console.log(`/fixMissionOrder/DownloadExcel error:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseText);
                }
            });

        }

        /**
         * 搜索
         */
        function fnSearch() {
            let workAreaId = document.getElementById('selWorkArea').value
            let selDates = document.getElementById('selDates').value
            location.href = `{{ url('/fixMissionOrder') }}?workAreaId=${workAreaId}&dates=${selDates}`;
        }

        /**
         * 初始页面获取检修时间
         */
        $(function () {
            let workAreaId = document.getElementById('selWorkArea').value
            let html = '<option value="0" selected disabled>请选择</option>';
            if (workAreaId > 0) {
                $.ajax({
                    url: `/fixMissionOrder/${workAreaId}`,
                    type: 'post',
                    data: {},
                    async: true,
                    success: res => {
                        console.log(`/fixMissionOrder/${workAreaId} success:`, res);
                        $.each(res, (idx, item) => {
                                                        html += `<option value="${item.serial_number}" ${"{{request('dates')}}" === item.serial_number ? 'selected' : ''}>${item.serial_number}</option>`;
                                // html += `<option value="${item.serial_number}">${item.serial_number}</option>`;
                        });
                        selDates.html(html);
                    },
                    error: err => {
                        console.log(`/fixMissionOrder/${workAreaId} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selDates.html(html);
            }
        });

        /**
         * 根据工区获取检修单列表
         * @param {string} workAreaId
         */
        function fnSelectWorkArea(workAreaId) {
            let html = '<option value="0" selected disabled>请选择</option>';
            if (workAreaId > 0) {
                $.ajax({
                    url: `/fixMissionOrder/${workAreaId}`,
                    type: 'post',
                    data: {},
                    async: true,
                    success: res => {
                        console.log(`/fixMissionOrder/${workAreaId} success:`, res);
                        $.each(res, (idx, item) => {
{{--                            html += `<option value="${item.serial_number}" ${"{{request('dates')}}" === item.serial_number ? 'selected' : ''}>${item.serial_number}</option>`;--}}
                            html += `<option value="${item.serial_number}">${item.serial_number}</option>`;
                        });
                        selDates.html(html);
                    },
                    error: err => {
                        console.log(`/fixMissionOrder/${workAreaId} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selDates.html(html);
            }
        }
    </script>
@endsection

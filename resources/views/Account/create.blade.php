@extends('Layout.index')
@section('style')
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            用户管理
            <small>新建</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('account')}}"><i class="fa fa-users">&nbsp;</i>用户管理</a></li>--}}
{{--            <li class="active">新建</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">新建用户</h3>
                <!--右侧最小化按钮-->
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <form class="form-horizontal" id="frmCreate">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">账号：</label>
                        <div class="col-sm-8 col-md-8">
                            <input name="account" type="text" class="form-control" placeholder="账号" required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">名称：</label>
                        <div class="col-sm-8 col-md-8">
                            <input name="nickname" type="text" class="form-control" placeholder="名称" required>
                        </div>
                    </div>
                    {{--<div class="form-group">--}}
                    {{--    <label class="col-sm-3 control-label">邮箱：</label>--}}
                    {{--    <div class="col-sm-8 col-md-8">--}}
                    {{--        <input name="email" type="email" class="form-control" placeholder="邮箱">--}}
                    {{--    </div>--}}
                    {{--</div>--}}
                    <div class="form-group">
                        <label class="col-sm-3 control-label">电话：</label>
                        <div class="col-sm-8 col-md-8">
                            <input name="phone" type="text" class="form-control" placeholder="电话">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">密码：</label>
                        <div class="col-sm-8 col-md-8">
                            <input name="password" type="password" class="form-control" placeholder="密码">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">工号：</label>
                        <div class="col-sm-8 col-md-8">
                            <input name="identity_code" type="text" class="form-control" placeholder="工号" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">车间：</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="workshop_unique_code" id="selWorkshop" class="select2 form-control" style="width: 100%;" onchange="fnFillWorkshops(this.value)"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">车站：</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="station_unique_code" id="selStation" class="select2 form-control" style="width: 100%;"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">工区：</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="work_area_unique_code" id="selWorkArea" class="select2 form-control" style="width: 100%;"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">验收权限：</label>
                        <div class="col-ms-8 col-md-8">
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="supervision" value="1">是</label>
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="supervision" value="0" checked>否</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">数据查询范围：</label>
                        <div class="col-ms-8 col-md-8">
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="read_scope" value="1" checked>个人</label>
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="read_scope" value="2">工区</label>
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="read_scope" value="3">车间</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">数据操作范围：</label>
                        <div class="col-ms-8 col-md-8">
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="write_scope" value="1" checked>个人</label>
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="write_scope" value="2">工区</label>
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="write_scope" value="3">车间</label>
                        </div>
                    </div>

                    {{--<div class="form-group">--}}
                    {{--    <label class="col-sm-3 control-label">临时生产任务角色：</label>--}}
                    {{--    <div class="col-ms-8 col-md-8">--}}
                    {{--        @foreach ($tempTaskPositions as $uniqueCode => $name)--}}
                    {{--            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="temp_task_position" value="{{ $uniqueCode }}">{{ $name }}</label>--}}
                    {{--        @endforeach--}}
                    {{--    </div>--}}
                    {{--</div>--}}
                    <div class="form-group">
                        <label class="col-sm-3 control-label">职级</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="rank" id="selRank" class="form-control select2" style="width: 100%;">
                                @foreach($ranks as $rank_code => $rank_name)
                                    <option value="{{ $rank_code }}">{{ $rank_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="box-footer">
{{--                        <a href="{{ url('account') }}" class="btn btn-default pull-left btn-flat btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                        <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default pull-left btn-flat btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <a onclick="fnUpdate()" class="btn btn-success pull-right btn-flat btn-sm"><i class="fa fa-check">&nbsp;</i>新建</a>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $selWorkshop = $('#selWorkshop');
        let $selStation = $('#selStation');
        let $selWorkArea = $('#selWorkArea');

        let workshops = {!! $workshops_as_json !!};
        let stations = {!! $stations_as_json !!};
        let workAreas = {!! $work_areas_as_json !!};

        /**
         * 填充车间下拉列表
         */
        function fnFillWorkshops(workshopUniqueCode=null) {
            if(!workshopUniqueCode){
                let html = '<option value="">无</option>';
                $.each(workshops, function (k, workshop) {
                    html += `<option value="${workshop['unique_code']}">${workshop['name']}</option>`;
                });
                $selWorkshop.html(html);
            }

            fnFillStations($selWorkshop.val());
            fnFillWorkAreas($selWorkshop.val());
        }

        /**
         * 填充车站下拉列表
         */
        function fnFillStations(workshopUniqueCode = null) {
            let html = '<option value="">无</option>';
            let tmp = {};

            if (workshopUniqueCode) {
                if (stations.hasOwnProperty(workshopUniqueCode)) {
                    tmp[workshopUniqueCode] = stations[workshopUniqueCode];
                }
            } else {
                tmp = stations;
            }

            if (tmp) {
                $.each(tmp, function (wun, station) {
                    $.each(station, function (k, v) {
                        html += `<option value="${v['unique_code']}">${v['name']}</option>`;
                    });
                });
            }

            $selStation.html(html);
        }

        /**
         * 填充工区下拉列表
         */
        function fnFillWorkAreas(workshopUniqueCode = null) {
            let html = '<option value="">无</option>';
            let tmp = {};

            if (workshopUniqueCode) {
                if (workAreas.hasOwnProperty(workshopUniqueCode)) {
                    tmp[workshopUniqueCode] = workAreas[workshopUniqueCode];
                }
            } else {
                tmp = workAreas;
            }

            if (tmp) {
                $.each(tmp, function (wun, workArea) {
                    $.each(workArea, function (k, v) {
                        html += `<option value="${v['unique_code']}">${v['name']}</option>`;
                    });
                });
            }

            $selWorkArea.html(html);
        }

        $(function () {
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });

            $('.select2').select2();

            fnFillWorkshops();
        });

        /**
         * 退回前一页
         */
        function fnBack() {
            location.history(-1);
        }

        /**
         * 新建
         */
        function fnUpdate() {
            $.ajax({
                url: "{{url('account')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (res) {
                    console.log('success:', res);
                    let {data} = res;
                    location.href = `{{ url('account') }}/${data['account']['id']}/edit?page={{ request('page',1) }}`;
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection

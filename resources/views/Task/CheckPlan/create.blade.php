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
            现场检修计划
            <small>新建</small>
        </h1>
        <ol class="breadcrumb">

        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-body box-defalut">
            <div class="row">
                <div class="col-md-12">
                    <form class="form-horizontal" id="frmStore">
                        <div class="box-header">
                            <h3 class="box-title">基本信息</h3>
                            <div class="box-tools pull-right"></div>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label" style="color: red">类型*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="type" class="form-control select2" style="width:100%;" onchange="selType(this.value)">
                                        @foreach($types as $key=>$typeName)
                                            <option value="{{$key}}">{{$typeName}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" style="color: #ff0000">项目名称*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="check_project_id" id="check_project_id" class="select2 form-control" style="width:100%;">
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" style="color: red">车间*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="workshop_unique_code" id="selWorkshop" class="select2 form-control" style="width:100%;" onchange="fnSelWorkshop(this.value)">
                                        <option value="">全部</option>
                                        @foreach($sceneWorkshops as $workshopUniqueCode=>$workshopName)
                                            <option value="{{ $workshopUniqueCode }}">{{ $workshopName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" style="color: red">车站*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="station_unique_code" id="selStation" class="select2 form-control" style="width:100%;">

                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" style="color: #ff0000">任务时间：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input class="form-control" id="expiring_at" name="expiring_at" type="text" placeholder="任务时间" value="" autofocus="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">单位：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input class="form-control" name="unit" type="text" placeholder="单位" value="" autofocus="">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="box-footer">
                <a href="{{url('task/checkPlan')}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                <a href="javascript:" onclick="fnStore()" class="btn btn-success btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>添加</a>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/EasyWeb/spa/assets/libs/layui/layui.js"></script>
    <script>
        let $select2 = $(".select2");
        layui.config({
            base: '/EasyWeb/spa/assets/module/'
        }).use(['layer', 'laydate'], function () {
            let laydate = layui.laydate;
            laydate.render({
                elem: '#expiring_at',
                trigger: 'click',
                range: false,
                type: 'month'
            });
        });

        $(function () {
            if ($select2.length > 0) $select2.select2();
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });
            selType(`1`);
            fnSelWorkshop(`${$('#selWorkshop').val()}`);
        });


        /**
         * 选择计划类型
         * @param typeKey
         */
        function selType(typeKey) {
            let html = ``;
            $.ajax({
                url: `{{ url('task/checkProject/project') }}`,
                type: 'get',
                data: {
                    type: typeKey
                },
                async: false,
                success: response => {
                    console.log(`success:`, response);
                    $.each(response['data'], function (k, project) {
                        html += `<option value="${project['id']}">${project['name']}</option>`;
                    });
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
            $('#check_project_id').html(html);
        }

        /**
         * 选择车间
         */
        function fnSelWorkshop(workshopUniqueCode) {
            let html = `<option value="">全部</option>`;
            $.ajax({
                url: `{{ url('maintain/station') }}`,
                type: 'get',
                data: {
                    scene_workshop_unique_code: workshopUniqueCode
                },
                async: false,
                success: response => {
                    console.log(`success:`, response);
                    $.each(response['data'], function (k, station) {
                        html += `<option value="${station['unique_code']}">${station['name']}</option>`;
                    });
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
            $('#selStation').html(html);
        }

        /**
         * 创建计划
         */
        function fnStore() {
            $.ajax({
                url: "{{url('task/checkPlan')}}",
                type: 'post',
                data: $("#frmStore").serialize(),
                success: function (response) {
                    console.log(`success：`, response)
                    location.href = "{{url('task/checkPlan')}}";
                },
                error: function (error) {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
        }
    </script>
@endsection

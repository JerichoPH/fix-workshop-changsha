@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
      <h1>
        部件管理
        <small>批量导入</small>
      </h1>
{{--      <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li><a href="{{url('part/instance')}}"><i class="fa fa-users">&nbsp;</i>部件管理</a></li>--}}
{{--        <li class="active">批量导入</li>--}}
{{--      </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">添加部件批量导入</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <div class="box-body">
                @include('Layout.alert')
                <form class="form-horizontal" action="{{url('part/instance/batch')}}" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">供应商：</label>
                        <div class="col-sm-10 col-md-9">
                            <select name="factory_name" class="form-control select2" style="width: 100%;">
                                @foreach($factories as $factory)
                                    <option value="{{$factory->name}}">{{$factory->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">种类：</label>
                        <div class="col-sm-10 col-md-9">
                            <select id="selCategoryUniqueCode" name="category_unique_code" class="form-control select2" style="width: 100%;" onchange="fnGetEntireModelByCategoryUniqueCode()">
                                <option value="">请选择</option>
                                @foreach($categories as $category)
                                    <option value="{{$category->unique_code}}">{{$category->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">类型：</label>
                        <div class="col-sm-10 col-md-9">
                            <select id="selEntireModelUniqueCode" name="entire_model_unique_code" class="form-control select2" style="width: 100%;" onchange="fnGetPartModelByEntireModelUniqueCode()"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">型号：</label>
                        <div class="col-sm-10 col-md-9">
                            <select id="selPartModelUniqueCode" name="part_model_unique_code" class="form-control select2" style="width: 100%;" onchange="fnGetPartModelByEntireModelUniqueCode()"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">文件：</label>
                        <div class="col-sm-10 col-md-9">
                            <input type="file" name="part" class="form-control">
                        </div>
                    </div>
                    <div class="box-footer">
                        <a href="{{url('/part/instance')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <button class="btn btn-success btn-flat pull-right"><i class="fa fa-sign-in">&nbsp;</i>导入</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script src="/AdminLTE/bower_components/select2/dist/js/select2.full.min.js"></script>
    <!-- iCheck 1.0.1 -->
    <script src="/AdminLTE/plugins/iCheck/icheck.min.js"></script>
    <!-- bootstrap datepicker -->
    <script src="/AdminLTE/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
            //Date picker
            $('#datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd'
            });
        });

        /**
         * 根据种类获取型号列表
         */
        fnGetEntireModelByCategoryUniqueCode = function () {
            $.ajax({
                url: "{{url('entire/model')}}",
                type: "get",
                data: {type: 'category_unique_code', category_unique_code: $("#selCategoryUniqueCode").val()},
                async: true,
                success: function (response) {
                    html = '';
                    for (let i in response) {
                        html += '<option value="' + response[i].unique_code + '">' + response[i].name + '</option>';
                    }
                    $("#selEntireModelUniqueCode").html(html);
                    fnGetPartModelByEntireModelUniqueCode();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status == 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        /**
         * 根据整件类型获取部件型号
         */
        fnGetPartModelByEntireModelUniqueCode = function () {
            $.ajax({
                url: "{{url('part/model')}}",
                type: "get",
                data: {type: 'entire_model_unique_code', entire_model_unique_code: $("#selEntireModelUniqueCode").val()},
                async: true,
                success: function (response) {
                    html = '';
                    for (let i in response) {
                        html += '<option value="' + response[i].part_model.unique_code + '">' + response[i].part_model.name + '</option>';
                    }
                    $("#selPartModelUniqueCode").html(html);
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status == 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };
    </script>
@endsection

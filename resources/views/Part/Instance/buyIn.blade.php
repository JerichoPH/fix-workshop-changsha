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
            设备入所
            <small>部件采购导入</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('/warehouse/report/scanInBatch') }}?type=IN">设备导入</a></li>--}}
{{--            <li class="active">部件采购导入</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">部件采购导入</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right">
                    <div class="btn-group btn-group-sm">
                        <a href="javascript:" onclick="location.href=`/部件采购导入模板.xlsx.zip?v=${Math.random()}`" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-download">&nbsp;</i>下载部件采购导入模板</a>
                    </div>
                </div>
            </div>
            <br>
            <div class="box-body">
                @include('Layout.alert')
                <form class="form-horizontal" action="{{ url('part/instance/buyIn') }}" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">上传文件：</label>
                        <div class="col-sm-10 col-md-9">
                            <input type="file" name="part">
                        </div>
                    </div>
                    <div class="box-footer">
                        <a href="{{ url('/warehouse/report/scanInBatch') }}?type=IN" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <button type="submit" class="btn btn-success btn-flat pull-right"><i class="fa fa-upload">&nbsp;</i>导入</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@section('script')
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
                    if (error.status === 401) location.href = "{{ url('login') }}";
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
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };
    </script>
@endsection

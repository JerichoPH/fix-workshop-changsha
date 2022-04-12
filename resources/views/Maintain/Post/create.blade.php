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
        台账管理
        <small>新建</small>
      </h1>
{{--      <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li><a href="{{url('maintain')}}"><i class="fa fa-users">&nbsp;</i>台账管理</a></li>--}}
{{--        <li class="active">新建</li>--}}
{{--      </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">新建台账</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <div class="box-body">
                <form class="form-horizontal" id="frmCreate">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">统一代码：</label>
                        <div class="col-sm-10 col-md-8">
                            <input placeholder="统一代码" class="form-control" type="text" onkeydown="if(event.keyCode==13){return false;}" required
                                   name="unique_code" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">说明：</label>
                        <div class="col-sm-10 col-md-8">
                            <textarea placeholder="说明" name="explain" cols="30" rows="5" class="form-control" onkeydown="if(event.keyCode==13){return false;}"></textarea>
                        </div>
                    </div>
                    <div class="box-footer">
{{--                        <a href="{{url('maintains')}}" class="btn btn-default pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                        <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <a href="javascript:" onclick="fnCreate()" class="btn btn-success pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
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
    <script>
        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
        });

        /**
         * 新建
         */
        fnCreate = function () {
            $.ajax({
                url: "{{url('maintains')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    alert(response);
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

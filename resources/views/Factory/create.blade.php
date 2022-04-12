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
            供应商管理
            <small>新建</small>
        </h1>
        {{--      <ol class="breadcrumb">--}}
        {{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--        <li><a href="{{url('factory')}}"><i class="fa fa-users">&nbsp;</i>供应商管理</a></li>--}}
        {{--        <li class="active">新建</li>--}}
        {{--      </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">添加供应商</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <div class="box-body">
                <form class="form-horizontal" id="frmCreate">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">名称：</label>
                        <div class="col-sm-10 col-md-9">
                            <input placeholder="名称" class="form-control input-lg" type="text" required autofocus onkeydown="if(event.keyCode==13){return false;}"
                                   name="name" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">联系电话：</label>
                        <div class="col-sm-10 col-md-9">
                            <input placeholder="联系电话" class="form-control input-lg" type="text" required autofocus onkeydown="if(event.keyCode==13){return false;}"
                                   name="phone" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">网址：</label>
                        <div class="col-sm-10 col-md-9">
                            <input placeholder="网址" class="form-control input-lg" type="text" required autofocus onkeydown="if(event.keyCode==13){return false;}"
                                   name="official_home_link" value="">
                        </div>
                    </div>
                    <div class="box-footer">
                        {{--                        <a href="{{url('factory')}}?page={{request('page',1)}}" class="btn btn-default pull-left btn-flat btn-lg"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                        <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default pull-left btn-flat btn-lg"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <a href="javascript:" onclick="fnCreate()" class="btn btn-success pull-right btn-flat btn-lg"><i class="fa fa-check">&nbsp;</i>新建</a>
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
                url: `{{ url('factory') }}`,
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    alert(response);
                    location.href = `{{ url('factory') }}?page={{ request('page',1) }}`;
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.responseText === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

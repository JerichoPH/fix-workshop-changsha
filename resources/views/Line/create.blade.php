@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
      <h1>
        线别管理
        <small>新建</small>
      </h1>
{{--      <ol class="breadcrumb">--}}
{{--          <li><a href="/"><i class="fa fa-home"></i> 首页</a></li>--}}
{{--          <li class="active">线别管理</li>--}}
{{--          <li class="active">新建</li>--}}
{{--      </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">新建线路</h3>
                {{--右侧最小化按钮--}}
            </div>
            <br>
            <form class="form-horizontal" id="frmCreate">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">名称：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="name" type="text" class="form-control" placeholder="名称" required value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">线别编码：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="unique_code" type="text" class="form-control" placeholder="线别编码" required value="{{ $lineUniqueCode }}" disabled>
                        </div>
                        <input name="unique_code" type="hidden" value="{{ $lineUniqueCode }}">
                    </div>
                </div>
                <div class="box-footer">
                    <a href="javascript:history.back(-1)" class="btn btn-default pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    <a href="javascript:" onclick="fnCreate()" class="btn btn-success pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script src="/AdminLTE/bower_components/select2/dist/js/select2.full.min.js"></script>
    <script>
        $(function () {
            $('.select2').select2();
        });

        /**
         * 新建
         */
        fnCreate = function () {
            $.ajax({
                url: "{{url('line')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    alert(response);
                    location.href = `{{ url('line') }}`;
                },
                error: function (error) {
                    // console.log('fail:', error);
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

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
        <small>编辑</small>
      </h1>
{{--      <ol class="breadcrumb">--}}
{{--          <li><a href="/"><i class="fa fa-home"></i> 首页</a></li>--}}
{{--          <li class="active">基础数据</li>--}}
{{--          <li class="active">线别编辑</li>--}}
{{--      </ol>--}}
    </section>
    <section class="content">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">保存线别</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                    <button type="button" class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <br>
            <form class="form-horizontal" id="frmUpdate">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">名称：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="name" type="text" class="form-control" placeholder="{{ $line[0]->name }}" required value="{{ $line[0]->name }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">线别编码：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="unique_code" type="text" class="form-control" placeholder="{{ $line[0]->unique_code }}" required value="{{ $line[0]->unique_code }}" disabled>
                        </div>
                        <input name="unique_code" type="hidden" value="{{ $line[0]->unique_code }}">
                    </div>
                </div>
                <div class="box-footer">
{{--                    <a href="{{url('line')}}" class="btn btn-default pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    <a href="javascript:" onclick="fnUpdate({{ $line[0]->id }})" class="btn btn-warning pull-right"><i class="fa fa-check">&nbsp;</i>保存</a>
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
         * 保存
         */
        fnUpdate = function (id) {
            $.ajax({
                url: `{{url('line')}}/${id}`,
                type: "put",
                data: $("#frmUpdate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    alert(response);
                    location.href = "{{url('line')}}?page={{request('page')}}";
                },
                error: function (error) {
                    // console.log('fail:', error);
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

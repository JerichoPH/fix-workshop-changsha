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
            种类管理
            <small>编辑</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('category')}}?page={{request('page',1)}}"><i class="fa fa-users">&nbsp;</i>种类管理</a></li>--}}
{{--            <li class="active">编辑</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">保存设备种类</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <form class="form-horizontal" id="frmUpdate">
                <div class="form-group">
                    <label class="col-sm-3 control-label">名称：</label>
                    <div class="col-sm-10 col-md-8">
                        <input
                            placeholder="名称"
                            class="form-control"
                            type="text"
                            required
                            autofocus
                            onkeydown="if(event.keyCode===13){return false;}"
                            name="name"
                            value="{{$category->name}}"
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">唯一标识：</label>
                    <div class="col-sm-10 col-md-8">
                        <input
                            placeholder="唯一标识"
                            disabled
                            class="disabled form-control"
                            type="text"
                            required
                            onkeydown="if(event.keyCode===13){return false;}"
                            name="unique_code"
                            value="{{$category->unique_code}}"
                        >
                    </div>
                </div>
                <div class="box-footer">
{{--                    <a href="{{url('category')}}?page={{request('page',1)}}" class="btn btn-default pull-left btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default pull-left btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    <a href="javascript:" onclick="fnUpdate()" class="btn btn-warning pull-right btn-flat"><i class="fa fa-check">&nbsp;</i>保存</a>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select();
        });

        /**
         * 保存
         */
        fnUpdate = function () {
            $.ajax({
                url: "{{url('category',$category->id)}}",
                type: "put",
                data: $("#frmUpdate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    alert(response);
                    location.href = "{{url('category')}}?page{{request('page',1)}}";
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

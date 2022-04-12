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
            盘点管理
            <small>新建</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('warehouse/storage/stock')}}"><i class="fa fa-users">&nbsp;</i>盘点管理</a></li>--}}
{{--            <li class="active">新建</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">新建盘点</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
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
                        <label class="col-sm-2 control-label">所属机构：</label>
                        <div class="col-sm-10 col-md-8">
                            <select name="organization_id" class="form-control select2">
                                @foreach($organizations as $organization)
                                    <option value="{{$organization->id}}">{{$organization->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
{{--                    <a href="{{url('warehouse/storage/stock')}}" class="btn btn-default pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
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
                url: "{{url('warehouse/storage/stock')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

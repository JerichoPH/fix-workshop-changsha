@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备/器材管理
            <small>赋码</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li><a href="{{ url('entire/instance') }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>设备管理</a></li>--}}
        {{--    <li class="active">新建</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form class="form-horizontal" id="frmStore">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">新建设备</h3>
                    {{--右侧最小化按钮--}}
                    <div class="btn-group btn-group-sm pull-right"></div>
                </div>
                <br>
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">名称：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="name" type="text" class="form-control" placeholder="名称" required value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">种类：</label>
                        <div class="col-sm-10 col-md-8">
                            <select name="category_unique_code" class="form-control select2" style="width: 100%;">
                                @foreach($categories as $category)
                                    <option value="{{ $category->unique_code }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="from-group">

                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ url('entire/instance') }}?page={{ request('page',1) }}" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>
                    <a onclick="fnStore()" class="btn btn-success btn-flat btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
                </div>
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 新建
         */
        function fnStore() {
            $.ajax({
                url: `{{ url('entire/instance') }}`,
                type: "post",
                data: $("#frmStore").serialize(),
                success: function (res) {
                    console.log(`{{ url('entire/instance') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('entire/instance') }} fail:`, err);
                    if (err.responseText === 401) location.href = "{{ url('login') }}";
                    if (err.responseJSON.msg.constructor === Object) {
                        let message = '';
                        for (let msg of err.responseJSON.msg) message += `${msg}\r\n`;
                        alert(message);
                        return;
                    }
                    alert(err.responseJSON.msg);
                }
            });
        }
    </script>
@endsection

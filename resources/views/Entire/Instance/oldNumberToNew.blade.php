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
    <section class="content">
        <!-- 面包屑 -->
        <section class="content-header">
            <h1>
              整件管理
              <small>旧编号换取新编号</small>
            </h1>
{{--            <ol class="breadcrumb">--}}
{{--              <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--              <li><a href="{{url('entire/instance')}}"><i class="fa fa-users">&nbsp;</i>整件管理</a></li>--}}
{{--              <li class="active">旧编号换取新编号</li>--}}
{{--            </ol>--}}
          </section>
        @include('Layout.alert')
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">旧编码设备列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="javascript:" class="btn btn-lg btn-flat btn-default" onclick="fnOldNumberToNew()">批量转换</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>型号</th>
                        <th>类型</th>
                        <th>所编号</th>
                        <th>供应商</th>
                        <th>厂编号</th>
                        <th>安装位置</th>
                        <th>安装时间</th>
                        <th>主/备用</th>
                        <th>状态</th>
{{--                        <th>在库状态</th>--}}
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($entireInstances as $entireInstance)
                        <tr>
                            <td>{{$entireInstance->id}}</td>
                            <td><a href="{{url('search',$entireInstance->identity_code)}}">{{$entireInstance->EntireModel ? $entireInstance->EntireModel->name.'（'.$entireInstance->entire_model_unique_code.'）' : ''}}</a></td>
                            <td>{{$entireInstance->Category->name."&nbsp;".$entireInstance->EntireModel->name}}</td>
                            <td>{{$entireInstance->serial_number}}</td>
                            <td>{{$entireInstance->factory_name}}</td>
                            <td>{{$entireInstance->factory_device_code}}</td>
                            <td>{{$entireInstance->maintain_station_name.'&nbsp;'.$entireInstance->maintain_location_code}}</td>
                            <td>{{$entireInstance->last_installed_time ? date('Y-m-d',$entireInstance->last_installed_time) : ''}}</td>
                            <td>{{$entireInstance->is_main ? '主用' : '备用'}}</td>
                            <td>{{$entireInstance->status}}</td>
{{--                            <td>{{$entireInstance->in_warehouse ? '在库' : '库外'}}</td>--}}
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($entireInstances->hasPages())
                <div class="box-footer">
                    {{ $entireInstances->links() }}
                </div>
            @endif
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
         * 旧编码批量转新编码
         */
        fnOldNumberToNew = function () {
            $.ajax({
                url: "{{url('entire/instance/oldNumberToNew')}}",
                type: "post",
                data: {},
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.reload();
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

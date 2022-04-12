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
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">部件列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('part/instance/batch')}}" class="btn btn-flat btn-default btn-lg">批量添加</a>
                </div>
            </div>
            <div class="box-body table-responsive" style="font-size: 18px;">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <td>唯一编号</td>
                        <td>入库时间</td>
                        <td>供应商</td>
                        <td>出厂编号</td>
                        <td>种类</td>
                        <td>类型</td>
                        <td>型号</td>
                        <td>状态</td>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($partInstances as $partInstance)
                        <tr>
                            <td>{{$partInstance->identity_code}}</td>
                            <td>{{$partInstance->updated_at}}</td>
                            <td>{{$partInstance->factory_name}}</td>
                            <td>{{$partInstance->factory_device_code}}</td>
                            <td>{{$partInstance->Category ? $partInstance->Category->name : ''}}</td>
                            <td>{{$partInstance->EntireModel ? $partInstance->EntireModel->name : ''}}</td>
                            <td>{{$partInstance->part_model_name}}({{$partInstance->part_model_unique_code}})</td>
                            <td>{{$partInstance->status}}</td>
                            <td>
                                <a href="{{route('part-instance.fixWorkflowRecode.get',$partInstance->identity_code)}}" class="btn btn-warning btn-flat btn-lg">检</a>
                                <a href="javascript:" onclick="fnDelete('{{$partInstance->identity_code}}')" class="btn btn-danger btn-flat btn-lg">删</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($partInstances->hasPages())
                <div class="box-footer">
                    {{ $partInstances->links() }}
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
         * 删除
         * @param {int} identityCode 编号
         */
        fnDelete = function (identityCode) {
            $.ajax({
                url: "{{url('part/instance')}}/" + identityCode,
                type: "delete",
                data: {},
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.reload();
                },
                error: function (error) {
                    console.log('fail:', error);
                }
            });
        };
    </script>
@endsection

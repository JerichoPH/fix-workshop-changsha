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
        @include('Layout.alert')
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">已绑定的数据列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
{{--                    <a href="{{url('measurement/fixWorkflowProcess',$fixWorkflowProcessSerialNumber)}}/edit?type={{request('type')}}&page={{request('page',1)}}" class="btn btn-lg btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-lg btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                </div>
            </div>
            <div class="box-body">
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-condensed table-hover">
                            <thead>
                            <tr>
                                <td>厂编号</td>
                                <td>部件型号</td>
                                <td>测试项</td>
                                <td>测试动作</td>
                                <td>实测值</td>
                                <td>绑定</td>
                            </tr>
                            </thead>
                            <tbody id="#tbodyFixWorkflowRecord">
                            @foreach($fixWorkflowRecords as $fixWorkflowRecord)
                                <tr>
                                    <td>{{$fixWorkflowRecord->PartInstance->factory_device_code}}</td>
                                    <td>{{$fixWorkflowRecord->PartInstance->PartModel->name}}</td>
                                    <td>{{$fixWorkflowRecord->Measurement->key}}</td>
                                    <td>{{$fixWorkflowRecord->Measurement->operation}}</td>
                                    <td>{{$fixWorkflowRecord->measured_value}}</td>
                                    <td><a href="javascript:" onclick="fnCancelBoundFixWorkflowProcess('{{$fixWorkflowRecord->serial_number}}')"><i class="fa fa-times" style="color: red;"></i></a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @if($fixWorkflowRecords->hasPages())
                <div class="box-footer">
                    {{ $fixWorkflowRecords->links() }}
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
         * 绑定测试数据和测试单
         * @param fixWorkflowRecordSerialNumber
         */
        fnCancelBoundFixWorkflowProcess = (fixWorkflowRecordSerialNumber) => {
            $.ajax({
                url: "{{url('measurement/fixWorkflowRecord/cancelBoundFixWorkflowProcess',$fixWorkflowProcessSerialNumber)}}",
                type: "post",
                data: {fixWorkflowRecordSerialNumber: fixWorkflowRecordSerialNumber},
                async: true,
                success: function (response) {
                    // console.log('success:', response);
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

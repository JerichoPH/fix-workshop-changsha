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
<div class="modal fade" id="modalChangePartInstance">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">部件更换管理</h4>
            </div>
            <div class="modal-body">
                <p>
                    型号：{{ $part_instance->PartModel->name }}&nbsp;&nbsp;
                    种类：{{ $part_instance->PartCategory->name }}&nbsp;&nbsp;
                    编号：{{ $part_instance->identity_code }}]
                    整件：{{ $entireInstanceIdentityCode }}
                </p>
                <div class="table-condensed table-responsive table-responsive-sm table-responsive-md table-responsive-lg table-responesive-xl">
                    <table class="table table-condensed table-hover">
                        <thead>
                        <tr>
                            <th>编号</th>
                            <th>仓库位置</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($part_instances as $part)
                            <tr>
                                <td>{{ $part->identity_code }}</td>
                                <td>{{ $part->location_unique_code }}</td>
                                <td>
                                    <a
                                        href="javascript:"
                                        onclick="fnChangePartInstance('{{ $part->identity_code }}')"
                                        class="btn btn-success btn-flat btn-sm">
                                        <i class="fa fa-check">&nbsp;</i>
                                        替换
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-right btn-sm btn-flat" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
            </div>
        </div>
    </div>
</div>
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

    /*
     * 更换部件实例
     * @params {int} newIdentityCode
     */
    fnChangePartInstance = function (newIdentityCode) {
        $.ajax({
            url: "{{route('changePartInstance.post')}}",
            type: "post",
            data: {
                entireInstanceIdentityCode: "{{$entireInstanceIdentityCode}}",
                old_identity_code: '{{ $part_instance->identity_code }}',
                new_identity_code: newIdentityCode,
                fixWorkflowSerialNumber: '{{ $fixWorkflowSerialNumber }}'
            },
            async: false,
            success: function (response) {
                // console.log('success:', response);
                // alert(response);
                location.reload();
            },
            error: function (error) {
                // console.log('fail:', error);
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.responseText);
            },
        });
    };
</script>

@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
@endsection
<div class="modal fade" id="modalStoreFixWorkflowProcess">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">新建检测单{{ $type == 'ENTIRE' ? '(整件)' : '(部件)'}}</h4>
            </div>
            <div class="modal-body table-responsive">
                <form id="frmStoreFixWorkflowProcess" class="form-horizontal">
                    <input type="hidden" name="fix_workflow_serial_number" value="{{request('fixWorkflowSerialNumber')}}">
                    <input type="hidden" name="type" value="{{request('type')}}">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">检测阶段：</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="stage" class="form-control select2" style="width: 100%;">
                                <option value="FIX_BEFORE">修前检</option>
                                <option value="FIX_AFTER">修后检</option>
                                @if(session('account.supervision') == 1)
                                    <option value="CHECKED">工区验收</option>
                                    <option value="WORKSHOP">车间抽验</option>
                                    <option value="SECTION">段抽验</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    @if($part_instances)
                    <div class="form-group">
                        <label class="col-sm-3 control-label">选择部件：</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="part_instance_identity_code" class="form-control select2" style="width: 100%;">
                                @foreach($part_instances as $part_instance)
                                    <option value="{{ $part_instance->identity_code }}">{{ $part_instance->PartCategory->name }}：{{ $part_instance->identity_code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                    <div class="form-group">
                        <label class="col-sm-3 control-label">备注：</label>
                        <div class="col-sm-8 col-md-8">
                            <textarea placeholder="备注" class="form-control" rows="5" type="text" name="note" value=""></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left btn-flat btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreWorkflowProcess()"><i class="fa fa-check">&nbsp;</i>保存</button>
            </div>
        </div>
    </div>
</div>
<script>

    $(function () {
        if ($('.select2').length > 0) {
            $('.select2').select2();
        }
        // iCheck for checkbox and radio inputs
        if ($('input[type="checkbox"].minimal, input[type="radio"].minimal').length > 0) {
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
        }

        $('#datepicker').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
        });
    });

    /*
     * 新建工单操作
     * @param {string} fixWorkflowSerialNumber 工单流水号
     */
    fnStoreWorkflowProcess = function () {
        $.ajax({
            url: "{{ url('measurement/fixWorkflowProcess') }}",
            type: "post",
            data: $("#frmStoreFixWorkflowProcess").serialize(),
            success: function (response) {
                console.log('success:', response);
                location.href = "{{ url('measurement/fixWorkflowProcess') }}/" + response + "/edit?type={{ request('type') }}" + "&fixWorkflowType={{ request('fixWorkflowType') }}";
            },
            error: function (error) {
                // console.log('fail:', error);
                if (error.responseText === 401) location.href = "{{ url('login') }}";
                alert(error.responseText);
            }
        });
    };
</script>

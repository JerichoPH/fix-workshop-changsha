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
<div class="modal fade" id="modalEntireModelAndPartModelBindingNumber">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">部件类型绑定数量管理</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmEntireModelAndPartModelBindingNumber">
                    @foreach($pivotEntireModelAndPartModels as $pivotEntireModelAndPartModel)
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{$pivotEntireModelAndPartModel->PartModel->name}}：</label>
                            <div class="col-sm-8 col-md-8">
                                <input class="form-control" type="text" required onkeydown="if(event.keyCode==13){return false;}"
                                       name="{{$pivotEntireModelAndPartModel->part_model_unique_code}}" placeholder="数量" value="{{$pivotEntireModelAndPartModel->number}}">
                            </div>
                        </div>
                    @endforeach
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat" onclick="fnUpdateEntireModelAndPartModelBindingNumber()"><i class="fa fa-check">&nbsp;</i>保存</button>
            </div>
        </div>
    </div>
</div>
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

    /*
     * 部件类型绑定数量管理
     */
    fnUpdateEntireModelAndPartModelBindingNumber = function () {
        $.ajax({
            url: "{{url('pivotEntireModelAndPartModel',$entireModelUniqueCode)}}",
            type: "put",
            data: $("#frmEntireModelAndPartModelBindingNumber").serialize(),
            async: false,
            success: function (response) {
                console.log('success:', response);
                // alert(response);
                // location.reload();
            },
            error: function (error) {
                // console.log('fail:', error);
                if (error.status == 401) location.href = "{{ url('login') }}";
                alert(error.responseText);
            },
        });
    };
</script>

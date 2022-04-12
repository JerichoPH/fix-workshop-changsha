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
<div class="modal fade" id="modalMakeSubTaskFinish">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">工区任务完成<h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmPutSubTaskFinish">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">工作汇报：</label>
                        <div class="col-sm-8 col-md-8">
                            <textarea id="txaFinishMessage" name="content" rows="10" cols="80"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i
                        class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnPutSubTaskFinish()"><i
                        class="fa fa-check">&nbsp;</i>任务完成</button>
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
        // 出所时间
        $('#datepicker').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });

        // 初始化 ckeditor
        CKEDITOR.replace('txaFinishMessage', {
            toolbar: [
                {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']},
                {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                {name: 'colors', items: ['TextColor', 'BGColor']},
                {name: 'tools', items: ['Maximize', 'ShowBlocks']}
            ]
        });
    });

    /**
     * 标注任务已完成
     */
    function fnPutSubTaskFinish() {
        let data = {
            finish_message:CKEDITOR.instances['txaFinishMessage'].getData(),
        }
        $.ajax({
            url: `{{ url('temporaryTask/production/sub/makeSubTaskFinish',$sub_task_id) }}`,
            type: 'put',
            data: data,
            success: function (res) {
                console.log(`{{ url('temporaryTask/production/sub/makeSubTaskFinish',$sub_task_id) }} success:`, res);
                // alert(res['message']);
                location.href = `{{ url('temporaryTask/production/sub',$sub_task_id) }}`;
            },
            error: function (err) {
                console.log(`{{ url('temporaryTask/production/sub/makeSubTaskFinish',$sub_task_id) }} fail:`, err);
                if (err.status === 401) return location.href = `{{ url('login') }}`;
                alert(err['responseJSON']['message']);
            }
        });
    }
</script>

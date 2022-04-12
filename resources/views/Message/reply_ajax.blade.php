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
<div class="modal fade" id="modalReply">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">回复消息<h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmReply">
                    <input type="hidden" id="hdnSenderId" value="{{request('sender_id')}}">
                    <input type="hidden" id="hdnSenderAffiliation" value="{{request('sender_affiliation')}}">
                    <input type="hidden" id="hdnReceiverId" value="{{request('receiver_id')}}">
                    <input type="hidden" id="hdnReceiverAffiliation" value="{{request('receiver_affiliation')}}">
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">收件人：</label>
                        <div class="col-sm-9 col-md-8">
                            <input type="text" class="form-control" name="receiver_name" value="{{request('receiver_affiliation_name')}}:{{request('receiver_name')}}" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">内容：</label>
                        <div class="col-sm-9 col-md-8">
                            <textarea id="txaContent" name="content" rows="10" cols="80"></textarea>
                        </div>
                    </div>
                    {{--<div class="form-group">
                        <label class="col-sm-3 control-label">出所日期：</label>
                        <div class="col-sm-10 col-md-8">
                            <div class="input-group date">
                                <div class="input-group-addon" style="font-size: 18px;"><i class="fa fa-calendar"></i></div>
                                <input name="processed_at" type="text" class="form-control pull-right input-lg" id="datepicker" value="{{date('Y-m-d')}}">
                            </div>
                        </div>
                    </div>--}}
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnReply()"><i class="fa fa-envelope-o">&nbsp;</i>确定</button>
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
        CKEDITOR.replace('txaContent', {
            toolbar: [
                // {name: 'document', items: ['Source', '-', 'Save', 'NewPage', 'DocProps', 'Preview', 'Print', '-', 'Templates']},
                // {name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']},
                // {name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt']},
                // {name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']},
                // '/',
                // {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                // {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']},
                // {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
                // {name: 'insert', items: ['Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']},
                // '/',
                // {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                // {name: 'colors', items: ['TextColor', 'BGColor']},
                // {name: 'tools', items: ['Maximize', 'ShowBlocks', '-', 'About']}
            ]
        });
    });

    /**
     * 打开回复消息窗口
     */
    function fnReply() {
        $.ajax({
            url: `{{url('message')}}`,
            type: 'post',
            data: {
                title: "回复：{{request('title')}}",
                receiver_id: $('#hdnSenderId').val(),
                receiver_affiliation: $('#hdnSenderAffiliation').val(),
                content: CKEDITOR.instances['txaContent'].getData(),
            },
            async: true,
            success: res => {
                console.log(`{{url('message')}} success:`, res);
                alert(res['message']);
                location.reload();
            },
            error: err => {
                console.log(`{{url('message')}} fail:`, err);
                if (err['status'] === 401) location.href = "{{ url('login') }}";
                alert(err['responseJSON']['message']);
            }
        });
    }
</script>

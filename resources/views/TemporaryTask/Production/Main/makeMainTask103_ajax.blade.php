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
<div class="modal fade" id="modalMakeMainTask">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">下达任务到检修基地<h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmStoreMakeSubTask103">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">检修基地负责人：</label>
                        <div class="col-sm-8 col-md-8">
                            <select id="selReceiverId" name="receiver_id" class="form-control select2 input-lg" style="width:100%;">
                                @foreach($accounts  as $account_id => $account_nickname)
                                    @if($account_id !== session('account.id'))
                                        <option value="{{ $account_id }}">{{ $account_nickname }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">追加说明：</label>
                        <div class="col-sm-8 col-md-8">
                            <textarea id="txaContent103" name="content" rows="10" cols="80" class="form-control"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreMakeMainTask103()"><i class="fa fa-check">&nbsp;</i>下达</button>
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

        let tomorrow = moment().add(1, 'days').format('YYYY-MM-DD');
        // 出所时间
        $('#datepicker').datepicker({
            format: "yyyy-mm-dd",
            language: "cn",
            clearBtn: true,
            autoclose: true,
            startDate: tomorrow,
            endData: '9999-12-31',
        });

        // 初始化 ckeditor
        CKEDITOR.replace('txaContent103', {
            toolbar: [
                // {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                // {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']},
                // {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                // {name: 'colors', items: ['TextColor', 'BGColor']},
                // {name: 'tools', items: ['Maximize', 'ShowBlocks']}
            ]
        });
    });

    /**
     * 下达子任务103
     */
    function fnStoreMakeMainTask103() {
        if (confirm('下达任务不可逆，确认下达？'))
            $.ajax({
                url: `{{ url('temporaryTask/production/main/makeMainTask103',$main_task['id']) }}`,
                type: 'post',
                data: {
                    main_task_title: "{{ $main_task['title'] }}",
                    paragraph_workshop_id: $('#selReceiverId').val(),
                    message: CKEDITOR.instances['txaContent103'].getData(),
                },
                async: true,
                success: res => {
                    console.log(`{{ url('temporaryTask/production/makeMainTask104') }} success:`, res);
                    location.reload();
                },
                error: err => {
                    console.log(`{{url('temporaryTask/production/makeMainTask')}} fail:`, err);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
    }
</script>

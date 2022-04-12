@section('style')

@endsection
<div class="modal fade" id="modalInstall">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">出库安装（出入所）</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmStoreInstall">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">联系人：</label>
                        <div class="col-sm-10 col-md-8">
                            <input class="form-control" type="text" autofocus onkeydown="if(event.keyCode===13){return false;}" name="connection_name" placeholder="联系人" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">联系电话：</label>
                        <div class="col-md-8">
                            <input class="form-control" type="text" autofocus onkeydown="if(event.keyCode===13){return false;}" name="connection_phone" placeholder="电话" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">经办人：</label>
                        <div class="col-sm-10 col-md-8">
                            <input type="hidden" name="processor_id" value="{{ session('account.id') }}">
                            <input class="form-control input-lg" type="text" name="" placeholder="经办人" value="{{session('account.account')}}" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">出所日期：</label>
                        <div class="col-sm-10 col-md-8">
                            <div class="input-group date">
                                <div class="input-group-addon" style="font-size: 18px;"><i class="fa fa-calendar"></i></div>
                                <input name="processed_at" type="text" class="form-control pull-right" id="datepicker" value="{{date('Y-m-d')}}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreInstall()"><i class="fa fa-check">&nbsp;</i>确定</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        // 出所时间
        $('#datepicker').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });

    });

    /*
     * 出库安装
     */
    fnStoreInstall = function () {
        $.ajax({
            url: "{{url('warehouse/breakdownOrder/out')}}",
            type: "post",
            data: $("#frmStoreInstall").serialize(),
            async: false,
            success: function (response) {
                if (response.status === 200) {

                } else {
                    alert(response.message);
                }
                location.reload();
            },
            error: function (error) {
                alert(error.responseText);
                // console.log('fail:', error);
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.responseText);
            },
        });
    };
</script>

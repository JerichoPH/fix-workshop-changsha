<div class="modal fade" id="modalCreateBreakdownLogAsWarehouseIn">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">入所故障描述</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmStoreCreateBreakdownLogAsWarehouseIn">
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">名称：</label>
                        <div class="col-sm-9 col-md-8">
                            <input type="text" class="form-control" name="name" value="">
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreCreateBreakdownLogAsWarehouseIn()"><i class="fa fa-check">&nbsp;</i>确定</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('.select2').select2();
    });

    /**
     * 入所故障描述
     */
    function fnStoreCreateBreakdownLogAsWarehouseIn() {
        $.ajax({
            url: `{{ url('breakdownLog') }}?type=WAREHOUSE_IN`,
            type: 'post',
            data: $('#frmStoreCreateBreakdownLogAsWarehouseIn').serialize(),
            async: true,
            success: function (res) {
                console.log(`{{ url('breakdownLog') }}?type=WAREHOUSE_IN success:`, res);
                location.reload();
            },
            error: function (err) {
                console.log(`{{ url('breakdownLog') }}?type=WAREHOUSE_IN fail:`, err);
                if (err.status === 401) location.href = "{{ url('login') }}";
                if (err['responseJSON']['message'].constructor === Object) {
                    let message = '';
                    for (let msg of err['responseJSON']['message']) message += `${msg}\r\n`;
                    alert(message);
                    return;
                }
                alert(err['responseJSON']['message']);
            }
        });
    }
</script>

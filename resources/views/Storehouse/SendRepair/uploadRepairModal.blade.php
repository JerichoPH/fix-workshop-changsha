<div class="modal fade" id="modalUploadRepair">
    <form action="{{ url('storehouse/sendRepair/uploadRepair') }}?send_repair_unique_code={{$send_repair_unique_code}}&material_unique_code={{$material_unique_code}}" method="post" enctype="multipart/form-data">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">上传报告 记录<h4>
                </div>
                <div class="modal-body form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">文件：</label>
                        <div class="col-sm-9 col-md-8">
                            <input type="file" name="file">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    <button type="submit" class="btn btn-success btn-flat pull-right btn-sm"><i class="fa fa-upload">&nbsp;</i>上传</button>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    $(function () {
        $('.select2').select2();
    });
</script>

@section('style')
@endsection
<div class="modal fade" id="modalUploadCheck">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">上传检修记录</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form action="{{url('measurement/fixWorkflow/uploadCheck')}}?fixWorkflowSerialNumber={{ $fixWorkflowSerialNumber }}&entireInstanceIdentityCode={{ $entireInstanceIdentityCode }}" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">检测文件：</label>
                        <div class="col-sm-9 col-md-8">
                            <input type="file" name="file" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">是否合格：</label>
                        <div class="col-md-9">
                            <input type="radio" name="is_allow" value="1" checked>合格
                            <input type="radio" name="is_allow" value="0">不合格
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label">阶段：</label>
                        <div class="col-sm-10 col-md-8">
                            <select name="stage" class="form-control select2 input-lg" style="width:100%;">
                                @foreach($stages as $k=>$v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">说明：</label>
                        <div class="col-sm-3 col-md-8">
                            <textarea name="auto_explain" cols="30" rows="5" class="form-control input-lg"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-lg" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="submit" class="btn btn-success btn-flat btn-lg"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </form>
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

</script>

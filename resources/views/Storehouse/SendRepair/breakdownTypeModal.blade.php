<div class="modal fade bs-example-modal-lg" id="breakdownType">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">故障类型</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmBreakdownLog" style="font-size: 18px;">
                    <input type="hidden" name="identityCode" value="{{ $currentIdentityCode }}">
                    <input type="hidden" name="materialType" value="{{ $currentMaterialType }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="col-sm-2 col-md-2 control-label">故障类型：</label>
                            <div class="col-sm-10 col-md-10">
                                @if(!empty($breakdownTypes))
                                    <div class="table-responsive">
                                        <table class="table table-condensed table-striped">
                                            @foreach($breakdownTypes as $breakdownTypeId => $breakdownTypeName)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="breakdownTypeIds[]" {{in_array($breakdownTypeId,$tmpBreakdownTypeIds) ? 'checked':''}} class="breakdown-type-checkbox" value="{{ $breakdownTypeId }}">
                                                        <label class="control-label">{{ $breakdownTypeName }}</label>
                                                    </td>
                                                </tr>
                                                @endforeach
                                                </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 col-md-2 control-label">故障补充说明：</label>
                            <div class="col-sm-10 col-md-10">
                                <textarea name="repairDesc" cols="30" rows="5" class="form-control">{{ $tmpMaterial->repair_desc }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-lg" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-lg" onclick="fnStore()"><i class="fa fa-check">&nbsp;</i>保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('.select2').select2();

    });

    /**
     * 保存临时故障记录
     */
    function fnStore() {
        $.ajax({
            url: `{{url('storehouse/sendRepair/tmpBreakdownLog')}}`,
            type: 'post',
            data: $("#frmBreakdownLog").serialize(),
            async: true,
            success: response => {
                console.log(`success:`, response);
                if (response.status === 200) {
                    $("#breakdownType").modal("hide");
                } else {
                    alert(response.message);
                    location.reload();
                }
            },
            error: error => {
                console.log(`error:`, error);
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.message);
                location.reload();
            }
        });
    }
</script>

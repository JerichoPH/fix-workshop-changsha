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
<div class="modal fade" id="modalPointSwitchModifyLocation">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">绑定转辙机位置</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmPointSwitchModifyLocation">
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">现场车间：</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="scene_workshop_name" id="selSceneWorkshop" class="form-control select2" style="width: 100%;" onchange="fnFillStation(this.value)"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">车站：</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="station_name" id="selStation" class="form-control select2" style="width: 100%;">
                                <option value="">未选择</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">道岔：</label>
                        <div class="col-sm-8 col-md-8">
                            <input type="text" name="crossroad_number" id="txtCrossroadNumber" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat" onclick="fnStorePointSwitchModifyLocation('{{$identity_code}}')"><i class="fa fa-check">&nbsp;</i>确定</button>
            </div>
        </div>
    </div>
</div>
<script>
    let $selSceneWorkshop = $('#selSceneWorkshop');
    let $selStation = $("#selStation");
    let maintains = JSON.parse('{!! $maintains_json !!}');

    $(function () {
        $('.select2').select2();
        // iCheck for checkbox and radio inputs
        $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
        });

        let html = `<option value="">未选择</option>`;
        // 填充现场车间列表
        $.each(maintains, (sceneWorkshopName, stations) => {
            html += `<option value="${sceneWorkshopName}">${sceneWorkshopName}</option>`;
        });
        $selSceneWorkshop.html(html);
    });

    /**
     * 根据现场车间名称，刷新车站列表
     * @param {string} sceneWorkshopName
     */
    let fnFillStation = sceneWorkshopName => {
        let html = `<option value="">未选择</option>`;
        if (sceneWorkshopName !== '') {
            console.log(maintains[sceneWorkshopName]);
            $.each(maintains[sceneWorkshopName], (index, stationName) => {
                console.log(index, stationName);
                html += `<option value="${stationName}">${stationName}</option>`;
            });
            $selStation.html(html);
        } else {
            $selStation.html(html);
        }
    };

    /**
     * 保存道岔位置
     * @param {string} identityCode
     */
    let fnStorePointSwitchModifyLocation = identityCode => {
        $.ajax({
            url: `{{url('/warehouse/report/pointSwitchModifyLocation')}}/${identityCode}`,
            type: 'post',
            data: $('#frmPointSwitchModifyLocation').serialize(),
            async: true,
            success: response => {
                console.log(`{{url('/warehouse/report/pointSwitchModifyLocation')}} success:`, response);
                alert(response.message);
                if (response.status === 200) location.reload();
            },
            fail: error => {
                console.log(`{{url('/warehouse/report/pointSwitchModifyLocation')}} fail:`, error);
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.responseText);
            }
        });
    };
</script>

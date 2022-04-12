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
<div class="modal fade" id="modalInstall">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">出库安装（设备管理）</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmStoreInstall" style="font-size: 18px;">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">出所流水号：</label>
                        <div class="col-sm-10 col-md-8">
                            <input
                                class="form-control input-lg"
                                type="text"
                                autofocus
                                onkeydown="if(event.keyCode===13){return false;}"
                                name="serial_number"
                                placeholder="用于从老系统中生成的流水号"
                                value=""
                            >
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">联系人：</label>
                        <div class="col-sm-10 col-md-8">
                            <input
                                class="form-control input-lg"
                                type="text"
                                autofocus
                                onkeydown="if(event.keyCode===13){return false;}"
                                name="connection_name"
                                placeholder="联系人"
                                value=""
                            >
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">联系电话：</label>
                        <div class="col-md-8">
                            <input
                                class="form-control input-lg"
                                type="text"
                                autofocus
                                onkeydown="if(event.keyCode===13){return false;}"
                                name="connection_phone"
                                placeholder="电话"
                                value=""
                            >
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label">经办人：</label>
                        <div class="col-sm-10 col-md-8">
                            <input type="hidden" name="processor_id" value="{{ session('account.id') }}">
                            <label>{{ session('account.nickname') }}</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">现场车间：</label>
                        <div class="col-sm-10 col-md-8">
                            <select
                                id="selMaintainWorkshop"
                                name="maintain_workshop_name"
                                class="form-control select2 input-lg"
                                style="width:100%;"
                                onchange="fnGetStationNameByInstallModal(this.value)"
                            >
                                <option value="" selected>未选择</option>
                                @foreach($workshops as $workshop)
                                    <option value="{{$workshop->unique_code}}"
                                    @if(session('searchCondition.search_type') == 'entire') {{session('searchCondition.maintain_workshop_name') == $workshop->unique_code ? 'selected' : ''}} @endif>{{$workshop->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">站名：</label>
                        <div class="col-sm-10 col-md-8">
                            <select
                                id="selStationName"
                                name="maintain_station_name"
                                class="form-control select2"
                                style="width:100%;"
                            ></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">出所日期：</label>
                        <div class="col-sm-10 col-md-8">
                            <div class="input-group date">
                                <div class="input-group-addon" style="font-size: 18px;"><i class="fa fa-calendar"></i></div>
                                <input
                                    name="processed_at"
                                    type="text"
                                    class="form-control pull-right input-lg"
                                    id="datepicker"
                                    value="{{date('Y-m-d')}}"
                                >
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <a href="{{url('qrcode',$entireInstance->identity_code)}}" class="btn btn-default btn-lg btn-flat pull-left" target="_blank"><i class="fa fa-qrcode">&nbsp;</i>二维码</a>
                <a href="{{url('barcode',$entireInstance->identity_code)}}" class="btn btn-default btn-lg btn-flat pull-left" target="_blank"><i class="fa fa-barcode">&nbsp;</i>条形码 </a>
                <button type="button" class="btn btn-default btn-flat pull-left btn-lg" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat btn-lg" onclick="fnStoreInstall()"><i class="fa fa-check">&nbsp;</i>确定</button>
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

        // 预计上道时间
        $('#datepickerForecastInstallAt').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });
    });

    /**
     * 通过车间获取站名
     * @param workshopName
     */
    fnGetStationNameByInstallModal = (workshopName) => {
        if (workshopName !== '') {
            $.ajax({
                url: `{{url('maintain')}}`,
                type: "get",
                data: {
                    'type': 'STATION',
                    workshopName: workshopName
                },
                async: false,
                success: function (response) {
                    html = '';
                    $.each(response, function (index, item) {
                        html += `<option value="${item.name}">${item.name}</option>`;
                    });
                    $("#selStationName").html(html);
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }
    };

    /*
     * 出库安装
     */
    fnStoreInstall = function () {
        $.ajax({
            url: "{{url('entire/instance/install',$entireInstance->identity_code)}}",
            type: "post",
            data: $("#frmStoreInstall").serialize(),
            async: false,
            success: function (response) {
                // console.log('success:', response);
                // alert(response);
                location.reload();
            },
            error: function (error) {
                // console.log('fail:', error);
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.responseText);
            },
        });
    };
</script>

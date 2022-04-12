<div class="modal fade" id="stationReform">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">确认送修</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmStationReform" style="font-size: 18px;">
                    <input type="hidden" name="operator_id" value="{{session('account.id')}}">
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label">经办人：</label>
                        <div class="col-md-8">
                            <input class="form-control input-lg" type="text" autofocus name="account_name" placeholder="操作人" value="{{session('account.account')}}" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">现场车间：</label>
                        <div class="col-md-8">
                            <select id="selSceneWorkshop" name="scene_workshop_unqiue_code" required class="form-control select2" style="width:100%;" onchange="fnSelectSceneWorkshop(this.value)">
                                @foreach ($scene_workshops as $unique_code=>$name)
                                    <option value="{{$unique_code}}">{{$name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">车站：</label>
                        <div class="col-md-8">
                            <select id="selStation" name="station_unique_code" required class="form-control select2" style="width:100%;">

                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">类型：</label>
                        <div class="col-md-8">
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="type" value="NEW" checked>新站</label>
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="type" value="OLD">老站</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-lg" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-lg" onclick="store()"><i class="fa fa-check">&nbsp;</i>生成站改计划</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('.select2').select2();

        fnSelectSceneWorkshop(`{{$first_scene_workshop_unique_code}}`);
    });

    /**
     * 选择现场车间
     * @param scene_workshop_code
     */
    function fnSelectSceneWorkshop(scene_workshop_code) {
        let html = `<option value="">未选择</option>`;
        if (scene_workshop_code && scene_workshop_code !== '') {
            $.ajax({
                url: `{{url('maintain/station')}}`,
                type: 'get',
                data: {
                    scene_workshop_unique_code: scene_workshop_code
                },
                async: false,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        $.each(response.data, function (k, station) {
                            html += `<option value="${station.unique_code}">${station.name}</option>`;
                        });
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
        $("#selStation").html(html);
    }

    /**
     * 生成站改计划
     */
    function store() {
        $.ajax({
            url: `{{url('repairBase/stationReform')}}`,
            type: 'post',
            data: $("#frmStationReform").serialize(),
            async: true,
            success: response => {
                console.log(`success:`, response);
                if (response.status === 200) {
                    alert(response.data.message);
                    // window.location.href = response.data.return_url;
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

<div class="modal fade bs-example-modal-lg" id="sendRepair">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">确认送修</h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmSendRepari" action="{{url('storehouse/sendRepair')}}" method="post" enctype="multipart/form-data" style="font-size: 18px;">
                    <input type="hidden" name="account_id" value="{{session('account.id')}}">
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label">经办人：</label>
                        <div class="col-md-9">
                            <input class="form-control input-lg" type="text" autofocus name="account_name" placeholder="操作人" value="{{session('account.nickname')}}" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label text-danger">送修单位*：</label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <div class="input-group-addon">现场车间</div>
                                <select name="from_scene_workshop_code" id="from_scene_workshop_code" class="select2 form-control" required onchange="fnFromSelectSceneWorkshop(this.value)" style="width:100%;">
                                    @foreach($maintains as $sceneWorkshopCode=>$maintain)
                                        <option value="{{$sceneWorkshopCode}}" {{ $sceneWorkshopCode == $firstSceneWorkshopUniqueCode ? 'selected' : '' }}>{{$maintain['name']}}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-addon">车站</div>
                                <select id="selModalStation" name="from_station_code" class="select2 form-control" style="width:100%;">

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label text-danger">接收单位*：</label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <input type="radio" name="to_check" required value="factory" checked>
                                    供应商
                                </div>
                                <select name="to_factory_unique_code" class="form-control select2" style="width:100%;">
                                    @foreach ($factories as $factoryUniqueCode=>$factoryName)
                                        <option value="{{$factoryUniqueCode}}">{{$factoryName}}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-addon">
                                    <input type="radio" name="to_check" id="to_check_scene_workshop_code" value="sceneWorkshop">
                                    现场车间
                                </div>
                                <select name="to_scene_workshop_code" id="to_scene_workshop_code" class="select2 form-control" style="width:100%;">
                                    @foreach($maintains as $sceneWorkshopCode=>$maintain)
                                        <option value="{{$sceneWorkshopCode}}">{{$maintain['name']}}</option>
                                    @endforeach
                                </select>

                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">接收联系人：</label>
                        <div class="col-md-9">
                            <input class="form-control input-lg" type="text" autofocus name="to_name" placeholder="接收联系人" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">接收联系电话：</label>
                        <div class="col-md-9">
                            <input class="form-control input-lg" type="text" autofocus name="to_phone" placeholder="接收联系电话" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">维修期限（天）：</label>
                        <div class="col-md-9">
                            <input class="form-control input-lg" type="number" min="0" id="repair_day" oninput="checkNum(this.value,'repair_day')" autofocus name="repair_day" placeholder="维修期限（天）" value="15">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-lg" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="submit" class="btn btn-success btn-flat btn-lg"><i class="fa fa-check">&nbsp;</i>确定送修</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>

    $(function () {
        $('.select2').select2();

        fnFromSelectSceneWorkshop(`${$('#from_scene_workshop_code').val()}`);
    });

    /**
     * 选择来源现场车间
     * @param scene_workshop_code
     */
    function fnFromSelectSceneWorkshop(scene_workshop_code) {
        let maintainJson = JSON.parse(`{!! $maintainJson !!}`);
        let html = `<option value="">未选择</option>`;
        if (scene_workshop_code && scene_workshop_code !== '') {
            if (maintainJson.hasOwnProperty(scene_workshop_code)) {
                $.each(maintainJson[scene_workshop_code]['subs'], function (station_unique_code, station) {
                    html += `<option value="${station_unique_code}">${station['name']}</option>`;
                });
            }
        }
        $("#selModalStation").html(html);
    }

</script>

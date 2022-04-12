<div class="modal fade bs-example-modal-lg" role="dialog" aria-labelledby="myLargeModalLabel" id="checkOrder">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">分配任务
                    <small>
                        {{ $checkPlan->serial_number }}&emsp;{{ $checkPlan->WithCheckProject->type['text'] ?? '' }}&emsp;{{ $checkPlan->WithCheckProject->name ?? '' }}&emsp;{{ $checkPlan->WithStation->Parent->name ?? '' }}&emsp;{{ $checkPlan->WithStation->name ?? '' }}&emsp;{{ date('Y-m',strtotime($checkPlan->expiring_at)) }}
                    </small>
                </h4>
            </div>
            <div class="modal-body form-horizontal">
                <form class="form-horizontal" id="frmStore">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" name="check_plan_serial_number" value="{{ $checkPlanSerialNumber }}">
                            <input type="hidden" name="principal_id_level_3" value="{{ $principalIdLevel3->id }}">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">现场车间主任：</label>
                                <div class="col-sm-10 col-md-8">
                                    {{ $principalIdLevel3->nickname }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">单位：</label>
                                <div class="col-sm-10 col-md-8">
                                    {{ $checkPlan->unit }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">车站*：</label>
                                <div class="col-sm-10 col-md-8">
                                    {{ $checkPlan->WithStation->name ?? '' }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" style="color: red">现场工区职工*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="principal_id_level_5" class="select2 form-control" style="width:100%;">
                                        @foreach($principalIdLevel5s as $id=>$nickname)
                                            <option value="{{ $id }}">{{ $nickname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" style="color: #ff0000">任务时间：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input class="form-control" id="expiring_at" name="expiring_at" type="text" placeholder="任务时间" value="" autofocus="">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    <a href="javascript:" onclick="fnStore()" class="btn btn-success btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>分配任务并前往添加设备</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        if ($(".select2").length > 0) $(".select2").select2();
    });
    layui.config({
        base: '/EasyWeb/spa/assets/module/'
    }).use(['layer', 'laydate'], function () {
        let laydate = layui.laydate;
        laydate.render({
            elem: '#expiring_at',
            trigger: 'click',
            range: false,
            type: 'datetime',
            value: `{{ $minExpiringAt }}`,
            min: `{{ $minExpiringAt }}`,
            max: `{{ $maxExpiringAt }}`
        });
    });

    function fnStore() {
        $.ajax({
            url: "{{url('task/checkOrder')}}",
            type: 'post',
            data: $("#frmStore").serialize(),
            success: function (response) {
                console.log(`success：`, response)
                location.href = `{{ url('task/checkOrder/instance') }}?task_station_check_order_serial_number=${response['data']['task_station_check_order_serial_number']}`;
            },
            error: function (error) {
                console.log(`error:`, error);
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error['responseJSON']['msg']);
                location.reload();
            }
        });
    }
</script>

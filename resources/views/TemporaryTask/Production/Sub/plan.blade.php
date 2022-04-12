@extends('Layout.index')
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
@section('content')
<!-- 面包屑 -->
<section class="content-header">
    <h1>
        工区任务
        <small>计划分配</small>
    </h1>
{{--    <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li><a href="/temporaryTask/production/sub/{{ $sub_task['id'] }}"> 工区任务详情</a></li>--}}
{{--        <li class="active">工区任务计划分配</li>--}}
{{--    </ol>--}}
</section>
@include('Layout.alert')
<section class="content">
    {{--工区任务--}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header">
                    <div class="row">
                        <div class="col-md-9">
                            <h3 class="box-title">{{ $main_task['title'] }}({{ $sub_task['receiver_work_area_name'] }})
                            </h3>
                        </div>
                        <div class="col-md-3">
{{--                            <a href="/temporaryTask/production/sub/{{ $sub_task['id'] }}" class="btn btn-sm btn-default btn-flat pull-right"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-sm btn-default btn-flat pull-right"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        </div>
                    </div>
                    {{--右侧最小化按钮--}}
                    <div class="box-tools pull-right"></div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-hover table-condensed">
                        <thead>
                            <tr>
                                <th>型号</th>
                                <th>数量</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($main_task['models'][$sub_task['receiver_work_area_name']] as $item)
                            <tr>
                                <td>{{ $item['name3'] }}</td>
                                <td>{{ $item['number'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">计划分配</h3>

                    {{--右侧最小化按钮--}}
                    <div class="box-tools pull-right">
                        <a href="javascript:" id="btnSave" onclick="fnSavePlan()" class="text-success"><i
                                class="fa fa-check">&nbsp;</i>保存</a>&nbsp;
                        <a href="javascript:" id="btnCancel" onclick="location.reload()" class="text-danger"><i
                                class="fa fa-times">&nbsp;</i>放弃</a>&nbsp;
                        <a href="javascript:" id="btnEdit" onclick="fnEdit()"><i
                                class="fa fa-pencil">&nbsp;</i>编辑</a>&nbsp;
                        {{--                            <a href="javascript:" id="btnDownloadExcel" onclick="fnDownloadExcel()"><i class="fa fa-save">&nbsp;</i>下载Excel</a>--}}
                        <a href="/temporaryTask/production/sub/plan/{{ $sub_task['id'] }}?download=1" target="_blank"><i
                                class="fa fa-save">&nbsp;</i>下载Excel</a>
                    </div>
                </div>
                <div class="box-body table-responsive table-responsive-sm table-responsive-lg" style="font-size: 9px;">
                    <table style="border-spacing: 0" class="table table-bordered table-hover table-condensed">
                        <thead>
                            <tr>
                                <th>型号/人员</th>
                                <th>合计</th>
                                @foreach($accounts as $account_id => $account_nickname)
                                <th>{{ $account_nickname }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plan as $item)
                            <tr>
                                <td>{{ $item['name3'] }}</td>
                                <td><span
                                        id="spanPlanCountAsSubModel_{{ $item['code3'] }}">{{ isset($item['plan']) ? $item['plan'] : '' }}</span>
                                </td>
                                @foreach($accounts as $account_id => $account_nickname)
                                <td style="padding: 0;">
                                    <input type="number"
                                        value="{{ key_exists($account_nickname,$item['accounts']) ? $item['accounts'][$account_nickname] : 0 }}"
                                        style="width: 50px;" step="1" min="0"
                                        name="{{ $item['code3'] }}:{{ $account_nickname }}" class="plan-input disabled"
                                        onchange="fnChangeAccountPlan(this)" disabled>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                            <tr>
                                <td></td>
                                <td>人员合计</td>
                                @foreach ($account_plan_total as $item)
                                <td>{{ $item }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('script')
<script>
    let $planInputs = $('.plan-input');
    let $btnSave = $('#btnSave');
    let $btnCancel = $('#btnCancel');
    let $btnEdit = $('#btnEdit');

    let plan = JSON.parse('{!! $plan_as_json !!}');
    let planWithAccounts = [];

    $(function () {
        $btnSave.hide();
        $btnCancel.hide();
        $('.select2').select2();
        // iCheck for checkbox and radio inputs
        $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
        });
        //Date picker
        $('#date').daterangepicker({
            locale: {
                format: "YYYY-MM-DD",
                separator: "~",
                daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"]
            }
        });
    });

    /**
     * 更新
     * @param event 触发事件的标签
     */
    function fnChangeAccountPlan(event) {
        let nameArr = event.name.split(':');
        let subModelUniqueCode = nameArr[0];
        let accountNickname = nameArr[1];

        let $spanPlanCountAsSubModel = $(`#spanPlanCountAsSubModel_${subModelUniqueCode}`);
        let $spanMissionCountAsSubModel = $(`#spanMissionCountAsSubModel_${subModelUniqueCode}`);

        let intValue = parseInt(event.value);
        event.value = plan[subModelUniqueCode]['accounts'][accountNickname] = intValue;

        let count = 0;
        for (let key in plan[subModelUniqueCode]['accounts']){
            count += parseInt(plan[subModelUniqueCode]['accounts'][key]);
        }
        plan[subModelUniqueCode]['plan'] = count;

        $spanPlanCountAsSubModel.text(count);
        // if (count >= parseInt($($spanMissionCountAsSubModel).text())) {
        //     $spanPlanCountAsSubModel.removeClass('text-danger');
        //     $spanPlanCountAsSubModel.addClass('text-success');
        // } else {
        //     $spanPlanCountAsSubModel.removeClass('text-success');
        //     $spanPlanCountAsSubModel.addClass('text-danger');
        // }
    }

    /**
     * 保存计划分配
     */
    function fnSavePlan () {
        for (let key in plan) {
            tmp = new Object();
            for(let k in plan[key]['accounts']) tmp[k] =plan[key]['accounts'][k];
            plan[key]['accounts'] = tmp;
        }

        $.ajax({
            url: `/temporaryTask/production/sub/plan/{{ $sub_task['id'] }}`,
            type: 'post',
            data: plan,
            async: true,
            success: function(response) {
                console.log(`/temporaryTask/production/sub/plan/{{ $sub_task['id'] }} success:`, response);
                alert(response.message);
                location.reload();
            },
            fail: function(error) {
                console.log(`/temporaryTask/production/sub/plan/{{ $sub_task['id'] }} fail:`, error);
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.responseText);
            }
        });
    }

    /**
     * 编辑
     */
    function fnEdit () {
        $planInputs.removeClass('disabled');
        $planInputs.removeAttr('disabled');
        $btnSave.show();
        $btnCancel.show();
        $btnEdit.hide();
    }

    /**
     * 下载Excel
     */
    function fnDownloadExcel() {
        $.ajax({
            url: `/temporaryTask/production/sub/makeExcelWithPlan/{{ $sub_task['id'] }}`,
            type: 'get',
            data: {date: "{{ request('date') }}"},
            async: false,
            success: function (response) {
                console.log(`/temporaryTask/production/sub/makeExcelWithPlan/{{ $sub_task['id'] }} success:`, response);
            },
            fail: function (error) {
                console.log(`/temporaryTask/production/sub/makeExcelWithPlan/{{ $sub_task['id'] }} fail:`, error);
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.responseText);
            }
        });
    }
</script>
@endsection

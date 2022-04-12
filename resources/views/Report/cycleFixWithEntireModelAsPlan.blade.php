@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            周期修
            <small>任务分配</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="/report/cycleFixWithEntireModelAsMission/{{ $currentCategoryUniqueCode }}?year={{ explode('-',request('date'))[0] }}"> 周期修任务（类型）</a></li>--}}
{{--            <li class="active">周期修任务分配</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
    @include('Layout.alert')
    <!--周期修计划与周期修完成报表-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-4">
                                <h3 class="box-title">周期修任务 {{ request('date') }}</h3>
                            </div>
                            <div class="col-md-8">
                                <div class="box-tools pull-right">
                                    <div class="input-group">
                                        <div class="input-group-addon">时间</div>
                                        <select
                                            name="date"
                                            id="selDate"
                                            class="select2"
                                            style="width: 100%;"
                                        >
                                            @foreach($dateList as $item)
                                                <option value="{{ $item }}" {{ request('date',date('Y-m')) == $item ? 'selected' : '' }}>{{ $item }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select
                                            name="category_unique_code"
                                            id="selCategoryUniqueCode"
                                            class="select2"
                                            style="width: 100%;"
                                        >
                                        </select>
                                        {{--<div class="input-group-addon">类型</div>--}}
                                        {{--<select--}}
                                        {{--name="entire_model_unique_code"--}}
                                        {{--id="selEntireModelUniqueCode"--}}
                                        {{--class="select2"--}}
                                        {{--style="width: 100%;"--}}
                                        {{-->--}}
                                        {{--</select>--}}
                                        <div class="input-group-btn">
                                            <a href="javascript:" onclick="fnCurrentEntireModel()" class="btn btn-default btn-flat">搜索</a>
                                            <a href="javascript:" id="btnSave" onclick="fnSavePlan()" class="btn btn-success btn-flat">保存</a>&nbsp;
                                            <a href="javascript:" id="btnCancel" onclick="location.reload()" class="btn btn-danger btn-flat">放弃</a>&nbsp;
                                            <a href="javascript:" id="btnEdit" onclick="fnEdit()" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>&nbsp;
                                            {{--<a href="javascript:" id="btnDownloadExcel" class="btn btn-default btn-flat" onclick="fnDownloadExcel()">下载Excel</a>--}}
                                            <a href="javascript:" onclick="fnDownloadExcel()" class="btn btn-default btn-flat" target="_blank">下载Excel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body table-responsive">
                        {{--<table class="table table-bordered table-hover table-condensed" id="table2">--}}
                        {{--<thead>--}}
                        {{--<tr>--}}
                        {{--<th>型号</th>--}}
                        {{--<th>任务</th>--}}
                        {{--@foreach($wholeStationNames as $stationName)--}}
                        {{--<th>{{ $stationName }}</th>--}}
                        {{--@endforeach--}}
                        {{--</tr>--}}
                        {{--</thead>--}}
                        {{--<tbody>--}}
                        {{--@foreach($missionWithSubModelAsMonthForStation as $subModelUniqueCode => $mission)--}}
                        {{--@if(!empty($mission))--}}
                        {{--<tr>--}}
                        {{--<td>{{ $subModelUniqueCode }}</td>--}}
                        {{--<td><span id="spanMissionCountAsSubModel_{{ $subModelUniqueCode }}">{{ $missionWithSubModelAsMonth[$subModelUniqueCode] }}</span></td>--}}
                        {{--@foreach($wholeStationNames as $stationName)--}}
                        {{--<td>--}}
                        {{--@if(array_key_exists($stationName,$mission))--}}
                        {{--{{ $mission[$stationName] }}--}}
                        {{--@else--}}
                        {{--0--}}
                        {{--@endif--}}
                        {{--</td>--}}
                        {{--@endforeach--}}
                        {{--</tr>--}}
                        {{--@else--}}
                        {{--<tr>--}}
                        {{--<td>{{ $subModelUniqueCode }}</td>--}}
                        {{--<td>{{ $missionWithSubModelAsMonth[$subModelUniqueCode] }}</td>--}}
                        {{--@foreach($wholeStationNames as $stationName)--}}
                        {{--<td>0</td>--}}
                        {{--@endforeach--}}
                        {{--</tr>--}}
                        {{--@endif--}}
                        {{--@endforeach--}}
                        {{--</tbody>--}}
                        {{--</table>--}}
                        <table style="border-spacing: 0" class="table table-bordered table-hover table-condensed" id="table">
                            <thead>
                            <tr>
                                <th>型号/人员</th>
                                <th>任务</th>
                                <th>小计</th>
                                @foreach($accounts as $accountId => $accountNickname)
                                    <th>{{ $accountNickname }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($planWithSubModelAsMonth as $subModelUniqueCode => $plan)
                                @if(!empty($plan))
                                    <tr>
                                        <td>{{ $subModelUniqueCode }}</td>
                                        <td>{{ $missionWithSubModelAsMonth[$subModelUniqueCode] }}</td>
                                        <td><span id="spanPlanCountAsSubModel_{{ str_replace('/', '__', $subModelUniqueCode) }}">{{ $plan['count'] }}</span></td>
                                        @foreach($accounts as $accountId => $accountNickname)
                                            <td style="padding: 0;">
                                                <input
                                                    type="number"
                                                    value="{{ $plan['accounts'][$accountNickname] }}"
                                                    style="width: 50px;"
                                                    step="1"
                                                    min="0"
                                                    name="{{ str_replace('/', '__', $subModelUniqueCode) }}:{{ $accountNickname }}"
                                                    class="plan-input disabled"
                                                    onchange="fnChangeAccountPlan(this)"
                                                    onkeydown="fnCheckInt(this)"
                                                    disabled
                                                >
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                            <tr>
                                <td></td>
                                <td></td>
                                <td>人员合计</td>
                                @foreach($accounts as $accountId => $accountNickname)
                                    <td>{{ $planWithMonthAsAccount[$accountNickname]['plan'] }}</td>
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
        let $selDate = $('#selDate');
        let $selCategory = $('#selCategoryUniqueCode');
        let $selEntireModel = $('#selEntireModelUniqueCode');
        let cycleFixCategories = JSON.parse('{!! $cycleFixCategoriesAsJson !!}');
        let haveCycleFixCategories = JSON.parse('{!! $haveCycleFixCategoriesAsJson !!}');
        let categoriesFlip = JSON.parse('{!! $cycleFixCategoriesFlipAsJson !!}');

        let planWithSubModelAsMonth = JSON.parse('{!! json_encode($planWithSubModelAsMonth,256) !!}');

        /**
         * 填充种类下拉列表
         */
        function fnFillCategory() {
            let html = '';
            $.each(haveCycleFixCategories, function (idx, categoryName) {
                html += `<option value="${categoriesFlip[categoryName]}" ${'{{ $currentCategoryName }}' === categoryName ? 'selected' : ''}>${categoryName}</option>`;
            });
            $selCategory.html(html);
        }

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

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,  // 分页器
                    lengthChange: true,
                    searching: true,  // 搜索框
                    ordering: false,  // 列排序
                    info: false,
                    autoWidth: false,  // 自动宽度
                    iDisplayLength: 15,  // 默认分页数
                    aLengthMenu: [15, 30, 50, 100],  // 分页下拉框选项
                    language: {
                        sInfoFiltered: "从_MAX_中过滤",
                        sProcessing: "正在加载中...",
                        info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "每页显示_MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "筛选：",
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });
            }

            fnFillCategory();  // 填充种类下拉列表
        });

        /**
         * 检查输入是否小于0
         */
        function fnCheckInt(event) {
            if (parseInt(event.value) < 0) event.value = 0;
        }

        /**
         * 更新
         * @param event 触发事件的标签
         */
        function fnChangeAccountPlan(event) {
            let nameArr = event.name.split(':');
            let subModelUniqueCode = nameArr[0];
            let subModelName = subModelUniqueCode.replace('__', '/');
            let accountNickname = nameArr[1];

            let $spanPlanCountAsSubModel = $(`#spanPlanCountAsSubModel_${subModelUniqueCode}`);
            let $spanMissionCountAsSubModel = $(`#spanMissionCountAsSubModel_${subModelUniqueCode}`);

            let intValue = parseInt(event.value) ? parseInt(event.value) : 0;
            if (intValue < 0) {
                event.value = 0;
                return;
            }
            event.value = intValue;

            planWithSubModelAsMonth[subModelName]['accounts'][accountNickname] = intValue;

            let count = 0;
            $.each(planWithSubModelAsMonth[subModelName]['accounts'], (idx, item) => {
                if (idx !== 'count') count += parseInt(item);
            });
            planWithSubModelAsMonth[subModelName]['count'] = count;

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
        function fnSavePlan() {
            $.ajax({
                url: `/report/savePlan`,
                type: 'post',
                data: {
                    categoryName: "{{ $currentCategoryName}}",
                    planWithSubModelAsMonth: planWithSubModelAsMonth,
                    year: "{{ $year }}",
                    date: "{{ $date }}"
                },
                async: true,
                success: function (response) {
                    console.log(`/report/savePlan success:`, response);
                    alert(response.message);
                    location.reload();
                },
                fail: function (error) {
                    console.log(`/report/savePlan fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        }

        /**
         * 编辑
         */
        function fnEdit() {
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
            window.open(`/report/makeExcelWithPlan?date={{ request('date') }}&category_unique_code=${$selCategory.val()}`,'_blank');
        }

        /**
         * 选择种类 刷新类型离诶包
         */
        function fnCurrentCategory() {
            fnFillEntireModel();
        }

        /**
         * 跳转到类型
         */
        function fnCurrentEntireModel() {
            location.href = `{{ url('report/cycleFixWithEntireModelAsPlan') }}/${$selCategory.val()}?date=${$selDate.val()}`;
        }
    </script>
@endsection

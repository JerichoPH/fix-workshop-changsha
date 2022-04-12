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
{{--            --}}{{--<li><a href="/report/cycleFixWithEntireModelAsMission/{{ $currentCategoryUniqueCode }}?year={{ explode('-',request('date'))[0] }}"> 周期修任务（类型）</a></li>--}}
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
                                            class="form-control select2"
                                            style="width: 100%;"
                                        >
                                            @foreach($dateList as $item)
                                                <option value="{{ $item }}" {{ request('date',date('Y-m')) == $item ? 'selected' : '' }}>{{ $item }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select
                                            name="category_unique_code"
                                            id="selCategory"
                                            class="form-control select2"
                                            style="width: 100%;"
                                        >
                                        </select>
                                        <div class="input-group-btn">
                                            <a href="javascript:" onclick="fnCurrentEntireModel()" class="btn btn-default btn-flat">搜索</a>
                                            <a href="javascript:" id="btnSave" onclick="fnSavePlan()" class="btn btn-success btn-flat">保存</a>&nbsp;
                                            <a href="javascript:" id="btnCancel" onclick="location.reload()" class="btn btn-danger btn-flat">放弃</a>&nbsp;
                                            <a href="javascript:" id="btnEdit" onclick="fnEdit()" class="btn btn-warning btn-flat">编辑</a>&nbsp;
                                            <a href="javascript:" onclick="fnDownloadExcel()" class="btn btn-default btn-flat" target="_blank">下载Excel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form id="frmStore">
                        <div class="box-body table-responsive">
                            <table style="border-spacing: 0" class="table table-bordered table-hover table-condensed" id="table">
                                <thead id="theadMission"></thead>
                                <tbody id="tbodyMission"></tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $btnSave = $('#btnSave');
        let $btnCancel = $('#btnCancel');
        let $btnEdit = $('#btnEdit');
        let $selDate = $('#selDate');
        let $selCategory = $('#selCategory');
        let $selEntireModel = $('#selEntireModelUniqueCode');
        let $theadMission = $('#theadMission');
        let $tbodyMission = $('#tbodyMission');
        let categories = JSON.parse('{!! $categoriesAsJson !!}');
        let accounts = JSON.parse('{!! $accountsAsJson !!}');
        let missions = JSON.parse('{!! $missionsAsJson !!}');
        // 重排missions
        let tmp = {};
        $.each(missions, function (accountId, missions) {
            if (!tmp.hasOwnProperty(accountId)) tmp[accountId] = {};
            $.each(missions, function (missionKey, mission) {
                tmp[accountId][mission['model_unique_code']] = mission['number'];
            });
        });
        missions = tmp;
        let statistics = JSON.parse('{!! $statisticsAsJson !!}');
        let workAreaWithCategory = '{{ $workAreaWithCategory }}';
        let totalWithModelUniqueCode = {};
        let totalWithAccountId = {};

        /**
         * 填充种类
         */
        function fnFillCategory() {
            let html = '';
            $.each(categories, function (cu, cn) {
                html += `<option ${cu} '{{ request('categoryUniqueCode') }}' === cu ? 'selected' : ''>${cn}</option>`;
            });
            $selCategory.html(html);
        }

        /**
         * 填充人员
         */
        function fnFillTHeadMission() {
            let html = `<tr><th>型号/人员</th><th>任务</th><th>小计</th>`;
            $.each(accounts[workAreaWithCategory], function (accountKey, account) {
                html += `<th>${account['nickname']}</th>`;
            });
            html += `</tr>`;
            $theadMission.html(html);
        }

        /**
         * 填充任务表格
         */
        function fnFillTBodyMission() {
            let html = '';
            $.each(statistics, function (statisticsKey, statistic) {
                $.each(statistic['categories'], function (statisticKey, categories) {
                    $.each(categories['subs'], function (emu, entireModels) {
                        $.each(entireModels['subs'], function (mu, model) {
                            let number = 0;
                            html += `<tr>`;
                            html += `<td>${model['name']}</td>`;
                            html += `<td>${model['statistics']['plan_device_count'] ? model['statistics']['plan_device_count'] : 0}</td>`;
                            html += `<td><span id="spanCountRow_${mu}">0</span></td>`;
                            $.each(accounts[workAreaWithCategory], function (accountKey, account) {
                                if (!totalWithModelUniqueCode.hasOwnProperty(mu)) totalWithModelUniqueCode[mu] = 0;
                                if (!totalWithAccountId.hasOwnProperty(account['id'])) totalWithAccountId[account['id']] = 0;
                                number =
                                    missions[account['id']]
                                        ? (missions[account['id']][mu] ? missions[account['id']][mu] : 0)
                                        : 0;
                                totalWithAccountId[account['id']] += number;
                                totalWithModelUniqueCode[mu] += number;
                                let base64InputName = window.btoa(`${mu}:${model['name']}:${account['id']}`);
                                html += `<td><input
                                                type="number"
                                                value="${number}"
                                                step="1"
                                                style="width: 50px;"
                                                min="0"
                                                name="${base64InputName}"
                                                class="plan-input disabled"
                                                onchange="fnChangeAccountPlan(event)"
                                                onkeydown="fnCheckInt(event)"
                                                disabled></td>`;
                            });
                            html += `</tr>`;
                        });
                    });
                });
            });

            // 合计列
            html += `<tr><td></td><td></td><td></td>`;
            $.each(accounts[workAreaWithCategory], function (accountKey, account) {
                html += `<td id="totalWithAccountId_${account['id']}">${totalWithAccountId[account['id']] ? totalWithAccountId[account['id']] : 0}</td>`;
            });
            html += `</tr>`;
            $tbodyMission.html(html);

            // 小计
            $.each(totalWithModelUniqueCode, function (cKey, cVal) {
                $(`#spanCountRow_${cKey}`).text(cVal);
            });
        }

        $(function () {
            $select2.select2();
            $btnSave.hide();
            $btnCancel.hide();

            fnFillCategory();  // 填充种类
            fnFillTHeadMission();  // 填充表头
            fnFillTBodyMission();  // 填充表格
        });

        /**
         * 检查输入是否小于0
         */
        function fnCheckInt(event) {
            if (parseInt(event.value) < 0) event.value = 0;
        }

        /**
         * 修改数字时重新计算合计
         * @param event 触发事件的标签
         */
        function fnChangeAccountPlan(event) {

        }

        /**
         * 保存计划分配
         */
        function fnSavePlan() {
            let data = $('#frmStore').serializeArray();
            $btnSave.text('正在保存，请稍后……').addClass('disabled').prop('disabled',true);
            $btnSave.prop('disabled', 'disabled').addClass('disabled').prop('disabled',true);
            $.ajax({
                url: `/report/savePlan?date=${$selDate.val()}`,
                type: 'post',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`/report/savePlan success:`, res);
                    alert(res['message']);
                    location.reload();
                },
                error: function (err) {
                    console.log(`/report/savePlan fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseText['message']);
                    $btnSave.text('保存').removeClass('disabled').prop('disabled',false);
                    $btnCancel.removeClass('disabled').prop('disabled', false);
                }
            });
        }

        /**
         * 编辑
         */
        function fnEdit() {
            $('.plan-input').removeClass('disabled').prop('disabled', false);
            $btnSave.show();
            $btnCancel.show();
            $btnEdit.hide();
        }

        /**
         * 下载Excel
         */
        function fnDownloadExcel() {
            window.open(`/report/makeExcelWithPlan?date={{ request('date') }}&category_unique_code=${$selCategory.val()}`, '_blank');
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

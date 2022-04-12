@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            故障修
            <small>检修任务分配</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('/repairBase/breakdownOrder') }}?direction=IN&page={{ request('page',1) }}"> 故障修列表</a></li>--}}
{{--            <li class="active"> 故障修检修任务分配</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
    @include('Layout.alert')
    <!--故障修入所计划-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-9">
                                <h3 class="box-title">故障修 {{ request('date',date('Y-m')) }}</h3>
                            </div>
                            <div class="col-md-3">
                                <form action="">
                                    <div class="input-group">
                                        <div class="input-group-btn">
                                            <a href="{{ url('/repairBase/breakdownOrder') }}?direction=IN&page={{ request('page',1) }}" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                        </div>
                                        <div class="input-group-addon">时间</div>
                                        <label for="dpCreatedAt" style="display: none;"></label>
                                        <input name="date" type="text" class="form-control" id="dpCreatedAt" value="{{ request('date',date('Y-m')) }}">
                                        <div class="input-group-btn">
                                            <button class="btn btn-default btn-flat">搜索</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-bordered table-hover table-condensed">
                            <thead>
                            <tr>
                                <th>型号</th>
                                <th>任务</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($plan_count as $plan_item)
                                <tr>
                                    <td>{{ $plan_item->model_name }}</td>
                                    <td>{{ $plan_item->aggregate }}</td>
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
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right">
                            <a href="javascript:" id="btnSave" onclick="fnSavePlan()" class="text-success"><i class="fa fa-check">&nbsp;</i>保存</a>&nbsp;
                            <a href="javascript:" id="btnCancel" onclick="location.reload()" class="text-danger"><i class="fa fa-times">&nbsp;</i>放弃</a>&nbsp;
                            <a href="javascript:" id="btnEdit" onclick="fnEdit()"><i class="fa fa-pencil">&nbsp;</i>编辑</a>&nbsp;
                            <a href="{{ url('repairBase/breakdownOrder/mission') }}?download=1&date={{ request('date',date('Y-m')) }}" target="_blank"><i class="fa fa-save">&nbsp;</i>下载Excel</a>
                        </div>
                    </div>
                    <form id="frmMission">
                        <div class="box-body table-responsive">
                            <table style="border-spacing: 0" class="table table-bordered table-hover table-condensed">
                                <thead>
                                <tr>
                                    <th>型号/人员</th>
                                    <th>任务</th>
                                    <th>合计</th>
                                    @foreach($accounts as $account_id => $account_nickname)
                                        <th>{{ $account_nickname }}</th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($plan_count as $model_unique_code => $plan_item)
                                    <tr>
                                        <td>{{ $plan_item->model_name }}</td>
                                        <td>{{ $plan_item->aggregate }}</td>
                                        <td><span id="spanModel_{{ $model_unique_code }}" class="span-model">{{ @$account_statistics['statistics_model'][$plan_item->model_name] ?: 0 }}</span></td>
                                        @foreach($accounts as $account_id => $account_nickname)
                                            @if(!empty($account_statistics) && array_key_exists($account_id,$account_statistics['statistics']))
                                                <td style="padding: 0;">
                                                    <input
                                                        type="number"
                                                        value="{{ $account_statistics['statistics'][$account_id][$plan_item->model_name]['number'] }}"
                                                        style="width: 50px;"
                                                        step="1"
                                                        min="0"
                                                        name="{{ $model_unique_code.':'.$account_id.':'.$plan_item->model_name }}"
                                                        class="plan-input plan-input-{{ $model_unique_code }} plan-input-{{ $account_id }} disabled"
                                                        onchange="fnChangeAccountPlan(this)"
                                                        disabled
                                                    >
                                                </td>
                                            @else
                                                <td style="padding: 0;">
                                                    <input
                                                        type="number"
                                                        value="0"
                                                        style="width: 50px;"
                                                        step="1"
                                                        min="0"
                                                        name="{{ $model_unique_code.':'.$account_id.':'.$plan_item->model_name }}"
                                                        class="plan-input plan-input-{{ $model_unique_code }} plan-input-{{ $account_id }} disabled"
                                                        onchange="fnChangeAccountPlan(this)"
                                                        disabled
                                                    >
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>人员合计</td>
                                        <td><span id="spanTotalCount">0</span></td>
                                        @foreach($accounts as $account_id => $account_nickname)
                                            <td>
                                                @if(!empty($account_statistics) && array_key_exists($account_id,$account_statistics['statistics_account']))
                                                    <span id="spanAccount_{{ $account_id }}">{{ $account_statistics['statistics_account'][$account_id] }}</span>
                                                @else
                                                    <span id="spanAccount_{{ $account_id }}">0</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
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
        let $planInputs = $('.plan-input');
        let $btnSave = $('#btnSave');
        let $btnCancel = $('#btnCancel');
        let $btnEdit = $('#btnEdit');
        let $dpCreatedAt = $('#dpCreatedAt');

        /**
         * 统计所有合计
         */
        function fnCalTotalCount() {
            let $spanModels = $('.span-model');
            let $spanTotalCount = $('#spanTotalCount');
            let totalCount = 0;
            $.each($spanModels, function (index, item) {
                totalCount += parseInt($(item).text());
            });
            $spanTotalCount.text(totalCount);
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
            $('#dpCreatedAt').datepicker({
                format: 'yyyy-mm',
                language: 'cn',
                clearBtn: false,
                autoclose: true,
            });

            fnCalTotalCount();
        });

        /**
         * 更新
         * @param event 触发事件的标签
         */
        function fnChangeAccountPlan(event) {
            let nameArr = event.name.split(':');
            let modelUniqueCode = nameArr[0];
            let accountId = nameArr[1];

            // 型号合计
            let $spanModel = $(`#spanModel_${modelUniqueCode}`);
            let modelCount = 0;
            $.each($(`.plan-input-${modelUniqueCode}`), function (index, item) {
                modelCount += parseInt(item.value);
            });
            $spanModel.text(modelCount);

            // 人员合计
            let $spanAccount = $(`#spanAccount_${accountId}`);
            let accountCount = 0;
            $.each($(`.plan-input-${accountId}`), function (index, item) {
                accountCount += parseInt(item.value);
            });
            $spanAccount.text(accountCount);

            // 统计所有合计
            fnCalTotalCount();
        }

        /**
         * 保存计划分配
         */
        function fnSavePlan() {
            if (!$dpCreatedAt.val()) {
                alert('必须选择月份');
                return;
            }
            $.ajax({
                url: `/repairBase/breakdownOrder/mission?date=${$dpCreatedAt.val()}`,
                type: 'post',
                data: $('#frmMission').serialize(),
                async: true,
                success: function (response) {
                    console.log(`/repairBase/breakdownOrder/mission success:`, response);
                    // alert(response.message);
                    location.reload();
                },
                fail: function (error) {
                    console.log(`/repairBase/breakdownOrder/mission fail:`, error);
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
         * 切换日期
         * @param date
         */
        function fnCurrentDate(date) {
            location.href = `?date=${date}`;
        }
    </script>
@endsection

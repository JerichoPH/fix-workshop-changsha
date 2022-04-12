@extends('Layout.index')
@section('content')
<section class="content">
    @include('Layout.alert')
    <form>
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">搜索</h3>
                <div class="pull-right btn-group btn-group-sm">
                    <div class="btn btn-default btn-flat" onclick="fnQuery('{{ $sn }}')"><i class="fa fa-search">&nbsp;</i>搜索</div>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-addon">种类</div>
                            <select id="selCategory" name="categoryUniqueCode" class="select2 form-control" style="width:100%;" onchange="fnSelectCategory(this.value)">
                                <option value="">全部</option>
                                @foreach($categories as $categoryUniqueCode=>$categoryName)
                                <option value="{{ $categoryUniqueCode }}" {{ request('categoryUniqueCode') == $categoryUniqueCode ? 'selected' : '' }}>{{ $categoryName }}</option>
                                @endforeach
                            </select>
                            <div class="input-group-addon">类型</div>
                            <select id="selEntireModel" name="entireModelUniqueCode" class="select2 form-control" style="width:100%;" onchange="fnSelectEntireModel(this.value)">
                                <option value="">全部</option>
                            </select>
                            <div class="input-group-addon">型号</div>
                            <select id="selSubModel" name="subModelUniqueCode" class="select2 form-control" style="width:100%;">
                                <option value="">全部</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <div class="input-group-addon">供应商</div>
                            <select id="factories" class="form-control select2" name="factories" style="width:100%;">
                                <option value="" selected="selected">全部</option>
                                @foreach($factories as $factoryName)
                                <option {{ request('factoryName') == $factoryName ? 'selected' : '' }}>{{ $factoryName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <div class="input-group-addon">设备编号</div>
                            <input type="text" id="entireInstanceUniqueCode" name="entireInstanceUniqueCode" class="form-control" value="{{ request('entireInstanceUniqueCode') ? request('entireInstanceUniqueCode') : '' }}" onkeydown="if(event.keyCode===13) fnQuery('{{ $sn }}')">
                        </div>
                    </div>
                    <input type="hidden" id="type" name="type" value="{{ request('type') }}" />
                    <input type="hidden" name="sn" value="{{ request('sn') }}" />
                    <input type="hidden" name="is_iframe" value="1" />
                </div>
            </div>
        </div>
        <div class="box box-solid">
            <div class="box-header">
                <ul class="nav nav-tabs">
                    <li {{ request('type') == 'tab_1' ? "class=active" : ''}}><a href="#tab_1" data-toggle="tab" onclick="fnTabs('tab_1')">任务设备列表</a></li>
                    <li {{ request('type') == 'tab_2' ? "class=active" : ''}}><a href="#tab_2" data-toggle="tab" onclick="fnTabs('tab_2')">所内设备列表</a></li>
                    <li {{ request('type') == 'tab_3' ? "class=active" : ''}}><a href="#tab_3" data-toggle="tab" onclick="fnTabs('tab_3')">检修统计</a></li>
                </ul>
                <div class="tab-content">
                    <!--任务设备列表-->
                    <div id="tab_1" class="{{ request('type') === 'tab_1' ? 'tab-pane active' : 'tab-pane' }}">
                        <div class="box-header">
                            <!--右侧最小化按钮-->
                            <div class="pull-right btn-group btn-group-sm">
                                <a href="javascript:" onclick="fnOverhaul(1)" class="btn btn-success btn-flat"><i class="fa fa-wrench">&nbsp;</i>检修分配</a>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" name="all1"></th>
                                            <th>设备编号</th>
                                            <th>所编号</th>
                                            <th>型号</th>
                                            <th>厂家</th>
                                            <th>厂编号</th>
                                            <th>生产日期</th>
                                            <th>仓库位置</th>
                                            <th>状态</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($taskEntireInstances as $taskEntireInstance)
                                        <tr>
                                            <td><input type="checkbox" name="check1" value="{{ $taskEntireInstance->identity_code }}"/></td>
                                            <td>{{ $taskEntireInstance->identity_code }}</td>
                                            <td>{{ $taskEntireInstance->serial_number }}</td>
                                            <td>{{ $taskEntireInstance->model_name }}</td>
                                            <td>{{ $taskEntireInstance->factory_name }}</td>
                                            <td>{{ $taskEntireInstance->factory_device_code }}</td>
                                            <td>{{ $taskEntireInstance->made_at ? date('Y-m-d',strtotime($taskEntireInstance->made_at)) :'' }}</td>
                                            @if(@$taskEntireInstance->position_name)
                                            <td>
                                                <a href="javascript:" onclick="fnLocation(`{{ $entireInstance->identity_code }}`)"><i class="fa fa-location-arrow"></i>
                                                    {{ @$taskEntireInstance->storehous_name }}
                                                    {{ @$taskEntireInstance->area_name }}
                                                    {{ @$taskEntireInstance->platoon_name }}
                                                    {{ @$taskEntireInstance->shelf_name }}
                                                    {{ @$taskEntireInstance->tier_name }}
                                                    {{ @$taskEntireInstance->position_name }}
                                                </a>
                                            </td>
                                            @else
                                            <td></td>
                                            @endif
                                            <td>{{ \App\Model\EntireInstance::$STATUSES[$taskEntireInstance->status] ? \App\Model\EntireInstance::$STATUSES[$taskEntireInstance->status] : '' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!--所内设备列表-->
                    <div id="tab_2" class="{{ request('type') === 'tab_2' ? 'tab-pane active' : 'tab-pane' }}">
                        <div class="box-header">
                            <h3 class="box-title"></h3>
                            <!--右侧最小化按钮-->
                            <div class="pull-right btn-group btn-group-sm">
                                <a href="javascript:" onclick="fnOverhaul(2)" class="btn btn-success btn-flat"><i class="fa fa-wrench">&nbsp;</i>检修分配</a>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" name="all2"></th>
                                            <th>设备编号</th>
                                            <th>所编号</th>
                                            <th>型号</th>
                                            <th>厂家</th>
                                            <th>厂编号</th>
                                            <th>生产日期</th>
                                            <th>仓库位置</th>
                                            <th>状态</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($workshopEntireInstances as $workshopEntireInstance)
                                        <tr>
                                            <td><input type="checkbox" name="check2" value="{{ $workshopEntireInstance->identity_code }}"/></td>
                                            <td>{{ $workshopEntireInstance->identity_code }}</td>
                                            <td>{{ $workshopEntireInstance->serial_number }}</td>
                                            <td>{{ $workshopEntireInstance->model_name }}</td>
                                            <td>{{ $workshopEntireInstance->factory_name }}</td>
                                            <td>{{ $workshopEntireInstance->factory_device_code }}</td>
                                            <td>{{ $workshopEntireInstance->made_at ? date('Y-m-d',strtotime($workshopEntireInstance->made_at)) :'' }}</td>
                                            @if(@$workshopEntireInstance->position_name)
                                            <td>
                                                <a href="javascript:" onclick="fnLocation(`{{ $entireInstance->identity_code }}`)"><i class="fa fa-location-arrow"></i>
                                                    {{ @$workshopEntireInstance->storehous_name }}
                                                    {{ @$workshopEntireInstance->area_name }}
                                                    {{ @$workshopEntireInstance->platoon_name }}
                                                    {{ @$workshopEntireInstance->shelf_name }}
                                                    {{ @$workshopEntireInstance->tier_name }}
                                                    {{ @$workshopEntireInstance->position_name }}
                                                </a>
                                            </td>
                                            @else
                                            <td></td>
                                            @endif
                                            <td>{{ \App\Model\EntireInstance::$STATUSES[$workshopEntireInstance->status] ?? '' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($workshopEntireInstances->hasPages())
                                <div class="box-footer">
                                    {{ $workshopEntireInstances->appends(request()->all())->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <!--检修统计-->
                    <div id="tab_3" class="{{ request('type') === 'tab_3' ? 'tab-pane active' : 'tab-pane' }}">
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed">
                                    <thead>
                                        <tr>
                                            <th>检修人\年月</th>
                                            @foreach($yearMonths as $yearMonth)
                                            <th>{{ $yearMonth }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($overhaulStatistics as $overhaulStatistic)
                                        <tr>
                                            <td>{{ $overhaulStatistic->accountNickname }}</td>
                                            @foreach($yearMonths as $yearMonth)
                                                <td>
                                                    <a href="javascript:" onclick="fnOverhaulEntireInstance('{{ $yearMonth }}', '{{ $overhaulStatistic->accountId }}', 'tab_1')">任务：{{\Illuminate\Support\Facades\DB::table('overhaul_entire_instances')->where('fixer_id', $overhaulStatistic->accountId)->whereBetween('allocate_at', [$yearMonth.'-01'.' '. '00:00:00',$yearMonth.'-31'.' '. '00:00:00'])->count()}}</a><br>
                                                    <a href="javascript:" onclick="fnOverhaulEntireInstance('{{ $yearMonth }}', '{{ $overhaulStatistic->accountId }}', 'tab_2')">已完成：{{\Illuminate\Support\Facades\DB::table('overhaul_entire_instances')->where('status', '1')->where('fixer_id', $overhaulStatistic->accountId)->whereBetween('allocate_at', [$yearMonth.'-01'.' '. '00:00:00',$yearMonth.'-31'.' '. '00:00:00'])->count()}}</a><br>
                                                    <a href="javascript:" onclick="fnOverhaulEntireInstance('{{ $yearMonth }}', '{{ $overhaulStatistic->accountId }}', 'tab_3')">超期完成：{{\Illuminate\Support\Facades\DB::table('overhaul_entire_instances')->where('status', '2')->where('fixer_id', $overhaulStatistic->accountId)->whereBetween('allocate_at', [$yearMonth.'-01'.' '. '00:00:00',$yearMonth.'-31'.' '. '00:00:00'])->count()}}</a><br>
                                                    <a href="javascript:" onclick="fnOverhaulEntireInstance('{{ $yearMonth }}', '{{ $overhaulStatistic->accountId }}', 'tab_4')">未完成：{{\Illuminate\Support\Facades\DB::table('overhaul_entire_instances')->where('status', '0')->where('fixer_id', $overhaulStatistic->accountId)->whereBetween('allocate_at', [$yearMonth.'-01'.' '. '00:00:00',$yearMonth.'-31'.' '. '00:00:00'])->count()}}</a>
                                                </td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!--仓库位置图-->
    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" id="locationShow">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">位置：<span id="title"></span></h4>
                </div>
                <div class="modal-body">
                    <img id="location_img" class="model-body-location" alt="" style="width: 100%;">
                    <div class="spot"></div>
                </div>
            </div>
        </div>
    </div>

    <!--任务内检修分配->检修分配-->
    <div class="modal fade" id="modalOverhaul">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">检修分配</h4>
                </div>
                <div class="modal-body form-horizontal">
                    <div class="form-group">
                        <label for="selWorkArea" class="col-sm-3 col-md-3 control-label">工区：</label>
                        <div class="col-sm-9 col-md-8">
                            <select id="selWorkArea" name="work_area" class="select2 form-control" style="width: 100%;" disabled>
                                <option value="{{ $taskOrder->WorkAreaByUniqueCode->name }}">{{ $taskOrder->WorkAreaByUniqueCode->name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">截至日期：</label>
                        <div class="col-sm-9 col-md-8">
                            <input id="deadLine" name="fixed_at" type="text" class="form-control" autocomplete="off" value="{{ $taskOrder->expiring_at ? date('Y-m-d',strtotime($taskOrder->expiring_at)) : '' }}" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">检修人：</label>
                        <div class="col-sm-9 col-md-8">
                            <select id="selAccount" name="account_id" class="select2 form-control" style="width: 100%;">
                                <option disabled selected>请选择</option>
                                @foreach(\Illuminate\Support\Facades\DB::table('accounts')->where('work_area_unique_code', $taskOrder->WorkAreaByUniqueCode->unique_code)->get()->toArray() as $v)
                                    <option value="{{ $v->id }}">{{ $v->nickname }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    <a href="javascript:" onclick="fnStoreOverhaul('{{ $sn }}')" class="btn btn-success btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>确定</a>
                </div>
            </div>
        </div>
    </div>

    <!--所内检修分配->检修分配-->
    <div class="modal fade" id="fixMisson">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">检修任务分配</h4>
                </div>
                <div class="modal-body form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">月份：</label>
                        <div class="col-sm-9 col-md-8">
                            <select id="dates1" name="dates1" class="select2 form-control" style="width: 100%;">
                                @foreach($dates as $v)
                                    <option value="{{ $v }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="selWorkArea" class="col-sm-3 col-md-3 control-label">工区：</label>
                        <div class="col-sm-9 col-md-8">
                            <select id="selWorkArea1" name="work_area1" class="select2 form-control" style="width: 100%;" disabled>
                                <option value="{{ $taskOrder->WorkAreaByUniqueCode->name }}">{{ $taskOrder->WorkAreaByUniqueCode->name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">检修人：</label>
                        <div class="col-sm-9 col-md-8">
                            <select id="selAccount1" name="account_id1" class="select2 form-control" style="width: 100%;">
                                <option disabled selected>请选择</option>
                                @foreach(\Illuminate\Support\Facades\DB::table('accounts')->where('work_area_unique_code', $taskOrder->WorkAreaByUniqueCode->unique_code)->get()->toArray() as $v)
                                    <option value="{{ $v->id }}">{{ $v->nickname }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">截至日期：</label>
                        <div class="col-sm-9 col-md-8">
                            <input id="deadLine1" name="fixed_at" type="text" class="form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    <a href="javascript:" onclick="fnStoreOverhaul1('{{ $sn }}')" class="btn btn-success btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>确定</a>
                </div>
            </div>
        </div>
    </div>

    <!--检修统计设备列表-->
    <div class="modal fade" id="modalOverhaulEntireInstance">
        <div class="modal-dialog modal-dialog-centered" style="width:80vw;height:90vh">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">检修统计</h4>
                </div>
                <div class="modal-body form-horizontal">
                    <iframe id="url" src="" style="width:calc(80vw - 30px);height: calc(90vh - 95px);border:none;margin:auto;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                </div>
            </div>
        </div>
    </div>

</section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let selEntireModel = $('#selEntireModel');
        let selSubModel = $('#selSubModel');

        $(function () {
            async function fnInitData() {
                await fnSelectCategory($('#selCategory').val());
            }

            fnInitData();

            if ($select2.length > 0) {
                $select2.select2();
            }

            var date = new Date();
            // 日期选择器
            let datepickerOption = {
                autoclose: true,
                todayHighlight: true,
                language: "cn",
                format: "yyyy-mm-dd",
                beforeShowDay: $.noop,
                calendarWeeks: false,
                clearBtn: true,
                daysOfWeekDisabled: [],
                endDate: Infinity,
                forceParse: true,
                keyboardNavigation: true,
                minViewMode: 0,
                orientation: "auto",
                rtl: false,
                startDate: date.toLocaleDateString(),
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#deadLine1').datepicker(datepickerOption);

            /**
             * 获取url
             */
            function GetQueryString(name) {
                var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
                var r = window.location.search.substr(1).match(reg); //获取url中"?"符后的字符串并正则匹配
                var context = "";
                if (r != null)
                    context = r[2];
                reg = null;
                r = null;
                return context == null || context == "" || context == "undefined" ? "" : context;
            }
            // 默认全局变量
            window.type = GetQueryString('type');
            window.sn = GetQueryString('sn');
        });

        // tab_1 全选or全不选
        var all1 = document.getElementsByName("all1")[0];
        var checks1 = document.getElementsByName("check1");
        // 实现全选和全不选
        all1.onclick = function () {
            for (var i = 0; i < checks1.length; i++) {
                checks1[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择
        for (var j = 0; j < checks1.length; j++) {
            checks1[j].onclick = function () {
                var count = 0; //定义一个计数器
                for (var m = 0; m < checks1.length; m++) {
                    //判断是否取消全选
                    if (!checks1[m].checked) {
                        all1.checked = false;
                    } else { //如果是选中状态，计数器+1
                        count++;
                    }
                    //判断是否都是选中状态/如果是则自动选中全选按钮
                    if (count == checks1.length) {
                        all1.checked = true;
                    }
                }
            }
        }

        // tab_2 全选or全不选
        var all2 = document.getElementsByName("all2")[0];
        var checks2 = document.getElementsByName("check2");
        // 实现全选和全不选
        all2.onclick = function () {
            for (var i = 0; i < checks2.length; i++) {
                checks2[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择
        for (var j = 0; j < checks2.length; j++) {
            checks2[j].onclick = function () {
                var count = 0; //定义一个计数器
                for (var m = 0; m < checks2.length; m++) {
                    //判断是否取消全选
                    if (!checks2[m].checked) {
                        all2.checked = false;
                    } else { //如果是选中状态，计数器+1
                        count++;
                    }
                    //判断是否都是选中状态/如果是则自动选中全选按钮
                    if (count == checks2.length) {
                        all2.checked = true;
                    }
                }
            }
        }

        /**
         * 点击选项卡获取tab
         */
        function fnTabs(tabs) {
            window.type = tabs;
            document.getElementById('type').value = tabs;
        }

        /**
         * 检修统计设备列表iframe
         */
        function fnOverhaulEntireInstance(yearMonth, accountId, tab) {
            document.getElementById('url').src = `{{ url('v250OverhaulEntireInstance/') }}?year_month=${yearMonth}&account_id=${accountId}&type=${tab}&is_iframe=1`;
            $('#modalOverhaulEntireInstance').modal('show')
            $('#modalOverhaulEntireInstance').on('hidden.bs.modal', function (e) {
                location.href = `{{ url('v250Overhaul/') }}?type=tab_3&sn=${sn}&is_iframe=1`;
            })
        }

        /**
         * 搜索
         * @param event
         * @returns {boolean}
         */
        function fnQuery(sn) {
            var categoryUniqueCode = $('#selCategory').val();
            var entireModelUniqueCode = $('#selEntireModel').val();
            var subModelUniqueCode = $('#selSubModel').val();
            var factoryName = $('#factories').val();
            var entireInstanceUniqueCode = $('#entireInstanceUniqueCode').val();
            location.href = `{{ url('v250Overhaul') }}?type=${type}&sn=${sn}&categoryUniqueCode=${categoryUniqueCode}&entireModelUniqueCode=${entireModelUniqueCode}&subModelUniqueCode=${subModelUniqueCode}&factoryName=${factoryName}&entireInstanceUniqueCode=${entireInstanceUniqueCode}&is_iframe=1`;
        }

        /**
         * 选择种类，获取类型列表
         * @param {string} categoryUniqueCode
         */
        function fnSelectCategory(categoryUniqueCode) {
            let html = '<option value="">全部<option>';
            if (categoryUniqueCode !== '') {
                $.ajax({
                    url: `/query/entireModels/${categoryUniqueCode}`,
                    type: 'get',
                    data: {},
                    async: false,
                    success: res => {
                        $.each(res, (entireModelUniqueCode, entireModelName) => {
                            html += `<option value=${entireModelUniqueCode} ${"{{request('entireModelUniqueCode')}}" === entireModelUniqueCode ? 'selected' : ''}>${entireModelName}</option>`;
                        });
                        selEntireModel.html(html);
                        fnSelectEntireModel(selEntireModel.val());
                    },
                    error: err => {
                        console.log(`query/entireModels/${categoryUniqueCode} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selEntireModel.html(html);
                selSubModel.html(html);
            }
        }

        /**
         * 根据类型，获取型号列表
         * @param {string} entireModelUniqueCode
         */
        function fnSelectEntireModel(entireModelUniqueCode) {
            let html = '<option value="">全部<option>';
            if (entireModelUniqueCode !== '') {
                $.ajax({
                    url: `/query/subModels/${entireModelUniqueCode}`,
                    type: 'get',
                    data: {},
                    async: true,
                    success: res => {
                        $.each(res, (subModelUniqueCode, subModelName) => {
                            html += `<option value=${subModelUniqueCode} ${"{{request('subModelUniqueCode')}}" === subModelUniqueCode ? 'selected' : ''}>${subModelName}</option>`;
                        });
                        selSubModel.html(html);
                    },
                    error: err => {
                        console.log(`query/subModels/${entireModelUniqueCode} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selSubModel.html(html);
            }
        }

        /**
         * 任务内检修分配->打开模态框
         */
        function fnOverhaul(type) {
            if (type == 1) {
                //处理数据
                let selected_for_fix_misson = [];
                $("input[type='checkbox'][name='check1']:checked").each((index, item) => {
                    let new_code = $(item).val();
                    if (new_code !== '') selected_for_fix_misson.push(new_code);
                });
                if (selected_for_fix_misson.length <= 0) {
                    alert('请先选择设备');
                    return false;
                }
                $('#modalOverhaul').modal('show');
            } else {
                //处理数据
                let selected_for_fix_misson = [];
                $("input[type='checkbox'][name='check2']:checked").each((index, item) => {
                    let new_code = $(item).val();
                    if (new_code !== '') selected_for_fix_misson.push(new_code);
                });
                if (selected_for_fix_misson.length <= 0) {
                    alert('请先选择设备');
                    return false;
                }
                $('#fixMisson').modal('show');
            }

        }

        /**
         * 任务内检修分配
         */
        function fnStoreOverhaul(sn) {
            var selAccountId = document.getElementById('selAccount').value;
            if (selAccountId === '请选择') {
                alert('请选择检修人');
                return false;
            }
            //处理数据
            let selected_for_fix_misson = [];
            $("input[type='checkbox'][name='check1']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_fix_misson.push(new_code);
            });
            if (selected_for_fix_misson.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            $.ajax({
                url: `{{ url('v250Overhaul') }}/${sn}/storeOverhaul`,
                type: 'post',
                data: {'selected_for_fix_misson': selected_for_fix_misson, 'selAccountId': selAccountId},
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250Overhaul') }}/${sn}/storeOverhaul success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('v250Overhaul') }}/${sn}/storeOverhaul fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 所内检修分配
         */
        function fnStoreOverhaul1(sn) {
            var selAccountId = document.getElementById('selAccount1').value;
            if (selAccountId === '请选择') {
                alert('请选择检修人');
                return false;
            }
            //处理数据
            let selected_for_fix_misson = [];
            $("input[type='checkbox'][name='check2']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_fix_misson.push(new_code);
            });
            if (selected_for_fix_misson.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            let dates = document.getElementById('dates1').value;
            let selAccount = document.getElementById('selAccount1').value;
            let deadLine = document.getElementById('deadLine1').value;
            if (!deadLine) {
                alert('请选择截止日期');
                return false;
            }

            $.ajax({
                url: `{{ url('v250Overhaul') }}/${sn}/storeOverhaul1`,
                type: 'post',
                data: {
                    'selected_for_fix_misson': selected_for_fix_misson,
                    'dates': dates,
                    'selAccountId': selAccountId,
                    'deadLine': deadLine
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250Overhaul') }}/${sn}/storeOverhaul1 success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('v250Overhaul') }}/${sn}/storeOverhaul1 fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 查找位置
         * @param identity_code
         */
        function fnLocation(identity_code) {
            $.ajax({
                url: `{{url('storehouse/location/getImg')}}/${identity_code}`,
                type: 'get',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        console.log(response);
                        $('#title').text(response.data.location_full_name);
                        let location_img = response.data.location_img;
                        if (location_img) {
                            document.getElementById('location_img').src = location_img;
                            $("#locationShow").modal("show");
                        } else {
                            alert('请联系管理员，绑定位置图片');
                            // location.reload();
                        }
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }
    </script>
@endsection

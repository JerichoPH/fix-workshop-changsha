@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <section class="invoice">
        <div class="row">
            <div class="col-xs-12">
                <h2 class="page-header">
                    <i class="fa fa-globe"></i> 检修车间器材全生命周期管理系统
                    <small class="pull-right">
                        日期：{{ $warehouseReport->processed_at }}
                    </small>
                </h2>
            </div>
        </div>
        <div class="row invoice-info">
            <div class="col-sm-6 invoice-col">
                <strong>基本信息</strong>
                <address>
                    序列号：{{ $warehouseReport->serial_number }}<br>
                    经手人：{{ $warehouseReport->Processor ? $warehouseReport->Processor->nickname : '' }}<br>
                    联系人姓名：{{ $warehouseReport->connection_name }}<br>
                    联系电话：{{ $warehouseReport->connection_phone }}<br>
                    时间：{{ $warehouseReport->processed_at }}<br>
                    类型：{{ $warehouseReport->type }}<br>
                </address>
            </div>
            <div class="col-sm-6 invoice-col">
                <strong>设备类型及数量</strong>
                <address>
                    @foreach($entireModels as $entireModelName=>$entireInstanceIdentityCodes)
                        {{ $entireModelName }}（{{ count($entireInstanceIdentityCodes) }}）<br>
                    @endforeach
                </address>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-6">
            </div>
            <div class="col-xs-6">
                <p class="lead">统计</p>
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>总计</th>
                            <td>{{ count($warehouseReport->WarehouseReportEntireInstances) }}&nbsp;件</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row no-print">
            <div class="col-xs-12">
                <a
                    href="{{ url('warehouse/report') }}?page={{ request('page',1) }}&direction={{ request('direction') }}&current_work_area={{ request('current_work_area') }}&updated_at={{ request('updated_at') }}&show_type={{ request('show_type') }}"
                    class="btn btn-default pull-left btn-flat btn-sm"
                >
                    <i class="fa fa-arrow-left">&nbsp;</i>返回
                </a>
            </div>
        </div>
    </section>

    <!--设备列表-->
    <section class="invoice">
        <div class="box box-solid">
            <div class="box-header">
                <h1 class="box-title">出入所设备列表</h1>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm btn-sm">
                    <a href="{{ url('storehouse/index/in') }}" class="btn btn-default btn-flat"><i class="fa fa-toggle-right">&nbsp;</i>设备/器材入库</a>
                    <a href="javascript:" class="btn btn-warning btn-flat" onclick="modalBatchEdit()">批量修改</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="chkAllCheck" checked></th>
                        <th>设备编号<br>所编号</th>
                        <th>型号</th>
                        <th>厂家<br>厂编号</th>
                        <th>生产日期<br>寿命(年)</th>
                        <th>到期日期</th>
                        <th>现场车间/车站<br>上道位置</th>
                        <th>状态</th>
                        <th>检测/检修人<br>检测/检修时间</th>
                        <th>验收人<br>验收时间</th>
                        <th>抽验人<br>抽验时间</th>
                        <th>上道日期<br>出所日期</th>
                        <th>周期修(年)<br>下次周期修日期</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($warehouseReport->WarehouseReportEntireInstances as $WarehouseReportEntireInstance)
                        <tr>
                            <td><input type="checkbox" name="chk_identity_code" class="chk-entire-instances" value="{{ $WarehouseReportEntireInstance->EntireInstance->identity_code }}" checked></td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->identity_code }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->serial_number ?: '无' }}
                            </td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->Category->name ?: '无' }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->SubModel->name }}
                                {{ @$WarehouseReportEntireInstance->EntireInstance->PartModel->name }}
                            </td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->Factory->name ?: '无' }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->factory_device_code ?: '无' }}
                            </td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($WarehouseReportEntireInstance->EntireInstance->made_at)) : '无' }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->life_year }}
                            </td>
                            <td>{{ @$WarehouseReportEntireInstance->EntireInstance->scraping_at ? date('Y-m-d',strtotime($WarehouseReportEntireInstance->EntireInstance->scraping_at)) : '无' }}</td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->SceneWorkshop->name }}
                                {{ @$WarehouseReportEntireInstance->EntireInstance->Station->name }}
                                <br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->InstallPosition->real_name ?: @$WarehouseReportEntireInstance->EntireInstance->maintain_location_code }}
                                {{ @$WarehouseReportEntireInstance->EntireInstance->crossrod_number }}
                                {{ @$WarehouseReportEntireInstance->EntireInstance->open_direction }}
                            </td>
                            <td>{{ @$WarehouseReportEntireInstance->EntireInstance->status }}</td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->fixer_name ?: '无' }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->fixed_at ? date('Y-m-d',strtotime($WarehouseReportEntireInstance->EntireInstance->fixed_at)) :'无' }}
                            </td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->checker_name ?: '无' }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->checked_at ? date('Y-m-d',strtotime($WarehouseReportEntireInstance->EntireInstance->checked_at)) :'无' }}
                            </td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->spot_checker_name ?: '无' }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->spot_checked_at ? date('Y-m-d',strtotime($WarehouseReportEntireInstance->EntireInstance->spot_checked_at)) :'无' }}
                            </td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->last_installed_time ? date('Y-m-d',@$WarehouseReportEntireInstance->EntireInstance->last_installed_time) : '无' }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->last_out_at ? date('Y-m-d',strtotime(@$WarehouseReportEntireInstance->EntireInstance->last_out_at)) : '无' }}
                            </td>
                            <td>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->fix_cycle_value ?: 0 }}<br>
                                {{ @$WarehouseReportEntireInstance->EntireInstance->next_fixing_time ? date('Y-m-d',@$WarehouseReportEntireInstance->EntireInstance->next_fixing_time) : '无' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{--模态框--}}
    <section class="section">
        {{--批量修改模态框--}}
        <div class="modal fade" id="modalEditBatch">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">批量修改&emsp;<small class="text-danger">无需修改项选“无”或不填</small></h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmUpdateBatch">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">厂家：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select
                                        name="factory_name"
                                        id="selFactory"
                                        class="form-control select2"
                                        style="width: 100%;"
                                    >
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">生产日期：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input
                                        type="text"
                                        name="made_at"
                                        id="dpMadeAt"
                                        class="form-control"
                                        onkeydown="if(13===event.keyCode) fnGenerateScrapingAt(dpMadeAt.value,numLifeYear.value)"
                                        onchange="fnGenerateScrapingAt(dpMadeAt.value,numLifeYear.value)"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">寿命(年)：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        name="life_year"
                                        id="numLifeYear"
                                        class="form-control"
                                        onkeydown="if(13===event.keyCode) fnGenerateScrapingAt(dpMadeAt.value,numLifeYear.value)"
                                        onchange="fnGenerateScrapingAt(dpMadeAt.value,numLifeYear.value)"
                                    />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">报废日期：</label>
                                <div class="col-sm-9 col-md-8">
                                    <div class="input-group">
                                        <input
                                            type="text"
                                            name="scraping_at"
                                            id="txtScrapingAt"
                                            class="form-control disabled"
                                            disabled
                                        />
                                        <div class="input-group-btn">
                                            <a href="javascript:" class="btn btn-default btn-flat" onclick="fnGenerateScrapingAt(dpMadeAt.value,numLifeYear.value)">计算</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">上道日期：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input
                                        type="text"
                                        name="last_installed_time"
                                        id="dpLastInstalledTime"
                                        class="form-control"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">出所日期：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input
                                        type="text"
                                        name="last_out_at"
                                        id="dpLastOutAt"
                                        class="form-control"
                                        onkeydown="if(13===event.keyCode) fnGenerateNextFixingTime(dpLastOutAt.value,numFixCycleValue.value)"
                                        onchange="fnGenerateNextFixingTime(dpLastOutAt.value,numFixCycleValue.value)"
                                    />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">周期修(年)：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        name="fix_cycle_value"
                                        id="numFixCycleValue"
                                        class="form-control"
                                        value="0"
                                        onkeydown="if(13===event.keyCode) fnGenerateNextFixingTime(dpLastOutAt.value,numFixCycleValue.value)"
                                        onchange="fnGenerateNextFixingTime(dpLastOutAt.value,numFixCycleValue.value)"
                                    />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">下次周期修日期：</label>
                                <div class="col-sm-9 col-md-8">
                                    <div class="input-group">
                                        <input
                                            type="text"
                                            name="next_fixing_time"
                                            id="txtNextFixingTime"
                                            class="form-control disabled"
                                            disabled
                                        />
                                        <div class="input-group-btn">
                                            <a href="javascript:" class="btn btn-default btn-flat" onclick="fnGenerateNextFixingTime(dpLastOutAt.value,numFixCycleValue.value)">计算</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{--<div class="form-group">--}}
                            {{--    <label class="col-sm-3 col-md-3 control-label">现场车间：</label>--}}
                            {{--    <div class="col-sm-9 col-md-8">--}}
                            {{--        <select--}}
                            {{--            name="scene_workshop_unique_code"--}}
                            {{--            id="selSceneWorkshop"--}}
                            {{--            class="form-control select2"--}}
                            {{--            style="width: 100%;"--}}
                            {{--            onchange="fnFillStation(this.value)"--}}
                            {{--        ></select>--}}
                            {{--    </div>--}}
                            {{--</div>--}}
                            {{--<div class="form-group">--}}
                            {{--    <label class="col-sm-3 col-md-3 control-label">车站：</label>--}}
                            {{--    <div class="col-sm-9 col-md-8">--}}
                            {{--        <select--}}
                            {{--            name="maintain_station_unique_code"--}}
                            {{--            id="selStation"--}}
                            {{--            class="form-control select2"--}}
                            {{--            style="width: 100%;"--}}
                            {{--        ></select>--}}
                            {{--    </div>--}}
                            {{--</div>--}}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdateBatch()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="clearfix"></div>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $selType = $('#selType');
        let $chkAllCheck = $('#chkAllCheck');
        let $modalEditBatch = $('#modalEditBatch');
        let $frmUpdateBatch = $('#frmUpdateBatch');
        let $selFactory = $('#selFactory');
        let $dpMadeAt = $('#dpMadeAt');
        let $numLifeYear = $('#numLifeYear');
        let $txtScrapingAt = $('#txtScrapingAt');
        let $dpLastInstalledTime = $('#dpLastInstalledTime');
        let $dpLastOutAt = $('#dpLastOutAt');
        let $numFixCycleValue = $('#numFixCycleValue');
        let $txtNextFixingTime = $('#txtNextFixingTime');
        let $selSceneWorkshop = $('#selSceneWorkshop');
        let $selStation = $('#selStation');

        let factories = {!! $factories_as_json !!};
        let sceneWorkshops = {!! $scene_workshops_as_json !!};
        let stations = {!! $stations_as_json !!};

        /**
         * 填充供应商下拉列表
         */
        function fnFillFactory() {
            let html = `<option value="">无</option>`;
            if (factories.length > 0) {
                factories.map(function (factory) {
                    let {name} = factory;
                    html += `<option value="${name}">${name}</option>`;
                });
            }
            $selFactory.html(html);
        }

        /**
         * 填充现场车间下拉列表
         */
        function fnFillSceneWorkshop() {
            let html = '<option value="">无</option>';
            if (sceneWorkshops.length > 0) {
                sceneWorkshops.map(function (item) {
                    let {unique_code: uniqueCode, name} = item;
                    html += `<option value="${uniqueCode}">${name}</option>`;
                });
            }
            $selSceneWorkshop.html(html);
            fnFillStation($selSceneWorkshop.val());  // 刷新车站列表
        }

        /**
         * 根据现场车间填充车站下拉列表
         * @param {string} sceneWorkshopUniqueCode 现场车间代码
         */
        function fnFillStation(sceneWorkshopUniqueCode = '') {
            let html = '<option value="">无</option>';

            $.each(
                stations.hasOwnProperty(sceneWorkshopUniqueCode) ? [stations[sceneWorkshopUniqueCode]] : stations,
                function (swun, ss) {
                    $.each(ss, function (k, v) {
                        let {unique_code: uniqueCode, name} = v;
                        html += `<option value="${uniqueCode}">${name}</option>`;
                    })
                }
            );

            $selStation.html(html);
        }

        /**
         * 全选多选框绑定
         * @param {string} allCheckId
         * @param {string} checkClassName
         */
        function fnAllCheckBind(allCheckId, checkClassName) {
            $(allCheckId).on('click', function () {
                $(checkClassName).prop('checked', $(allCheckId).prop('checked'));
            });
            $('.chk-entire-instances').on('click', function () {
                $(allCheckId).prop('checked', $(`${checkClassName}:checked`).length === $(checkClassName).length);
            });
        }

        fnAllCheckBind('#chkAllCheck', '.chk-entire-instances');

        $(function () {
            if ($select2.length > 0) $select2.select2();

            // 日期选择器
            let datepickerProperty = {
                format: "yyyy-mm-dd",
                language: "cn",
                clearBtn: true,
                autoclose: true,
            };

            $('#dpMadeAt').datepicker(datepickerProperty);

            // $dpMadeAt.datepicker(datepickerProperty);
            $dpLastInstalledTime.datepicker(datepickerProperty);
            $dpLastOutAt.datepicker(datepickerProperty);

            // 关闭批量修改模态框时刷新页面
            $modalEditBatch.on('hidden.bs.modal', function (e) {
                location.reload();
            });

            fnFillFactory();  // 刷新供应商
            // fnFillSceneWorkshop();  // 刷新现场车间列表
        });

        /**
         * 打开批量修改模态框
         */
        function modalBatchEdit() {
            let identityCodes = [];
            $(".chk-entire-instances:checked:not('distabled')").each(function (idx, item) {
                identityCodes.push(item.value);
            });
            if (identityCodes.length <= 0) {
                alert('请勾选设备');
                return;
            }
            $modalEditBatch.modal('show');
        }

        /**
         * 批量修改
         */
        function fnUpdateBatch() {
            let data = $frmUpdateBatch.serializeArray();
            $(".chk-entire-instances:checked:not('distabled')").each(function (idx, item) {
                data.push({name: 'identity_codes[]', value: item.value});
            });
            data.push({name: 'last_installed_time', value: $dpLastInstalledTime.val()});  // 上道日期
            data.push({name: 'next_fixing_time', value: $txtNextFixingTime.val()});  // 下次周期修日期
            data.push({name: 'scraping_at', value: $txtScrapingAt.val()});  // 报废日期

            $.ajax({
                url: `{{ url('entire/instance/updateBatch') }}`,
                type: 'put',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/instance/updateBatch') }} success:`, res);
                    alert(res.msg);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('entire/instance/updateBatch') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    if (err.responseJSON.msg.constructor === Object) {
                        let message = '';
                        for (let msg of err.responseJSON.msg) message += `${msg}\r\n`;
                        alert(message);
                        return;
                    }
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 计算报废日期
         * @param {string} madeAt 生产日期
         * @param {int} lifeYear 寿命(年)
         */
        function fnGenerateScrapingAt(madeAt = '', lifeYear = 0) {
            if (madeAt !== '' && lifeYear > 0) {
                $txtScrapingAt.val(moment(madeAt).add(lifeYear, 'years').format('YYYY-MM-DD'));
            } else {
                $txtScrapingAt.val('');
            }
        }

        /**
         * 计算下次周期修日期
         * @param {string} lastOutAt 最后出所日期
         * @param {int} fixCycleValue 周期修(年)
         */
        function fnGenerateNextFixingTime(lastOutAt = '', fixCycleValue = 0) {
            if (lastOutAt !== '' && fixCycleValue > 0) {
                $txtNextFixingTime.val(moment(lastOutAt).add(fixCycleValue, 'years').format('YYYY-MM-DD'));
            } else {
                $txtNextFixingTime.val('');
            }
        }
    </script>
@endsection

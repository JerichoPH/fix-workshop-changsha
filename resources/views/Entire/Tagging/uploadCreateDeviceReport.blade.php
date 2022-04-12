@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            上传设备赋码Excel
            <small>结果</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li><a href="{{ url('entire/tagging/uploadCreateDevice') }}">设备赋码</a></li>--}}
        {{--    <li class="active">上传设备赋码Excel结果</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">上传设备赋码Excel结果</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('entire/tagging/uploadCreateDevice') }}" class="btn btn-default btn-flat">返回设备赋码</a>
                            @switch(env('ORGANIZATION_CODE'))
                                @case('B050')
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnPrint('printQrCode',2)">打印二维码(20×12)</a>
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnPrint('printQrCode',3)">打印二维码(40×25)</a>
                                @break
                                @default
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnPrint('printQrCode',1)">打印二维码(35×20)</a>
                                @break
                            @endswitch
                            <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnPrint('printLabel')">打印位置标签</a>
                            <a href="javascript:" class="btn btn-warning btn-flat" onclick="modalBatchEdit()">批量修改</a>
                            @if($hasCreateDeviceError)
                                <p>
                                    <span class="text-danger">上传设备赋码Excel有错误</span>，<a href="{{ url('entire/tagging',$entireInstanceExcelTaggingReport->serial_number) }}/downloadCreateDeviceErrorExcel?{{ http_build_query(['path'=>$createDeviceErrorFilename]) }}" target="_blank">下载错误报告</a>
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                @if($entireInstances->hasPages())
                                    <div>
                                        <div class="pull-left">
                                            共计：{{ $entireInstances->total() }}&emsp;当前页：{{ $current_total }}
                                        </div>
                                        <div class="pull-right">
                                            {{ $entireInstances->appends(['page'=>request('page',1),'type'=>request('type')])->links() }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-condensed table-hover table-bordered">
                                        <thead>
                                        <tr>
                                            <th><input type="checkbox" id="chkAllCheck" checked></th>
                                            <th>序号</th>
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
                                            <th>出所日期<br>上道日期</th>
                                            <th>周期修(年)<br>下次周期修日期</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php $i=0;?>
                                        @foreach($entireInstances as $entireInstance)
                                            <tr>
                                                <td><input type="checkbox" name="chk_identity_code" class="chk-entire-instances" value="{{ $entireInstance->EntireInstance->identity_code }}" checked></td>
                                                <td>{{ ++$i }}</td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->identity_code }}<br>
                                                    {{ @$entireInstance->EntireInstance->serial_number ?: '无' }}
                                                </td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->Category->name ?: '无' }}<br>
                                                    {{ @$entireInstance->EntireInstance->SubModel->name }}
                                                    {{ @$entireInstance->EntireInstance->PartModel->name }}
                                                </td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->Factory->name ?: '无' }}<br>
                                                    {{ @$entireInstance->EntireInstance->factory_device_code ?: '无' }}
                                                </td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($entireInstance->EntireInstance->made_at)) : '无' }}<br>
                                                    {{ @$entireInstance->EntireInstance->life_year }}
                                                </td>
                                                <td>{{ @$entireInstance->EntireInstance->scarping_at ? date('Y-m-d',strtotime($entireInstance->EntireInstance->scarping_at)) : '无' }}</td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->SceneWorkshop->name }}
                                                    {{ @$entireInstance->EntireInstance->Station->name }}
                                                    <br>
                                                    {{ @$entireInstance->EntireInstance->maintain_location_code }}
                                                    {{ @$entireInstance->EntireInstance->crossroad_number }}
                                                    {{ @$entireInstance->EntireInstance->open_direction }}
                                                </td>
                                                <td>{{ @$entireInstance->EntireInstance->status }}</td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->fixer_name ?: '无' }}<br>
                                                    {{ @$entireInstance->EntireInstance->fixed_at ? date('Y-m-d',strtotime($entireInstance->EntireInstance->fixed_at)) : '无' }}
                                                </td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->checker_name ?: '无' }}<br>
                                                    {{ @$entireInstance->EntireInstance->checked_at ? date('Y-m-d',strtotime($entireInstance->EntireInstance->checked_at)) : '无' }}
                                                </td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->spot_checker_name ?: '无' }}<br>
                                                    {{ @$entireInstance->EntireInstance->spot_checked_at ? date('Y-m-d',strtotime($entireInstance->EntireInstance->spot_checked_at)) : '无' }}
                                                </td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->last_out_at ? date('Y-m-d',strtotime(@$entireInstance->EntireInstance->last_out_at)) : '无' }}
                                                    {{ @$entireInstance->EntireInstance->last_installed_time ? strtotime(@$entireInstance->EntireInstance->last_installed_time) : '无' }}
                                                </td>
                                                <td>
                                                    {{ @$entireInstance->EntireInstance->fix_cycle_value ?: 0 }}<br>
                                                    {{ @$entireInstance->EntireInstance->next_fixing_time ? date('Y-m-d',@$entireInstance->EntireInstance->next_fixing_time) : '无' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($entireInstances->hasPages())
                        <div class="box-footer">
                            <div class="pull-left">
                                共计：{{ $entireInstances->total() }}&emsp;当前页：{{ $current_total }}
                            </div>
                            <div class="pull-right">
                                {{ $entireInstances->appends(['page'=>request('page',1),'type'=>request('type')])->links() }}
                            </div>
                        </div>
                    @endif
                </div>
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
         * 打印标签
         * @param {string} type
         * @param {string} sizeType
         */
        function fnPrint(type, sizeType) {
            // 处理数据
            let identityCodes = [];
            $(".chk-entire-instances:checked:not('disabled')").each((index, item) => {
                let code = item.value;
                if (code !== '') identityCodes.push(code);
            });

            if (identityCodes.length <= 0) {
                alert('请选择打印标签设备');
                return false;
            }

            $.ajax({
                url: `{{ url('warehouse/report/identityCodeWithPrint') }}`,
                type: 'post',
                data: {identityCodes,},
                async: true,
                success: function (response) {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        window.open(`/qrcode/${type}?$size_type=${sizeType}`);
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (error) {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }

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

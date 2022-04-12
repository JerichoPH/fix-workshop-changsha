@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            整件管理
            <small>编辑</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('entire/instance') }}"><i class="fa fa-users">&nbsp;</i>整件管理</a></li>--}}
{{--            <li class="active">编辑</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑设备&nbsp;{{ @$entireInstance->identity_code }}</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="form-group">
                            <label class="col-md-3 control-label">型号：</label>
                            <label class="col-md-3 control-label" style="text-align: left; font-weight: normal;">{{ @$entireInstance->Category->name . ' ' . @$entireInstance->model_name }}</label>
                            <label class="col-md-3 control-label">唯一编号：</label>
                            <label class="col-md-3 control-label" style="text-align: left; font-weight: normal;">{{ @$entireInstance->identity_code }}</label>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">所编号：</label>
                            <div class="col-sm-10 col-md-8">
                                <input
                                    class="form-control"
                                    name="serial_number" type="text" placeholder="所编号" value="{{ @$entireInstance->serial_number }}"
                                    required autofocus onkeydown="if(event.keyCode===13){return false;}"
                                >
                                @if(@$entireInstance->serial_number)
                                    <div class="help-block">
                                        <a href="javascript:" onclick="fnToSameSerialNumber('{{ @$entireInstance->serial_number }}')">
                                            同所编号设备：{{ @$sameSerialNumberCount ?? 0 }}台
                                        </a>
                                    </div>
                                @endif
                                {{--<div class="input-group">--}}
                                {{--<div class="input-group-btn">--}}
                                {{--<a href="javascript:" class="btn btn-info btn-flat" onclick="fnCreateInstall('{{$entireInstance->identity_code}}')">更换位置</a>--}}
                                {{--</div>--}}
                                {{--</div>--}}
                            </div>
                        </div>
                        {{--<div class="form-group">--}}
                        {{--<label class="col-sm-3 control-label">RFID TID：</label>--}}
                        {{--<div class="col-sm-10 col-md-8">--}}
                        {{--<input class="form-control"--}}
                        {{--name="rfid_code" type="text" placeholder="RFID TID" value="{{$entireInstance->rfid_code}}"--}}
                        {{--required autofocus onkeydown="if(event.keyCode===13){return false;}">--}}
                        {{--</div>--}}
                        {{--</div>--}}
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-10 col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <div class="input-group-addon">供应商：</div>
                                            <select name="factory_name" class="form-control select2" style="width:100%;">
                                                @foreach((@$factories ?? []) as $factoryName)
                                                    <option value="{{ @$factoryName }}" {{ @$factoryName == @$entireInstance->factory_name ? 'selected' : ''}}>{{ @$factoryName }}</option>
                                                @endforeach
                                                @if(!in_array(@$entireInstance->factory_name, $factories))
                                                    <option value="{{ @$entireInstance->factory_name ?? '无' }}" selected>{{ @$entireInstance->factory_name ?? '无' }}</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <div class="input-group-addon">出厂日期：</div>
                                            <input
                                                type="text"
                                                name="made_at"
                                                id="dpMadeAt"
                                                class="form-control"
                                                value="{{ @$entireInstance->made_at ? explode(' ',$entireInstance->made_at)[0] : '' }}"
                                                onkeydown="if(13===event.keyCode) fnMadeAt(this.value)"
                                                onchange="fnMadeAt(this.value)"
                                            >
                                            <div class="input-group-addon">出厂编号：</div>
                                            <input class="form-control" name="factory_device_code" type="text" placeholder="出厂编号" value="{{ @$entireInstance->factory_device_code }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{--<div class="form-group">--}}
                        {{--<label class="col-md-3 control-label">主/备用状态：</label>--}}
                        {{--<label class="control-label" style="text-align: left; font-weight: normal;"><input name="is_main" type="radio" {{$entireInstance->is_main == 1 ? 'checked' : ''}} class="minimal" value="1">主用</label>--}}
                        {{--<label class="control-label" style="text-align: left; font-weight: normal;"><input name="is_main" type="radio" {{$entireInstance->is_main == 0 ? 'checked' : ''}} class="minimal" value="0">备用</label>--}}
                        {{--</div>--}}
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-addon">现场车间：</div>
                                    <select name="" id="selSceneWorkshop" class="form-control select2" style="width: 100%;" onchange="fnFillStations()">
                                        <option value="">未选择</option>
                                        @foreach((@$scene_workshops ?? []) as $scene_workshop_unique_code => $scene_workshop_name)
                                            <option value="{{ $scene_workshop_unique_code }}" {{ @$entireInstance->Station->Parent->unique_code === $scene_workshop_unique_code ? 'selected' : '' }}>{{ $scene_workshop_name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-addon">站场：</div>
                                    <select name="maintain_station_name" id="selStation" class="form-control select2" style="width: 100%;"></select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        组合位置：&emsp;
                                        @if(@$sameMaintainLocationCodeCount)
                                            <a href="javascript:" onclick="fnToSameMaintainLocationCode('{{ @$entireInstance->maintain_station_name }}','{{ @$entireInstance->maintain_location_code }}')">
                                                同位置设备{{ @$sameMaintainLocationCodeCount ?? 0 }}台
                                            </a>
                                        @else
                                            同位置设备{{ @$sameMaintainLocationCodeCount ?? 0 }}台
                                        @endif
                                    </div>
                                    <input type="text" name="maintain_location_code" id="txtMaintainLocationCode" class="form-control" value="{{ @$entireInstance->maintain_location_code }}">
                                    <div class="input-group-addon">
                                        道岔号：&emsp;
                                        @if(@$sameCrossroadNumberCount)
                                            <a href="javascript:" onclick="fnToSameCrossroadNumber('{{ @$entireInstance->maintain_station_name }}','{{ @$entireInstance->crossroad_number }}')">
                                                同位置设备：{{ @$sameCrossroadNumberCount ?? 0 }}台
                                            </a>
                                        @else
                                            同位置设备：{{ @$sameCrossroadNumberCount ?? 0 }}台
                                        @endif
                                    </div>
                                    <input type="text" name="crossroad_number" id="txtCrossroadNumber" class="form-control" value="{{ @$entireInstance->crossroad_number }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-addon">道岔类型：</div>
                                    <input type="text" name="crossroad_type" id="txtCrossroadType" class="form-control" value="{{ @$entireInstance->crossroad_type }}">
                                    <div class="input-group-addon">线制：</div>
                                    <input type="text" name="line_name" id="txtLineName" class="form-control" value="{{ @$entireInstance->line_name }}">
                                    <div class="input-group-addon">挤压保护罩：</div>
                                    <div class="input-group-addon">
                                        <input type="radio" name="extrusion_protect" class="minimal" value="1" id="rdoExtrusionProtectYes" {{ @$entireInstance->extrusion_protect ? 'checked' : '' }}>&nbsp;<label for="rdoExtrusionProtectYes">是</label>&nbsp;&nbsp;
                                        <input type="radio" name="extrusion_protect" class="minimal" value="0" id="rdoExtrusionProtectNo" {{ !@$entireInstance->extrusion_protect ? 'checked' : '' }}>&nbsp;<label for="rdoExtrusionProtectNo">否</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-addon">转辙机组合类型：</div>
                                    <input type="text" name="point_switch_group_type" id="txtPointSwitchGroupType" class="form-control" value="{{ @$entireInstance->point_switch_group_type }}">
                                    <div class="input-group-addon">牵引：</div>
                                    <input type="text" name="traction" id="txtTraction" class="form-control" value="{{ @$entireInstance->traction }}">
                                    <div class="input-group-addon">开向：</div>
                                    <input type="text" name="open_direction" id="txtOpenDirection" class="form-control" value="{{ @$entireInstance->open_direction }}">
                                    <div class="input-group-addon">表示杆特征：</div>
                                    <input type="text" name="said_rod" id="txtSaidRod" class="form-control" value="{{ @$entireInstance->said_rod }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-addon">上道日期：</div>
                                    <input type="text" name="last_installed_at" id="dpLastInstalledAt" class="form-control" value="{{ @$entireInstance->last_installed_time ? date('Y-m-d',$entireInstance->last_installed_time) : '' }}" onkeydown="if(13===event.keyCode) fnLastInstalledAt(this.value)" onchange="fnLastInstalledAt(this.value)">
                                    <div class="input-group-addon">出所日期：</div>
                                    <input type="text" name="last_out_at" id="dpWarehouseOutTime" class="form-control" value="{{ @$entireInstance->last_out_at != '0000-00-00 00:00:00' ? (@$entireInstance->last_out_at ? explode(' ',$entireInstance->last_out_at)[0] : '') : '' }}" onkeydown="if(13===event.keyCode) fnLastOutAt(this.value)" onchange="fnLastOutAt(this.value)">
                                    <div class="input-group-addon">报废日期：</div>
                                    <input type="text" name="scarping_at1" id="dpScarpingAt1" class="form-control" value="{{ @date('Y-m-d', strtotime(@$entireInstance->scarping_at)) ?: '' }}" disabled>
                                    <input type="hidden" name="scarping_at" id="dpScarpingAt" value="{{ @date('Y-m-d', strtotime(@$entireInstance->scarping_at)) ?: '' }}">
                                </div>
                            </div>
                        </div>
                        {{--                        <div class="form-group">--}}
                        {{--                            <label class="col-sm-3 control-label">仓库位置：</label>--}}
                        {{--                            <label class="col-sm-8 control-label" style="font-weight: normal; text-align: left;">--}}
                        {{--                                <input type="text" name="location_unique_code" id="txtWarehouseLocation" value="{{ @$entireInstance->location_unique_code }}" class="form-control">--}}
                        {{--                            </label>--}}
                        {{--                        </div>--}}
                        <div class="form-group">
                            <label class="col-sm-3 control-label">状态：</label>
                            <div class="col-sm-8">
                                <select name="status" id="selStatus" class="form-control select2" style="width: 100%;">
                                    @foreach((@$statuses ?? []) as $status_code => $status_name)
                                        <option value="{{ $status_code }}" {{ array_flip($statuses)[@$entireInstance->status] === $status_code ? 'selected' : '' }}>{{ $status_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{--@if(explode(' ', @$entireInstance->next_fixing_day)[0])--}}
                        {{--    <div class="form-group">--}}
                        {{--        <label class="col-sm-3 control-label">下次周期修日期：</label>--}}
                        {{--        <label class="col-sm-8 control-label" style="font-weight: normal; text-align: left;">{{ @date('Y-m-d', strtotime($entireInstance->next_fixing_day)) ?: '无' }}</label>--}}
                        {{--    </div>--}}
                        {{--@endif--}}
                        <div class="form-group">
                            <label class="col-sm-3 control-label"></label>
                            <div class="col-sm-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <div class="input-group-addon">检测人：</div>
                                            <select name="fixer" id="selFixer" class="form-control select2" style="width: 100%;">
                                                <option value="">无</option>
                                                @foreach((@$fixers ?? []) as $fixer_id => $fixer_nickname)
                                                    <option value="{{ $fixer_id }}" {{ $fixer_id === $fixer ? 'selected' : '' }}>{{ $fixer_nickname }}</option>
                                                @endforeach
                                            </select>
                                            <div class="input-group-addon">验收人：</div>
                                            <select name="checker" id="selChecker" class="form-control select2" style="width: 100%;">
                                                <option value="">无</option>
                                                @foreach((@$checkers ?? []) as $checker_id => $checker_nickname)
                                                    <option value="{{ $checker_id }}" {{ $checker_id === $checker ? 'selected' : '' }}>{{ $checker_nickname }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <div class="input-group-addon">检修日期：</div>
{{--                                            <input type="text" name="last_fix_workflow_at" id="dpLastFixWorkflowAt" class="form-control" value="{{ @$entireInstance->last_fix_workflow_at ? @date('Y-m-d', strtotime($entireInstance->last_fix_workflow_at)) : '' }}" onkeydown="if(13===event.keyCode) fnLastFixWorkflowAt(this.value)" onchange="fnLastFixWorkflowAt(this.value)">--}}
                                            <input type="text" name="last_fix_workflow_at" id="dpLastFixWorkflowAt" class="form-control" value="{{ @$entireInstance->last_fix_workflow_at ? @date('Y-m-d', strtotime($entireInstance->last_fix_workflow_at)) : '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">周期修：</label>
                            <div class="col-sm-8 col-md-8">
                                <div class="input-group">
                                    <div class="input-group-addon">年限：</div>
                                    <input type="number" min="0" step="1" max="15" id="dpFixCycleValue" name="fix_cycle_value" onkeydown="return false;" class="form-control" value="{{ @$entireInstance->fix_cycle_value }}" onchange="fnFixCycleValue(this.value)" autocomplete="off">
                                    <div class="input-group-addon">下次周期修日期：</div>
                                    <input type="text" name="next_fixing_day1" id="dpNextFixingDay1" class="form-control" value="{{ @$entireInstance->next_fixing_time ? date('Y-m-d',$entireInstance->next_fixing_time) : '' }}" disabled>
                                    <input type="hidden" name="next_fixing_day" id="dpNextFixingDay" value="{{ @$entireInstance->next_fixing_time ? date('Y-m-d',$entireInstance->next_fixing_time) : '' }}">
                                </div>
{{--                                <div class="help-block"><span class="text-success">计算下次周期修日期规则:根据出所日期/上道日期计算,权重:出所日期>上道日期。</span></div>--}}
                                <div class="help-block"><span class="text-success">计算下次周期修日期规则:根据出所日期/上道日期计算,权重:出所日期>上道日期。</span></div>
                            </div>
                        </div>
                        <div class="box-footer">
                            {{--<a href="{{url('entire/instance')}}" class="btn btn-default btn-flat pull-left btn-lg"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                            <a href="javascript:" onclick="history.back(-1);" class="btn btn-default btn-flat pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a href="javascript:" onclick="fnUpdate()" class="btn btn-warning btn-flat pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="divModalInstall"></div>
    </section>
@endsection
@section('script')
    <script>
        let today = moment().add(1, 'days').format('YYYY-MM-DD');
        let $selSceneWorkshop = $('#selSceneWorkshop');
        let $selStation = $('#selStation');
        let stations = JSON.parse('{!! @$stations_as_json ?? '{}' !!}');

        $(function () {
            $('.select2').select2();

            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });

            // 日期选择器
            $('#dpLastInstalledAt').datepicker({
                format: "yyyy-mm-dd",
                language: "cn",
                clearBtn: true,
                autoclose: true,
                // startDate: today,
                // endData: '9999-12-31',
            });
            $('#dpMadeAt').datepicker({
                format: "yyyy-mm-dd",
                language: "cn",
                clearBtn: true,
                autoclose: true,
                // startDate: today,
                // endData: '9999-12-31',
            });
            $('#dpScarpingAt').datepicker({
                format: "yyyy-mm-dd",
                language: "cn",
                clearBtn: true,
                autoclose: true,
                // startDate: today,
                // endData: '9999-12-31',
            });
            // $('#dpNextFixingDay1').datepicker({
            //     format: "yyyy-mm-dd",
            //     language: "cn",
            //     clearBtn: true,
            //     autoclose: true,
            //     // startDate: today,
            //     // endData: '9999-12-31',
            // });
            $('#dpLastFixWorkflowAt').datepicker({
                format: "yyyy-mm-dd",
                language: "cn",
                clearBtn: true,
                autoclose: true,
                // startDate: today,
                // endData: '9999-12-31',
            });
            $('#dpWarehouseOutTime').datepicker({
                format: "yyyy-mm-dd",
                language: "cn",
                clearBtn: true,
                autoclose: true,
                // startDate: today,
                // endData: '9999-12-31',
            });

            // 填充车站
            fnFillStations();
        });

        $(function () {
            if (document.getElementById('dpFixCycleValue').value == 0) {
                document.getElementById('dpNextFixingDay1').value = null;
                document.getElementById('dpNextFixingDay').value = null;
            }
        });

        /**
         * 根据出厂日期计算报废日期 +15year
         */
        function fnMadeAt(date) {
            if (date) {
                var date = date.split("-");
                document.getElementById('dpScarpingAt1').value = Number(date[0]) + Number(15) + '-' + date[1] + '-' + date[2];
                document.getElementById('dpScarpingAt').value = Number(date[0]) + Number(15) + '-' + date[1] + '-' + date[2];
            }
        }

        /**
         * 根据上道日期计算下次周期修时间
         */
        function fnLastInstalledAt(date) {
            if (document.getElementById('dpFixCycleValue').value == 0) {
                document.getElementById('dpNextFixingDay1').value = null;
                document.getElementById('dpNextFixingDay').value = null;
            }else if (date) {
                if (!document.getElementById('dpWarehouseOutTime').value) {
                    var date = date.split("-");
                    var dpFixCycleValue = document.getElementById('dpFixCycleValue').value // 周期修年限
                    var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
                    let dpScarpingAtDate = new Date(dpScarpingAt); //报废时间戳
                    let dpNextFixingDayDate = new Date(Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2]); // 周期修时间戳
                    if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
                        document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
                        document.getElementById('dpNextFixingDay').value = dpScarpingAt;
                    } else {
                        document.getElementById('dpNextFixingDay1').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
                        document.getElementById('dpNextFixingDay').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
                    }
                }

                // if (!document.getElementById('dpLastFixWorkflowAt').value) {
                //     if (!document.getElementById('dpWarehouseOutTime').value) {
                //         var date = date.split("-");
                //         var dpFixCycleValue = document.getElementById('dpFixCycleValue').value // 周期修年限
                //         var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
                //         let dpScarpingAtDate = new Date(dpScarpingAt); //报废时间戳
                //         let dpNextFixingDayDate = new Date(Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2]); // 周期修时间戳
                //         if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
                //             document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
                //             document.getElementById('dpNextFixingDay').value = dpScarpingAt;
                //         } else {
                //             document.getElementById('dpNextFixingDay1').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
                //             document.getElementById('dpNextFixingDay').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
                //         }
                //     }
                // }
            }
        }

        /**
         * 根据出所日期计算下次周期修时间
         */
        function fnLastOutAt(date) {
            if (document.getElementById('dpFixCycleValue').value == 0) {
                document.getElementById('dpNextFixingDay1').value = null;
                document.getElementById('dpNextFixingDay').value = null;
            }else if (date) {
                var date = date.split("-");
                var dpFixCycleValue = document.getElementById('dpFixCycleValue').value // 周期修年限
                var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
                let dpScarpingAtDate = new Date(dpScarpingAt); //报废时间戳
                let dpNextFixingDayDate = new Date(Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2]); // 周期修时间戳
                if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
                    document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
                    document.getElementById('dpNextFixingDay').value = dpScarpingAt;
                } else {
                    document.getElementById('dpNextFixingDay1').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
                    document.getElementById('dpNextFixingDay').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
                }

                // if (!document.getElementById('dpLastFixWorkflowAt').value) {
                //     var date = date.split("-");
                //     var dpFixCycleValue = document.getElementById('dpFixCycleValue').value // 周期修年限
                //     var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
                //     let dpScarpingAtDate = new Date(dpScarpingAt); //报废时间戳
                //     let dpNextFixingDayDate = new Date(Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2]); // 周期修时间戳
                //     if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
                //         document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
                //         document.getElementById('dpNextFixingDay').value = dpScarpingAt;
                //     } else {
                //         document.getElementById('dpNextFixingDay1').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
                //         document.getElementById('dpNextFixingDay').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
                //     }
                // }
            }
        }

        /**
         * 根据检修日期计算下次周期修时间
         */
        // function fnLastFixWorkflowAt(date) {
        //     if (date) {
        //         var date = date.split("-");
        //         var dpFixCycleValue = document.getElementById('dpFixCycleValue').value // 周期修年限
        //         var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
        //         let dpScarpingAtDate = new Date(dpScarpingAt); //报废时间戳
        //         let dpNextFixingDayDate = new Date(Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2]); // 周期修时间戳
        //         if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
        //             document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
        //             document.getElementById('dpNextFixingDay').value = dpScarpingAt;
        //         } else {
        //             document.getElementById('dpNextFixingDay1').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
        //             document.getElementById('dpNextFixingDay').value = Number(date[0]) + Number(dpFixCycleValue) + '-' + date[1] + '-' + date[2];
        //         }
        //     }
        // }

        /**
         * 根据周期修年限计算下次周期修时间
         */
        function fnFixCycleValue(date) {
            if (document.getElementById('dpFixCycleValue').value == 0) {
                document.getElementById('dpNextFixingDay1').value = null;
                document.getElementById('dpNextFixingDay').value = null;
            } else if (document.getElementById('dpWarehouseOutTime').value) {
                // 存在出所日期
                var dpWarehouseOutTime = document.getElementById('dpWarehouseOutTime').value // 获取出所日期
                var dpWarehouseOutTime = dpWarehouseOutTime.split("-");
                var dpNextFixingDay = Number(dpWarehouseOutTime[0]) + Number(date) + '-' + dpWarehouseOutTime[1] + '-' + dpWarehouseOutTime[2]; // 计算下次周期修日期
                var dpScarpingAt = document.getElementById('dpScarpingAt').value; // 报废日期
                let dpScarpingAtDate = new Date(dpScarpingAt); // 报废时间戳
                let dpNextFixingDayDate = new Date(dpNextFixingDay); // 下次周期修时间戳
                document.getElementById('dpNextFixingDay1').value = dpNextFixingDay;
                document.getElementById('dpNextFixingDay').value = dpNextFixingDay;
                // if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
                //     console.log('周期修计算：21');
                //     document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
                //     document.getElementById('dpNextFixingDay').value = dpScarpingAt;
                // } else {
                //     console.log('周期修计算：22');
                //     document.getElementById('dpNextFixingDay1').value = dpNextFixingDay;
                //     document.getElementById('dpNextFixingDay').value = dpNextFixingDay;
                // }

            } else if (document.getElementById('dpLastInstalledAt').value) {
                // 存在上道日期
                var dpLastInstalledAt = document.getElementById('dpLastInstalledAt').value // 获取上道日期
                var dpLastInstalledAt = dpLastInstalledAt.split("-");
                var dpNextFixingDay = Number(dpLastInstalledAt[0]) + Number(date) + '-' + dpLastInstalledAt[1] + '-' + dpLastInstalledAt[2]; // 计算下次周期修日期
                var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
                let dpScarpingAtDate = new Date(dpScarpingAt); // 报废时间戳
                let dpNextFixingDayDate = new Date(dpNextFixingDay); // 下次周期修时间戳
                document.getElementById('dpNextFixingDay1').value = dpNextFixingDay;
                document.getElementById('dpNextFixingDay').value = dpNextFixingDay;
                // if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
                //     document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
                //     document.getElementById('dpNextFixingDay').value = dpScarpingAt;
                // } else {
                //     document.getElementById('dpNextFixingDay1').value = dpNextFixingDay;
                //     document.getElementById('dpNextFixingDay').value = dpNextFixingDay;
                // }

            } else {
                alert("请填写出所日期/上道日期");
            }
            // 检修日期/出所日期/上道日期计算
            // if (document.getElementById('dpLastFixWorkflowAt').value) {
            //     // 存在检修日期
            //     var dpLastFixWorkflowAt = document.getElementById('dpLastFixWorkflowAt').value // 获取检修日期
            //     var dpLastFixWorkflowAt = dpLastFixWorkflowAt.split("-");
            //     var dpNextFixingDay = Number(dpLastFixWorkflowAt[0]) + Number(date) + '-' + dpLastFixWorkflowAt[1] + '-' + dpLastFixWorkflowAt[2]; // 计算下次周期修日期
            //     var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
            //     let dpScarpingAtDate = new Date(dpScarpingAt); // 报废时间戳
            //     let dpNextFixingDayDate = new Date(dpNextFixingDay); // 下次周期修时间戳
            //     if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
            //         document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
            //         document.getElementById('dpNextFixingDay').value = dpScarpingAt;
            //     } else {
            //         document.getElementById('dpNextFixingDay1').value = dpNextFixingDay;
            //         document.getElementById('dpNextFixingDay').value = dpNextFixingDay;
            //     }
            // } else if (document.getElementById('dpWarehouseOutTime').value) {
            //     // 存在出所日期
            //     var dpWarehouseOutTime = document.getElementById('dpWarehouseOutTime').value // 获取出所日期
            //     var dpWarehouseOutTime = dpWarehouseOutTime.split("-");
            //     var dpNextFixingDay = Number(dpWarehouseOutTime[0]) + Number(date) + '-' + dpWarehouseOutTime[1] + '-' + dpWarehouseOutTime[2]; // 计算下次周期修日期
            //     var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
            //     let dpScarpingAtDate = new Date(dpScarpingAt); // 报废时间戳
            //     let dpNextFixingDayDate = new Date(dpNextFixingDay); // 下次周期修时间戳
            //     if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
            //         document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
            //         document.getElementById('dpNextFixingDay').value = dpScarpingAt;
            //     } else {
            //         document.getElementById('dpNextFixingDay1').value = dpNextFixingDay;
            //         document.getElementById('dpNextFixingDay').value = dpNextFixingDay;
            //     }
            //
            // } else if (document.getElementById('dpLastInstalledAt').value) {
            //     // 存在上道日期
            //     var dpLastInstalledAt = document.getElementById('dpLastInstalledAt').value // 获取上道日期
            //     var dpLastInstalledAt = dpLastInstalledAt.split("-");
            //     var dpNextFixingDay = Number(dpLastInstalledAt[0]) + Number(date) + '-' + dpLastInstalledAt[1] + '-' + dpLastInstalledAt[2]; // 计算下次周期修日期
            //     var dpScarpingAt = document.getElementById('dpScarpingAt').value // 报废日期
            //     let dpScarpingAtDate = new Date(dpScarpingAt); // 报废时间戳
            //     let dpNextFixingDayDate = new Date(dpNextFixingDay); // 下次周期修时间戳
            //     if (dpNextFixingDayDate.getTime() > dpScarpingAtDate.getTime()) {
            //         document.getElementById('dpNextFixingDay1').value = dpScarpingAt;
            //         document.getElementById('dpNextFixingDay').value = dpScarpingAt;
            //     } else {
            //         document.getElementById('dpNextFixingDay1').value = dpNextFixingDay;
            //         document.getElementById('dpNextFixingDay').value = dpNextFixingDay;
            //     }
            //
            // } else {
            //     alert("请填写检修日期/出所日期/上道日期");
            // }
        }

        /**
         * 选择现场车间填充车站
         */
        function fnFillStations() {
            let html = '<option value="">未选择</option>';
            if ($selSceneWorkshop.val()) {
                html = '';
                $.each(stations[$selSceneWorkshop.val()], function (index, item) {
                    html += `<option value="${item['station_name']}" ${'{{ @$entireInstance->maintain_station_name }}' === item['station_name'] ? 'selected' : ''}>${item['station_name']}</option>`;
                });
            }
            $selStation.html(html);
        }

        /**
         * 保存
         */
        function fnUpdate() {
            $.ajax({
                url: "{{url('entire/instance',@$entireInstance->identity_code)}}",
                type: "put",
                data: $("#frmUpdate").serialize(),
                success: function (res) {
                    console.log('success:', res);
                    alert(res['message']);
                    location.href = `{{ url('search',@$entireInstance->identity_code) }}`
                },
                error: function (error) {
                    console.log('fail:', error);
                    if (error['status'] === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['message']);
                }
            });
        }

        /**
         * 打开出库窗口
         * @param entireInstanceIdentityCode
         */
        function fnCreateInstall(entireInstanceIdentityCode) {
            $.ajax({
                url: "{{url('entire/instance/install')}}",
                type: "get",
                data: {entireInstanceIdentityCode: entireInstanceIdentityCode},
                async: true,
                success: function (response) {
                    $("#divModalInstall").html(response);
                    $("#modalInstall").modal("show");
                },
                error: function (error) {
                    console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 绑定特殊测试项到整件实例
         */
        function fnBindingExtraTagToEntireInstance() {
            let selExtraTag = $('#selExtraTag');
            if (selExtraTag.val().length > 0) {
                $.ajax({
                    url: "{{url('pivotEntireInstanceAndExtraTag')}}",
                    type: "post",
                    data: {
                        entire_instance_identity_code: "{{@$entireInstance->identity_code}}",
                        extra_tag: $("#selExtraTag").val()
                    },
                    async: true,
                    success: function (response) {
                        location.reload();
                    },
                    error: function (error) {
                        console.log('fail:', error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    },
                });
            }
        }

        /**
         * 解除绑定
         * @param extraTag
         */
        function fnCancelBoundExtraTagFromEntireInstance(extraTag) {
            $.ajax({
                url: "{{url('pivotEntireInstanceAndExtraTag',@$entireInstance->identity_code)}}/?extra_tag=" + extraTag,
                type: "delete",
                data: {},
                async: true,
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 跳转到搜索页面（同所编号）
         * @param serialNumber
         */
        function fnToSameSerialNumber(serialNumber) {
            let params = [];
            params.push(`ici=0`);
            params.push(`code_type=serial_number`);
            params.push(`is_scraped=all`);
            params.push(`code_value=${serialNumber}`);
            params.push(`category_unique_code=`);
            params.push(`entire_model_unique_code=`);
            params.push(`sub_model_unique_code`);
            let url = `{{ url('query') }}?${params.join('&')}`;
            location.href = url;
        }

        /**
         * 跳转到搜索页面（同组合位置）
         * @param maintainStationName
         * @param maintainLocationCode
         */
        function fnToSameMaintainLocationCode(maintainStationName, maintainLocationCode) {
            let params = [];
            params.push(`ici=0`);
            params.push(`code_type=serial_number`);
            params.push(`is_scraped=all`);
            params.push(`station_name=${maintainStationName}`);
            params.push(`maintain_location_code=${maintainLocationCode}`);
            location.href = `{{ url('query') }}?${params.join('&')}`;
        }

        /**
         * 跳转到搜索页面（同组合位置）
         * @param maintainStationName
         * @param crossroadNumber
         */
        function fnToSameCrossroadNumber(maintainStationName, crossroadNumber) {
            let params = [];
            params.push(`ici=0`);
            params.push(`code_type=serial_number`);
            params.push(`is_scraped=all`);
            params.push(`station_name=${maintainStationName}`);
            params.push(`maintain_location_code=`);
            params.push(`crossroad_number=${crossroadNumber}`);
            location.href = `{{ url('query') }}?${params.join('&')}`;
        }
    </script>
@endsection

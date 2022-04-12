@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            质量报告
            <small>检修记录</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active"> 质量报告</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
    @include('Layout.alert')
    <!--查询-->
        <div class="row">
            <form id="frmScreen">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h1 class="box-title">查询</h1>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right">
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                            </div>
                        </div>
                        <div class="box-body form-horizontal">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">供应商</div>
                                        <select id="selFactory" class="form-control select2" style="width:100%;"></select>
                                        <div class="input-group-addon">种类</div>
                                        <select id="selCategory" class="form-control select2" style="width:100%;" onchange="fnSelectCategory(this.value)"></select>
                                        <div class="input-group-addon">型号</div>
                                        <select id="selSubModel" class="form-control select2" style="width:100%;"></select>
                                        <div class="input-group-addon">现场车间</div><select id="selSceneWorkshop" class="form-control select2" style="width:100%;" onchange="fnSelectSceneWorkshop(this.value)">
                                        </select>
                                        <div class="input-group-addon">车站</div>
                                        <select id="selStation" class="form-control select2" style="width:100%;"></select>
                                        <div class="input-group-addon">状态</div>
                                        <select id="selStatus" class="form-control select2" style="width:100%;"></select>
                                    </div>
                                </div>
                            </div>
                            <p></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <label style="font-weight: normal;"><input type="checkbox" id="selRepairAt" value="1" {{ $currentSelRepairAt == '1' ? 'checked' : ''}}>返修时间</label>
                                        </div>
                                        <input id="repairAt" name="made_at" type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="box">
            <div class="box-header">
                <h3 class="box-title">质量报告 <small>检修记录 总数：{{ $entireInstances->total() }}</small></h3>
                <!--右侧最小化按钮-->
                <div class="box-tools pull-right"></div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>唯一编号</th>
                        <th>型号</th>
                        <th>返修时间</th>
                        <th>状态</th>
                        <th>供应商</th>
                        <th>位置</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($entireInstances as $entireInstance)
                        <tr>
                            <td><a href="{{ url('search',$entireInstance->identity_code) }}" target="_blank">{{$entireInstance->identity_code}}</a></td>
                            <td>
                                {{ @$entireInstance->category_name }}
                                {{ @$entireInstance->sub_model_name }}
                            </td>
                            <td>{{ @$entireInstance->repair_updated_at }}</td>
                            <td>{{ @\App\Model\EntireInstance::$STATUSES[$entireInstance->status] }}</td>
                            <td>{{ @$entireInstance->factory_name }}</td>
                            <td>
                                {{ @$entireInstance->maintain_station_name }}
                                {{ @$entireInstance->open_direction }}
                                {{ @$entireInstance->said_rod }}
                                {{ @$entireInstance->crossroad_number }}
                                {{ @$entireInstance->line_name }}
                                @if($entireInstance->maintain_location_code)
                                    {{ \App\Model\Install\InstallPosition::getRealName($entireInstance->maintain_location_code) ?: $entireInstance->maintain_location_code }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($entireInstances->hasPages())
                <div class="box-footer">
                    {{ $entireInstances
                        ->appends([
                            'factoryUniqueCode'=>request('factoryUniqueCode'),
                            'categoryUniqueCode'=>request('categoryUniqueCode'),
                            'subModelUniqueCode'=>request('subModelUniqueCode'),
                            'sceneWorkshopUniqueCode'=>request('sceneWorkshopUniqueCode'),
                            'stationUniqueCode'=>request('stationUniqueCode'),
                            'statusCode'=>request('statusCode'),
                            'repairAt'=>request('repairAt'),
                            'selRepairAt'=>request('selRepairAt'),
                            'qualityDateType'=>request('qualityDateType'),
                            'qualityDate'=>request('qualityDate'),
                            'type'=>request('type','breakdown'),
                        ])
                        ->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        let selFactory = $('#selFactory');
        let selCategory = $('#selCategory');
        let selSubModel = $('#selSubModel');
        let selSceneWorkshop = $('#selSceneWorkshop');
        let selStation = $('#selStation');
        let selStatus = $('#selStatus');
        let models = JSON.parse(`{!! $models !!}`);
        let stations = JSON.parse(`{!! $stations !!}`);

        $(function () {
            let dateLocale = {
                format: "YYYY-MM-DD",
                separator: "~",
                daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                applyLabel: "确定",
                cancelLabel: "取消",
                fromLabel: "开始时间",
                toLabel: "结束时间",
                customRangeLabel: "自定义",
                weekLabel: "W",
            };
            if ($select2.length > 0) $select2.select2();
            init();

            $('#repairAt').daterangepicker({
                locale: dateLocale,
                startDate: "{{$currentRepairAtOrigin}}",
                endDate: "{{$currentRepairAtFinish}}"
            });

        });

        /**
         * 初始化页面
         */
        function init() {
            let factories = JSON.parse(`{!! $factories !!}`);
            let status = JSON.parse(`{!! $status !!}`);

            let factoryHtml = `<option value="">全部</option>`;
            $.each(factories, function (factoryUniqueCode, factory) {
                factoryHtml += `<option value="${factoryUniqueCode}" ${factoryUniqueCode === "{{ $currentFactoryUniqueCode }}" ? 'selected' : ''}>${factory.name}</option>`;
            });
            selFactory.html(factoryHtml);

            let categoryHtml = `<option value="">全部</option>`;
            $.each(models, function (categoryUniqueCode, category) {
                categoryHtml += `<option value="${categoryUniqueCode}" ${categoryUniqueCode === "{{ $currentCategoryUniqueCode }}" ? 'selected' : ''}>${category.name}</option>`;
            });
            selCategory.html(categoryHtml);

            fnSelectCategory(`{{ $currentCategoryUniqueCode }}`);

            let sceneWorkshopHtml = `<option value="">全部</option>`;
            $.each(stations, function (sceneWorkshopUniqueCode, sceneWorkshop) {
                sceneWorkshopHtml += `<option value="${sceneWorkshopUniqueCode}" ${sceneWorkshopUniqueCode === "{{ $currentSceneWorkshopUniqueCode }}" ? 'selected' : ''}>${sceneWorkshop.name}</option>`;
            });
            selSceneWorkshop.html(sceneWorkshopHtml);

            fnSelectSceneWorkshop(`{{ $currentSceneWorkshopUniqueCode }}`);

            let statusHtml = `<option value="">全部</option>`;
            $.each(status, function (statusCode, statusName) {
                statusHtml += `<option value="${statusCode}" ${statusCode === "{{ $currentStatusCode }}" ? 'selected' : ''}>${statusName}</option>`;
            });
            selStatus.html(statusHtml);
        }

        /**
         * 选择种类
         */
        function fnSelectCategory(categoryUniqueCode) {
            let subModelHtml = `<option value="">全部</option>`;
            if (categoryUniqueCode && categoryUniqueCode !== '') {
                if (models.hasOwnProperty(categoryUniqueCode)) {
                    $.each(models[categoryUniqueCode]['subs'], function (subModelUniqueCode, subModel) {
                        subModelHtml += `<option value="${subModelUniqueCode}" ${subModelUniqueCode === "{{ $currentSubModelUniqueCode }}" ? 'selected' : ''}>${subModel.name}</option>`;
                    });
                }
            }
            selSubModel.html(subModelHtml);
        }

        /**
         * 选择现场车间
         */
        function fnSelectSceneWorkshop(sceneWorkshopUniqueCode) {
            let stationHtml = `<option value="">全部</option>`;
            if (sceneWorkshopUniqueCode && sceneWorkshopUniqueCode !== '') {
                if (stations.hasOwnProperty(sceneWorkshopUniqueCode)) {
                    $.each(stations[sceneWorkshopUniqueCode]['subs'], function (stationUniqueCode, station) {
                        stationHtml += `<option value="${stationUniqueCode}" ${stationUniqueCode === "{{ $currentStationUniqueCode }}" ? 'selected' : ''}>${station.name}</option>`;
                    });
                }
            }
            selStation.html(stationHtml);
        }


        /**
         * 组合生成url参数
         * @param {string} url
         * @param {object} params
         */
        let fnMakeUrl = (url, params) => {
            let urlParams = [];
            $.each(params, (key, value) => {
                urlParams.push(`${key}=${value}`);
            });
            return `${url}?${urlParams.join("&")}`;
        };


        /**
         * 查询
         */
        function fnScreen() {
            let urlParams = {
                factoryUniqueCode: selFactory.val(),
                categoryUniqueCode: selCategory.val(),
                subModelUniqueCode: selSubModel.val(),
                sceneWorkshopUniqueCode: selSceneWorkshop.val(),
                stationUniqueCode: selStation.val(),
                statusCode: selStatus.val(),
                selRepairAt: $("#selRepairAt").is(":checked") ? "1" : "0",
                repairAt: $("#repairAt").val(),
            };

            location.href = fnMakeUrl(`{{url('report/qualityEntireInstance')}}`, urlParams);
        }
    </script>
@endsection

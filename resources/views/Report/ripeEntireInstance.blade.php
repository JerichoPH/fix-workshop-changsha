@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/skins/_all-skins.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            一次过检
            <small>设备列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active"> 一次过检</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')

        {{--查询--}}
        <div class="row">
            <form id="frmScreen">
                <div class="col-md-12">
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h1 class="box-title">查询</h1>
                            {{--右侧最小化按钮--}}
                            <div class="box-tools pull-right">
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                            </div>
                        </div>
                        <div class="box-body form-horizontal">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">供应商</div>
                                        <select
                                            id="selFactory"
                                            name="factory_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select
                                            id="selCategory"
                                            name="category_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectCategory()"
                                        >
                                        </select>
                                        <div class="input-group-addon">类型</div>
                                        <select
                                            id="selEntireModel"
                                            name="entire_model_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectEntireModel()"
                                        >
                                        </select>
                                        <div class="input-group-addon">型号</div>
                                        <select
                                            id="selSubModel"
                                            name="sub_model_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                        </select>
                                        <div class="input-group-addon">现场车间</div>
                                        <select
                                            id="selSceneWorkshop"
                                            name="scene_workshop_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectSceneWorkshop(this.value)"
                                        >
                                        </select>
                                        <div class="input-group-addon">车站</div>
                                        <select
                                            id="selStation"
                                            name="station_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                        </select>
                                        <div class="input-group-addon">状态</div>
                                        <select
                                            id="selStatus"
                                            name="status_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                            <option value="">全部</option>
                                            <option value="INSTALLED" {{request('status') == 'INSTALLED' ? 'selected' : ''}}>上道</option>
                                            <option value="INSTALLING" {{request('status') == 'INSTALLING' ? 'selected' : ''}}>备品</option>
                                            <option value="FIXED" {{request('status') == 'FIXED' ? 'selected' : ''}}>成品</option>
                                            <option value="FIXING" {{request('status') == 'FIXING' ? 'selected' : ''}}>在修</option>
                                            <option value="RETURN_FACTORY" {{request('status') == 'RETURN_FACTORY' ? 'selected' : ''}}>送修</option>
                                        </select>
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
                <h3 class="box-title">质量报告 设备列表 总数：{{$entireInstances->total()}}</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>检修单号</th>
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
                            <td><a href="/measurement/fixWorkflow/{{$entireInstance->serial_number}}/edit">{{$entireInstance->serial_number}}</a></td>
                            <td><a href="/search/{{$entireInstance->identity_code}}">{{$entireInstance->identity_code}}</a></td>
                            <td>
                                {{$entireInstance->category_name}}
                                {{$entireInstance->model_name}}
                            </td>
                            <td>{{$entireInstance->fw_created_at ? explode(' ',$entireInstance->fw_created_at)[0] : ''}}</td>
                            <td>{{$statuses[$entireInstance->status]}}</td>
                            <td>{{$entireInstance->factory_name}}</td>
                            <td>
                                {{$entireInstance->maintain_station_name}}
                                {{$entireInstance->open_direction}}
                                {{$entireInstance->said_rod}}
                                {{$entireInstance->crossroad_number}}
                                {{$entireInstance->line_name}}
                                {{$entireInstance->maintain_location_code}}
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
                                        'category_unique_code'=>request('category_unique_code'),
                                        'entireModel_unique_code'=>request('entire_model_unique_code'),
                                        'sub_model_unique_code'=>request('sub_model_unique_code'),
                                        'factory_name'=>request('factory_name'),
                                        ])
                                        ->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $selFactory = $("#selFactory");
        let $selCategory = $("#selCategory");
        let $selEntireModel = $("#selEntireModel");
        let $selSubModel = $("#selSubModel");
        let $select2 = $(".select2");
        let $selSceneWorkshop = $("#selSceneWorkshop");
        let $selStation = $("#selStation");
        let $selStatus = $("#selStatus");
        let queryConditions = JSON.parse('{!! $queryConditions !!}');

        /**
         * 初始化页面
         */
        function initPage () {
            console.log(queryConditions);
            // 初始化供应商
            let html = `<option value="">全部</option>`;
            $.each(queryConditions.factories, (index, factory) => {
                html += `<option value=${factory} ${factory === queryConditions.current_factory_name ? 'selected' : ''}>${factory}</option>`
            });
            $selFactory.html(html);

            // 初始化种类列表
            fnFillSelect($selCategory, queryConditions.categories, "", queryConditions.current_category_unique_code);
            // 初始化类型列表
            fnFillSelect($selEntireModel, queryConditions.entire_models[queryConditions.current_category_name], "", queryConditions.current_entire_model_unique_code);
            // 初始化子类和型号列表
            fnFillSelect($selSubModel, queryConditions.sub_models[queryConditions.current_entire_model_name], "", queryConditions.current_sub_model_unique_code);
            // 刷新类型列表
            fnSelectCategory();
            // 刷新型号和子类列表
            fnSelectEntireModel();

            // 初始化现场车间列表
            fnFillSelect($selSceneWorkshop, queryConditions.scene_workshops, queryConditions.current_scene_workshop_unique_code);
            // 刷新车站列表
            fnSelectSceneWorkshop();
        }

        function fnFillSelect ($obj, items, defaultValue = "", current, useKey = true) {
            let html = `<option value="${defaultValue}">未选择</option>`;
            $.each(items, (index, item) => {
                let value = useKey ? index : item;
                html += `<option value="${index}" ${value === current ? "selected" : ""}>${item}</option>`;
            });
            $obj.html(html);
        }

        /**
         * 选择种类
         */
        function fnSelectCategory () {
            let value = $selCategory.val();
            let html = `<option value="">全部</option>`;
            if (value !== "") {
                $.each(queryConditions.entire_models[queryConditions.categories[value]], (entireModelUniqueCode, entireModelName) => {
                    html += `<option value="${entireModelUniqueCode}" ${entireModelUniqueCode === queryConditions.current_entire_model_unique_code ? 'selected' : ''}>${entireModelName}</option>`;
                });
            } else {
                $selSubModel.html(`<option value="">全部</option>`);
            }
            $selEntireModel.html(html);
            fnSelectEntireModel();
        }

        /**
         * 选择类型
         */
        function fnSelectEntireModel () {
            let value = $selEntireModel.val();
            let html = `<option value="">全部</option>`;
            if (value !== "") {
                $.each(queryConditions.sub_models[queryConditions.entire_models[queryConditions.categories[value.substr(0, 3)]][value]], (subModelUniqueCode, subModelName) => {
                    html += `<option value="${subModelUniqueCode}" ${subModelUniqueCode === queryConditions.current_sub_model_unique_code ? 'selected' : ''}>${subModelName}</option>`;
                });
            }
            $selSubModel.html(html);
        }

        /**
         * 选择现场车间
         */
        function fnSelectSceneWorkshop (){
            let value = $selSceneWorkshop.val();
            let html = `<option value="">全部</option>`;
            if (value !== "") {
                $.each(queryConditions.maintains[queryConditions.scene_workshops[value]], (index, stationName) => {
                    html += `<option value="${stationName}" ${stationName === queryConditions.current_station_name ? 'selected' : ''}>${stationName}</option>`;
                });
            }
            $selStation.html(html);
        }

        /**
         * 组合生成url参数
         * @param {string} url
         * @param {object} params
         */
        function fnMakeUrl (url, params) {
            let urlParams = [];
            $.each(params, (key, value) => {
                urlParams.push(`${key}=${value}`);
            });
            return `${url}?${urlParams.join("&")}`;
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            initPage();  // 初始化页面
        });

        /**
         * 查询
         */
        function fnScreen () {
            let urlParams = {
                category_unique_code: $selCategory.val(),
                entire_model_unique_code: $selEntireModel.val(),
                sub_model_unique_code: $selSubModel.val(),
                factory_name: $selFactory.val(),
                scene_workshop_unique_code: $selSceneWorkshop.val(),
                station_name: $selStation.val(),
                status_unique_code: $selStatus.val(),
            };

            location.href = fnMakeUrl(`{{url('report/ripeEntireInstance')}}`, urlParams);
        }
    </script>
@endsection

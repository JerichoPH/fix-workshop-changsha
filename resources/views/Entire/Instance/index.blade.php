@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    @if(request('is_iframe')!=1)
        <section class="content-header">
            <h1>
                设备列表
                <small>型号</small>
            </h1>
            {{--            <ol class="breadcrumb">--}}
            {{--                <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
            {{--                <li class="active"> 设备列表</li>--}}
            {{--            </ol>--}}
        </section>
    @endif
    <section class="content">
        @include('Layout.alert')

        <form id="frmScreen">
            <!--查询-->
            <div class="row">
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
                                        <select
                                            id="selFactory"
                                            name="factory"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select
                                            id="selCategory"
                                            name="category_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectCategory()"
                                        >
                                        </select>
                                        <div class="input-group-addon">类型</div>
                                        <select
                                            id="selEntireModel"
                                            name="entire_model_unique_code"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectEntireModel()"
                                        >
                                        </select>
                                        <div class="input-group-addon">型号</div>
                                        <select
                                            id="selSubModel"
                                            name="sub_model_unique_code"
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
                                            name="status"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                            <option value="">全部</option>
                                            @foreach($statuses as $statusCode => $statusName)
                                                <option value="{{ $statusCode }}" {{ request('status') == $statusCode ? 'selected' : '' }}>{{ $statusName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <p></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <label style="font-weight: normal;">
                                                <input
                                                    type="checkbox"
                                                    id="chkMadeAt"
                                                    value="1"
                                                    {{ request('use_made_at') == '1' ? 'checked' : '' }}
                                                >
                                                出厂时间
                                            </label>
                                        </div>
                                        <input
                                            id="dateMadeAt"
                                            name="date_made_at"
                                            type="text"
                                            class="form-control"
                                        >
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <label style="font-weight: normal;">
                                                <input
                                                    type="checkbox"
                                                    id="chkCreatedAt"
                                                    value="1"
                                                    {{ request('use_created_at') == '1' ? 'checked' : '' }}
                                                >
                                                采购时间
                                            </label>
                                        </div>
                                        <input
                                            id="dateCreatedAt"
                                            name="created_at"
                                            type="text"
                                            class="form-control"
                                        >
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-addon"><label style="font-weight: normal;"><input
                                                    type="checkbox" id="chkNextFixingDay" value="1"
                                                    {{ request('use_next_fixing_day') == '1' ? 'checked' : '' }}>下次周期修时间</label>
                                        </div>
                                        <input
                                            id="dateNextFixingDay"
                                            name="next_fixing_day"
                                            type="text"
                                            class="form-control"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">资产列表 总数：{{ $entireInstances->total() }}</h3>
                    <!--右侧最小化按钮-->
                    <div class="box-tools pull-right"></div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>唯一编号</th>
                            <th>供应商名称</th>
                            <th>位置</th>
                            <th>开向</th>
                            <th>表示杆特征</th>
                            <th>安装日期</th>
                            <th>上次检修日期</th>
                            <th>下次周期修日期</th>
                            <th>报废日期</th>
                            <th>型号</th>
                            <th>状态</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($entireInstances as $entireInstance)
                            <tr>
                                <td><a href="{{ url('search',$entireInstance->identity_code) }}?is_iframe={{ request('is_iframe') }}">{{ $entireInstance->identity_code }}</a>
                                </td>
                                <td>{{ @$entireInstance->factory_name }}</td>
                                <td>
                                    {{ @$entireInstance->maintain_station_name }}:
                                    {{ @$entireInstance->maintain_location_code }}
                                    {{ @$entireInstance->crossroad_number }}
                                    {{ @$entireInstance->line_name }}
                                </td>
                                <td>{{ @$entireInstance->open_direction }}</td>
                                <td>{{ @$entireInstance->said_rod }}</td>
                                <td>{{ @$entireInstance->last_installed_time ? date('Y-m-d',$entireInstance->last_installed_time) : '' }}</td>
                                <td>{{ @$entireInstance->last_fix_workflow_at ? date('Y-m-d',strtotime($entireInstance->last_fix_workflow_at)) : '' }}</td>
                                @if($entireInstance->ei_fix_cycle_value == 0 && $entireInstance->em_fix_cycle_value == 0)
                                    <td>状态修设备</td>
                                @else
                                    <td style="{{ @$entireInstance->next_fixing_time < time() ? 'color: red;' :'' }}">{{ @$entireInstance->next_fixing_time ? date('Y-m-d', $entireInstance->next_fixing_time) : '' }}</td>
                                @endif
                                <td style="{{ @$entireInstance->scarping_at ? (strtotime($entireInstance->scarping_at) < time() ? 'color: red;' : '') : ''}}">{{ @$entireInstance->scarping_at ? date('Y-m-d',strtotime($entireInstance->scarping_at)) : '' }}</td>
                                <td>
                                    {{ @$entireInstance->category_name }}
                                    {{ @$entireInstance->model_name }}
                                </td>
                                <td>{{ @$statuses[$entireInstance->status] }}</td>
                            </tr>
                        @endforeach


                        </tbody>
                    </table>
                </div>
                @if($entireInstances->hasPages())
                    <div class="box-footer">
                        {{ $entireInstances->appends([
                                                        "category_unique_code"=>request("category_unique_code"),
                                                        "entire_model_unique_code"=>request("entire_model_unique_code"),
                                                        "sub_model_unique_code"=>request("sub_model_unique_code"),
                                                        "factory"=>request("factory"),
                                                        "scene_workshop_unique_code"=>request("scene_workshop_unique_code"),
                                                        "station_name"=>request("station_name"),
                                                        "status"=>request("status"),
                                                        "use_made_at"=>request("use_made_at"),
                                                        "made_at"=>request("made_at"),
                                                        "use_created_at"=>request("use_created_at"),
                                                        "created_at"=>request("created_at"),
                                                        "use_next_fixing_day"=>request("use_next_fixing_day"),
                                                        "next_fixing_day"=>request("next_fixing_day"),
                                                        "is_bind_location"=>request("is_bind_location"),
                                                        "is_iframe"=>request("is_iframe"),
                                                        "page"=>request("page")
                                                        ])
                                                        ->links() }}
                    </div>
                @endif
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        let $selFactory = $("#selFactory");
        let $selCategory = $("#selCategory");
        let $selEntireModel = $("#selEntireModel");
        let $selSubModel = $("#selSubModel");
        let $selSceneWorkshop = $("#selSceneWorkshop");
        let $selStation = $("#selStation");
        let $selStatus = $("#selStatus");
        let queryConditions = {!! $queryConditions !!};

        // console.log(queryConditions)
        /**
         * 初始化页面
         */
        function initPage() {
            // 初始化供应商
            let html = `<option value="">全部</option>`;
            $.each(queryConditions.factories, (index, factory) => {
                html += `<option value=${factory} ${factory === queryConditions.current_factory_name ? 'selected' : ''}>${factory}</option>`
            });
            $selFactory.html(html);

            // 初始化种类列表
            fnFillSelect($selCategory, queryConditions.categories, queryConditions.current_category_unique_code);
            // 初始化类型列表
            fnFillSelect($selEntireModel, queryConditions.entire_models[queryConditions.current_category_name], queryConditions.current_entire_model_unique_code);
            // 初始化子类和型号列表
            fnFillSelect($selSubModel, queryConditions.sub_models[queryConditions.current_entire_model_name], queryConditions.current_sub_model_unique_code);
            // 刷新类型列表
            fnSelectCategory();
            // 刷新型号和子类列表
            fnSelectEntireModel();

            // 初始化现场车间列表
            fnFillSelect($selSceneWorkshop, queryConditions.scene_workshops, queryConditions.current_scene_workshop_unique_code);
            // 刷新车站列表
            fnSelectSceneWorkshop();
        }

        /**
         * 填充种类列表
         * @param obj
         * @param {array} items
         * @param {string} currentUniqueCode
         */
        function fnFillSelect(obj, items, currentUniqueCode) {
            let html = `<option value="">全部</option>`;
            $.each(items, (uniqueCode, name) => {
                html += `<option value="${uniqueCode}" ${uniqueCode === currentUniqueCode ? 'selected' : ''}>${name}</option>`;
            });
            obj.html(html);
        }

        /**
         * 选择种类
         */
        function fnSelectCategory() {
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
        function fnSelectEntireModel() {
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
        function fnSelectSceneWorkshop() {
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
        function fnMakeUrl(url, params) {
            let urlParams = [];
            $.each(params, (key, value) => {
                urlParams.push(`${key}=${value}`);
            });
            return `${url}?${urlParams.join("&")}`;
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

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

            $('#dateMadeAt').daterangepicker({
                locale: dateLocale,
                startDate: "{{$dateMadeAtOrigin}}",
                endDate: "{{$dateMadeAtFinish}}"
            });
            $('#dateCreatedAt').daterangepicker({
                locale: dateLocale,
                startDate: "{{$dateCreatedAtOrigin}}",
                endDate: "{{$dateCreatedAtFinish}}"
            });
            $('#dateNextFixingDay').daterangepicker({
                locale: dateLocale,
                startDate: "{{$dateNextFixingDayOrigin}}",
                endDate: "{{$dateNextFixingDayFinish}}"
            });

            initPage();
        });

        /**
         * 查询
         */
        function fnScreen() {
            let urlParams = {
                category_unique_code: $selCategory.val(),
                entire_model_unique_code: $selEntireModel.val(),
                sub_model_unique_code: $selSubModel.val(),
                status: $selStatus.val(),
                factory: $selFactory.val(),
                scene_workshop_unique_code: $selSceneWorkshop.val(),
                station_name: $selStation.val(),
                use_created_at: $("#chkCreatedAt").is(":checked") ? "1" : "0",
                created_at: $("#dateCreatedAt").val(),
                use_made_at: $("#chkMadeAt").is(":checked") ? "1" : "0",
                made_at: $("#dateMadeAt").val(),
                use_next_fixing_day: $("#chkNextFixingDay").is(":checked") ? "1" : "0",
                next_fixing_day: $("#dateNextFixingDay").val(),
            };
            location.href = fnMakeUrl(`{{url('entire/instance')}}`, urlParams) + '&is_iframe={{ request('is_iframe') }}';
        }
    </script>
@endsection

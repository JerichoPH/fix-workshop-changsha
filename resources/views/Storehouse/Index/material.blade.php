@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备列表
        </h1>
        {{--        <ol class="breadcrumb">--}}
        {{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--            <li><a href="JavaScript:"> 设备列表</a></li>--}}
        {{--        </ol>--}}
    </section>
    <section class="content">
    @include('Layout.alert')
    <!--种类型库存统计-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">库存统计</h3>
                    </div>
                    <div class="box-body">
                        <div id="echartsInventory" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <form id="frmScreen">
            {{--查询--}}
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h1 class="box-title">查询</h1>
                            {{--右侧最小化按钮--}}
                            <div class="btn-group pull-right">
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreenEntireInstance()">搜索整件</a>
                                <a href="javascript:" class="btn btn-info btn-flat" onclick="fnScreenPartInstance()">搜索部件</a>
                            </div>
                        </div>
                        <div class="box-body form-horizontal">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">供应商</div>
                                        <select id="selFactory" name="factory_name" class="form-control select2" style="width:100%;">
                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select id="selCategory" name="category_name" class="form-control select2" style="width:100%;" onchange="fnSelectCategory()">
                                        </select>
                                        <div class="input-group-addon">类型</div>
                                        <select id="selEntireModel" name="entire_model_name" class="form-control select2" style="width:100%;" onchange="fnSelectEntireModel()">
                                        </select>
                                        <div class="input-group-addon">型号</div>
                                        <select id="selSubModel" name="sub_model_name" class="form-control select2" style="width:100%;">
                                        </select>
                                        <div class="input-group-addon">状态</div>
                                        <select id="selStatus" name="status_unique_code" class="form-control select2" style="width:100%;">
                                            <option value="ALL" {{ (request('status_unique_code') == 'ALL' or request('status_unique_code') == '') ? 'selected' :'' }}>全部</option>
                                            <option value="FIXING" {{ request('status_unique_code') == 'FIXING' ? 'selected' : '' }}>待修</option>
                                            <option value="FIXED" {{ request('status_unique_code') == 'FIXED' ? 'selected' : '' }}>成品</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">仓</div>
                                        <select id="selStorehouse" class="form-control select2" style="width:100%;" onchange="fnSelStorehouse()">

                                        </select>
                                        <div class="input-group-addon">区</div>
                                        <select id="selArea" class="form-control select2" style="width:100%;" onchange="fnSelArea()">

                                        </select>
                                        <div class="input-group-addon">排</div>
                                        <select id="selPlatoon" class="form-control select2" style="width:100%;" onchange="fnSelPlatoon()">

                                        </select>
                                        <div class="input-group-addon">架</div>
                                        <select id="selShelf" class="form-control select2" style="width:100%;" onchange="fnSelShelf()">

                                        </select>
                                        <div class="input-group-addon">层</div>
                                        <select id="selTier" class="form-control select2" style="width:100%;" onchange="fnSelTier()">

                                        </select>
                                        <div class="input-group-addon">位</div>
                                        <select id="selPosition" class="form-control select2" style="width:100%;">

                                        </select>
                                    </div>
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">资产列表 总数：{{$entireInstances->total()}}</h3>
                    {{--右侧最小化按钮--}}
                    <div class="box-tools pull-right"></div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed">
                        <theader>
                            <tr>
                                <th>唯一编号</th>
                                <th>供应商名称</th>
                                <th>仓库位置</th>
                                <th>型号</th>
                                <th>状态</th>
                            </tr>
                        </theader>
                        <tbody>
                        @foreach($entireInstances as $entireInstance)
                            <tr>
                                <td>
                                    @if(array_key_exists('material_type',$entireInstance))
                                        @if($entireInstance->material_type == 'ENTIRE')
                                            <a href="{{ url('search',$entireInstance->identity_code) }}">{{ $entireInstance->identity_code }}</a>
                                        @else
                                            {{ $entireInstance->identity_code }}
                                        @endif
                                    @else
                                        <a target="_blank" href="{{ url('search',$entireInstance->identity_code) }}">{{ $entireInstance->identity_code }}</a>
                                    @endif
                                </td>
                                <td>{{$entireInstance->factory_name}}</td>
                                <td>
                                    <a href="javascript:" onclick="fnLocation(`{{ $entireInstance->identity_code }}`)"><i class="fa fa-location-arrow"></i>
                                        {{ $entireInstance->storehous_name }}
                                        {{ $entireInstance->area_name }}
                                        {{ $entireInstance->platoon_name }}
                                        {{ $entireInstance->shelf_name }}
                                        {{ $entireInstance->tier_name }}
                                        {{ $entireInstance->position_name }}
                                    </a>
                                </td>
                                <td>
                                    {{$entireInstance->model_name}}({{ $entireInstance->material_type_name}})
                                </td>
                                <td>{{$statuses[$entireInstance->status]}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" id="locationShow">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title">位置：<span id="title"></span></h4>
                            </div>
                            <div class="modal-body">
                                <img id="location_img" class="model-body-location" alt="" style="width: 100%;">
                                <div class="spot"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($entireInstances->hasPages())
                    <div class="box-footer">
                        {{ $entireInstances
                                            ->appends([
                                            "category_unique_code"=>request("category_unique_code"),
                                            "entire_model_unique_code"=>request("entire_model_unique_code"),
                                            "sub_model_unique_code"=>request("sub_model_unique_code"),
                                            "factory_name"=>request("factory_name"),
                                            "status_unique_code"=>request("status_unique_code"),
                                            "storehouse_unique_code"=>request("storehouse_unique_code"),
                                            "area_unique_code"=>request("area_unique_code"),
                                            "platoon_unique_code"=>request("platoon_unique_code"),
                                            "shelf_unique_code"=>request("shelf_unique_code"),
                                            "tier_unique_code"=>request("tier_unique_code"),
                                            "position_unique_code"=>request("position_unique_code"),
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
        let $selStatus = $("#selStatus");
        let $selStorehouse = $("#selStorehouse");
        let $selArea = $("#selArea");
        let $selPlatoon = $("#selPlatoon");
        let $selShelf = $("#selShelf");
        let $selTier = $("#selTier");
        let $selPosition = $("#selPosition");

        let queryConditions = JSON.parse('{!! $queryConditions !!}');
        console.log(queryConditions)

        /**
         * 生成库存统计图
         */
        function fnMakeInventoryChart() {
            let statuses = JSON.parse(`{!! $status_as_json !!}`);
            let categoryNames = [];
            let categories = {};
            let categoriesAsFlip = {};
            let statistics = {};

            // 整理数据
            $.each(JSON.parse('{!! $statisticsAsJson !!}'), function (k, v) {
                if (!statistics.hasOwnProperty(v['cu'])) statistics[v['cu']] = {name: v['cn'], statistics: {}}
                if (!statistics[v['cu']].hasOwnProperty(v['eis'])) statistics[v['cu']]['statistics'][v['eis']] = 0;
                statistics[v['cu']]['statistics'][v['eis']] += v['aggregate'];
            });

            let series = [{
                name: '全部',
                type: 'bar',
                data: [],
                label: {
                    show: true,
                    position: 'top'
                },
            }, {
                name: '成品',
                type: 'bar',
                data: [],
                label: {
                    show: true,
                    position: 'top'
                },
            }, {
                name: '待修',
                type: 'bar',
                data: [],
                label: {
                    show: true,
                    position: 'top'
                },
            }];
            $.each(statistics, function (cu, item) {
                categoryNames.push(item['name']);
                categories[cu] = item['name'];
                categoriesAsFlip[item['name']] = cu;
                let fixing = item['statistics']['FIXING'] ? item['statistics']['FIXING'] : 0;
                let fixed = item['statistics']['FIXED'] ? item['statistics']['FIXED'] : 0;
                series[0]['data'].push(fixing + fixed);
                series[1]['data'].push(fixed);
                series[2]['data'].push(fixing);
            });

            let echartsInventory = echarts.init(document.getElementById('echartsInventory'));
            let option = {
                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow',
                        label: {show: true,},
                    },
                },
                calculable: true,
                legend: {
                    data: ['全部', '成品', '待修'],
                    itemGap: 5
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '15%',
                    containLabel: true,
                },
                xAxis: [{
                    type: 'category',
                    data: categoryNames
                }],
                yAxis: [{type: 'value'}],
                dataZoom: [{
                    show: true,
                    start: 0,
                    end: 100
                }, {
                    type: 'inside',
                    start: 94,
                    end: 100
                }, {
                    show: false,
                    yAxisIndex: 0,
                    filterMode: 'empty',
                    width: 30,
                    height: '80%',
                    showDataShadow: false,
                    left: '93%'
                }],
                series: series
            };
            echartsInventory.setOption(option);
            echartsInventory.on('click', function (params) {
                // 点击进入指定种类的库存页面
                location.href = `{{url('storehouse/index/material')}}?status_unique_code=${statuses[params.seriesName]}&category_unique_code=${categoriesAsFlip[params.name]}`
            });
        }

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
            // 初始化仓列表
            fnFillSelect($selStorehouse, queryConditions.storehouses, queryConditions.current_storehouse_unique_code);
            // 初始化区列表
            fnFillSelect($selArea, queryConditions.areas[queryConditions.current_storehouse_unique_code], queryConditions.current_area_unique_code);
            // 初始化排列表
            fnFillSelect($selPlatoon, queryConditions.platoons[queryConditions.current_area_unique_code], queryConditions.current_platoon_unique_code);
            // 初始化架列表
            fnFillSelect($selShelf, queryConditions.shelves[queryConditions.current_platoon_unique_code], queryConditions.current_shelf_unique_code);
            // 初始化层列表
            fnFillSelect($selTier, queryConditions.tiers[queryConditions.current_shelf_unique_code], queryConditions.current_tier_unique_code);
            // 初始化位列表
            fnFillSelect($selPosition, queryConditions.positions[queryConditions.current_position_unique_code], queryConditions.current_position_unique_code);

            // 刷新类型列表
            fnSelectCategory();
            // 刷新型号和子类列表
            fnSelectEntireModel();

            // 种类型库存统计图表
            fnMakeInventoryChart();
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
                            location.reload();
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
                    // location.reload();
                }
            });
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
         * 选择仓
         */
        function fnSelStorehouse() {
            let value = $selStorehouse.val();
            let html = `<option value="">全部</option>`;
            if (value !== "") {
                $.each(queryConditions.areas[value], (uniqueCode, name) => {
                    html += `<option value="${uniqueCode}" ${uniqueCode === queryConditions.current_area_unique_code ? 'selected' : ''}>${name}</option>`;
                });
            }
            $selArea.html(html);
            fnSelArea();
            fnSelPlatoon();
            fnSelShelf();
            fnSelTier();
        }

        /**
         * 选择区
         */
        function fnSelArea() {
            let value = $selArea.val();
            let html = `<option value="">全部</option>`;
            if (value !== "") {
                $.each(queryConditions.platoons[value], (uniqueCode, name) => {
                    html += `<option value="${uniqueCode}" ${uniqueCode === queryConditions.current_platoon_unique_code ? 'selected' : ''}>${name}</option>`;
                });
            }
            $selPlatoon.html(html);

            fnSelPlatoon();
            fnSelShelf();
            fnSelTier();
        }

        /**
         * 选择排
         */
        function fnSelPlatoon() {
            let value = $selPlatoon.val();
            let html = `<option value="">全部</option>`;
            if (value !== "") {
                $.each(queryConditions.shelves[value], (uniqueCode, name) => {
                    html += `<option value="${uniqueCode}" ${uniqueCode === queryConditions.current_shelf_unique_code ? 'selected' : ''}>${name}</option>`;
                });
            }
            $selShelf.html(html);
            fnSelShelf();
            fnSelTier();
        }

        /**
         * 选择架
         */
        function fnSelShelf() {
            let value = $selShelf.val();
            let html = `<option value="">全部</option>`;
            if (value !== "") {
                $.each(queryConditions.tiers[value], (uniqueCode, name) => {
                    html += `<option value="${uniqueCode}" ${uniqueCode === queryConditions.current_tier_unique_code ? 'selected' : ''}>${name}</option>`;
                });
            }
            $selTier.html(html);
            fnSelTier();
        }

        /**
         * 选择层
         */
        function fnSelTier() {
            let value = $selTier.val();
            let html = `<option value="">全部</option>`;
            if (value !== "") {
                $.each(queryConditions.positions[value], (uniqueCode, name) => {
                    html += `<option value="${uniqueCode}" ${uniqueCode === queryConditions.current_positon_unique_code ? 'selected' : ''}>${name}</option>`;
                });
            }
            $selPosition.html(html);
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

            initPage();
        });

        /**
         * 查询整件
         */
        function fnScreenEntireInstance() {
            let urlParams = {
                category_unique_code: $selCategory.val(),
                entire_model_unique_code: $selEntireModel.val(),
                sub_model_unique_code: $selSubModel.val(),
                factory_name: $selFactory.val(),
                status_unique_code: $selStatus.val(),
                storehouse_unique_code: $selStorehouse.val(),
                area_unique_code: $selArea.val(),
                platoon_unique_code: $selPlatoon.val(),
                shelf_unique_code: $selShelf.val(),
                tier_unique_code: $selTier.val(),
                position_unique_code: $selPosition.val(),
                material_type: 'ENTIRE'
            };

            location.href = fnMakeUrl(`{{ url('storehouse/index/material') }}`, urlParams);
        }

        /**
         * 查询部件
         */
        function fnScreenPartInstance() {
            let urlParams = {
                category_unique_code: $selCategory.val(),
                entire_model_unique_code: $selEntireModel.val(),
                sub_model_unique_code: $selSubModel.val(),
                factory_name: $selFactory.val(),
                status_unique_code: $selStatus.val(),
                storehouse_unique_code: $selStorehouse.val(),
                area_unique_code: $selArea.val(),
                platoon_unique_code: $selPlatoon.val(),
                shelf_unique_code: $selShelf.val(),
                tier_unique_code: $selTier.val(),
                position_unique_code: $selPosition.val(),
                material_type: 'PART'
            };

            location.href = fnMakeUrl(`{{ url('storehouse/index/material') }}`, urlParams);
        }

    </script>
@endsection

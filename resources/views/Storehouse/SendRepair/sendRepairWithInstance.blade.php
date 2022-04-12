@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备送修
        </h1>
        {{--        <ol class="breadcrumb">--}}
        {{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--            <li class="active">设备送修</li>--}}
        {{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form id="frmQuery">
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-default">
                        <div class="box-body form-horizontal">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">唯一编码</div>
                                        <input type="text" id="identity_code" name="identity_code" class="form-control" value="{{request('identity_code')}}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <select id="selMaterialType" name="materialType" class="form-control select2" style="width:100%;">
                                            <option value="ENTIRE" {{ $currentMaterialType == 'ENTIRE' ? 'selected' : '' }}>整件</option>
                                            <option value="PART" {{ $currentMaterialType == 'PART' ? 'selected' : '' }}>部件</option>
                                        </select>
                                        <div class="input-group-addon">状态</div>
                                        <select id="selStatus" name="status_code" class="form-control select2" style="width:100%;">
                                            <option value="">全部</option>
                                            @foreach($statuses as $statusCode=>$statusName)
                                                <option value="{{ $statusCode }}" {{ request('status_code') == $statusCode ? 'selected' : '' }}>{{ $statusName }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select id="selCategory" name="category_unique_code" class="form-control select2" style="width:100%;" onchange="fnSelectCategory()">
                                        </select>
                                        <div class="input-group-addon">类型</div>
                                        <select id="selEntireModel" name="entire_model_unique_code" class="form-control select2" style="width:100%;" onchange="fnSelectEntireModel()">
                                        </select>
                                        <div class="input-group-addon">型号</div>
                                        <select id="selSubModel" name="sub_model_unique_code" class="form-control select2" style="width:100%;">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">仓</div>
                                        <select id="selStorehouse" name="storehouse_unique_code" class="form-control select2" style="width:100%;" onchange="fnSelStorehouse()">

                                        </select>
                                        <div class="input-group-addon">区</div>
                                        <select id="selArea" name="area_unique_code" class="form-control select2" style="width:100%;" onchange="fnSelArea()">

                                        </select>
                                        <div class="input-group-addon">排</div>
                                        <select id="selPlatoon" name="platoon_unique_code" class="form-control select2" style="width:100%;" onchange="fnSelPlatoon()">

                                        </select>
                                        <div class="input-group-addon">架</div>
                                        <select id="selShelf" name="shelf_unique_code" class="form-control select2" style="width:100%;" onchange="fnSelShelf()">

                                        </select>
                                        <div class="input-group-addon">层</div>
                                        <select id="selTier" name="tier_unique_code" class="form-control select2" style="width:100%;" onchange="fnSelTier()">

                                        </select>
                                        <div class="input-group-addon">位</div>
                                        <select id="selPosition" name="position_unique_code" class="form-control select2" style="width:100%;">

                                        </select>
                                    </div>
                                </div>
                            </div>
                            <p></p>
                            <div class="box-header with-border">
                                <h1 class="box-title"></h1>
                                <div class="box-tools pull-right">
                                    <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">设备列表 (总数：{{empty($entireInstances) ? 0 : $entireInstances->total()}})</h3>
                    <div class="box-tools pull-right">
                        <a href="JavaScript:" onclick="fmSendRepairModal()" class="btn btn-success btn-flat">确认送修</a>
                    </div>
                </div>

                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed">
                        <theader>
                            <tr>
                                <th>选择</th>
                                <th>唯一编号</th>
                                <th>供应商名称</th>
                                <th>所编号</th>
                                <th>种类型</th>
                                <th>状态</th>
                                <th>故障类型</th>
                                <th>备注</th>
                            </tr>
                        </theader>
                        <tbody>
                        @foreach($entireInstances as $entireInstance)
                            <tr>
                                <td><input type="checkbox" id="{{$entireInstance->identity_code}}"
                                           @if(array_key_exists($entireInstance->identity_code,$tmpMaterials))
                                           checked
                                           @endif
                                           value="{{$entireInstance->identity_code}}" onchange="selInstance(this.value,`{{ $entireInstance->material_type }}`)"></td>
                                <td>
                                    @if($entireInstance->material_type == 'ENTIRE')
                                        <a href="{{ url('search',$entireInstance->identity_code) }}">{{ $entireInstance->identity_code }}</a>
                                    @else
                                        {{ $entireInstance->identity_code }}
                                    @endif
                                </td>
                                <td>{{$entireInstance->factory_name}}</td>
                                <td>{{ $entireInstance->serial_number }}</td>
                                <td>
                                    {{$entireInstance->category_name}}
                                    {{ empty($entireInstance->part_category_name)?'':($entireInstance->part_category_name) }}
                                    {{$entireInstance->model_name}}
                                </td>
                                <td>{{$statuses[$entireInstance->status]}}</td>
                                <td><a href="javascript:" onclick="fnBreakdownTypeModal(`{{ $entireInstance->identity_code }}`,`{{ $entireInstance->material_type }}`)"><i class="fa fa-plus-square"></i></a></td>
                                <td><input type="text" id="{{'repair_remark' . $entireInstance->identity_code}}" value="@if(array_key_exists($entireInstance->identity_code,$tmpMaterials)){{ $tmpMaterials[$entireInstance->identity_code] }}@endif @if(array_key_exists($entireInstance->identity_code,$sendRepairInstances)){{ $sendRepairInstances[$entireInstance->identity_code]['repair_remark'] }}@endif" @if(!array_key_exists($entireInstance->identity_code,$tmpMaterials)) disabled @endif onchange="updateRepair(`{{$entireInstance->identity_code}}`,`{{ $entireInstance->material_type }}`,this.value,'repair_remark')"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if(!empty($entireInstances) && $entireInstances->hasPages())
                    <div class="box-footer">
                        {{ $entireInstances->appends([
                                "materialType"=>request("materialType",$currentMaterialType),
                                "identity_code"=>request("identity_code"),
                                "status_code"=>request("status_code"),
                                "category_unique_code"=>request("category_unique_code"),
                                "entire_model_unique_code"=>request("entire_model_unique_code"),
                                "sub_model_unique_code"=>request("sub_model_unique_code"),
                                "storehouse_unique_code"=>request("storehouse_unique_code"),
                                "area_unique_code"=>request("area_unique_code"),
                                "platoon_unique_code"=>request("platoon_unique_code"),
                                "shelf_unique_code"=>request("shelf_unique_code"),
                                "tier_unique_code"=>request("tier_unique_code"),
                                "position_unique_code"=>request("position_unique_code"),
                                'page'=>request('page'),
                            ])->links() }}
                    </div>
                @endif
            </div>
        </form>
        <div id="divSendRepair"></div>
        <div id="divBreakdownType"></div>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/layer/layer.js"></script>
    <script>
        let $select2 = $(".select2");
        let $selCategory = $("#selCategory");
        let $selEntireModel = $("#selEntireModel");
        let $selSubModel = $("#selSubModel");
        let $selStorehouse = $("#selStorehouse");
        let $selArea = $("#selArea");
        let $selPlatoon = $("#selPlatoon");
        let $selShelf = $("#selShelf");
        let $selTier = $("#selTier");
        let $selPosition = $("#selPosition");
        let $selMaterialType = $("#selMaterialType");

        let queryConditions = JSON.parse('{!! $queryConditions !!}');


        $(function () {
            if ($select2.length > 0) $select2.select2();
            initPage();
        });

        /**
         * 初始化页面
         */
        function initPage() {
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
         * 查询
         */
        function fnScreen() {
            let urlParams = $('#frmQuery').serialize().split('&');
            let urlJoin = urlParams.join('&');
            location.href = `{{url('storehouse/sendRepair/instance')}}` + '?' + urlJoin + '&is_iframe={{ request('is_iframe') }}';

            {{--let materialType = $selMaterialType.val();--}}
            {{--let category_unique_code = $selCategory.val();--}}
            {{--let entire_model_unique_code = $selEntireModel.val();--}}
            {{--let sub_model_unique_code = $selSubModel.val();--}}
            {{--let storehouse_unique_code = $selStorehouse.val();--}}
            {{--let area_unique_code = $selArea.val();--}}
            {{--let platoon_unique_code = $selPlatoon.val();--}}
            {{--let shelf_unique_code = $selShelf.val();--}}
            {{--let tier_unique_code = $selTier.val();--}}
            {{--let position_unique_code = $selPosition.val();--}}
            {{--let identity_code = $('#identity_code').val();--}}
            {{--let status_code = $('#selStatus').val();--}}

            {{--location.href = `{{url('storehouse/sendRepair/instance')}}?identity_code=${identity_code}&status_code=${status_code}&materialType=${materialType}&category_unique_code=${category_unique_code}&entire_model_unique_code=${entire_model_unique_code}&sub_model_unique_code=${sub_model_unique_code}&storehouse_unique_code=${storehouse_unique_code}&area_unique_code=${area_unique_code}&platoon_unique_code=${platoon_unique_code}&shelf_unique_code=${shelf_unique_code}&tier_unique_code=${tier_unique_code}&position_unique_code=${position_unique_code}`;--}}
        }


        /**
         * 选择设备
         * @param identityCode
         * @param materialType
         */
        function selInstance(identityCode, materialType) {
            let loading = layer.load(2, {shade: false});
            let labelChecked = $(`#${identityCode}`).prop('checked');
            let url = '';
            let type = '';
            let disable = '';
            if (labelChecked) {
                url = `{{url('storehouse/sendRepair/tmpMaterial')}}`;
                type = 'post';
                disable = false;
            } else {
                url = `{{url('storehouse/sendRepair/tmpMaterial')}}/${identityCode}`;
                type = 'delete';
                disable = true;
            }
            $.ajax({
                url: url,
                type: type,
                data: {
                    identityCode: identityCode,
                    materialType: materialType
                },
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        // layer.msg(response.message)
                        if (disable) {
                            $("#repair_remark" + identityCode).attr('disabled', 'disabled');
                        } else {
                            $("#repair_remark" + identityCode).removeAttr('disabled');
                        }

                        layer.close(loading);
                    } else {
                        alert(response.message);
                        location.reload();
                        layer.close(loading);
                    }
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }


        /**
         * 更新送修临时表
         * @param identityCode
         * @param materialType
         * @param value
         * @param field
         */
        function updateRepair(identityCode, materialType, value, field) {
            let loading = layer.load(2, {shade: false});
            $.ajax({
                url: `{{url('storehouse/sendRepair/tmpMaterial')}}/${identityCode}`,
                type: 'put',
                data: {
                    value: value,
                    field: field,
                    materialType: materialType
                },
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        // layer.msg(response.message)
                        layer.close(loading);
                    } else {
                        alert(response.message);
                        location.reload();
                        layer.close(loading);
                    }
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }

        /**
         * 临时故障日志模态框
         */
        function fnBreakdownTypeModal(identityCode, materialType) {
            let labelChecked = $(`#${identityCode}`).prop('checked');
            if (labelChecked) {
                $.ajax({
                    url: `{{url('storehouse/sendRepair/tmpBreakdownLog')}}/create`,
                    type: "get",
                    data: {
                        identityCode: identityCode,
                        materialType: materialType
                    },
                    async: true,
                    success: function (response) {
                        $("#divBreakdownType").html(response);
                        $("#breakdownType").modal("show");
                    },
                    error: function (error) {
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    },
                });

            } else {
                console.log('未选择');
            }
        }

        /**
         * 确认送修模态框
         */
        function fmSendRepairModal() {
            $.ajax({
                url: "{{url('storehouse/sendRepair/create')}}",
                type: "get",
                async: true,
                success: function (response) {
                    $("#divSendRepair").html(response);
                    $("#sendRepair").modal("show");
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

    </script>
@endsection

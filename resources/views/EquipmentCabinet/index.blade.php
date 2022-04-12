@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            机柜及室内组合位置管理
            <small></small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">列表</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">机柜列表</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm">
                            <a onclick="modalCreateEquipmentCabinet()" class="btn btn-flat btn-success"><i class="fa fa-plus">&nbsp;</i>添加机柜</a>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-condensed" id="tableEquipmentCabinet">
                                <thead>
                                <tr>
                                    <th>名称</th>
                                    <th>排</th>
                                    <th>房间类型</th>
                                    <th>所属车站</th>
                                    <th>绑定设备</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($equipment_cabinets as $equipment_cabinet)
                                    <tr class="{{ $equipment_cabinet->unique_code == $current_equipment_cabinet_unique_code ? 'bg-orange' : '' }}">
                                        <td>
                                            @if($equipment_cabinet->unique_code != $current_equipment_cabinet_unique_code)
                                                <a href="{{ url('equipmentCabinet') }}?maintain_station_unique_code={{ request('maintain_station_unique_code') }}&equipment_cabinet_unique_code={{ $equipment_cabinet->unique_code }}">{{ $equipment_cabinet->name }}</a>
                                            @else
                                                {{ $equipment_cabinet->name }}
                                            @endif
                                        </td>
                                        <td>{{ $equipment_cabinet->row }}</td>
                                        <td>{{ $equipment_cabinet->room_type->name }}</td>
                                        <td>{{ @$equipment_cabinet->Station->name ?: '-' }}</td>
                                        <td>
                                            @if( $equipment_cabinet->entire_instance_identity_code )
                                                <a href="{{ url('search',$equipment_cabinet->entire_instance_identity_code) }}">$equipment_cabinet->entire_instance_identity_code</a>
                                            @else
                                                无
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a onclick="modalEditEquipmentCabinet({{ $equipment_cabinet->id }})" class="btn btn-warning btn-flat"><i class="fa fa-pencil">&nbsp;</i>编辑</a>
                                                <a class="btn btn-danger btn-flat" onclick="fnDelete('{{ $equipment_cabinet->id }}')"><i class="fa fa-times">&nbsp;</i>删除</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($equipment_cabinets->hasPages())
                        <div class="box-footer">
                            {{ $equipment_cabinets
                                                ->appends([
                                                    'page'=>request('page',1),
                                                    'maintain_station_unique_code'=>request('maintain_station_unique_code'),
                                                    'equipment_cabinet_unique_code'=>request('equipment_cabinet_unique_code'),
                                                    ])
                                                ->links() }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">层列表</h3>
                        {{--右侧最小化按钮--}}
                        <div class="pull-right btn-group btn-group-sm">
                            <a onclick="modalCreateCombinationLocationRow()" class="btn btn-flat btn-info"><i class="fa fa-plus">&nbsp;</i>添加层</a>
                            <a onclick="modalCreateCombinationLocationBatch()" class="btn btn-flat btn-primary"><i class="fa fa-plus">&nbsp;</i>批量添加位置</a>
                            <a onclick="fnPrintCombinationLocation()" class="btn btn-flat btn-default"><i class="fa fa-print">&nbsp;</i>打印标签</a>
                            <a onclick="fnDeleteCombinationLocationBatch()" class="btn btn-flat btn-danger"><i class="fa fa-times">&nbsp;</i>批量删除</a>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-condensed" id="tableCombinationLocation">
                                <tbody>
                                @foreach($combination_locations as $combination_location)
                                    @for($r=0;$r<$combination_location->row;$r++)
                                        <tr>
                                            <td>{{ $combination_location->row_name }}</td>
                                        </tr>
                                    @endfor
{{--                                    <tr>--}}
{{--                                        <td><input type="checkbox" class="combination-location-id" id="chkId_combinationLocation" value="{{ $combination_location->id }}"></td>--}}
{{--                                        <td>--}}
{{--                                            {{ $combination_location->row }}--}}
{{--                                            <br>--}}
{{--                                            {{ substr($combination_location->unique_code,0,19) }}--}}
{{--                                        </td>--}}
{{--                                        <td>--}}
{{--                                            {{ $combination_location->column }}--}}
{{--                                            <br>--}}
{{--                                            {{ $combination_location->unique_code }}--}}
{{--                                        </td>--}}
{{--                                        --}}{{--<td><a class="btn btn-danger btn-flat btn-sm" onclick="fnCombinationLocation({{ $combination_location->id }})"><i class="fa fa-times"></i></a></td>--}}
{{--                                    </tr>--}}
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>

    {{--模态框--}}
    <section class="section">
        {{--添加机柜模态框--}}
        <div class="modal fade" id="modalCreateEquipmentCabinet">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加机柜</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreEquipmentCabinet">
                            <input type="hidden" name="maintain_station_unique_code" value="{{ request('maintain_station_unique_code') }}">
                            <div class="form-group">
                                <label for="txtName_modalCreateEquipmentCabinet" class="col-sm-3 col-md-3 control-label">名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" id="txtName_modalCreateEquipmentCabinet" class="form-control" name="name" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="selRoomType_modalCreateEquipmentCabinet" class="col-sm-3 col-md-3 control-label">房间类型</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="room_type" id="selRoomType_modalCreateEquipmentCabinet" class="form-control select2" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="numRow_modalCreateEquipmentCabinet" class="col-sm-3 col-md-3 control-label">排：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="number" id="numRow_modalCreateEquipmentCabinet" class="form-control" name="row" value="1">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreEquipmentCabinet()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>

        {{--编辑机柜模态框--}}
        <div class="modal fade" id="modalEditEquipmentCabinet">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">编辑机柜</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmUpdateEquipmentCabinet">
                            <input type="hidden" name="maintain_station_unique_code" value="{{ request('maintain_station_unique_code') }}">
                            <div class="form-group">
                                <label for="txtName_modalEditEquipmentCabinet" class="col-sm-3 col-md-3 control-label">名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" id="txtName_modalEditEquipmentCabinet" class="form-control" name="name" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="selRoomType_modalCreateEquipmentCabinet" class="col-sm-3 col-md-3 control-label">房间类型</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="room_type" id="selRoomType_modalEditEquipmentCabinet" class="form-control select2" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="numRow_modalEditEquipmentCabinet" class="col-sm-3 col-md-3 control-label">排：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="number" id="numRow_modalEditEquipmentCabinet" class="form-control" name="row" value="">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-warning btn-flat" onclick="fnUpdateEquipmentCabinet()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>

        {{--添加层模态框--}}
        <div class="modal fade" id="modalCreateCombinationLocationRow">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加层</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreCombinationLocation">
                            <input type="hidden" name="equipment_cabinet_unique_code" value="{{ $current_equipment_cabinet_unique_code }}">
                            <div class="input-group">
                                <div class="input-group-addon">层名称</div>
                                <input type="text" id="txtRowName_modalCreateCombinationLocationRow" class="form-control" name="row" value="">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreCombinationLocationRow()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>

        {{--批量添加层列模态框--}}
        <div class="modal fade" id="modalCreateCombinationLocationBatch">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加组合位置（批量）</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreCombinationLocationBatch">
                            <input type="hidden" name="equipment_cabinet_unique_code" value="{{ $current_equipment_cabinet_unique_code }}">
                            <div class="input-group">
                                <div class="input-group-addon">层</div>
                                <input type="text" id="txtRow_modalCreateCombinationLocation" class="form-control" name="row" value="">
                                <div class="input-group-addon">位</div>
                                <input type="text" id="txtColumn_modalCreateCombinationLocation" class="form-control" name="column" value="">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreCombinationLocationBatch()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $modalCreateEquipmentCabinet = $('#modalCreateEquipmentCabinet');  // 新建机柜模态框
        let $selRoomType_modalCreateEquipmentCabinet = $('#selRoomType_modalCreateEquipmentCabinet');  // 新建机柜模态框 房间类型下拉列表
        let $frmStoreEquipmentCabinet = $('#frmStoreEquipmentCabinet');  // 新建机柜 表单
        let $modalEditEquipmentCabinet = $('#modalEditEquipmentCabinet');  // 编辑机柜模态框
        let $txtName_modalEditEquipmentCabinet = $('#txtName_modalEditEquipmentCabinet');  // 编辑机柜模态框 机柜名称
        let $selRoomType_modalEditEquipmentCabinet = $('#selRoomType_modalEditEquipmentCabinet');  // 编辑机柜模态框 房间类型下拉列表
        let $numRow_modalEditEquipmentCabinet = $('#numRow_modalEditEquipmentCabinet');  // 编辑机柜模态框 机柜排
        let $frmUpdateEquipmentCabinet = $('#frmUpdateEquipmentCabinet');  // 编辑机柜 表单
        let $modalBindEntireInstance = $('#modalBindEntireInstance');  // 绑定设备模态框
        let $tbody_modalBindEntireInstance = $('#tbody_modalBindEntireInstance');  // 绑定设备模态框 设备列表
        let $modalCreateCombinationLocationRow = $('#modalCreateCombinationLocationRow');  // 添加层模态框
        let $frmStoreCombinationLocationRow = $('#frmStoreCombinationLocationRow');  // 添加层 表单
        let $modalCreateCombinationLocationBatch = $('#modalCreateCombinationLocationBatch');  // 添加组合位置模态框（批量）
        let $frmStoreCombinationLocationBatch = $('#frmStoreCombinationLocationBatch');  // 添加组合位置（批量） 表单
        let $tableEquipmentCabinet = $('#tableEquipmentCabinet');  // 机柜表
        let $tableCombinationLocation = $('#tableCombinationLocation');  // 室内组合位置表

        let equipmentCabinetRoomTypes = {!! $equipment_cabinet_room_types_as_json !!};
        let currentUpdateEquipmentCabinetId = 0;

        /**
         * 全选多选框绑定
         * @param {string} allCheckId
         * @param {string} checkClassName
         */
        function __fnAllCheckBind(allCheckId, checkClassName) {
            $(allCheckId).on('click', function () {
                $(checkClassName).prop('checked', $(allCheckId).prop('checked'));
            });
            $(checkClassName).on('click', function () {
                $(allCheckId).prop('checked', $(`${checkClassName}:checked:not(:disabled)`).length === $(checkClassName).length);
            });
        }

        __fnAllCheckBind('#chkAllCheck_combinationLocation', '.combination-location-id');

        /**
         * 获取已选位置id
         */
        function __fnGetChecked_combinationLocationId() {
            let combinationLocationIds = [];
            $(`.combination-location-id:checked:not(:disabled)`).each(function (idx, item) {
                combinationLocationIds.push(item.value);
            });
            return combinationLocationIds;
        }

        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');
            let dataTableConfig = {
                columnDefs: [
                    {
                        orderable: false,
                        targets: 0,  // 清除第一列排序
                    }
                ],
                paging: false,  // 分页器
                lengthChange: true,
                searching: false,  // 搜索框
                ordering: true,  // 列排序
                info: true,
                autoWidth: true,  // 自动宽度
                order: [],  // 排序依据
                iDisplayLength: "{{ env('PAGE_SIZE', 50) }}",  // 默认分页数
                aLengthMenu: [50, 100, 200],  // 分页下拉框选项
                language: {
                    sInfoFiltered: "从_MAX_中过滤",
                    sProcessing: "正在加载中...",
                    info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                    sLengthMenu: "每页显示_MENU_条记录",
                    zeroRecords: "没有符合条件的记录",
                    infoEmpty: " ",
                    emptyTable: "没有符合条件的记录",
                    search: "筛选：",
                    paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                }
            };

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('tableEquipmentCabinet')) $tableEquipmentCabinet.DataTable(dataTableConfig);
            if (document.getElementById('tableCombinationLocation')) $tableCombinationLocation.DataTable(dataTableConfig);

            $('#reservation').daterangepicker({
                locale: {
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
                },
                startDate: originAt,
                endDate: finishAt
            });
        });

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('equipmentCabinet') }}/${id}`,
                    type: 'delete',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('equipmentCabinet') }}/${id} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('equipmentCabinet') }}/${id} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseJSON.msg);
                    }
                });
        }

        /**
         * 填充房间类型下拉列表
         */
        function fnFillRootType(equipmentCabinetRootTypeCode = '') {
            let html_modalCreateEquipmentCabinet = '';
            let html_modalEditEquipmentCabinet = '';
            $.each(equipmentCabinetRoomTypes, function (code, name) {
                html_modalCreateEquipmentCabinet += `<option value="${code}">${name}</option>`;
                html_modalEditEquipmentCabinet += `<option value="${code}" ${code === equipmentCabinetRootTypeCode ? 'selected' : ''}>${name}</option>`;
            });
            $selRoomType_modalCreateEquipmentCabinet.html(html_modalCreateEquipmentCabinet);
            $selRoomType_modalEditEquipmentCabinet.html(html_modalEditEquipmentCabinet);
        }

        /**
         * 打开新建机柜模态框
         */
        function modalCreateEquipmentCabinet() {
            fnFillRootType();
            $modalCreateEquipmentCabinet.modal('show');
        }

        /**
         * 新建机柜
         */
        function fnStoreEquipmentCabinet() {
            let data = $frmStoreEquipmentCabinet.serialize();

            $.ajax({
                url: `{{ url('equipmentCabinet') }}`,
                type: 'post',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('equipmentCabinet') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('equipmentCabinet') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 打开编辑机柜模态框
         * @param {int} equipmentCabinetId
         */
        function modalEditEquipmentCabinet(equipmentCabinetId) {
            $.ajax({
                url: `{{ url('equipmentCabinet') }}/${equipmentCabinetId}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('equipmentCabinet') }}/${equipmentCabinetId} success:`, res);
                    let {equipment_cabinet: equipmentCabinet} = res.data;
                    $txtName_modalEditEquipmentCabinet.val(equipmentCabinet.name);  // 机柜名称
                    fnFillRootType(equipmentCabinet.room_type.code);  // 填充房间类型下拉框
                    $numRow_modalEditEquipmentCabinet.val(equipmentCabinet.row);  // 机柜排
                    currentUpdateEquipmentCabinetId = equipmentCabinetId;  // 设置当前编辑机柜编号
                    $modalEditEquipmentCabinet.modal('show');  // 显示编辑模态框
                },
                error: function (err) {
                    console.log(`{{ url('equipmentCabinet') }}/${equipmentCabinetId} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 编辑机柜
         */
        function fnUpdateEquipmentCabinet() {
            let data = $frmUpdateEquipmentCabinet.serializeArray();

            $.ajax({
                url: `{{ url('equipmentCabinet') }}/${currentUpdateEquipmentCabinetId}`,
                type: 'put',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('equipmentCabinet') }}/${currentUpdateEquipmentCabinetId} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('equipmentCabinet') }}/${currentUpdateEquipmentCabinetId} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 打开绑定设备模态框
         * @param {int} equipmentCabinetId
         */
        function modalBindEntireInstance(equipmentCabinetId) {
            $.ajax({
                url: `{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance success:`, res);
                    let {entire_instances: entireInstances, current_entire_instance_identity_code: currentEntireInstanceIdentityCode} = res.data.data;
                    let html = '';
                    entireInstances.map(function (item) {
                        html += `<tr>`;
                        html += `<td>${item.identity_code}</td>`;
                        if (currentEntireInstanceIdentityCode === '') {
                            html += `<td><a href="javascript:" onclick="fnBindEntireInstance(${equipmentCabinetId},'${item.identity_code}')"><i class="fa fa-link">&nbsp;</i>绑定</td>`;
                        } else if (currentEntireInstanceIdentityCode === item.identity_code) {
                            html += `<td><a href="javascript:" onclick="fnUnBindEntireInstance(${equipmentCabinetId})"><i class="fa fa-unlink">&nbsp;</i>解绑</td>`;
                        } else {
                            html += `<td></td>`;
                        }
                        html += `</tr>`;
                    });
                    $tbody_modalBindEntireInstance.html(html);
                    $modalBindEntireInstance.modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 绑定机柜和设备
         * @param {string} entireInstanceIdentityCode
         */
        function fnBindEntireInstance(entireInstanceIdentityCode) {
            $.ajax({
                url: `{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance`,
                type: 'post',
                data: {entireInstanceIdentityCode},
                async: true,
                success: function (res) {
                    console.log(`{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 解绑机柜和设备
         */
        function fnUnBindEntireInstance(equipmentCabinetId) {
            $.ajax({
                url: `{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance`,
                type: 'delete',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('equipmentCabinet') }}/${equipmentCabinetId}/bindEntireInstance fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 打开添加层模态框
         */
        function modalCreateCombinationLocationRow() {
            $modalCreateCombinationLocationRow.modal('show');
        }

        /**
         * 添加层
         */
        function fnStoreCombinationLocationRow() {
            let data = $frmStoreCombinationLocationRow.serializeArray();

            $.ajax({
                url: `{{ url('combinationLocation') }}`,
                type: 'post',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('combinationLocation') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('combinationLocation') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 打开添加组合位置模态框（批量）
         */
        function modalCreateCombinationLocationBatch() {
            $modalCreateCombinationLocationBatch.modal('show');
        }

        /**
         * 添加组合位置（批量）
         */
        function fnStoreCombinationLocationBatch() {
            let data = $frmStoreCombinationLocationBatch.serializeArray();

            $.ajax({
                url: `{{ url('combinationLocation/batch') }}`,
                type: 'post',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('combinationLocation/batch') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('combinationLocation/batch') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 删除组合位置
         * @param {int} combinationLocationId
         */
        function fnCombinationLocation(combinationLocationId) {
            $.ajax({
                url: `{{ url('combinationLocation') }}/${combinationLocationId}`,
                type: 'delete',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('combinationLocation') }}/${combinationLocationId} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('combinationLocation') }}/${combinationLocationId} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 删除组合位置（批量）
         */
        function fnDeleteCombinationLocationBatch() {
            let combinationLocationIds = __fnGetChecked_combinationLocationId();
            if (combinationLocationIds.length === 0) {
                alert('请打勾选择需要删除的位置');
                return;
            }
            if (confirm('删除不能恢复，是否确认？'))
                $.ajax({
                    url: `{{ url('combinationLocation/batch') }}`,
                    type: 'delete',
                    data: {combinationLocationIds},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('combinationLocation/batch') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('combinationLocation/batch') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseJSON.msg);
                    }
                });
        }

        /**
         * 打印二维码标签
         */
        function fnPrintCombinationLocation() {
            let combinationLocationIds = __fnGetChecked_combinationLocationId();
            if (combinationLocationIds.length === 0) {
                alert('请打勾选择需要打印的位置');
                return;
            }

            $.ajax({
                url: `{{ url('combinationLocation/print') }}`,
                type: 'post',
                data: {combinationLocationIds},
                async: true,
                success: function (res) {
                    console.log(`{{ url('combinationLocation/print') }} success:`, res);
                    location.href = `{{ url('') }}`;
                },
                error: function (err) {
                    console.log(`{{ url('combinationLocation/print') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }
    </script>
@endsection

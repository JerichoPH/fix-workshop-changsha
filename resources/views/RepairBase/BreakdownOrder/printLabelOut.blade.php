@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            故障修管理
            <small>绑定设备</small>
        </h1>
        {{--        <ol class="breadcrumb">--}}
        {{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--            <li><a href="{{ url('repairBase/breakdownOrder',$out_sn) }}?direction=OUT">故障修出所详情</a></li>--}}
        {{--            <li class="active">绑定设备</li>--}}
        {{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">故障修出所设备列表 <small>任务总需：{{ $plan_sum }} 成品可用：{{ $usable_entire_instance_sum }}</small>
                </h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="javascript:" onclick="fnModalBindBreakdownType()" class="btn btn-flat btn-warning"><i class="fa fa-wrench">&nbsp;</i>设置故障类型</a>
                    <a href="javascript:" onclick="fnAutoBindEntireInstances()" class="btn btn-flat btn-default"><i class="fa fa-link">&nbsp;</i>自动绑定</a>
                    <a href="javascript:" onclick="fnUnBindEntireInstances()" class="btn btn-flat btn-default"><i class="fa fa-unlink">&nbsp;</i>解绑设备/器材</a>
                    <a href="{{ url('repairBase/breakdownOrder',$out_sn) }}?direction=OUT" class="btn btn-flat btn-primary"><i class="fa fa-sign-out">&nbsp;</i>添加出所单</a>
                    {{--<a href="javascript:" class="btn btn-flat btn-default" onclick="fnPrintLabel()"><i class="fa fa-print">&nbsp;</i>打印标签</a>--}}
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th><input type="checkbox" name="" id="chkAll" {{ $is_all_bound ? 'checked' : '' }}></th>
                            <th style="width: 13%;">唯一编号<br>所编号(故障)</th>
                            <th style="width: 10%;">型号</th>
                            <th style="width: 10%;">位置</th>
                            <th style="width: 10%;">故障<br>次数</th>
                            <th style="width: 13%;">唯一编号<br>所编号(成品)</th>
                            <th style="width: 10%;">仓库<br>位置</th>
                            <th style="width: 10%;">故障<br>类型</th>
                            <th style="width: 8%;">故障<br>报告</th>
                            <th style="width: 8%;">检修<br>检测</th>
                            <th style="width: 13%;">替换</th>
                        </tr>
                        </thead>
                        <tbody id="tbody">
                        @foreach($entire_instances as $entire_instance)
                            <tr id="tr_{{ $entire_instance->OldEntireInstance->identity_code }}">
                                <td>
                                    <input type="checkbox"
                                           class="select-bind-entire-instance {{ $entire_instance->out_warehouse_sn ? 'disabled' : '' }}"
                                           name="labelChecked"
                                           id="chk_{{ $entire_instance->OldEntireInstance->identity_code }}"
                                           value="{{ $entire_instance->OldEntireInstance->identity_code }}"
                                        {{ $entire_instance->new_entire_instance_identity_code ? 'checked' : '' }}
                                        {{ $entire_instance->out_warehouse_sn ? 'disabled' : '' }}
                                    >
                                </td>
                                <td>
                                    {{ $entire_instance->OldEntireInstance->identity_code }}<br>
                                    {{ $entire_instance->OldEntireInstance->serial_number }}
                                </td>
                                <td>{{ $entire_instance->OldEntireInstance->model_name }}</td>
                                <td>
                                    {{ $entire_instance->maintain_station_name }}
                                    {{ $entire_instance->maintain_location_code }}
                                    {{ $entire_instance->crossroad_number }}
                                </td>
                                <td>{{ count(@$breakdown_logs_as_install_location["{$entire_instance->maintain_station_name} {$entire_instance->maintain_location_code} {$entire_instance->crossroad_number}"] ?? []) }}</td>
                                <td>
                                    <span id="spanNewEntireInstance_{{ $entire_instance->OldEntireInstance->identity_code }}">
                                        {{ @$entire_instance->NewEntireInstance->identity_code }}<br>
                                        {{ @$entire_instance->NewEntireInstance->serial_number }}
                                    </span>
                                </td>
                                <td>
                                    @if(@$entire_instance->NewEntireInstance)
                                        @if(@$entire_instance->NewEntireInstance->storehouse_location['has_img'])
                                            <a id="spanWarehouseLocation_{{ $entire_instance->OldEntireInstance->identity_code }}" onclick="fnStorehouseLocationImg('{{ @$entire_instance->NewEntireInstance->storehouse_location['code'] }}','{{ @$entire_instance->NewEntireInstance->storehouse_location['name'] }}')">
                                                {{ @$entire_instance->NewEntireInstance->storehouse_location['name'] }}
                                            </a>
                                        @else
                                            <span id="spanWarehouseLocation_{{ $entire_instance->OldEntireInstance->identity_code }}">
                                                {{ @$entire_instance->NewEntireInstance->storehouse_location['name'] }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if(count($entire_instance->breakdown_types) > 20)
                                        <a href="javascript:" onclick="fnModalEditBreakdownType({{ $entire_instance->id }})">{{ substr($entire_instance->breakdown_types,0,20) }}……</a>
                                    @else
                                        <a href="javascript:" onclick="fnModalEditBreakdownType({{ $entire_instance->id }})">{{ $entire_instance->breakdown_types }}</a>
                                    @endif
                                </td>
                                <td>
                                    <a href="javascript:" onclick="fnModalUploadBreakdownReportFile({{ $entire_instance->id }})">
                                        @if(@$entire_instance->BreakdownReportFiles ? $entire_instance->BreakdownReportFiles->isNotEmpty() : false)
                                            <i class="fa fa-file-o"></i>
                                        @else
                                            <i class="fa fa-upload"></i>
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    @if($entire_instance->OldEntireInstance)
                                        @if($entire_instance->OldEntireInstance->FixWorkflow)
                                            <a href="{{ url('measurement/fixWorkflow',$entire_instance->OldEntireInstance->FixWorkflow->serial_number) }}/edit" target="_blank"><i class="fa fa-file-text-o"></i></a>
                                        @else
                                            <a href="{{ url('measurement/fixWorkflow/create') }}?identity_code={{ $entire_instance->old_entire_instance_identity_code }}&type=FIX" target="_blank"><i class="fa fa-plus"></i></a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <label for="selNewIdentityCode" style="display: none;"></label>
                                    <select name="new_identity_code"
                                            id="selNewIdentityCode_{{ $entire_instance->OldEntireInstance->identity_code }}"
                                            class="form-control select2 {{ $entire_instance->out_warehouse_sn ? 'disabled' : '' }}"
                                            style="width: 100%;"
                                            onchange="fnBindEntireInstance('{{ $entire_instance->OldEntireInstance->identity_code }}',this.value)"
                                        {{ $entire_instance->out_warehouse_sn ? 'disabled' : '' }}
                                    >
                                        @if(empty($usable_entire_instances->get($entire_instance->OldEntireInstance->model_name)))
                                            <option value="">无</option>
                                        @else
                                            <option value="">未选择</option>
                                            @foreach($usable_entire_instances->get($entire_instance->OldEntireInstance->model_name)->all() as $ei)
                                                <option value="{{ $ei->identity_code }}">{{ $ei->identity_code }}</option>
                                            @endforeach
                                        @endif
                                        @if(!is_null($entire_instance->NewEntireInstance))
                                            <option value="{{ $entire_instance->NewEntireInstance->identity_code }}" selected>{{ $entire_instance->NewEntireInstance->identity_code }}</option>
                                        @endif
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!--模态框-->
    <section>
        <!--故障类型列表模态框-->
        <div class="modal fade" id="modalBindBreakdownType">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">选择故障类型</h4>
                    </div>
                    <div class="modal-body">
                        <form id="frmBindBreakdownType">
                            <input type="hidden" name="old_identity_codes" id="hdnOldIdentityCodes_modalBindBreakdownType" value="">
                            <input type="hidden" name="out_sn" value="{{ $out_sn }}">
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <tbody id="tbody_modalBindBreakdownType">
                                    @foreach($breakdown_types as $chunk)
                                        <tr>
                                            @foreach($chunk as $breakdown_type_id => $breakdown_type_name)
                                                <td>
                                                    <input type="checkbox" name="breakdown_type_ids[]" class="breakdown-type-checkbox" id="chkBreakdownTypeId_{{ $breakdown_type_id }}" value="{{ $breakdown_type_id }}">
                                                    <label for="chkBreakdownTypeId_{{ $breakdown_type_id }}" class="control-label">{{ $breakdown_type_name }}</label>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{--<label for="txaExplain">补充描述：</label>--}}
                            {{--<textarea name="explain" id="txaExplain" cols="30" rows="5" class="form-control"></textarea>--}}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnBindBreakdownType()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>

        <!--仓库图片弹窗-->
        <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
             id="locationShow">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">位置：<span id="title"></span></h4>
                    </div>
                    <div class="modal-body">
                        <img id="location_img" class="model-body-location" alt="" style="width: 100%;" src=""/>
                        <div class="spot"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--上传故障报告文件-->
        <div class="modal fade" id="modalUploadBreakdownReportFile">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">选择上传故障报告</h4>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-condensed table-bordered table-hover">
                                <tbody id="tbody_modalUploadBreakdownReportFile"></tbody>
                            </table>
                        </div>
                        <form id="frmUploadBreakdownReportFile" action="{{ url('repairBase/breakdownReportFile') }}" method="post" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="hidden" name="breakdown_order_entire_instance_id" id="hdnBreakdownOrderEntireInstance_frmUploadBreakdownReportFile" value="">
                                <input type="file" name="file[]" multiple class="form-control">
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-success btn-flat">上传</button>
                                </div>
                            </div>
                        </form>
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
        let $chkAll = $('#chkAll');
        let $modalBindBreakdownType = $('#modalBindBreakdownType');
        let $hdnOldIdentityCodes_modalBindBreakdownType = $('#hdnOldIdentityCodes_modalBindBreakdownType');
        let $frmBindBreakdownType = $('#frmBindBreakdownType');
        let $modalUploadBreakdownReportFile = $('#modalUploadBreakdownReportFile');
        let $tbody_modalUploadBreakdownReportFile = $('#tbody_modalUploadBreakdownReportFile');
        let $hdnBreakdownOrderEntireInstance_frmUploadBreakdownReportFile = $('#hdnBreakdownOrderEntireInstance_frmUploadBreakdownReportFile');

        /**
         * 绑定故障类型 模态框关闭后，刷新页面
         */
        $modalBindBreakdownType.on('hidden.bs.modal', function () {
            location.reload();
        });

        /**
         * 编辑故障类型 模态框关闭后，刷新页面
         */
        // $modalEditBreakdownType.on('hidden.bs.modal', function () {
        //     location.reload();
        // });

        /**
         * 获取已选择的多选的框值
         */
        function __getCheckedIdentityCodes() {
            let oldIdentityCodes = [];
            $.each($('.select-bind-entire-instance:checked'), function (key, val) {
                oldIdentityCodes.push(val.value);
            });
            return oldIdentityCodes;
        }

        /**
         * 获取已经选择的故障类型
         */
        function __getCheckedBreakdownType() {
            let breakdownTypeIds = [];
            $('.breakdown-type-checkbox:checked').each(function (key, val) {
                breakdownTypeIds.push(val.value);
            });
            return breakdownTypeIds;
        }

        /**
         * 全选多选框绑定
         * @param {string} allCheckId
         * @param {string} checkClassName
         */
        function __fnAllCheckBind(allCheckId, checkClassName) {
            $(allCheckId).on('click', function () {
                $(`checkClassName:not(:disabled)`).prop('checked', $(allCheckId).prop('checked'));
            });
            $(checkClassName).on('click', function () {
                $(allCheckId).prop('checked', $(`${checkClassName}:checked:not(:disabled)`).length === $(`checkClassName:not(:disabled)`).length);
            });
        }

        __fnAllCheckBind('#chkAll', '.select-bind-entire-instance');

        $(function () {
            if ($select2.length > 0) $('.select2').select2();
            if (document.getElementById('table')) {
                $('#table').DataTable({
                    columnDefs:[
                        {
                            orderable:false,
                            targets:0,
                        }
                    ],
                    paging: false,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    order: [[4, 'desc']],
                    info: true,
                    autoWidth: false,
                    iDisplayLength: 15,
                    aLengthMenu: [15, 30, 50, 100],
                    language: {
                        sProcessing: "正在加载中...",
                        info: "显示第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "显示 _MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "查询：",
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });
            }
        });

        /**
         * 打印标签
         */
        function fnPrintLabel() {
            //处理数据
            let selected_for_api = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each(function (index, item) {
                let value = $(item).closest('tr').find('td').eq(5).text().split('/');
                let new_code = value[0];
                if (new_code !== '') selected_for_api.push(new_code);
            });

            if (selected_for_api.length > 0) {
                window.open(`{{url('qrcode/printLabel')}}?identityCodes=${JSON.stringify(selected_for_api)}`);
            } else {
                alert('无数据')
            }
        }

        /**
         * 手动绑定新设备到老设备
         */
        function fnBindEntireInstance(oldIdentityCode, newIdentityCode) {
            if (newIdentityCode) {
                $.ajax({
                    url: `{{ url('repairBase/breakdownOrder/bindEntireInstance') }}`,
                    type: 'post',
                    data: {oldIdentityCode, newIdentityCode, outSn: '{{ $out_sn }}'},
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('repairBase/breakdownOrder/bindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/breakdownOrder/bindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                        location.reload();
                    }
                });
            } else {
                $.ajax({
                    url: `{{ url('repairBase/breakdownOrder/bindEntireInstance') }}`,
                    type: 'delete',
                    data: {oldIdentityCode, newIdentityCode, outSn: '{{ $out_sn }}'},
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('repairBase/breakdownOrder/bindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/breakdownOrder/bindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                        location.reload();
                    }
                });
            }
        }

        /**
         * 批量绑定设备
         */
        function fnAutoBindEntireInstances() {
            let oldIdentityCodes = __getCheckedIdentityCodes();
            if(oldIdentityCodes.length === 0){
                alert('请勾选需要解绑的设备/器材');
                return;
            }

            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/autoBindEntireInstance') }}`,
                type: 'post',
                data: {oldIdentityCodes, outSn: '{{ $out_sn }}'},
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/autoBindEntireInstance') }} success:`, res);
                    // alert(res.msg);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/autoBindEntireInstance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 批量解绑
         */
        function fnUnBindEntireInstances() {
            let oldIdentityCodes = __getCheckedIdentityCodes();
            if(oldIdentityCodes.length === 0) {
                alert('请勾选需要解绑的设备/器材');
                return;
            }

            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/bindEntireInstance') }}`,
                type: 'delete',
                data: {oldIdentityCodes, outSn: '{{ $out_sn }}'},
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/bindEntireInstance') }} success:`, res);
                    alert(res.msg);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/bindEntireInstance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 打开绑定故障类型模态框
         */
        function fnModalBindBreakdownType() {
            console.log(__getCheckedIdentityCodes());
            $hdnOldIdentityCodes_modalBindBreakdownType.val(__getCheckedIdentityCodes().join(','));
            $modalBindBreakdownType.modal('show');
        }

        /**
         * 设置故障类型
         */
        function fnBindBreakdownType() {
            let frmData = $frmBindBreakdownType.serializeArray();
            $.ajax({
                url: `{{ url('repairBase/breakdownOrder/breakdownType') }}`,
                type: 'post',
                data: frmData,
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrder/breakdownType') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrder/breakdownType') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 仓库名称
         * @param {string} locationUniqueCode
         * @param {string} locationFullName
         */
        function fnStorehouseLocationImg(locationUniqueCode, locationFullName) {
            $.ajax({
                url: `{{url('storehouse/location/img2')}}/${locationUniqueCode}`,
                type: 'get',
                data: {locationFullName},
                async: true,
                success: res => {
                    console.log(`success:`, res);

                    if (res.status === 200) {
                        // console.log(response);
                        $('#title').text(res.data.location_full_name);
                        let location_img = res.data.location_img;
                        if (location_img) {
                            document.getElementById('location_img').src = location_img;
                            $("#locationShow").modal("show");
                        } else {
                            alert('请联系管理员，绑定位置图片');
                            // location.reload();
                        }
                    } else {
                        alert(res.message);
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
         * 打开修改故障类型模态框
         */
        function fnModalEditBreakdownType(id) {
            $('.select-bind-entire-instance').each(function (key, item) {
                $(item).prop('checked', false);
            });

            $.ajax({
                url: `{{ url('repairBase/breakdownOrderEntireInstance') }}/${id}`,
                type: 'get',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownOrderEntireInstance') }}/${id} success:`, res);
                    let {breakdown_order_entire_instance, breakdown_type_ids} = res['data'];
                    $.each(breakdown_type_ids, function (key, breakdown_type_id) {
                        $(`#chkBreakdownTypeId_${breakdown_type_id}`).prop('checked', true);
                    });
                    $(`#chk_${breakdown_order_entire_instance.old_entire_instance_identity_code}`).prop('checked', true);
                    fnModalBindBreakdownType();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownOrderEntireInstance') }}/${id} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 填充故障报告文件表格
         */
        function _fnFillBreakdownReportFile(breakdownReportFiles) {
            let html = '';
            $.each(breakdownReportFiles, function (key, breakdownReportFile) {
                html += `<tr>`;
                html += `<td>${breakdownReportFile.source_filename}</td>`;
                html += `<td><a href="/repairBase/breakdownReportFile/${breakdownReportFile.id}/download" target="_blank"><i class="fa fa-download"></i></a></td>`;
                html += `<td><a href="javascript:" class="text-danger" onclick="fnDeleteBreakdownReportFile(${breakdownReportFile.id})"><i class="fa fa-times"></i></a></td>`;
                html += `</tr>`;
            });
            $tbody_modalUploadBreakdownReportFile.html(html);
        }

        /**
         * 打开上传故障报告模态框
         * @param {int} id
         */
        function fnModalUploadBreakdownReportFile(id) {
            $.ajax({
                url: `{{ url('repairBase/breakdownReportFile') }}`,
                type: 'get',
                data: {breakdown_order_entire_instance_id: id},
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/breakdownReportFile') }} success:`, res);
                    let {breakdown_report_files} = res.data;
                    if (breakdown_report_files) _fnFillBreakdownReportFile(breakdown_report_files);
                    $hdnBreakdownOrderEntireInstance_frmUploadBreakdownReportFile.val(id);
                    $modalUploadBreakdownReportFile.modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/breakdownReportFile') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 删除故障报告文件
         * @param id
         */
        function fnDeleteBreakdownReportFile(id) {
            if (confirm('删除文件不可恢复，是否确认？'))
                $.ajax({
                    url: `{{ url('repairBase/breakdownReportFile') }}/${id}`,
                    type: 'delete',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('repairBase/breakdownReportFile') }}/${id} success:`, res);
                        // alert(res.msg);
                        // 刷新故障报告文件列表
                        let {breakdown_report_files} = res.data;
                        if (breakdown_report_files) _fnFillBreakdownReportFile(breakdown_report_files);
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/breakdownReportFile') }}/${id} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
        }
    </script>
@endsection

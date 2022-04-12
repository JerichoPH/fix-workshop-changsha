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
{{--            <li><a href="{{ url('tempTaskSubOrder', $tempTaskSubOrder->id) }}/edit">高频/状态修</a></li>--}}
{{--            <li class="active">绑定设备</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">高频/状态修设备绑定列表 <small>任务总需：{{ $unBindCount }} 成品可用：{{ $usableEntireInstanceSum }}</small></h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ url('tempTaskSubOrder', $tempTaskSubOrder->id) }}/edit" class="btn btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    {{--<a href="{{ url('tempTaskSubOrder',$tempTaskSubOrder->id) }}/edit" class="btn btn-flat btn-primary"><i class="fa fa-sign-out">&nbsp;</i>添加出所单</a>--}}
                    {{--<a href="javascript:" class="btn btn-flat btn-default" onclick="downloadLabel()"><i class="fa fa-print">&nbsp;</i>打印</a>--}}
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>
                                <input type="checkbox" name="" id="chkAll" onchange="fnAllCheck()" {{ $isAllBound ? 'checked' : '' }}>
                            </th>
                            <th style="width: 20%;">唯一/所编号(待下道)</th>
                            <th style="width: 10%;">型号</th>
                            <th style="width: 10%;">位置</th>
                            {{--                            <th style="width: 10%;">故障次数</th>--}}
                            <th style="width: 20%;">唯一/所编号(成品)</th>
                            <th style="width: 10%;">仓库位置</th>
                            <th style="width: 20%;">替换</th>
                        </tr>
                        </thead>
                        <tbody id="tbody">
                        @foreach($tempTaskSubOrderEntireInstances as $entireInstance)
                            <tr id="tr_{{ $entireInstance->OldEntireInstance->identity_code }}">
                                <td>
                                    <input
                                        type="checkbox"
                                        class="select-bind-entire-instance {{ $entireInstance->out_warehouse_sn ? 'disabled' : '' }}"
                                        name="labelChecked"
                                        id="chk_{{ $entireInstance->OldEntireInstance->identity_code }}"
                                        value="{{ $entireInstance->OldEntireInstance->identity_code }}"
                                        {{ $entireInstance->new_entire_instance_identity_code ? 'checked' : '' }}
                                        {{ $entireInstance->out_warehouse_sn ? 'disabled' : '' }}
                                    >
                                </td>
                                <td>{{ $entireInstance->OldEntireInstance->identity_code }}/{{ $entireInstance->OldEntireInstance->serial_number }}</td>
                                <td>{{ $entireInstance->OldEntireInstance->model_name }}</td>
                                <td>
                                    {{ $entireInstance->maintain_station_name }}
                                    {{ $entireInstance->maintain_location_code }}
                                    {{ $entireInstance->crossroad_number }}
                                </td>
                                {{--                                <td>{{ count(@$breakdown_logs_as_install_location["{$entire_instance->maintain_station_name} {$entire_instance->maintain_location_code} {$entire_instance->crossroad_number}"] ?? []) }}</td>--}}
                                <td><span id="spanNewEntireInstance_{{ $entireInstance->OldEntireInstance->identity_code }}">{{ @$entireInstance->NewEntireInstance->identity_code }}/{{ @$entireInstance->NewEntireInstance->serial_number }}</span></td>
                                <td><span id="spanWarehouseLocation_{{ $entireInstance->OldEntireInstance->identity_code }}">{{ @$entireInstance->NewEntireInstance->location_unique_code }}</span></td>
                                <td>
                                    <label for="selNewIdentityCode" style="display: none;"></label>
                                    <select
                                        name="new_identity_code"
                                        id="selNewIdentityCode_{{ $entireInstance->OldEntireInstance->identity_code }}"
                                        class="form-control select2 {{ $entireInstance->out_warehouse_sn ? 'disabled' : '' }}"
                                        style="width: 100%;"
                                        onchange="fnBindEntireInstance('{{ $entireInstance->OldEntireInstance->identity_code }}',this.value)"
                                        {{ $entireInstance->out_warehouse_sn ? 'disabled' : '' }}
                                    >
                                        @if(empty($usableEntireInstances->get($entireInstance->OldEntireInstance->model_name)))
                                            <option value="">无</option>
                                        @else
                                            <option value="">未选择</option>
                                            @foreach($usableEntireInstances->get($entireInstance->OldEntireInstance->model_name)->all() as $ei)
                                                <option value="{{ $ei->identity_code }}">{{ $ei->identity_code }}</option>
                                            @endforeach
                                        @endif
                                        @if(!is_null($entireInstance->NewEntireInstance))
                                            <option value="{{ $entireInstance->NewEntireInstance->identity_code }}" selected>{{ $entireInstance->NewEntireInstance->identity_code }}</option>
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
@endsection
@section('script')
    <script>

        let $select2 = $('.select2');
        let $chkAll = $('#chkAll');

        $(function () {
            if ($select2.length > 0) $('.select2').select2();
            if (document.getElementById('table')) {
                $('#table').DataTable({
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
         *下载标签
         */
        function downloadLabel() {
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

        function fnAllCheck() {
            console.log($chkAll.prop('checked'));
            if ($chkAll.prop('checked')) {
                // 全选
                fnAutoBindEntireInstances();
            } else {
                // 取消全选
                fnDeleteBindEntireInstances();
            }
        }

        /**
         * 全选绑定设备
         */
        function fnAutoBindEntireInstances() {
            let data = {
                temp_task_id: '{{ $tempTask->id }}',
                temp_task_sub_order_id: '{{ $tempTaskSubOrder->id }}',
                type: 'HIGH_FREQUENCY',
                temp_task_serial_number: '{{ $tempTask->serial_number }}',
                temp_task_title: '{{ $tempTask->title }}',
                temp_task_sub_order_work_area_id: '{{ $tempTaskSubOrder->work_area_id }}',
                temp_task_sub_order_maintain_station_name: '{{ $tempTaskSubOrder->maintain_station_name }}',
            };

            $.ajax({
                url: `{{ url('tempTaskSubOrder/autoBindEntireInstances') }}`,
                type: 'post',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrder/autoBindEntireInstances') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrder/autoBindEntireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });

            $("input[type='checkbox']").iCheck("check");
        }

        /**
         * 取消全部绑定设备
         */
        function fnDeleteBindEntireInstances() {
            $.ajax({
                url: `{{ url('tempTaskSubOrder/bindEntireInstances') }}`,
                type: 'delete',
                data: {
                    temp_task_id: '{{ $tempTask->id }}',
                    temp_task_sub_order_id: '{{ $tempTaskSubOrder->id }}',
                    type: 'HIGH_FREQUENCY',
                },
                async: false,
                success: function (res) {
                    console.log(`{{ url('tempTaskSubOrder/bindEntireInstances') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskSubOrder/bindEntireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });

            $("input[type='checkbox']").iCheck("uncheck");
        }

        /**
         * 手动绑定新设备到老设备
         */
        function fnBindEntireInstance(oldIdentityCode, newIdentityCode) {
            let data = {
                oldIdentityCode,
                newIdentityCode,
                temp_task_id: '{{ $tempTask->id }}',
                temp_task_sub_order_id: '{{ $tempTaskSubOrder->id }}',
                type: 'HIGH_FREQUENCY',
                temp_task_serial_number: '{{ $tempTask->serial_number }}',
                temp_task_title: '{{ $tempTask->title }}',
                temp_task_sub_order_work_area_id: '{{ $tempTaskSubOrder->work_area_id }}',
                temp_task_sub_order_maintain_station_name: '{{ $tempTaskSubOrder->maintain_station_name }}',
            };

            if (newIdentityCode) {
                $.ajax({
                    url: `{{ url('tempTaskSubOrder/bindEntireInstance') }}`,
                    type: 'post',
                    data: data,
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('tempTaskSubOrder/bindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTaskSubOrder/bindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
            } else {
                $.ajax({
                    url: `{{ url('tempTaskSubOrder/bindEntireInstance') }}`,
                    type: 'delete',
                    data: data,
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('tempTaskSubOrder/bindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTaskSubOrder/bindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
            }
        }

        /**
         * 自动绑定新设备到老设备
         */
        $('.select-bind-entire-instance').on('change', function () {
            let oldIdentityCode = $(this).val();
            let data = {
                oldIdentityCode,
                temp_task_id: '{{ $tempTask->id }}',
                temp_task_sub_order_id: '{{ $tempTaskSubOrder->id }}',
                type: 'HIGH_FREQUENCY',
                temp_task_serial_number: '{{ $tempTask->serial_number }}',
                temp_task_title: '{{ $tempTask->title }}',
                temp_task_sub_order_work_area_id: '{{ $tempTaskSubOrder->work_area_id }}',
                temp_task_sub_order_maintain_station_name: '{{ $tempTaskSubOrder->maintain_station_name }}',
            };

            if ($(this).is(':checked')) {
                // 绑定
                $.ajax({
                    url: `{{ url('tempTaskSubOrder/autoBindEntireInstance') }}`,
                    type: 'post',
                    data: data,
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('tempTaskSubOrder/autoBindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTaskSubOrder/autoBindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
            } else {
                // 解绑
                $.ajax({
                    url: `{{ url('tempTaskSubOrder/bindEntireInstance') }}`,
                    type: 'delete',
                    data: data,
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('tempTaskSubOrder/bindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTaskSubOrder/bindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
            }
        });
    </script>
@endsection

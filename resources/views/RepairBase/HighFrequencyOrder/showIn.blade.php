@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            高频修管理
            <small>扫码</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('repairBase/highFrequencyOrder') }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>高频修管理</a></li>--}}
{{--            <li class="active">扫码</li>--}}
{{--        </ol>--}}
    </section>
    <div class="row">
        <div class="col-md-6">
            <section class="content">
                @include('Layout.alert')
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">高频修入所计划</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('repairBase/highFrequencyOrder') }}?page={{ request('page',1) }}" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a href="{{ url('repairBase/highFrequencyOrder/mission') }}?page={{ request('page',1) }}&date={{ $high_frequency_order->created_at->format('Y-m') }}" class="btn btn-flat btn-default"><i class="fa fa-wrench">&nbsp;</i>分配检修任务</a>
                            <a href="javascript:" class="btn btn-flat btn-default" onclick="fnOut()"><i class="fa fa-sign-out">&nbsp;</i>查看/创建出所任务</a>
                            <a href="{{ url('repairBase/highFrequencyOrder/printLabel', $high_frequency_order->serial_number) }}?direction=IN" class="btn btn-flat btn-default" target="_blank"><i class="fa fa-print">&nbsp;</i>打印唯一编号</a>
                            @if(array_flip(\App\Model\RepairBaseHighFrequencyOrder::$STATUSES)[$high_frequency_order->status] !== 'DONE')
                                <a href="javascript:" onclick="$('#modalWarehouse').modal('show')" class="btn btn-flat btn-primary"><i class="fa fa-sign-in">&nbsp;</i>入所</a>
                            @endif
                            @if(array_flip(\App\Model\RepairBaseHighFrequencyOrder::$STATUSES)[$high_frequency_order->status] === 'SATISFY')
                                <a href="javascript:" onclick="fnDone('{{ $high_frequency_order->serial_number }}')" class="btn btn-success btn-flat"><i class="fa fa-check">&nbsp;</i>确认完成</a>
                            @endif
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>车站</dt>
                            <dd>
                                {{ $high_frequency_order->SceneWorkshop ? $high_frequency_order->SceneWorkshop->name : '' }}
                                {{ $high_frequency_order->Station ? $high_frequency_order->Station->name : '' }}
                            </dd>
                            <dt>更换时间</dt>
                            <dd>{{ $high_frequency_order->created_at->format('Y-m') }}</dd>
                            <dt>任务总数</dt>
                            <dd><span id="spanPlanSum">{{ $plan_sum }}</span></dd>
                            <dt>入所总数</dt>
                            <dd><span id="spanWarehouseSum">{{ $warehouse_sum }}</span></dd>
                            <dt>状态</dt>
                            <dd><span id="spanStatus">{{ $high_frequency_order->status }}</span></dd>
                        </dl>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>型号</th>
                                    <th>任务</th>
                                    <th>已扫码</th>
                                    <th>已入所</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyInScan">
                                @foreach($plan_count as $model_name => $aggregate)
                                    <tr>
                                        <td>{{ $model_name }}</td>
                                        <td>{{ $aggregate }}</td>
                                        <td>{{ @$scan_count[$model_name] ?: 0 }}</td>
                                        <td>{{ @$warehouse_count[$model_name] ?: 0 }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">添加设备</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="input-group">
                            <div class="input-group-addon">唯一编号</div>
                            <label for="txtNo" style="display: none;"></label>
                            <input type="text" name="no" id="txtNo" class="form-control" onkeydown="if(event.keyCode===13){fnAddScan(this.value)}" {{ array_flip(\App\Model\RepairBaseHighFrequencyOrder::$STATUSES)[$high_frequency_order->status] === 'DONE' ? 'disabled' : ''}}>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>唯一/所编号</th>
                                    <th>型号</th>
                                    <th>组合位置/道岔号</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody id="tbody">
                                @foreach(@$high_frequency_order->InEntireInstances as $entire_instance)
                                    <tr>
                                        <td>{{ @$entire_instance->OldEntireInstance->identity_code }}/{{ @$entire_instance->OldEntireInstance->serial_number }}</td>
                                        <td>{{ @$entire_instance->OldEntireInstance->model_name }}</td>
                                        <td>
                                            {{ @$entire_instance->OldEntireInstance->maintain_location_code }}
                                            {{ @$entire_instance->OldEntireInstance->crossroad_number }}
                                        </td>
                                        <td>
                                            <a href="javascript:" onclick="fnDeleteScan('{{ @$entire_instance->OldEntireInstance->identity_code }}')" class="btn btn-danger btn-flat btn-sm"><i class="fa fa-times">&nbsp;</i>清除</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!--入所模态框-->
    <section class="content">
        <div class="modal fade" id="modalWarehouse">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">设备入所</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmWarehouse">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_name" id="txtConnectionName" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系电话：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_phone" id="txtConnectionPhone" class="form-control">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <a href="javascript:" onclick="fnWarehouse()" class="btn btn-success btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>确认完成</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $txtNo = $('#txtNo');

        /**
         * 添加扫码标记
         */
        function fnAddScan(identityCode) {
            $.ajax({
                url: `{{ url('repairBase/highFrequencyOrder/scanEntireInstances') }}`,
                type: 'post',
                data: {
                    identityCode,
                    highFrequencyOrderSn: "{{ $high_frequency_order->serial_number }}",
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/scanEntireInstances') }} success:`, res);
                    let html = '';
                    $.each(res['data'], function (index, item) {
                        html += '<tr>';
                        html += `<td>${item['old_entire_instance']['identity_code']}/${item['old_entire_instance']['serial_number']}</td>`;
                        html += `<td>${item['old_entire_instance']['model_name']}</td>`;
                        html += `<td>${item['old_entire_instance']['maintain_location_code']}${item['old_entire_instance']['crossroad_number']}</td>`;
                        html += `<td><a href="javascript:" class="btn btn-danger btn-flat btn-sm" onclick="fnDeleteScan('${item['old_entire_instance']['identity_code']}')"><i class="fa fa-times">&nbsp;</i>清除</a></td>`;
                        html += `</tr>`;
                    });
                    $('#tbody').html(html);
                    html = '';
                    $.each(res['plan_count'], function (modelName, aggregate) {
                        html += '<tr>';
                        html += `<td>${modelName}</td>`;
                        html += `<td>${aggregate}</td>`;
                        html += `<td>${res['scan_count'].hasOwnProperty(modelName) ? res['scan_count'][modelName] : 0}</td>`;
                        html += `<td>${res['warehouse_count'].hasOwnProperty(modelName) ? res['warehouse_count'][modelName] : 0}</td>`;
                        html += '</tr>';
                    });
                    $('#tbodyInScan').html(html);
                    $('#txtNo').val('');
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/scanEntireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 删除扫码标记
         * @param identityCode
         */
        function fnDeleteScan(identityCode) {
            $.ajax({
                url: `{{ url('repairBase/highFrequencyOrder/scanEntireInstances') }}`,
                type: 'delete',
                data: {
                    identityCode,
                    highFrequencyOrderSn: "{{ $high_frequency_order->serial_number }}",
                    direction: 'IN',
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/scanEntireInstances') }} success:`, res);
                    let html = '';
                    $.each(res['data'], function (index, item) {
                        html += '<tr>';
                        html += `<td>${item['old_entire_instance']['identity_code']}/${item['old_entire_instance']['serial_number']}</td>`;
                        html += `<td>${item['old_entire_instance']['model_name']}</td>`;
                        html += `<td>${item['old_entire_instance']['maintain_location_code']}${item['old_entire_instance']['crossroad_number']}</td>`;
                        html += `<td><a href="javascript:" class="btn btn-danger btn-flat btn-sm" onclick="fnDeleteScan('${item['old_entire_instance']['identity_code']}')"><i class="fa fa-times">&nbsp;</i>清除</a></td>`;
                        html += `</tr>`;
                    });
                    $('#tbody').html(html);
                    html = '';
                    $.each(res['plan_count'], function (modelName, aggregate) {
                        html += '<tr>';
                        html += `<td>${modelName}</td>`;
                        html += `<td>${aggregate}</td>`;
                        html += `<td>${res['scan_count'].hasOwnProperty(modelName) ? res['scan_count'][modelName] : 0}</td>`;
                        html += `<td>${res['warehouse_count'].hasOwnProperty(modelName) ? res['warehouse_count'][modelName] : 0}</td>`;
                        html += '</tr>';
                    });
                    $('#tbodyInScan').html(html);
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/scanEntireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 确认入所
         */
        function fnDone() {
            $.ajax({
                url: `{{ url('repairBase/highFrequencyOrder/done',$high_frequency_order->serial_number) }}?direction=IN`,
                type: 'put',
                data: $('#frmDone').serialize(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/highFrequencyOrder',$high_frequency_order->serial_number) }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/highFrequencyOrder',$high_frequency_order->serial_number) }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 查看/创建出所任务
         */
        function fnOut() {
            $.ajax({
                url: `{{ url('repairBase/highFrequencyOrder') }}`,
                type: 'post',
                data: {sn: '{{ $high_frequency_order->serial_number }}', direction: 'OUT'},
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/highFrequencyOrder') }} success:`, res);
                    location.href = res['return_url'];
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/highFrequencyOrder') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 入所
         */
        function fnWarehouse() {
            $.ajax({
                url: `{{ url('repairBase/highFrequencyOrder/warehouse',$high_frequency_order->serial_number) }}?direction=IN`,
                type: 'post',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/warehouse',$high_frequency_order->serial_number) }}?direction=IN success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/warehouse',$high_frequency_order->serial_number) }}?direction=IN fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <!-- 面包屑 -->
    <section onclick="document.getElementById('txtIdentityCode').focus();" class="content-header">
        <h1>
            高频修
            <small>子任务详情</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('temporaryTask/production/main',$main_task['id']) }}">--}}
{{--                    <i class="fa fa-users">&nbsp;</i>主任务</a>--}}
{{--            </li>--}}
{{--            <li class="active">子任务详情</li>--}}
{{--        </ol>--}}
    </section>
    <section onclick="document.getElementById('txtIdentityCode').focus();" class="content">
        <!--任务描述-->
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header ">
                    <h3 class="box-title">子任务详情</h3>
                    <!--右侧最小化按钮-->
                    <div class="btn-group btn-group-sm pull-right">
                        @if(array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction])
                            <a href="{{ url('temporaryTask/production/sub/printLabel',$high_frequency_order->serial_number) }}?type=HIGH_FREQUENCY&direction={{ array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction] }}" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-print">&nbsp;</i>打印标签</a>
                        @endif
                        <a href="javascript:" class="btn btn-success btn-flat" onclick="modalWarehouse()"><i class="fa fa-sign-{{ strtolower(array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction]) }}">&nbsp;</i>{{ array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction] === 'IN' ? '入' : '出' }}所</a>
                    </div>
                </div>
                <br>
                <div class="box-body">
                    <dl class="dl-horizontal">
                        <dt>任务编号：</dt>
                        <dd>{{ $main_task['serial_num'] }}</dd>
                        <dt>任务标题：</dt>
                        <dd>{{ $main_task['title'] }}</dd>
                        <dt>工区负责人：</dt>
                        <dd>{{ $main_task['paragraph_name'] }}:{{ $main_task['paragraph_principal_name'] }}</dd>
                        <dt>说明：</dt>
                        <dd>{!! $main_task['content'] !!}</dd>
                    </dl>

                    @if($sub_task['finish_message'])
                        <hr>
                        <dl class="dl-horizontal">
                            <dt>工作总计：</dt>
                            <dd>{!! $sub_task['finish_message'] !!}</dd>
                        </dl>
                    @endif
                    @if($sub_task['reject_message'])
                        <hr>
                        <dl class="dl-horizontal">
                            <dt>驳回说明：</dt>
                            <dd>{!! $sub_task['reject_message'] !!}</dd>
                        </dl>
                    @endif
                </div>
                <div class="box-footer">
{{--                    <a href="{{ url('temporaryTask/production/main',$main_task['id']) }}" class="btn btn-sm btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-sm btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    @if(array_flip(\App\Model\RepairBaseHighFrequencyOrder::$STATUSES)[$high_frequency_order->status] !== 'DONE')
                        <a href="javascript:" id="btnFinish" onclick="modalFinish()" class="btn btn-success pull-right btn-flat btn-sm">
                            <i class="fa fa-check">&nbsp;</i>任务总结
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!--任务内容：入所设备列表-->
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header">
                    <div class="row">
                        <div class="col-md-4">
                            <h3 class="box-title">设备列表</h3>
                        </div>
                        <div class="col-md-8">
                            <div class="pull-right input-group">
                                <div class="input-group-addon">唯一编号</div>
                                <input type="text" name="identity_code" id="txtIdentityCode" class="form-control" autofocus onkeydown="if(event.keyCode===13) fnAdd(this.value)">
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead>
                            <tr>
                                <th>唯一编号</th>
                                <th>型号</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if(array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction] === 'IN')
                                @foreach($high_frequency_order->InEntireInstances as $item)
                                    <tr class="{{ $item->in_warehouse_sn ? 'bg-green' : '' }}">
                                        <td>{{ $item->OldEntireInstance->identity_code }}</td>
                                        <td>{{ $item->OldEntireInstance->model_name }}</td>
                                        <td>{{ $item->in_warehouse_sn ? '已入所' : $item->in_scan ? '已扫码' : '' }}</td>
                                        <td><a href="javascript:" onclick="fnCut({{ $item->id }})" class="btn btn-danger btn-flat btn-xs"><i class="fa fa-times"></i></a></td>
                                    </tr>
                                @endforeach
                            @else
                                @foreach($high_frequency_order->OutEntireInstances as $item)
                                    <tr class="{{ $item->out_warehouse_sn ? 'bg-green' : '' }}">
                                        <td>{{ $item->OldEntireInstance->identity_code }}</td>
                                        <td>{{ $item->OldEntireInstance->model_name }}</td>
                                        <td>{{ $item->out_warehouse_sn ? '已出所' : $item->out_scan ? '已扫码' : '' }}</td>
                                        <td><a href="javascript:" onclick="fnCut({{ $item->id }})" class="btn btn-danger btn-flat btn-xs"><i class="fa fa-times"></i></a></td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <!--任务总结-->
        <div class="modal fade" id="modalFinish">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">任务总结</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmFinish">
                            <div class="form-group">
                                <div class="col-sm-12 col-md-12">
                                    <textarea name="finish_message" id="txaFinishMessage" rows="10" class="form-control"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnFinish()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>

        <!--设备出入所-->
        <div class="modal fade" id="modalWarehouse">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">设备{{ array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction] === 'IN' ? '入' : '出' }}所</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmWarehouse">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-2 control-label" for="txtConnectionName">联系人</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_name" id="txtConnectionName" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-2 control-label" for="txtConnectionPhone">联系电话</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_phone" id="txtConnectionPhone" class="form-control">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnWarehouse()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>
    </section>


@endsection
@section('script')
    <script>
        let $select2 = $('.select2');

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            // 初始化 ckeditor
            CKEDITOR.replace('txaFinishMessage', {
                toolbar: [
                    // {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                    // {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']},
                    // {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                    // {name: 'colors', items: ['TextColor', 'BGColor']},
                    // {name: 'tools', items: ['Maximize', 'ShowBlocks']}
                ]
            });


        });

        /**
         * 打开出入所窗口
         */
        function modalWarehouse() {
            $('#modalWarehouse').modal('show');
        }

        /**
         * 设备出入所
         */
        function fnWarehouse() {
            let data = {
                type: 'HIGH_FREQUENCY',
                direction: '{{ array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction] }}',
                serialNumber: '{{ $high_frequency_order->serial_number }}',
                connectionName: $('#txtConnectionName').val(),
                connectionPhone: $('#txtConnectionPhone').val(),
            };
            console.log(data);
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/warehouse') }}`,
                type: 'post',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/warehouse') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/warehouse') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 添加设备
         */
        function fnAdd(identityCode) {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/entireInstance') }}`,
                type: 'post',
                data: {
                    identityCode,
                    serialNumber: '{{ $high_frequency_order->serial_number }}',
                    type: 'HIGH_FREQUENCY',
                    direction: '{{ array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction] }}',
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} success:`, res);
                    // location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 删除设备
         */
        function fnCut(id) {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/entireInstance') }}`,
                type: 'delete',
                data: {
                    id,
                    type: 'HIGH_FREQUENCY',
                    direction: '{{ array_flip(\App\Model\RepairBaseHighFrequencyOrder::$DIRECTIONS)[$high_frequency_order->direction] }}',
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/entireInstance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 打开任务总结窗口
         */
        function modalFinish() {
            $('#modalFinish').modal('show');
        }

        /**
         * 标记工区任务完成
         */
        function fnFinish() {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/finish') }}?type=HIGH_FREQUENCY`,
                type: 'PUT',
                data: {
                    subTaskId: '{{ $sub_task['id'] }}',
                    newStationOrderSn: '{{ $high_frequency_order->serial_number }}',
                    finishMessage: CKEDITOR.instances['txaFinishMessage'].getData(),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/finish') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/finish') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

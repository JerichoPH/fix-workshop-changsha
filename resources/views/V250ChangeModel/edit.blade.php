@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            {{ \App\Model\V250TaskOrder::$TYPES[request('type')] }}任务
            <small>任务详情</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('v250TaskOrder') }}?page={{ request('page') }}&type={{ request('type')}}">任务列表</a></li>--}}
{{--            <li class="active">{{ \App\Model\V250TaskOrder::$TYPES[request('type')] }}任务详情</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">{{ \App\Model\V250TaskOrder::$TYPES[request('type')] }}任务详情</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm"></div>
            </div>
            <div class="box-body">
                <form id="frmCreate">
                    <div class="row">
                        <div class="col-md-12">
                            车站：{{ $taskOrder->SceneWorkshop ? $taskOrder->SceneWorkshop->name : '' }}
                            {{ $taskOrder->MaintainStation ? $taskOrder->MaintainStation->name : '' }}
                            &emsp;截止日期：{{ $taskOrder->expiring_at ? date('Y-m-d',strtotime($taskOrder->expiring_at)) : '' }}
                            &emsp;工区：{{ $taskOrder->WorkAreaByUniqueCode ? $taskOrder->WorkAreaByUniqueCode->name : '' }}
                            &emsp;任务总数：{{ $taskEntireInstances->count() }}
                            &emsp;出所总数：{{ $taskEntireInstances->where('is_out',true)->count() }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="pull-right">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ url('v250ChangeModel') }}?page={{ request('page',1) }}&type={{ request('type')}}" class="btn btn-default btn-flat">返回任务列表</a>
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="$('#modalChangeModel').modal('show')" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">换型</a>
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="$('#modalOverhaul').modal('show')" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">检修分配</a>
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="fnJudgeWorkshopOut('{{ $sn }}')" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">添加出所单</a>
                                    <a href="javascript:" class="btn btn-default btn-flat" onclick="fnPrint('printQrCode')">打印二维码</a>
                                    <a href="javascript:" class="btn btn-default btn-flat" onclick="fnPrint('printLabel')">打印位置标签</a>
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="fnDelete()" class="btn btn-danger btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">删除设备</a>
                                    @if($taskOrder->status['code'] == 'PROCESSING')
                                        <a href="javascript:" class="btn btn-success btn-flat" onclick="modalDelivery()">交付</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="table">
                        <thead>
                        <tr>
                            <th><input type="checkbox" class="checkbox-toggle" id="chkAllCheck"></th>
                            <th>设备编号</th>
                            <th>所编号</th>
                            <th>型号</th>
                            <th>厂家</th>
                            <th>厂编号</th>
                            <th>生产日期</th>
                            <th>出所日期</th>
                            <th>上道位置</th>
                            <th>检测/检修人</th>
                            <th>检测/检修时间</th>
                            <th>验收人</th>
                            <th>验收时间</th>
                            <th>抽验人</th>
                            <th>抽验时间</th>
                            <th>状态</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($taskEntireInstances as $taskEntireInstance)
                            <tr>
                                <td><input type="checkbox" class="chk-entire-instances" name="labelChecked" value="{{ $taskEntireInstance->entire_instance_identity_code }}"/></td>
                                <td><a href="{{ url('search',$taskEntireInstance->entire_instance_identity_code) }}">{{ $taskEntireInstance->entire_instance_identity_code }}</a></td>
                                <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->serial_number : ''}}</td>
                                <td>
                                    {{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->SubModel ? $taskEntireInstance->EntireInstance->SubModel->name : '') : '' }}
                                    {{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->PartModel ? $taskEntireInstance->EntireInstance->PartModel->name : '') : '' }}
                                </td>
                                <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->factory_name : '' }}</td>
                                <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->factory_device_code : '' }}</td>
                                <td>{{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($taskEntireInstance->EntireInstance->made_at)) : '') : '' }}</td>
                                <td>
                                    @if($taskEntireInstance->is_out)
                                        <a href="{{ url('warehouse',$taskEntireInstance->out_warehouse_sn) }}">{{ $taskEntireInstance->out_at ? $taskEntireInstance->out_at : ''}}</a>
                                    @endif
                                </td>
                                <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->maintain_location_code : '' }}</td>
                                <td>{{ $taskEntireInstance->Fixer ? $taskEntireInstance->Fixer->nickname : '' }}</td>
                                <td>{{ $taskEntireInstance->fixed_at ? date('Y-m-d',strtotime($taskEntireInstance->fixed_at)) : '' }}</td>
                                <td>{{ $taskEntireInstance->Checker ? $taskEntireInstance->Checker->nickname : '' }}</td>
                                <td>{{ $taskEntireInstance->checked_at ? date('Y-m-d',strtotime($taskEntireInstance->checked_at)) : '' }}</td>
                                <td>{{ $taskEntireInstance->SpotChecker ? $taskEntireInstance->SpotChecker->nickname : '' }}</td>
                                <td>{{ $taskEntireInstance->spot_checked_at ? date('Y-m-d',strtotime($taskEntireInstance->spot_checked_at)) : '' }}</td>
                                @if($taskEntireInstance->is_scene_back['code'] == 1)
                                    <td>现场退回</td>
                                @else
                                    <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->status : '' }}</td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!--检修分配列表-->
        <div class="modal fade" id="modalOverhaul">
            <div class="modal-dialog modal-dialog-centered" style="width:80vw;height:90vh">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">检修分配</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <iframe src="{{ url('v250Overhaul') }}?type=tab_1&sn={{ $taskOrder->serial_number }}&is_iframe=1" style="width:calc(80vw - 30px);height: calc(90vh - 95px);border:none;margin:auto;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    </div>
                </div>
            </div>
        </div>

        <!--换型-->
        <div class="modal fade" id="modalChangeModel">
            <div class="modal-dialog modal-dialog-centered" style="width:80vw;height:90vh">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">换型</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <iframe src="{{ url('v250ChangeModel') }}/changeModelList?sn={{ $taskOrder->serial_number }}&stationName={{ $taskOrder->MaintainStation->name }}&type=1&is_iframe=1" style="width:calc(80vw - 30px);height: calc(90vh - 95px);border:none;margin:auto;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    </div>
                </div>
            </div>
        </div>

        <!--添加出所单-->
        <div class="modal fade" id="modalWorkshopOut">
            <div class="modal-dialog modal-dialog-centered" style="width:80vw;height:90vh">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">待出所单</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        {{--<iframe  src="{{ url('v250WorkshopOut/create') }}?sn={{ $taskOrder->serial_number }}&is_iframe=1" style="width:calc(80vw - 30px);height: calc(90vh - 95px);border:none;margin:auto;"></iframe>--}}
                        <iframe id="sn" src="" style="width:calc(80vw - 30px);height: calc(90vh - 95px);border:none;margin:auto;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    </div>
                </div>
            </div>
        </div>

        <!--任务交付-->
        <div class="modal fade" id="modalDelivery">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">任务交付</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmDelivery">
                            <div class="form-group">
                                <div class="col-sm-12 col-md-12">
                                    <textarea class="form-control" id="txaDelivery" name="delivery_message" placeholder="交付总结" rows="15"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnDelivery()"><i class="fa fa-check">&nbsp;</i>确定交付</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $txaDelivery = $('#txaDelivery');

        /**
         * 全选多选框绑定
         * @param {string} allCheckId
         * @param {string} checkClassName
         */
        function fnAllCheckBind(allCheckId, checkClassName) {
            $(allCheckId).on('click', function () {
                $(checkClassName).prop('checked', $(allCheckId).prop('checked'));
            });
            $('.chk-entire-instances').on('click', function () {
                $(allCheckId).prop('checked', $(`${checkClassName}:checked`).length === $(checkClassName).length);
            });
        }

        fnAllCheckBind('#chkAllCheck', '.chk-entire-instances');

        $(function () {
            // 关闭iframe时刷新
            $('#modalChangeModel').on('hidden.bs.modal', function (e) {
                location.reload();
            });
            $('#modalOverhaul').on('hidden.bs.modal', function (e) {
                location.reload();
            });
            $('#modalWorkshopOut').on('hidden.bs.modal', function (e) {
                location.reload();
            });

            if ($select2.length > 0) $('.select2').select2();

        });

        /**
         * 添加待出所单->设备状态判断
         */
        function fnJudgeWorkshopOut(sn) {
            //处理数据
            let selected_for_workshop_out = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_workshop_out.push(new_code);
            });
            if (selected_for_workshop_out.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            $.ajax({
                url: `{{ url('v250TaskEntireInstance') }}/${sn}/judgeWorkshopOut`,
                type: 'post',
                data: {'selected_for_workshop_out': selected_for_workshop_out},
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250TaskEntireInstance') }}/${sn}/judgeWorkshopOut success:`, res);
                    if (res.code === 0) {
                        alert(res.msg);
                    } else {
                        document.getElementById("sn").src = `{{ url('v250WorkshopOut/create') }}?sn=${res.sn}&is_iframe=1`;
                        $('#modalWorkshopOut').modal('show');
                    }
                },
                error: function (err) {
                    console.log(`{{ url('v250TaskEntireInstance') }}/${sn}/judgeWorkshopOut fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 删除
         * @param {int} id
         */
        function fnDelete(id) {
            //处理数据
            let selected_for_workshop_out = [];
            $(".chk-entire-instances:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_workshop_out.push(new_code);
            });
            if (selected_for_workshop_out.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            if (confirm('删除设备不可恢复，是否确认？'))
                $.ajax({
                    url: `{{ url('v250TaskEntireInstance',$taskOrder->serial_number) }}/items`,
                    type: 'delete',
                    data: {'identityCodes': selected_for_workshop_out,},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('v250TaskEntireInstance',$taskOrder->serial_number) }}/items success:`, res);
                        alert(res.msg);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('v250TaskEntireInstance',$taskOrder->serial_number) }}/items fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
        }

        /**
         * 打印标签
         * @param type
         */
        function fnPrint(type) {
            // 处理数据
            let identityCodes = [];
            $(".chk-entire-instances:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') identityCodes.push(new_code);
            });
            if (identityCodes.length <= 0) {
                alert('请选择打印标签设备');
                return false;
            }

            $.ajax({
                url: `{{ url('warehouse/report/identityCodeWithPrint') }}`,
                type: 'post',
                data: {identityCodes,},
                async: true,
                success: function (response) {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        window.open(`/qrcode/${type}`);
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (error) {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }

        /**
         * 打开任务交付窗口
         */
        function modalDelivery() {
            $('#modalDelivery').modal('show');
        }

        /**
         * 任务交付
         */
        function fnDelivery() {
            $.ajax({
                url: `{{ url('v250TaskOrder', $taskOrder->serial_number) }}/delivery`,
                type: 'post',
                data: {
                    type: '{{ strtoupper(request('type')) }}',
                    delivery_message: $txaDelivery.val().replaceAll('\r\n', '<br>').replaceAll('\r', '<br>').replaceAll('\n', '<br>'),
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250TaskOrder', $taskOrder->serial_number) }}/delivery success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('v250TaskOrder', $taskOrder->serial_number) }}/delivery fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection

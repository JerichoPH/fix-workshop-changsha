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
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/uploadCreateDevice?page={{ request('page',1) }}&type={{ request('type') }}" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">新品赋码</a>
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="$('#modalUseOld').modal('show')" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">利旧</a>
                                    {{--<a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="fnJudgeService('{{ $sn }}')" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">检修分配</a>--}}
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="$('#modalOverhaul').modal('show')" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">检修分配</a>
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/uploadEditDevice?page={{ request('page',1) }}&type={{ request('type') }}" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">数据补充</a>
                                    {{--<a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/uploadCheckDevice?page={{ request('page',1) }}&type={{ request('type') }}" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">设备验收</a>--}}
                                    {{--<a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/uploadInstallLocation?page={{ request('page',1) }}&type={{ request('type') }}" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">上传位置</a>--}}
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="fnJudgeWorkshopOut('{{ $sn }}')" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">添加出所单</a>
                                    <a {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }} href="javascript:" onclick="$('#modalWorkshopIn').modal('show')" class="btn btn-default btn-flat {{ $taskOrder->status['code'] == 'DONE' ? 'disabled' : '' }}">现场退回</a>
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
    </section>

    <section class="content">
        <!--现场退回-->
        <div class="modal fade" id="modalWorkshopIn">
            <div class="modal-dialog modal-dialog-centered" style="width:80vw;height:90vh">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">现场退回</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <iframe src="{{ url('v250WorkshopIn/create') }}?sn={{ $taskOrder->serial_number }}&is_iframe=1" style="width:calc(80vw - 30px);height: calc(90vh - 95px);border:none;margin:auto;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <!--检修分配1-->
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
    </section>

    <section class="content">
        <!--利旧-->
        <div class="modal fade" id="modalUseOld">
            <div class="modal-dialog modal-dialog-centered" style="width:80vw;height:90vh">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">利旧</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <iframe src="{{ url('v250UseOld') }}/create?sn={{ $taskOrder->serial_number }}&is_iframe=1" style="width:calc(80vw - 30px);height: calc(90vh - 95px);border:none;margin:auto;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <!--检修分配-->
        <div class="modal fade" id="service">
            <div class="modal-dialog">
                <form action="" id="maintenanceTask">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">检修分配</h4>
                        </div>
                        <div class="modal-body form-horizontal">
                            <div class="form-group">
                                <label for="selWorkArea" class="col-sm-3 col-md-3 control-label">工区：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select id="selWorkArea" name="work_area" class="select2 form-control" style="width: 100%;" disabled>
                                        <option value="{{ $taskOrder->WorkAreaByUniqueCode->name }}">{{ $taskOrder->WorkAreaByUniqueCode->name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">截至日期：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input id="deadLine" name="fixed_at" type="text" class="form-control" autocomplete="off" value="{{ $taskOrder->expiring_at ? date('Y-m-d',strtotime($taskOrder->expiring_at)) : '' }}" disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">检修人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select id="selAccount" name="account_id" class="select2 form-control" style="width: 100%;">
                                        <option disabled selected>请选择</option>
                                        @foreach(\Illuminate\Support\Facades\DB::table('accounts')->where('work_area_unique_code', $taskOrder->WorkAreaByUniqueCode->unique_code)->get()->toArray() as $v)
                                            <option value="{{ $v->id }}">{{ $v->nickname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat pull-left btn-sm"
                                    data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭
                            </button>
                            <a href="javascript:" onclick="fnStoreService('{{ $sn }}')" class="btn btn-success btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>确定</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="content">
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
            $('#modalUseOld').on('hidden.bs.modal', function (e) {
                location.reload();
            });
            $('#modalOverhaul').on('hidden.bs.modal', function (e) {
                location.reload();
            });
            $('#modalWorkshopIn').on('hidden.bs.modal', function (e) {
                location.reload();
            });
            $('#modalWorkshopOut').on('hidden.bs.modal', function (e) {
                location.reload();
            });
            $('#modalCreateStation').on('hidden.bs.modal', function (e) {
                location.reload();
            });

            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,  // 分页器
                    lengthChange: true,
                    searching: false,  // 搜索框
                    ordering: false,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
                    order: [[0, 'desc']],  // 排序依据
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
                });
            }

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

            // 日期选择器
            let datepickerOption = {
                autoclose: true,
                todayHighlight: true,
                language: "cn",
                format: "yyyy-mm-dd",
                beforeShowDay: $.noop,
                calendarWeeks: false,
                clearBtn: true,
                daysOfWeekDisabled: [],
                endDate: Infinity,
                forceParse: true,
                keyboardNavigation: true,
                minViewMode: 0,
                orientation: "auto",
                rtl: false,
                startDate: -Infinity,
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#dpExpiringAt').datepicker(datepickerOption);
        });

        /**
         * 新建任务
         */
        function fnStore() {
            let data = $('#frmCreate').serialize();
            $.ajax({
                url: `{{ url('v250TaskOrder') }}?type={{ request('type') }}`,
                type: 'POST',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250TaskOrder') }}?type={{ request('type') }} success:`, res);
                    location.href = `/v250TaskOrder/${res.data.task_order.serial_number}/edit?type={{ request('type') }}`;
                },
                error: function (err) {
                    console.log(`{{ url('v250TaskOrder') }}?type={{ request('type') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 检修分配判断
         */
        function fnJudgeService(sn) {
            //处理数据
            let selected_for_fix_misson = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_fix_misson.push(new_code);
            });
            if (selected_for_fix_misson.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            $.ajax({
                url: `{{ url('v250TaskEntireInstance') }}/${sn}/judgeService`,
                type: 'post',
                data: {'selected_for_fix_misson': selected_for_fix_misson},
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250TaskEntireInstance') }}/${sn}/judgeService success:`, res);
                    if (res.code === 0) {
                        alert(res.msg);
                    } else {
                        $('#service').modal('show');
                    }
                },
                error: function (err) {
                    console.log(`{{ url('v250TaskEntireInstance') }}/${sn}/judgeService fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 检修分配
         */
        function fnStoreService(sn) {
            var selAccountId = document.getElementById('selAccount').value;
            if (selAccountId === '请选择') {
                alert('请选择检修人');
                return false;
            }
            //处理数据
            let selected_for_fix_misson = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_fix_misson.push(new_code);
            });
            if (selected_for_fix_misson.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            $.ajax({
                url: `{{ url('v250TaskEntireInstance') }}/${sn}/storeService`,
                type: 'post',
                data: {'selected_for_fix_misson': selected_for_fix_misson, 'selAccountId': selAccountId},
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250TaskEntireInstance') }}/${sn}/storeService success:`, res);
                    alert('分配成功');
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('v250TaskEntireInstance') }}/${sn}/storeService fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

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

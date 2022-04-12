@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            周期修管理
            <small>周期修绑定设备</small>
        </h1>
        {{--        <ol class="breadcrumb">--}}
        {{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--            <li class="active">绑定设备</li>--}}
        {{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box box-solid">
                <div class="box-header">
                    <div class="row">
                        <div class="col-md-4">
                            <h3 class="box-title">
                                周期修出所设备列表
                                {{ $repairBasePlanOutCycleFixEntireInstances->total() }}
                            </h3>
                        </div>
                    </div>
                    <!--右侧最小化按钮-->
                    <div class="box-tools pull-right">
                        <a href="JavaScript:" onclick="aotuReplace()" class="btn btn-default btn-flat">自动替换</a>
                        <a href="JavaScript:" onclick="aotuUnReplace()" class="btn btn-danger btn-flat">取消替换</a>
                        <a href="JavaScript:" onclick="fnOut()" class="btn btn-default btn-flat">出所</a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4 col-md-offset-8">
                            <div class="input-group pull-right">
                                <div class="input-group-addon">设备搜索</div>
                                <input type="text" id="txtSearchEntireInstanceLock" class="form-control" onkeydown="fnSearchEntireInstanceLock(event)">
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-default btn-flat" onclick="fnSearchEntireInstanceLock(event)"><i class="fa fa-search"></i></a>
                                </div>
                            </div>
                            <div class="help-block">如果在下拉列表中找不到需要的成品，可以在这里进行搜索，查看设备是否被其他任务锁定</div>
                        </div>
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-hover table-condensed" id="table">
                                    <thead>
                                    <tr>
                                        <th><input type="checkbox" class="checkbox-toggle"></th>
                                        <th>周期修时间</th>
                                        <th>
                                            唯一编号<br>
                                            所编号（周期修）
                                        </th>
                                        <th>型号</th>
                                        <th>位置(故障次数)</th>
                                        <th>唯一编号（成品）</th>
                                        <th>成品设备库房位置</th>
                                        <th>出所单</th>
                                        <th>替换</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($repairBasePlanOutCycleFixEntireInstances as $repairBasePlanOutCycleFixEntireInstance)
                                        <tr>
                                            <td><input type="checkbox" name="labelChecked" value="{{$repairBasePlanOutCycleFixEntireInstance->new}}"></td>
                                            <td>{{ $repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld ? explode(' ',$repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->next_fixing_day)[0]:'' }}</td>
                                            <td>
                                                {{ $repairBasePlanOutCycleFixEntireInstance->old }}<br>
                                                {{ @$repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->serial_number }}
                                            </td>
                                            <td>{{ @$repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->model_name }}</td>
                                            <td>
                                                {{ $repairBasePlanOutCycleFixEntireInstance->station_name }}
                                                {{ $repairBasePlanOutCycleFixEntireInstance->location }}
                                                ({{ $breakdownCountWithOlds[$repairBasePlanOutCycleFixEntireInstance->station_name.$repairBasePlanOutCycleFixEntireInstance->location] ?? 0 }})
                                            </td>
                                            <td>{{ $repairBasePlanOutCycleFixEntireInstance->new }}</td>
                                            <td>{{ $repairBasePlanOutCycleFixEntireInstance->WithEntireInstance ? $repairBasePlanOutCycleFixEntireInstance->WithEntireInstance->WithTier ? $repairBasePlanOutCycleFixEntireInstance->WithEntireInstance->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $repairBasePlanOutCycleFixEntireInstance->WithEntireInstance->WithTier->WithShelf->WithPlatoon->WithArea->name . $repairBasePlanOutCycleFixEntireInstance->WithEntireInstance->WithTier->WithShelf->WithPlatoon->name . $repairBasePlanOutCycleFixEntireInstance->WithEntireInstance->WithTier->WithShelf->name . $repairBasePlanOutCycleFixEntireInstance->WithEntireInstance->WithTier->name : '' :'' }}</td>
                                            @if(empty($repairBasePlanOutCycleFixEntireInstance->out_warehouse_sn))
                                                <td class="text-danger">未出所</td>
                                            @else
                                                <td class="text-success"><a href="{{ url('/warehouse/report') }}/{{ $repairBasePlanOutCycleFixEntireInstance->out_warehouse_sn }}" target="_blank">已出所</a></td>
                                            @endif

                                            <td style="width: 20%;">
                                                @if($repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld)
                                                    @if(array_key_exists($repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->model_unique_code,$newEntireInstances))
                                                        <select class="form-control select2 select-for-print" id="{{ $repairBasePlanOutCycleFixEntireInstance->old }}" style="width: 100%;" onchange="replaceEntireInstance(this.value,`{{ $repairBasePlanOutCycleFixEntireInstance->old }}`)" {{ empty($repairBasePlanOutCycleFixEntireInstance->out_warehouse_sn) ? '' : 'disabled' }}>
                                                            <option value="">未选择</option>
                                                            @foreach($newEntireInstances[$repairBasePlanOutCycleFixEntireInstance->WithEntireInstanceOld->model_unique_code] as $new_code)
                                                                <option value="{{ $new_code }}">{{ $new_code }}</option>
                                                            @endforeach
                                                            @if(!empty($repairBasePlanOutCycleFixEntireInstance->new))
                                                                <option value="{{ $repairBasePlanOutCycleFixEntireInstance->new }}" selected>{{ $repairBasePlanOutCycleFixEntireInstance->new }}</option>
                                                            @endif
                                                        </select>
                                                    @else
                                                        暂无可替换设备
                                                    @endif
                                                @else
                                                    设备数据丢失
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @if($repairBasePlanOutCycleFixEntireInstances->hasPages())
                    <div class="box-footer">
                        {{ $repairBasePlanOutCycleFixEntireInstances->links() }}
                    </div>
                @endif
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/layer/layer.js"></script>
    <script>
        let $select2 = $('.select2');

        $(function () {
            // iCheck for checkbox and radio inputs
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $("#table input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $("#table input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });

            if ($select2.length > 0) $select2.select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,
                    lengthChange: true,
                    searching: false,
                    ordering: true,
                    order: [[4, 'desc']],
                    info: true,
                    autoWidth: true,
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

        function fnOut() {
            location.href = `{{url('repairBase/planOut/scanCycleFixOut',$current_bill_id)}}`;
        }

        /**
         *下载标签
         */
        function downloadLabel() {
            //处理数据
            let selected_for_print = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_print.push(new_code);
            });
            if (selected_for_print.length > 0) {
                window.open(`{{url('qrcode/printLabel')}}?identityCodes=${JSON.stringify(selected_for_print)}`);
            } else {
                alert('请选择下载标签');
            }
        }

        /**
         * 替换设备
         * @param newCode
         * @param oldCode
         */
        function replaceEntireInstance(newCode, oldCode) {
            if (newCode === '') {
                let selected = [];
                selected.push(oldCode);
                unReplaces(selected);
                return;
            }
            let loading = layer.msg('设备替换中');
            $.ajax({
                url: `{{url('repairBase/planOut/cycleFix',$current_bill_id)}}`,
                type: 'put',
                data: {
                    newCode: newCode,
                    oldCode: oldCode
                },
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        location.reload();
                        layer.close(loading);
                    } else {
                        alert(response.message);
                        location.reload();
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
         * 自动替换
         */
        function aotuReplace() {
            let selected = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let old_code = $(item).closest('tr').find('td').eq(2).text();
                let new_code = $(item).val();
                if (new_code === '' && old_code !== '' && old_code) selected.push(old_code);
            });
            if (selected.length > 0) {
                let loading = layer.msg('设备替换中');
                $.ajax({
                    url: `{{url('repairBase/planOut/replaces',$current_bill_id)}}`,
                    type: 'put',
                    data: {
                        oldCodes: selected
                    },
                    async: true,
                    success: response => {
                        console.log(`success:`, response);
                        if (response.status === 200) {
                            location.reload();
                            layer.close(loading);
                        } else {
                            alert(response.message);
                            location.reload();
                        }
                    },
                    error: error => {
                        console.log(`error:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.message);
                        location.reload();
                    }
                });
            } else {
                alert('请选择未替换设备');
                location.reload();
            }
        }

        /**
         * 取消替换
         */
        function aotuUnReplace() {
            let selected = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let old_code = $(item).closest('tr').find('td').eq(2).text();
                if (old_code !== '' && old_code) selected.push(old_code);
            });
            if (selected.length > 0) {
                unReplaces(selected);
            } else {
                alert('请选择未替换设备');
            }
        }

        /**
         * 取消替换
         * @param selected
         */
        function unReplaces(selected) {
            let loading = layer.msg('取消设备替换中');
            $.ajax({
                url: `{{url('repairBase/planOut/replaces',$current_bill_id)}}`,
                type: 'delete',
                data: {
                    oldCodes: selected
                },
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        location.reload();
                        layer.close(loading);
                    } else {
                        alert(response.message);
                        location.reload();
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
         * 查询设备锁
         */
        function fnSearchEntireInstanceLock(event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                let entire_instance_identity_code = $('#txtSearchEntireInstanceLock').val();

                if (entire_instance_identity_code != '') {
                    let data = {
                        entire_instance_identity_code,
                        ordering: 'id desc',
                    };

                    $.ajax({
                        url: `{{ url('entire/instanceLock') }}`,
                        type: 'get',
                        data: data,
                        async: true,
                        success: function (res) {
                            console.log(`{{ url('entire/instanceLock') }} success:`, res);
                            alert(res.data.entire_instance_lock.remark);
                        },
                        error: function (err) {
                            console.log(`{{ url('entire/instanceLock') }} fail:`, err);
                            if (err.status === 401) location.href = "{{ url('login') }}";
                            alert(err['responseJSON']['msg']);
                        }
                    });
                }
            }
        }
    </script>
@endsection

@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备管理
            <small>列表</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">列表</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">设备列表</h3>
                    <!--右侧最小化按钮-->
                    <div class="pull-right btn-group btn-group-sm">
                        <a href="javascript:" onclick="fnRefresh()" class="btn btn-flat btn-success"><i class="fa fa-refresh">&nbsp;</i>恢复</a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-condensed" id="table">
                            <thead>
                            <tr>
                                <th><input type="checkbox" class="checkbox-toggle"></th>
                                <th>删除时间</th>
                                <th>唯一编号</th>
                                <th>供应商</th>
                                <th>所编号</th>
                                <th>状态</th>
                                <th>种类型</th>
                                <th>最后安装日期</th>
                                <th>位置</th>
                                <th>开向</th>
                                <th>表示杆特征</th>
                                <th>仓库位置</th>
                                <th>上次检修日期</th>
                                <th>下次周期修日期</th>
                                <th>报废日期</th>
                                @if(request('behavior_type'))
                                    <th>操作人</th>
                                @endif
                                <th>所属设备</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($entireInstances as $entireInstance)
                                <tr>
                                    <td><input type="checkbox" name="labelChecked" value="{{ $entireInstance->identity_code }}"/></td>
                                    <td>{{ $entireInstance->deleted_at }}</td>
                                    <td><a href="{{ url('search', $entireInstance->identity_code) }}">{{ $entireInstance->identity_code }}</a></td>
                                    <td>
                                        {{ $entireInstance->factory_name }}
                                        {{ $entireInstance->factory_device_code }}
                                    </td>
                                    <td>{{ $entireInstance->serial_number }}</td>
                                    <td>{{ $entireInstance->status }}</td>
                                    <td>
                                        {{ $entireInstance->category_name }}
                                        {{ $entireInstance->model_name }}
                                    </td>
                                    <td>{{ empty($entireInstance->last_installed_time) ? '' : date('Y-m-d',$entireInstance->last_installed_time) }}</td>
                                    <td>
                                        {{ $entireInstance->maintain_station_name }}
                                        {{ $entireInstance->maintain_location_code }}
                                        {{ $entireInstance->line_unique_code }}
                                        {{ $entireInstance->crossroad_number }}
                                        {{ $entireInstance->traction }}
                                    </td>
                                    <td>{{ $entireInstance->open_direction }}</td>
                                    <td>{{ $entireInstance->said_rod }}</td>
                                    <td>
                                        {{ $entireInstance->warehouse_name }}
                                        {{ $entireInstance->location_unique_code }}
                                    </td>
                                    <td>{{ @$entireInstance->fw_updated_at ? \Carbon\Carbon::parse($entireInstance->fw_updated_at)->toDateString() : '' }}</td>
                                    @if($entireInstance->ei_fix_cycle_value == 0 && $entireInstance->model_fix_cycle_value == 0)
                                        <td>状态修设备</td>
                                    @else
                                        <td style="{{ $entireInstance->next_fixing_time < time() ? 'color: red;' :'' }}">
                                            {{ empty($entireInstance->next_fixing_time) ? '' : date('Y-m-d',$entireInstance->next_fixing_time) }}
                                        </td>
                                    @endif
                                    <td style="{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $entireInstance->scarping_at)->timestamp < time() ? 'color: red;' : ''}}">{{ @$entireInstance->scarping_at ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$entireInstance->scarping_at)->toDateString() : '' }}</td>
                                    @if(request('behavior_type'))
                                        <td>{{ property_exists($entireInstance,'nickname') ? $entireInstance->nickname : '' }}</td>
                                    @endif
                                    <td>
                                        <a href="{{ url('search/bindDevice', $entireInstance->bind_device_code) }}">
                                            {{ $entireInstance->bind_crossroad_number }}
                                            {{ $entireInstance->bind_device_type_name }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($entireInstances->hasPages())
                    <div class="box-footer">
                        {{ $entireInstances->appends(['page'=>request('page',1)])->links() }}
                    </div>
                @endif
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,  // 分页器
                    lengthChange: true,
                    searching: true,  // 搜索框
                    ordering: false,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
                    order: [[0, 'desc']],  // 排序依据
                    iDisplayLength: "{{ env('PAGE_SIZE', 50) }}",  // 默认分页数
                    aLengthMenu: [15, 100, 200],  // 分页下拉框选项
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
        });

        /**
         * 删除
         */
        function fnRefresh() {
            let selectedForRefresh = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let newCode = $(item).val();
                if (newCode !== '') selectedForRefresh.push(newCode);
            });

            if (selectedForRefresh.length <= 0) {
                alert('没有选择设备');
                return;
            }

            $.ajax({
                url: `{{ url('entire/instance/refresh') }}`,
                type: 'post',
                data: {identityCodes: selectedForRefresh},
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/instance/refresh') }} success:`, res);
                    alert(res['message']);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('entire/instance/refresh') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

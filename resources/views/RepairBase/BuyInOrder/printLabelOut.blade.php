@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            新购管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('repairBase/newInOrder',$out_sn) }}?direction=OUT">新购出所计划详情</a></li>--}}
{{--            <li class="active">打印出所标签</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">新购出所设备列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
{{--                    <a href="{{ url('repairBase/newInOrder',$out_sn) }}?direction=OUT" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    <a href="javascript:" class="btn btn-flat btn-primary" onclick="downloadLabel()"><i class="fa fa-print">&nbsp;</i>打印</a>
                </div>
            </div>
            <br>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group btn-group-sm pull-right">
                            @if(!$is_all_bound)
                                <a href="javascript:" onclick="fnAutoBindEntireInstances()">全选</a> /
                            @endif
                            <a href="javascript:" onclick="fnDeleteBindEntireInstances()">取消全选</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <dl class="dl-horizontal">
                            <dt>任务总数</dt>
                            <dd>{{ $plan_sum }}</dd>
                            <dt>可用成品总数</dt>
                            <dd>{{ $usable_entire_instance_sum }}</dd>
                        </dl>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th></th>
                            <th style="width: 20%;">唯一/所编号(老)</th>
                            <th style="width: 10%;">型号</th>
                            <th style="width: 10%">组合位置/道岔号</th>
                            <th style="width: 20%;">唯一/所编号(新)</th>
                            <th style="width: 20%;">仓库位置</th>
                            <th style="width: 20%;">替换</th>
                        </tr>
                        </thead>
                        <tbody id="tbody">
                        @foreach($entire_instances as $entire_instance)
                            <tr id="tr_{{ $entire_instance->OldEntireInstance->identity_code }}">
                                <td>
                                    <input type="checkbox" class="select-bind-entire-instance" name="labelChecked" id="chk_{{ $entire_instance->OldEntireInstance->identity_code }}" value="{{ $entire_instance->OldEntireInstance->identity_code }}" {{ $entire_instance->new_entire_instance_identity_code ? 'checked' : '' }}>
                                </td>
                                <td>{{ $entire_instance->OldEntireInstance->identity_code }}/{{ $entire_instance->OldEntireInstance->serial_number }}</td>
                                <td>{{ $entire_instance->OldEntireInstance->model_name }}</td>
                                <td>
                                    {{ $entire_instance->maintain_location_code }}
                                    {{ $entire_instance->crossroad_number }}
                                </td>
                                <td><span id="spanNewEntireInstance_{{ $entire_instance->OldEntireInstance->identity_code }}">{{ @$entire_instance->NewEntireInstance->identity_code }}/{{ @$entire_instance->NewEntireInstance->serial_number }}</span></td>
                                <td><span id="spanWarehouseLocation_{{ $entire_instance->OldEntireInstance->identity_code }}">{{ @$entire_instance->NewEntireInstance->location_unique_code }}</span></td>
                                <td>
                                    <label for="selNewIdentityCode" style="display: none;"></label>
                                    <select
                                        name="new_identity_code"
                                        id="selNewIdentityCode_{{ $entire_instance->OldEntireInstance->identity_code }}"
                                        class="form-control select2"
                                        style="width: 100%;"
                                        onchange="fnBindEntireInstance('{{ $entire_instance->OldEntireInstance->identity_code }}',this.value)"
                                    >
                                        @if(empty($usable_entire_instances->get($entire_instance->OldEntireInstance->model_name)->all()))
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
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        $(function () {
            if ($select2.length > 0) $('.select2').select2();
            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: true,
                    lengthChange: true,
                    searching: true,
                    ordering: false,
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
        downloadLabel = () => {
            //处理数据
            let selected_for_api = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each(function () {
                let value = $(this).val().split(",");
                selected_for_api.push(value[0]);
            });

            if (selected_for_api.length > 0) {
                window.open(`{{url('qrcode/printLabel')}}?identityCodes=${JSON.stringify(selected_for_api)}`);
            } else {
                alert('无数据')
            }
        };

        /**
         * 全选绑定设备
         */
        function fnAutoBindEntireInstances() {
            $.ajax({
                url: `{{ url('repairBase/newInOrder/autoBindEntireInstances') }}`,
                type: 'post',
                data: {outSn: '{{ $out_sn }}'},
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/newInOrder/autoBindEntireInstances') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/newInOrder/autoBindEntireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });

            $("input[type='checkbox']").iCheck("check");
        }

        /**
         * 取消全部绑定设备
         */
        function fnDeleteBindEntireInstances() {
            $.ajax({
                url: `{{ url('repairBase/newInOrder/bindEntireInstances') }}`,
                type: 'delete',
                data: {outSn: '{{ $out_sn }}'},
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/newInOrder/bindEntireInstances') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/newInOrder/bindEntireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });

            $("input[type='checkbox']").iCheck("uncheck");
        }

        /**
         * 手动绑定新设备到老设备
         */
        function fnBindEntireInstance(oldIdentityCode, newIdentityCode) {
            if(newIdentityCode){
                $.ajax({
                    url: `{{ url('repairBase/newInOrder/bindEntireInstance') }}`,
                    type: 'post',
                    data: {oldIdentityCode, newIdentityCode, outSn: '{{ $out_sn }}'},
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('repairBase/newInOrder/bindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/newInOrder/bindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }else{
                $.ajax({
                    url: `{{ url('repairBase/newInOrder/bindEntireInstance') }}`,
                    type: 'delete',
                    data: {oldIdentityCode, newIdentityCode, outSn: '{{ $out_sn }}'},
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('repairBase/newInOrder/bindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/newInOrder/bindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 自动绑定新设备到老设备
         */
        $('.select-bind-entire-instance').on('change', function () {
            let oldIdentityCode = $(this).val();
            if ($(this).is(':checked')) {
                // 绑定
                $.ajax({
                    url: `{{ url('repairBase/newInOrder/autoBindEntireInstance') }}`,
                    type: 'post',
                    data: {oldIdentityCode, outSn: '{{ $out_sn }}'},
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('repairBase/newInOrder/autoBindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/newInOrder/autoBindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            } else {
                // 解绑
                $.ajax({
                    url: `{{ url('repairBase/newInOrder/bindEntireInstance') }}`,
                    type: 'delete',
                    data: {oldIdentityCode, outSn: '{{ $out_sn }}'},
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('repairBase/newInOrder/bindEntireInstance') }} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/newInOrder/bindEntireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        });
    </script>
@endsection

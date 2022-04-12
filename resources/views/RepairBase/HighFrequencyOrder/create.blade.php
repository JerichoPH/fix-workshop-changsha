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
            <small>新建</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('repairBase/highFrequencyOrder') }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>高频修管理</a></li>--}}
{{--            <li class="active">新建</li>--}}
{{--        </ol>--}}
    </section>
    <div class="row">
        <div class="col-md-6">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">新建高频修入所计划</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('repairBase/highFrequencyOrder') }}?page={{ request('page',1) }}" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a href="{{ url('repairBase/highFrequencyOrder',$high_frequency_order->serial_number) }}?page={{ request('page',1) }}" class="btn btn-flat btn-success"><i class="fa fa-check">&nbsp;</i>完成</a>
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
                            <dt>任务数量</dt>
                            <dd><span id="spanEntireInstancesTotal">{{ count($high_frequency_order->InEntireInstances) }}</span></dd>
                        </dl>
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
                                <tbody id="tbodyInOrder">
                                @foreach($high_frequency_order->InEntireInstances as $entire_instance)
                                    <tr>
                                        <td>{{ $entire_instance->OldEntireInstance->identity_code }}/{{ $entire_instance->OldEntireInstance->serial_number }}</td>
                                        <td>{{ $entire_instance->OldEntireInstance->model_name }}</td>
                                        <td>
                                            {{ $entire_instance->OldEntireInstance->maintain_location_code }}
                                            {{ $entire_instance->OldEntireInstance->crossroad_number }}
                                        </td>
                                        <td><a href="javascript:" onclick="fnDelete('{{ $entire_instance->OldEntireInstance->identity_code }}')" class="btn btn-flat btn-sm btn-danger"><i class="fa fa-times">&nbsp;</i>删除</a></td>
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
                            {{--<a href="javascript:" class="btn btn-flat btn-default" onclick="fnSearch()"><i class="fa fa-search">&nbsp;</i>搜索</a>--}}
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="input-group">
                            <div class="input-group-addon">唯一/所编号</div>
                            <label for="txtNo" style="display: none;"></label>
                            <input type="text" name="no" id="txtNo" class="form-control" onkeydown="if(event.keyCode === 13) {fnSearch();}">
                            <div class="input-group-addon">组合位置/道岔号</div>
                            <label for="txtLocation" style="display: none;"></label>
                            <input type="text" name="location" id="txtLocation" class="form-control" onkeydown="if(event.keyCode === 13) {fnSearch();}">
                        </div>
                        <span class="help-block">搜索：二选一</span>
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
                                <tbody id="tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
@section('script')
    <script>
        let $txtNo = $('#txtNo');
        let $txtLocation = $('#txtLocation');

        /**
         * 搜索设备
         */
        function fnSearch() {
            if ($txtNo.val() || $txtLocation.val()) {
                $.ajax({
                    url: `{{ url('repairBase/highFrequencyOrder/entireInstances') }}?direction=IN`,
                    type: 'get',
                    data: {
                        maintain_station_name: "{{ $high_frequency_order->Station ? $high_frequency_order->Station->name : '' }}",
                        no: $txtNo.val(),
                        location: $txtLocation.val(),
                    },
                    async: false,
                    success: function (res) {
                        console.log(`{{ url('repairBase/highFrequencyOrder/entireInstance') }} success:`, res);
                        let html = '';
                        $.each(res['data'], function (index, item) {
                            html += '<tr>';
                            html += `<td>${item['identity_code']}/${item['serial_number']}</td>`;
                            html += `<td>${item['model_name']}</td>`;
                            html += `<td>${item['maintain_location_code']}${item['crossroad_number']}</td>`;
                            html += `<td><a href="javascript:" class="btn btn-default btn-flat btn-sm" onclick="fnAdd('${item['identity_code']}')"><i class="fa fa-plus">&nbsp;</i>添加</a></td>`;
                            html += `</tr>`;
                        });
                        $('#tbody').html(html);
                        $txtNo.val('');
                        $txtLocation.val('');
                    },
                    error: function (err) {
                        console.log(`{{ url('repairBase/highFrequencyOrder/entireInstance') }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 添加到高频修计划表
         * @param identityCode
         */
        function fnAdd(identityCode) {
            $.ajax({
                url: `{{ url('repairBase/highFrequencyOrder/entireInstances') }}`,
                type: 'post',
                data: {
                    identityCode,
                    highFrequencyOrderSn: "{{ $high_frequency_order->serial_number }}",
                    direction: 'IN',
                },
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/entireInstances') }} success:`, res);

                    let html = '';
                    $.each(res['data'], function (index, item) {
                        html += `<tr>`;
                        html += `<td>${item['old_entire_instance']['identity_code']}/${item['old_entire_instance']['serial_number']}</td>`;
                        html += `<td>${item['old_entire_instance']['model_name']}</td>`;
                        html += `<td>${item['old_entire_instance']['maintain_location_code']}${item['old_entire_instance']['crossroad_number']}</td>`;
                        html += `<td><a href="javascript:" class="btn btn-danger btn-flat btn-sm" onclick="fnDelete('${item['old_entire_instance']['identity_code']}')"><i class="fa fa-times">&nbsp;</i>删除</a></td>`;
                        html += `</tr>`;
                    });
                    $('#tbodyInOrder').html(html);
                    $('#spanEntireInstancesTotal').text(res['data'].length);
                    $('#tbody').html('');
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/entireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 删除入所计划表中设备
         * @param identityCode
         */
        function fnDelete(identityCode) {
            $.ajax({
                url: `{{ url('repairBase/highFrequencyOrder/entireInstances') }}`,
                type: 'delete',
                data: {
                    identityCode,
                    highFrequencyOrderSn: "{{ $high_frequency_order->serial_number }}",
                    direction: 'IN',
                },
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/entireInstances') }} success:`, res);
                    let html = '';
                    $.each(res['data'], function (index, item) {
                        html += `<tr>`;
                        html += `<td>${item['old_entire_instance']['identity_code']}/${item['old_entire_instance']['serial_number']}</td>`;
                        html += `<td>${item['old_entire_instance']['model_name']}</td>`;
                        html += `<td>${item['old_entire_instance']['maintain_location_code']}${item['old_entire_instance']['crossroad_number']}</td>`;
                        html += `<td><a href="javascript:" class="btn btn-danger btn-flat btn-sm" onclick="fnDelete('${item['old_entire_instance']['identity_code']}')"><i class="fa fa-times">&nbsp;</i>删除</a></td>`;
                        html += `</tr>`;
                    });

                    $('#tbodyInOrder').html(html);
                    $('#spanEntireInstancesTotal').text(res['data'].length);
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/highFrequencyOrder/entireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

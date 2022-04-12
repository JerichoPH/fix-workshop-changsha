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
            更换设备管理
            <small>新建</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('repairBase/exchangeModelOrder') }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>更换设备管理</a></li>--}}
{{--            <li class="active">新建</li>--}}
{{--        </ol>--}}
    </section>
    <div class="row">
        <div class="col-md-6">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">新建更换设备入所计划</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('repairBase/exchangeModelOrder') }}?page={{ request('page',1) }}" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            {{--<a href="{{ url('repairBase/exchangeModelOrder',$exchange_model_order->serial_number) }}?page={{ request('page',1) }}" class="btn btn-flat btn-success"><i class="fa fa-check">&nbsp;</i>完成</a>--}}
                            <a href="javascript:" onclick="fnMakeEntireInstances()" class="btn btn-success btn-flat"><i class="fa fa-check">&nbsp;</i>完成</a>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>车站</dt>
                            <dd>
                                {{ $exchange_model_order->SceneWorkshop ? $exchange_model_order->SceneWorkshop->name : '' }}
                                {{ $exchange_model_order->Station ? $exchange_model_order->Station->name : '' }}
                            </dd>
                            <dt>更换时间</dt>
                            <dd>{{ $exchange_model_order->created_at->format('Y-m') }}</dd>
                            <dt>型号数量</dt>
                            <dd>{{ $station_models->where('picked',true)->sum() }}</dd>
                            <dt>设备数量</dt>
                            <dd>{{ $station_models->sum('number') }}</dd>
                        </dl>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>型号</th>
                                    <th>数量</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody id="tbodyInOrder">
                                @foreach($station_models->where('picked', true)->all() as $picked)
                                    <tr>
                                        <td>{{ $picked->model_name }}</td>
                                        <td>{{ $picked->number }}</td>
                                        <td><a href="javascript:" onclick="fnDelete('{{ $picked->id }}')" class="btn btn-flat btn-sm btn-danger"><i class="fa fa-times">&nbsp;</i>删除</a></td>
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
                        <div class="btn-group btn-group-sm pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped" id="table">
                                <thead>
                                <tr>
                                    <th>型号</th>
                                    <th>数量</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody id="tbody">
                                @foreach($station_models as $station_model)
                                    <tr>
                                        <td>{{ $station_model->model_name }}</td>
                                        <td>{{ $station_model->number }}</td>
                                        <td>
                                            @if($station_model->picked)
                                                <a href="javascript:" onclick="fnDelete('{{ $station_model->id }}')" class="btn btn-flat btn-sm btn-danger"><i class="fa fa-times">&nbsp;</i>删除</a>
                                            @else
                                                <a href="javascript:" onclick="fnAdd(' {{ $station_model->id }}')" id="btnAdd" class="btn btn-default btn-flat btn-sm"><i class="fa fa-check">&nbsp;</i>添加</a>
                                            @endif
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
@endsection
@section('script')
    <script>
        let $txtNo = $('#txtNo');
        let $txtLocation = $('#txtLocation');

        $(function () {
            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: true,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: true,
                    iDisplayLength: 15,  // 默认分页数
                    aLengthMenu: [15, 30, 50, 100],  // 分页下拉框选项
                    language: {
                        sInfoFiltered: "从_MAX_中过滤",
                        sProcessing: "正在加载中...",
                        info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "每页显示_MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "筛选：",
                        paginate: {sFirst: " 首页",  sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });
            }
        });

        /**
         * 根据所选类型生成具体设备列表
         */
        function fnMakeEntireInstances() {
            $.ajax({
                url: `{{ url('repairBase/exchangeModelOrder/entireInstances') }}`,
                type: 'post',
                data: {exchangeModelOrderSn: '{{ $exchange_model_order->serial_number }}'},
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/exchangeModelOrder/entireInstances') }} success:`, res);
                    location.href = `/repairBase/exchangeModelOrder/${res['sn']}?direction=IN`;
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/exchangeModel/entireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 添加到更换设备计划表
         * @param id
         */
        function fnAdd(id) {
            $.ajax({
                url: `{{ url('repairBase/exchangeModelOrder/models') }}`,
                type: 'post',
                data: {id},
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/exchangeModelOrder/entireInstances') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/exchangeModelOrder/entireInstances') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 删除入所计划表中设备
         * @param id
         */
        function fnDelete(id) {
            $.ajax({
                url: `{{ url('repairBase/exchangeModelOrder/models') }}`,
                type: 'delete',
                data: {id},
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/exchangeModelOrder/models') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/exchangeModelOrder/models') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

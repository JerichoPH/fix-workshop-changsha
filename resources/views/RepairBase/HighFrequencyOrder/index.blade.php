@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            高频修管理
            <small>{{ request('direction','IN') === 'IN' ? '入所' : '出所' }}计划列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            @if(request('direction','IN') === 'IN')--}}
{{--                <li><a href="{{ url('repairBase/highFrequencyOrder') }}?direction=OUT">出所计划列表</a></li>--}}
{{--            @else--}}
{{--                <li><a href="{{ url('repairBase/highFrequencyOrder') }}?direction=IN">入所计划列表</a></li>--}}
{{--            @endif--}}
{{--            <li class="active">{{ request('direction','IN') === 'IN' ? '入所' : '出所' }}计划列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">搜索</h3>
                <div class="pull-right btn-group btn-group-sm">
                    <a href="javascript:" onclick="fnSearch()" class="btn btn-flat btn-default"><i class="fa fa-search">&nbsp;</i>搜索</a>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-addon">现场车间</div>
                            <label for="selSceneWorkshop" style="display: none;"></label>
                            <select name="scene_workshop_code" id="selSceneWorkshop" class="form-control select2" style="width: 100%;" onchange="fnFillStation()"></select>
                            <div class="input-group-addon">车站</div>
                            <label for="selStation" style="display: none;"></label>
                            <select name="station_code" id="selStation" class="form-control select2" style="width: 100%;"></select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group">
                            <div class="input-group-addon">时间</div>
                            <label for="dpCreatedAt" style="display: none;"></label>
                            <input type="text" class="form-control" id="dpCreatedAt" value="{{ request('created_at') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">高频修{{ request('direction','IN') == 'IN' ? '入所' : '出所' }}列表</h3>
                {{--右侧最小化按钮--}}
                <div class="pull-right btn-group btn-group-sm">
                    @if(request('direction','IN') == 'IN')
                        <a href="javascript:" onclick="fnModalCreate()" class="btn btn-flat btn-success"><i class="fa fa-plus">&nbsp;</i>新建</a>
                    @endif
                </div>
            </div>
            <div class="box-body table-responsive">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>车站</th>
                            <th>时间</th>
                            <th>状态</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($high_frequency_orders as $high_frequency_order)
                            <tr>
                                <td>
                                    <a href="{{ url('repairBase/highFrequencyOrder',$high_frequency_order['serial_number']) }}?page={{ request('page',1) }}&scene_workshop_code={{ request('scene_workshop_code') }}&station_code={{ request('station_code') }}&direction={{ request('direction','IN') }}&created_at={{ request('created_at') }}">
                                        {{ $high_frequency_order->SceneWorkshop ? $high_frequency_order->SceneWorkshop->name : '' }}
                                        {{ $high_frequency_order->Station ? $high_frequency_order->Station->name : '' }}
                                    </a>
                                </td>
                                <td>{{ $high_frequency_order->created_at->format('Y-m') }}</td>
                                <td>{{ $high_frequency_order->status }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($high_frequency_orders->hasPages())
                <div class="box-footer">
                    {{ $high_frequency_orders->appends([
                                                        'page'=>request('page',1),
                                                        'scene_workshop_code'=>request('scene_workshop_code'),
                                                        'station_code'=>request('station_code'),
                                                        'direction'=>request('direction','IN'),
                                                        'created_at'=>request('created_at'),
                                                        ])->links() }}
                </div>
            @endif
        </div>
    </section>

    <!--模态框：新建-->
    <section class="content">
        <div class="modal fade" id="modalCreate">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">新建高频修入所计划</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmCreate">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">车站：</label>
                                <div class="col-sm-9 col-md-8">
                                    <label for="selSceneWorkshop_create" style="display: none;"></label>
                                    <select name="scene_workshop_code_create" id="selSceneWorkshop_create" class="form-control select2" style="width: 100%;" onchange="fnFillStation_create()"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label"></label>
                                <div class="col-sm-9 col-md-8">
                                    <label for="selStation_create" style="display: none;"></label>
                                    <select name="station_code_create" id="selStation_create" class="form-control select2" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">时间：</label>
                                <div class="col-sm-9 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-addon">时间</div>
                                        <label for="dpCreatedAt_create" style="display: none;"></label>
                                        <input name="created_at_create" type="text" class="form-control" id="dpCreatedAt_create">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <a href="javascript:" onclick="fnCreate()" class="btn btn-success btn-sm btn-flat"><i class="fa fa-arrow-right">&nbsp;</i>下一步</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let maintains = {};
        let sceneWorkshops = {};
        $.each(JSON.parse('{!! $maintains !!}'), function (index, sceneWorkshop) {
            if (!sceneWorkshops.hasOwnProperty(sceneWorkshop['unique_code'])) sceneWorkshops[sceneWorkshop['unique_code']] = sceneWorkshop['name'];
            if (!maintains.hasOwnProperty(sceneWorkshop['unique_code'])) maintains[sceneWorkshop['unique_code']] = {};
            $.each(sceneWorkshop['subs'], function (index, station) {
                if (!maintains[sceneWorkshop['unique_code']].hasOwnProperty(station['unique_code'])) maintains[sceneWorkshop['unique_code']][station['unique_code']] = '';
                maintains[sceneWorkshop['unique_code']][station['unique_code']] = station['name'];
            });
        });
        let $selSceneWorkshop = $('#selSceneWorkshop');
        let $selStation = $('#selStation');

        /**
         * 填充现场车间
         */
        function fnFillSceneWorkshop() {
            let html = '<option value="">全部</option>';
            $.each(sceneWorkshops, function (sceneWorkshopUniqueCode, sceneWorkshopName) {
                html += `<option value="${sceneWorkshopUniqueCode}" ${'{{ request('scene_workshop_code') }}' === sceneWorkshopUniqueCode ? 'selected' : ''}>${sceneWorkshopName}</option>`;
            });
            $selSceneWorkshop.html(html);
            fnFillStation();
        }

        /**
         * 填充现场车间(新建)
         */
        function fnFillSceneWorkshop_create() {
            let html = '';
            $.each(sceneWorkshops, function (sceneWorkshopUniqueCode, sceneWorkshopName) {
                html += `<option value="${sceneWorkshopUniqueCode}">${sceneWorkshopName}</option>`;
            });
            $('#selSceneWorkshop_create').html(html);
            fnFillStation_create();
        }

        /**
         * 填充车站列表
         */
        function fnFillStation() {
            let sceneWorkshopUniqueCode = $selSceneWorkshop.val();
            let html = '<option value="">全部</option>';
            if ('' !== sceneWorkshopUniqueCode) {
                $.each(maintains[sceneWorkshopUniqueCode], function (stationUniqueCode, stationName) {
                    html += `<option value="${stationUniqueCode}" ${'{{ request('station_code') }}' === stationUniqueCode ? 'selected' : ''}>${stationName}</option>`
                });
            }
            $selStation.html(html);
        }

        /**
         * 填充车站列表(新建)
         */
        function fnFillStation_create() {
            let sceneWorkshopUniqueCode = $('#selSceneWorkshop_create').val();
            let html = '';
            if ('' !== sceneWorkshopUniqueCode) {
                $.each(maintains[sceneWorkshopUniqueCode], function (stationUniqueCode, stationName) {
                    html += `<option value="${stationUniqueCode}">${stationName}</option>`
                });
            }
            $('#selStation_create').html(html);
        }

        let $select2 = $('.select2');
        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            $('#dpCreatedAt').datepicker({
                format: 'yyyy-mm',
                language: 'cn',
                clearBtn: true,
                autoclose: true,
            });

            fnFillSceneWorkshop();
        });


        function fnSearch() {
            location.href = `?page={{ request('page',1) }}&scene_workshop_code=${$selSceneWorkshop.val()}&station_code=${$selStation.val()}&direction={{ request('direction','IN') }}&created_at=${$('#dpCreatedAt').val()}`;
        }

        /**
         * 打开新建窗口
         */
        function fnModalCreate() {
            $('#dpCreatedAt_create').datepicker({
                format: 'yyyy-mm',
                language: 'cn',
                clearBtn: true,
                autoclose: true,
            });
            fnFillSceneWorkshop_create();

            $('#modalCreate').modal('show');
        }

        /**
         * 新建
         */
        function fnCreate() {
            $.ajax({
                url: `{{ url('repairBase/highFrequencyOrder') }}`,
                type: 'post',
                data: $('#frmCreate').serialize(),
                async: false,
                success: function (res) {
                    console.log(`{{ url('repairBase/highFrequencyOrder') }} success:`, res);
                    location.href = `/repairBase/highFrequencyOrder/create/${res['new_serial_number']}?page={{ request('page',1) }}&direction=IN`;
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/highFrequencyOrder') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    if (err.status === 555) {
                        location.href = err['responseJSON']['return_url'] + '?page={{ request('page',1) }}';
                        return;
                    }
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

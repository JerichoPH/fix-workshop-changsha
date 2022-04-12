@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            故障修管理
            <small>{{ request('direction','IN') === 'IN' ? '入所' : '出所' }}计划列表</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    @if(request('direction','IN') === 'IN')--}}
        {{--        <li><a href="{{ url('repairBase/breakdownOrder') }}?direction=OUT">出所计划列表</a></li>--}}
        {{--    @else--}}
        {{--        <li><a href="{{ url('repairBase/breakdownOrder') }}?direction=IN">入所计划列表</a></li>--}}
        {{--    @endif--}}
        {{--    <li class="active">{{ request('direction','IN') === 'IN' ? '入所' : '出所' }}计划列表</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">故障修{{ request('direction','IN') == 'IN' ? '入所' : '出所' }}列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    @if(request('direction','IN') == 'IN')
                        <a href="{{ url('repairBase/breakdownOrder/create') }}" class="btn btn-flat btn-success"><i class="fa fa-plus">&nbsp;</i>新建入所记录</a>
                    @endif
                </div>
            </div>
            <div class="box-body table-responsive">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>工区</th>
                            <th>车站</th>
                            <th>状态</th>
                            <th>完成时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($breakdown_orders as $breakdown_order)

                            <tr {!! $breakdown_order->status == '已完成' ? 'class="bg-success"' : '' !!}>
                                <td>{{ $breakdown_order->work_area_id }}</td>
                                <td>
                                    <?php
                                    $a = $breakdown_order->OutEntireInstances->groupBy('maintain_station_name');
                                    $b = [];
                                    foreach ($a as $sn => $item) {
                                        $b[] = "{$sn}：" . count($item) . '台';
                                    }
                                    echo implode('<br>', $b);
                                    ?>
                                </td>
                                <td>{{ $breakdown_order->status }}</td>
                                @if($breakdown_order->status != '已完成')
                                    <td style="{{ $breakdown_order->processed_at ? (strtotime($breakdown_order->processed_at) - strtotime($breakdown_order->created_at) > 3600*24*3 ? '' : 'color: red;') : '' }}">{{ $breakdown_order->processed_at ?? '-' }}</td>
                                @else
                                    <td>{{ $breakdown_order->processed_at ?? '-' }}</td>
                                @endif
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a
                                            href="{{ url('repairBase/breakdownOrder/printLabel',$breakdown_order['serial_number']) }}?page={{ request('page', 1) }}&direction={{ request('direction','IN') }}"
                                            class="btn btn-flat btn-warning"
                                        >
                                            <i class="fa fa-sign-out"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($breakdown_orders->hasPages())
                <div class="box-footer">
                    {{ $breakdown_orders->appends(['page'=>request('page',1),'direction'=>request('direction','IN'),])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            $('#dpCreatedAt').datepicker({
                format: 'yyyy-mm',
                language: 'cn',
                clearBtn: true,
                autoclose: true,
            });
        });
    </script>
@endsection

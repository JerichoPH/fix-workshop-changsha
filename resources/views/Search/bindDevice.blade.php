@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备器材绑定管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">设备器材绑定列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">设备器材绑定列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm"></div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped table-condensed">
                        <thead>
                        <tr>
                            <th>唯一编号</th>
                            <th>供应商</th>
                            <th>所编号</th>
                            <th>状态</th>
                            <th>种类型</th>
                            <th>最后安装日期</th>
                            <th>位置</th>
                            <th>仓库位置</th>
                            <th>上次检修日期</th>
                            <th>下次周期修日期</th>
                            <th>报废日期</th>
                            <th>所属设备</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entireInstances as $entireInstance)
                            <tr>
                                <td><a href="{{ url('search',$entireInstance->identity_code) }}">{{ $entireInstance->identity_code }}</a></td>
                                <td>
                                    {{ $entireInstance->factory_name }}
                                    {{ $entireInstance->factory_device_code }}
                                </td>
                                <td>{{ $entireInstance->serial_number }}</td>
                                {{--<td>{{ $statuses[$entireInstance->status] }}</td>--}}
                                <td>{{ $entireInstance->status }}</td>
                                <td>
                                    {{ $entireInstance->Category->name }}
                                    {{ $entireInstance->model_name }}
                                </td>
                                <td>{{ empty($entireInstance->last_installed_time) ? '' : date('Y-m-d',$entireInstance->last_installed_time) }}</td>
                                <td>
                                    {{ $entireInstance->maintain_station_name }}
                                    {{ $entireInstance->maintain_location_code }}
                                    {{ $entireInstance->line_unique_code }}
                                    {{ $entireInstance->crossroad_number }}
                                    {{ $entireInstance->open_direction }}
                                    {{ $entireInstance->to_direction }}
                                    {{ $entireInstance->traction }}
                                    {{ $entireInstance->said_rod }}
                                </td>
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
                                    {{ $entireInstance->bind_crossroad_number }}
                                    {{ $entireInstance->bind_device_type_name }}
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
        });
    </script>
@endsection

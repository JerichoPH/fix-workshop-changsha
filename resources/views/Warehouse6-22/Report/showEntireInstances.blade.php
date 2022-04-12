@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <section class="invoice">
        <div class="row">
            <div class="col-xs-12">
                <h2 class="page-header">
                    <i class="fa fa-globe"></i> 检修车间设备器材全生命周期管理系统
                    <small class="pull-right">
                        日期：{{ $warehouseReport->processed_at }}
                    </small>
                </h2>
            </div>
        </div>
        <div class="row invoice-info">
            <div class="col-sm-6 invoice-col">
                <strong>基本信息</strong>
                <address>
                    序列号：{{ $warehouseReport->serial_number }}<br>
                    经手人：{{ $warehouseReport->Processor ? $warehouseReport->Processor->nickname : '' }}<br>
                    联系人姓名：{{ $warehouseReport->connection_name }}<br>
                    联系电话：{{ $warehouseReport->connection_phone }}<br>
                    时间：{{ $warehouseReport->processed_at }}<br>
                    类型：{{ $warehouseReport->type }}<br>
                </address>
            </div>
            <div class="col-sm-6 invoice-col">
                <strong>设备类型及数量</strong>
                <address>
                    @foreach($entireModels as $entireModelName=>$entireInstanceIdentityCodes)
                        {{ $entireModelName }}（{{ count($entireInstanceIdentityCodes) }}）<br>
                    @endforeach
                </address>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-6">
            </div>
            <div class="col-xs-6">
                <p class="lead">统计</p>
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>总计</th>
                            <td>{{ count($warehouseReport->WarehouseReportEntireInstances) }}&nbsp;件</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row no-print">
            <div class="col-xs-12">
                <a
                    href="{{ url('warehouse/report') }}?page={{ request('page',1) }}&direction={{ request('direction') }}&current_work_area={{ request('current_work_area') }}&updated_at={{ request('updated_at') }}&show_type={{ request('show_type') }}"
                    class="btn btn-default pull-left btn-flat btn-sm"
                >
                    <i class="fa fa-arrow-left">&nbsp;</i>返回
                </a>
            </div>
        </div>
    </section>

    <!--设备列表-->
    <section class="invoice">
        <div class="box box-solid">
            <div class="box-header">
                <h1 class="box-title">出入所设备列表</h1>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group-sm btn-sm">
                    <a href="{{ url('storehouse/index/in') }}" class="btn btn-default btn-flat"><i class="fa fa-toggle-right">&nbsp;</i>设备入库</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>唯一编号</th>
                        <th>设备型号</th>
                        <th>车站</th>
                        <th>安装位置</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($warehouseReport->WarehouseReportEntireInstances as $WarehouseReportEntireInstance)
                        @if($WarehouseReportEntireInstance->EntireInstance)
                            <tr>
                                <td>
                                    <a href="{{ url('search',$WarehouseReportEntireInstance->EntireInstance->identity_code) }}">
                                        {{ $WarehouseReportEntireInstance->EntireInstance->identity_code }}
                                    </a>
                                </td>
                                <td>{{ @$WarehouseReportEntireInstance->EntireInstance->EntireModel->name }}</td>
                                <td>{{ @$WarehouseReportEntireInstance->maintain_station_name }}</td>
                                <td>
                                    {{ @$WarehouseReportEntireInstance->maintain_location_code }}
                                    {{ @$WarehouseReportEntireInstance->crossroad_number }}
                                    {{ @$WarehouseReportEntireInstance->traction }}
                                    {{ @$WarehouseReportEntireInstance->line_name }}
                                    {{ @$WarehouseReportEntireInstance->open_direction }}
                                    {{ @$WarehouseReportEntireInstance->said_rod }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <div class="clearfix"></div>
@endsection
@section('script')
    <script>
        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
            //Date picker
            $('#datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd'
            });
        });
    </script>
@endsection

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{$warehouseReport->prototype('direction') == 'IN' ? '入' : '出'}}所单</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/font-awesome/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/Ionicons/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- Google Font -->
    {{--    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">--}}
</head>
<body onload="window.print();">
<div class="wrapper">
    <section class="invoice">
        <div class="row">
            <div class="col-xs-12">
                <h2 class="page-header">
                    <i class="fa fa-globe"></i> 电务检修作业管理信息平台
                    <small class="pull-right">日期：{{ $warehouseReport->processed_at }}</small>
                </h2>
            </div>
        </div>

        <div class="row invoice-info">
            <div class="col-sm-6 invoice-col">
                <strong>基本信息</strong>
                <address>
                    序列号：{{$warehouseReport->serial_number}}<br>
                    经手人：{{$warehouseReport->Processor ? $warehouseReport->Processor->nickname : ''}}<br>
                    联系人姓名：{{$warehouseReport->connection_name}}<br>
                    联系电话：{{$warehouseReport->connection_phone}}<br>
                    时间：{{ $warehouseReport->processed_at }}<br>
                    类型：{{$warehouseReport->type}}<br>
                </address>
            </div>
            <div class="col-sm-6 invoice-col">
                <strong>设备类型及数量</strong>
                <address>
                    @foreach($entireModels as $entireModelName=>$entireInstanceIdentityCodes)
                        {{$entireModelName}}（{{count($entireInstanceIdentityCodes)}}）<br>
                    @endforeach
                </address>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 table-responsive">
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
                        <tr>
                            <td>{{ @$WarehouseReportEntireInstance->EntireInstance->identity_code }}</td>
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
                    @endforeach
                    </tbody>
                </table>
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
                            <td>{{count($warehouseReport->WarehouseReportEntireInstances)}}&nbsp;件</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
</body>
</html>

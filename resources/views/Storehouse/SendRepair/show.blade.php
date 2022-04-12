@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <section class="invoice">
        <div class="row">
            <div class="col-xs-12">
                <h2 class="page-header">
                    <i class="fa fa-globe"></i> 检修车间设备器材全生命周期管理系统
                    <small class="pull-right">
                        日期：{{ date('Y-m-d',strtotime($sendRepair->created_at)) }}
                    </small>
                </h2>
            </div>
        </div>
        <div class="row invoice-info" style="font-size: 12px">
            <div class="col-sm-6 invoice-col">
                <address>
                    送修编码：{{ $sendRepair->unique_code }}<br>
                    操作人：{{ $sendRepair->WithAccount->nickname ?? '' }}<br>
                    联系电话：{{ $sendRepair->WithAccount->phone ?? '' }}<br>
                    送修时间：{{ date('Y-m-d',strtotime($sendRepair->created_at)) }}<br>
                </address>
            </div>
            <div class="col-sm-6 invoice-col">
                <address>
                    送修单位：{{ $sendRepair->WithFromMaintain->name ?? '' }}<br>
                    接收单位：{{empty($sendRepair->WithToFactory) ? empty($sendRepair->WithToMaintain) ? '' : $sendRepair->WithToMaintain->name : $sendRepair->WithToFactory->name}}<br>
                    接收联系人：{{$sendRepair->to_name}}<br>
                    接收联系人电话：{{$sendRepair->to_phone}}<br>
                </address>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>唯一编码</th>
                        <th>种类</th>
                        <th>型号</th>
                        <th>厂家</th>
                        <th>检修报告</th>
                        <th>故障描述</th>
                        <th>备注</th>
                        <th>是否验收</th>
                    </tr>
                    </thead>
                    <tbody style="font-size: 12px">
                    @foreach($sendRepair->WithSendRepairInstance as $sendRepairInstance)
                        <tr>
                            <td>{{$sendRepairInstance->material_unique_code}}</td>
                            @if($sendRepairInstance->material_type == 'ENTIRE')
                                <td>{{ $sendRepairInstance->WithEntireInstance->category_name ?? '' }}</td>
                                <td>{{ $sendRepairInstance->WithEntireInstance->model_name ?? '' }}</td>
                                <td>{{ $sendRepairInstance->WithEntireInstance->factory_name ?? '' }}</td>
                            @else
                                <td>
                                    {{ $sendRepairInstance->WithPartInstance->Category->name ?? '' }}
                                    {{ $sendRepairInstance->WithPartInstance->PartCategory->name ?? '' }}
                                </td>
                                <td>{{ $sendRepairInstance->WithPartInstance->part_model_name ?? '' }}</td>
                                <td>{{ $sendRepairInstance->WithPartInstance->factory_name ?? '' }}</td>
                            @endif
                            <td>
                                @if(!empty($sendRepairInstance->repair_report_url))
                                    <a href="{{ url('storehouse/sendRepair/downloadSendRepairFile',$sendRepairInstance->id) }}/report" target="_blank"><i class="fa fa-download"></i> 下载报告</a>
                                @endif
                            </td>
                            <td>{{ $sendRepairInstance->repair_desc }}</td>
                            <td>{{ $sendRepairInstance->repair_remark }}</td>
                            <td>
                                @if($sendRepairInstance->is_check ==1)
                                    <span style="color: green">已验收</span>
                                @else
                                    <span style="color: red;">未验收</span>
                                @endif
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
                <p class="lead"></p>
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>总计</th>
                            <td>{{$sendRepair->WithSendRepairInstance->count()}}&nbsp;件</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-6">
            </div>
            <div class="col-xs-6">
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>送修人</th>
                            <td>______________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <th>领取人</th>
                            <td>______________</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row no-print">
            <div class="col-xs-12">
                <a href="{{url('storehouse/sendRepair')}}" class="btn btn-default pull-left btn-flat">
                    <i class="fa fa-arrow-left">&nbsp;</i>返回
                </a>
                <a href="javascript:" onclick="window.print()" class="btn btn-primary pull-right btn-flat">
                    <i class="fa fa-print"></i> 打印
                </a>
            </div>
        </div>
    </section>
@endsection

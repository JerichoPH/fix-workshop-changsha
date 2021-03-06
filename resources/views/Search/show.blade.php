@extends('Layout.index')
@section('style')
    <style>
        @media screen and (max-width: 1920px) {
            #table {
                height: 300px;
            }

            #table td {
                vertical-align: middle;
            }

            #table th {
                vertical-align: middle;
            }

            #map {
                width: 100%;
                height: 300px;
            }

            .maintainOpt {
                width: 300px;
                height: 70px;
                background: #ffffff;
                border: 1px #f4f4f4 solid;
                border-radius: 8px;
                color: #000000;
                font-size: 16px;
            }

            .maintainOpt img {
                width: 32px;
                height: 32px;
            }

            .maintainOpt .maintain-content {
                top: 15px;
                position: relative;
                left: 16px;
            }
        }
    </style>
@endsection
@section('content')
    <!-- 面包屑 -->
    @if(request('is_iframe')!=1)
        <section class="content-header">
            <h1>
                设备履历
                <small></small>
            </h1>
            {{--<ol class="breadcrumb">--}}
            {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
            {{--    <li class="active">设备履历</li>--}}
            {{--</ol>--}}
        </section>
    @endif
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div id="iframe" style="display: none"></div>
            <div class="col-md-4">
                <!--整件和部件-->
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">基础信息</h3>
                        <!--右侧最小化按钮-->
                        @if(request('is_iframe')!=1)
                            <div class="pull-right btn-group btn-group-sm ">
                                <a href="{{ url('entire/instance',$entireInstance->identity_code) }}/edit" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                            </div>
                        @endif
                        <div class="pull-right btn-group btn-group-sm ">
                            {{--<a href="#" onClick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left btn-sm"><i class="fa fa-arrow-left"></i> 返回</a>--}}
                            <a href="#" onclick="javascript:history.back(-1);" class="btn btn-default btn-flat pull-left btn-sm"><i class="fa fa-arrow-left"></i> 返回</a>
                        </div>

                    </div>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>唯一标识：</dt>
                            <dd>{{ @$entireInstance->identity_code ?: '' }}</dd>
                            <dt>所编号：</dt>
                            <dd>{{ @$entireInstance->serial_number ?: '' }}</dd>
                            <dt>厂编号：</dt>
                            <dd>{{ @$entireInstance->factory_device_code ?:'' }}</dd>
                            <dt>设备类型：</dt>
                            <dd>
                                {{ @$entireInstance->category_name ?: '' }}
                                {{ @$entireInstance->EntireModel->name ?: '' }}
                            </dd>
                            <dt>状态：</dt>
                            <dd>{{ @$entireInstance->status }}</dd>
                            <dt>车间：</dt>
                            <dd>{{ @$entireInstance->Station->Parent->name ?: '' }}</dd>
                            <dt>车站：</dt>
                            <dd>{{ @$entireInstance->maintain_station_name ?: '' }}</dd>
                            <dt>安装位置：</dt>
                            <dd>
                                {{ @$entireInstance->InstallPosition->real_name ?: @$entireInstance->maintain_location_code ?: '' }}
                                {{ @$entireInstance->crossroad_number ?: '' }}
                            </dd>
                            @if($entireInstance->category_unique_code == 'S03')
                                <dt>牵引：</dt>
                                <dd>{{ @$entireInstance->traction ?: '无' }}</dd>
                                <dt>开向：</dt>
                                <dd>{{ @$entireInstance->open_direction ?: '无' }}</dd>
                                <dt>线制：</dt>
                                <dd>{{ @$entireInstance->line_name ?: '无' }}</dd>
                                <dt>表示杆特征：</dt>
                                <dd>{{ @$entireInstance->said_rod ?: '无' }}</dd>
                                <dt>道岔类型：</dt>
                                <dd>{{ @$entireInstance->crossroad_type ?: '无' }}</dd>
                                <dt>转辙机分组类型：</dt>
                                <dd>{{ @$entireInstance->point_switch_group_type ?: '无' }}</dd>
                                <dt>防挤压保护罩：</dt>
                                <dd>{{ @$entireInstance->extrusion_protect ? '有' : '无' }}</dd>
                            @endif
                            <dt>相关图纸：</dt>
                            <dd>
                                <a href="javascript:" onclick="modalElectricImages('{{ $entireInstance->Station? $entireInstance->Station->unique_code : '' }}')">电子图纸</a>&nbsp;&nbsp;
                                <a href="javascript:" onclick="alert('暂无');">机柜照片</a>&nbsp;&nbsp;
                                <a href="javascript:" onclick="alert('暂无');">上道照片</a>&nbsp;&nbsp;
                            </dd>
                            <dt>仓库位置：</dt>
                            @if($entireInstance->location_unique_code)
                                <a href="javascript:" onclick="fnLocation(`{{ $entireInstance->identity_code }}`)">
                                    <dd>
                                        <i class="fa fa-location-arrow">
                                            {{ @$entireInstance->WithPosition ? @$entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . @@$entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . @$entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . @$entireInstance->WithPosition->WithTier->WithShelf->name . @$entireInstance->WithPosition->WithTier->name . @$entireInstance->WithPosition->name : '' }}
                                        </i>
                                    </dd>
                                </a>
                            @else
                                <dd>

                                </dd>
                            @endif
                            <dt>供应商：</dt>
                            <dd>{{ $entireInstance->factory_name }}</dd>
                            <dt>出厂日期：</dt>
                            <dd>{{ $entireInstance->made_at ? date('Y-m-d', strtotime($entireInstance->made_at)) : '未填写' }}</dd>
                            <dt>报废日期：</dt>
                            <dd>
                                <span class="{{ $entireInstance->scarping_at ? strtotime($entireInstance->scarping_at) < time() ? 'text-danger' : '' : '' }}">
                                    {{ $entireInstance->scarping_at ? date('Y-m-d', strtotime($entireInstance->scarping_at)) : '无' }}
                                </span>
                            </dd>
                            {{--<dt>出所日期：</dt>--}}
                            {{--<dd>{{ $entireInstance->WarehouseReportByOut ? date('Y-m-d',strtotime($entireInstance->WarehouseReportByOut->updated_at)) : '' }}</dd>--}}
                            <dt>出所日期：</dt>
                            <dd>{{ @$entireInstance->last_out_at ? explode(' ',$entireInstance->last_out_at)[0] : '无' }}</dd>
                            <dt>上道日期：</dt>
                            <dd>{{ $entireInstance->last_installed_time ? date('Y-m-d',$entireInstance->last_installed_time) : ''}}</dd>
                            <dt>入库日期：</dt>
                            <dd>{{ @$entireInstance->warehousein_at ? substr($entireInstance->warehousein_at,0,10) : '无'}}</dd>
                            <dt>检修人：</dt>
                            {{--<dd>{{ @$fixer ?: '无' }}</dd>--}}
                            <dd>{{ @$entireInstance->fixer_name ?: '无' }}</dd>
                            <dt>验收人：</dt>
                            {{--<dd>{{ @$checker ?: '无' }}</dd>--}}
                            <dd>{{ @$entireInstance->checker_name ?: '无' }}</dd>
                            <dt>检修日期：</dt>
                            {{--<dd>{{ @$fixed_at ?: '无' }}</dd>--}}
                            <dd>{{ @$entireInstance->fixed_at ? date('Y-m-d',strtotime($entireInstance->fixed_at)) : '无' }}</dd>
                            <dt>验收日期：</dt>
                            {{--<dd>{{ @$checker_at ?: '无' }}</dd>--}}
                            <dd>{{ @$entireInstance->checked_at ? date('Y-m-d',strtotime($entireInstance->checked_at)) : '无' }}</dd>
                            <dt>下次周期修日期：</dt>
                            @if(@$entireInstance->fix_cycle_value == 0 && @$entireInstance->EntireModel->fix_cycle_value == 0)
                                <dd>状态修设备</dd>
                            @else
                                <dd>{{ @$entireInstance->next_fixing_time ? date('Y-m-d', $entireInstance->next_fixing_time) : '无'}}</dd>
                            @endif
                            <dt>所属设备：</dt>
                            <dd>
                                <a href="{{ url('search/bindDevice', $entireInstance->bind_device_code) }}">
                                    {{ $entireInstance->bind_crossroad_number }}
                                    {{ $entireInstance->bind_device_type_name }}
                                </a>
                            </dd>
                        </dl>
                    </div>
                    {{--@if($entireInstance->PartInstances || $entireInstance->bind_crossroad_number)--}}
                    @if($entireInstance->category_name === '转辙机')
                        <div class="box-footer">
                            <div class="row">
                                <div class="col-md-12">
                                    <h4>部件信息</h4>
                                </div>
                                @if($entireInstance->PartInstances)
                                    @foreach($entireInstance->PartInstances as $partInstance)
                                        <div class="col-md-6">
                                            <div class="box box-solid">
                                                <div class="box-body">
                                                    <div class="form-group form-horizontal">
                                                        <label class="control-label col-md-12" style="text-align: left; font-weight: normal;">唯一编号：<a href="/search/{{ $partInstance->identity_code }}">{{ $partInstance->identity_code ?? '' }}</a></label>
                                                        <label class="control-label col-md-12" style="text-align: left; font-weight: normal;">部件型号：{{ $partInstance->EntireModel->Parent->name ?? '' }} >> {{ $partInstance->EntireModel->name ?? '' }}</label>
                                                        <label class="control-label col-md-12" style="text-align: left; font-weight: normal;">供应商：{{ $partInstance->factory_name ?? '' }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <!--故障记录-->
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">故障记录</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="javascript:" onclick="modalCreateBreakdownLogAsWarehouseIn()" class="btn btn-default btn-flat"><i class="fa fa-plus">&nbsp;</i>入所故障记录</a>
                            <a href="javascript:" onclick="modalCreateBreakdownLogAsStation()" class="btn btn-default btn-flat"><i class="fa fa-plus">&nbsp;</i>现场故障记录</a>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <ul class="timeline">
                            @foreach($breakdownLogsWithMonth as $month => $breakdownLog)
                                <li class="time-label"><span class="bg-red">{{ $month }}</span></li>
                                @foreach(collect($breakdownLogsWithMonth[$month])->sortByDesc('created_at') as $log)
                                    <li>
                                        {{--<i class="fa {{ \App\Model\EntireInstanceLog::$ICONS[$log->type] }} bg-aqua"></i>--}}
                                        <i class="fa bg-aqua"><small><small>{{ date('d', strtotime($log->created_at)) }}日</small></small></i>
                                        <div class="timeline-item">
                                            @if($log->submitted_at != '0000-00-00 00:00:00')
                                                <span class="time"><i class="fa fa-clock-o"></i> {{ $log->submitter_name }}&emsp;{{ $log->submitted_at }}</span>
                                            @endif
                                            {{--<span class="time"><a href="{{ $log->url }}"><i class="fa fa-clock-o"></i> 详情</a></span>--}}
                                            <div class="timeline-body">
                                                @if(mb_strlen($log->type) < 6)
                                                    {{ $log->type }}
                                                    @for($i=0; $i<6-mb_strlen($log->type); $i++)
                                                        {!! '　' !!}
                                                    @endfor
                                                @else
                                                    {{ $log->type }}
                                                @endif
                                                <div style="color: #999;">
                                                    @if($log->crossroad_number)
                                                        {!! '&emsp;&emsp;道岔号：'.$log->crossroad_number.'<br>' !!}
                                                    @endif
                                                    @if($log->BreakdownTypes->isNotEmpty())
                                                        <ol>
                                                            @foreach($log->BreakdownTypes as $breakdown_type)
                                                                <li>{{ $breakdown_type->name }}</li>
                                                            @endforeach
                                                        </ol>
                                                    @endif
                                                    @if($log->explain)
                                                        {!! '&emsp;&emsp;'.$log->explain !!}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                </div>
                <!--最后检测数据-->
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">最后检测记录</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            @if(!empty($lastFixWorkflow))
                                @if($lastFixWorkflow->status == 'FIXED')
                                    <a href="{{url('measurement/fixWorkflow/create')}}?identity_code={{$entireInstanceIdentityCode}}&type=FIX" class="btn btn-default btn-flat">新建检修单</a>
                                @endif
                            @else
                                <a href="{{url('measurement/fixWorkflow/create')}}?identity_code={{$entireInstanceIdentityCode}}&type=FIX" class="btn btn-default btn-flat">新建检修单</a>
                            @endif

                            @if(!empty($entireInstance->fix_workflow_serial_number))
                                <a href="{{url('measurement/fixWorkflow',$entireInstance->fix_workflow_serial_number)}}/edit" class="btn btn-default btn-flat">查看检修单详情</a>
                            @endif

                            @if((@strtoupper($lastFixWorkflowRecodeEntire->check_type) ?: '') == 'PDF')
                                <a class="btn btn-default btn-flat" href="{{ $lastFixWorkflowRecodeEntire->upload_url }}" target="_blank">查看详情</a>
                            @endif
                        </div>
                    </div>
                    <br>
                    @if($lastFixWorkflowRecodeEntire)
                        @switch(strtoupper(@$lastFixWorkflowRecodeEntire->check_type ?? ''))
                            @case('DB')
                            <div class="box-body table-responsive">
                                <table class="table table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>测试整/部件</th>
                                        <th>测试项</th>
                                        <th>标准值</th>
                                        <th>操作</th>
                                        <th>实测值</th>
                                    </tr>
                                    <?php $i = 0;?>
                                    @if(@$lastFixWorkflowRecodeEntire->type['value'] == 'ENTIRE')
                                        @foreach($lastFixWorkflowRecodeEntire->FixWorkflowRecords as $fixWorkflowRecords)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>整件</td>
                                                <td>{{ @$fixWorkflowRecords->Measurement->key }}</td>
                                                <td>{{ @$fixWorkflowRecords->Measurement->allow_explain }}</td>
                                                <td>{{ @$fixWorkflowRecords->Measurement->operation }}</td>
                                                <td><span style="color: {{ @$fixWorkflowRecords->is_allow ? 'green' : 'red' }};">{{ @$fixWorkflowRecords->measured_value. ' '.@$fixWorkflowRecords->Measurement->unit }}</span></td>
                                            </tr>
                                        @endforeach
                                    @else
                                        @foreach($lastFixWorkflowRecodeEntire->FixWorkflowRecords as $fixWorkflowRecords)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>部件</td>
                                                <td>{{ $fixWorkflowRecords->Measurement->key }}</td>
                                                <td>{{ $fixWorkflowRecords->Measurement->allow_min == $fixWorkflowRecords->Measurement->allow_max ? $fixWorkflowRecords->Measurement->allow_min .' ~ ' : '' }}{{ $fixWorkflowRecords->Measurement->allow_max.' '.$fixWorkflowRecords->Measurement->unit }}</td>
                                                <td>{{ $fixWorkflowRecords->Measurement->operation }}</td>
                                                <td><span style="color: {{$fixWorkflowRecords->is_allow ? 'green' : 'red'}};">{{$fixWorkflowRecords->measured_value. ' '.$fixWorkflowRecords->Measurement->unit}}</span></td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    </thead>
                                </table>
                            </div>
                            @break
                            @case('JSON')
                            <div class="box-body table-responsive">
                                <table class="table table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>测试整/部件</th>
                                        <th>测试项</th>
                                        <th>标准值</th>
                                        <th>操作</th>
                                        <th>实测值</th>
                                    </tr>
                                    <?php $i = 0;?>
                                    @if(!empty($check_json_data))
                                        @foreach($check_json_data as $check_item)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>{{ @$check_item['类型'] }}</td>
                                                <td>{{ @$check_item['项目编号'] }}</td>
                                                <td>{{ @$check_item['标准值'] }}</td>
                                                <td></td>
                                                <td><span style="color: {{ @$check_item['判定结论'] == '1' ? 'green' : 'red' }}">{{ @$check_item['测试值'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    </thead>
                                </table>
                            </div>
                            @break
                            @case('JSON2')
                            <div class="box-body table-responsive">
                                <table class="table table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>测试整/部件</th>
                                        <th>测试项</th>
                                        <th>标准值</th>
                                        <th>特性/操作</th>
                                        <th>实测值</th>
                                    </tr>
                                    <?php $i = 0;?>
                                    @if(!empty($check_json_data))
                                        @foreach($check_json_data as $check_item)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>{{ @['ENTIRE'=>'整件','PART'=>'部件'][$check_item['material_type']] ?? '' }}</td>
                                                <td>{{ @$check_item['test_project_name'] ?? '' }}</td>
                                                <td>{{ @$check_item['standard_value'] ?? '' }}{{ @$check_item['unit'] ?? '' }}</td>
                                                <td>{{ @$check_item['operation'] ?? '' }}{{ @$check_item['character'] }}</td>
                                                <td><span style="color: {{ @$check_item['conclusion'] == 1 ? 'green' : 'red' }}">{{ @$check_item['test_value'] }}{{ @$check_item['unit'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    </thead>
                                </table>
                            </div>
                            @break
                            @case('PDF')
                            <iframe src="/js/pdfjs-2.8.335-dist/web/viewer.html?file={{ urlencode($lastFixWorkflowRecodeEntire->upload_url) }}" frameborder="0" style="width: 100%; height: 90vh;"></iframe>
                            @break
                            @default
                            <div class="box-body table-responsive">
                                <table class="table table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>阶段</th>
                                        <th>说明</th>
                                        <th>结果</th>
                                        <th>操作</th>
                                    </tr>
                                    <tr>
                                        <td>{{$lastFixWorkflowRecodeEntire->stage}}</td>
                                        <td>{{$lastFixWorkflowRecodeEntire->auto_explain}}</td>
                                        <td>{!! $lastFixWorkflowRecodeEntire->is_allow ? '<span style="color: green;">合格</span>' : '<span style="color: red;">不合格</span>' !!}</td>
                                        <td><a href="{{ url('measurement/fixWorkflow/downloadCheck',$lastFixWorkflowRecodeEntire->id) }}" target="_blank"><i class="fa fa-download"></i> {{ $lastFixWorkflowRecodeEntire->upload_file_name }}</a></td>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            @break
                        @endswitch
                    @endif
                </div>
                <!--送修记录-->
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">送修记录</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-condensed table-hover table-striped">
                                <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>下载报告</th>
                                    <th>送修备注</th>
                                    <th>故障描述</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if($entireInstance->WithSendRepairInstances)
                                    @foreach($entireInstance->WithSendRepairInstances as $sendRepairInstance)
                                        <tr>
                                            <td><a href="{{url('storehouse/sendRepair' , $sendRepairInstance->send_repair_unique_code)}}">{{ $sendRepairInstance->created_at }}</a></td>
                                            <td>
                                                @if(!empty($sendRepairInstance->repair_report_url))
                                                    @if(is_file(storage_path($sendRepairInstance->repair_report_url)))
                                                        <a href="{{ url('storehouse/sendRepair/downloadSendRepairFile',$sendRepairInstance->id) }}/report" target="_blank"><i class="fa fa-download"></i> 下载报告</a>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{ $sendRepairInstance->repair_remark }}</td>
                                            <td>{{ $sendRepairInstance->repair_desc }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row">
                    {{--备品定位--}}
                    @if(@$entireInstance->status === '上道使用')
                        <div class="col-md-12">
                            <div class="box box-solid">
                                <div class="box-header">
                                    <h3 class="box-title">备品定位</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <table class="table table-bordered table-hover dataTable" id="table">
                                                @if(!empty($entireInstance->maintain_station_name))
                                                    <tr>
                                                        <th>所属车站</th>
                                                        @if($maintain_station_num > 0)
                                                            <td>{{ $entireInstance->maintain_station_name }}</a></td>
                                                            <td><a href="{{ url('entire/instance') }}?category_unique_code={{ $entireInstance->category_unique_code }}&entire_model_unique_code={{ $entire_model_unique_code }}&sub_model_unique_code={{ $entireInstance->model_unique_code }}&factory_name=&scene_workshop_unique_code={{ $scene_workshop_unique_code }}&station_name={{ $entireInstance->maintain_station_name }}&status=INSTALLING&use_made_at=0&date_made_at=&use_created_at=0&date_created_at=&use_next_fixing_day=0&date_next_fixing_day=">{{ $maintain_station_num }}</a></td>
                                                            <td>{{ @$station_distance }}Km</td>
                                                        @else
                                                            <td>{{ $entireInstance->maintain_station_name }}</td>
                                                            <td>{{ $maintain_station_num }}</td>
                                                            <td>{{ @$station_distance }}Km</td>
                                                        @endif
                                                    </tr>
                                                @endif
                                                @if(!empty($entireInstance->maintain_workshop_name))
                                                    <tr>
                                                        <th>所属车间</th>
                                                        @if($maintain_workshop_num > 0)
                                                            <td>{{ $entireInstance->maintain_workshop_name }}</a></td>
                                                            <td><a href="{{ url('entire/instance') }}?category_unique_code={{ $entireInstance->category_unique_code }}&entire_model_unique_code={{ $entire_model_unique_code }}&sub_model_unique_code={{ $entireInstance->model_unique_code }}&factory_name=&scene_workshop_unique_code={{ $scene_workshop_unique_code }}&station_name=&status=INSTALLING&use_made_at=0&date_made_at=&use_created_at=0&date_created_at=&use_next_fixing_day=0&date_next_fixing_day=">{{ $maintain_workshop_num }}</a></td>
                                                            <td>{{ $workshop_distance }}Km</td>
                                                        @else
                                                            <td>{{ $entireInstance->maintain_workshop_name }}</td>
                                                            <td>{{ $maintain_workshop_num }}</td>
                                                            <td>{{ $workshop_distance }}Km</td>
                                                        @endif
                                                    </tr>
                                                @endif
                                                @foreach($stations2 as $k=>$stations)
                                                    <tr>
                                                        @if($k == 0)
                                                            <th rowspan="2">临近车站</th>
                                                        @endif
                                                        @if($stations->maintain_station_num >0)
                                                            <td>{{ $stations->maintains_name }}</td>
                                                            <td><a href="{{ url('entire/instance') }}?category_unique_code={{ $entireInstance->category_unique_code }}&entire_model_unique_code={{ $entire_model_unique_code }}&sub_model_unique_code={{ $entireInstance->model_unique_code }}&factory_name=&scene_workshop_unique_code=&station_name={{ $stations->maintains_name }}&status=INSTALLING&use_made_at=0&date_made_at=&use_created_at=0&date_created_at=&use_next_fixing_day=0&date_next_fixing_day=">{{ $stations->maintain_station_num }}</a></td>
                                                            <td>{{ $stations->distance }}Km</td>
                                                        @else
                                                            <td>{{ $stations->maintains_name }}</td>
                                                            <td>{{ $stations->maintain_station_num }}</td>
                                                            <td>{{ $stations->distance }}Km</td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                                <tr>
                                                    <td></td>
                                                    @if($workshop_num >0)
                                                        <td>{{ $workshop_name }}</td>
                                                        <td>
                                                            @if($stations2)
                                                                <a href="{{ url('entire/instance') .'?'. http_build_query([
                                                                    'category_unique_code'=>$entireInstance->category_unique_code,
                                                                    'entire_model_unique_code'=>$entire_model_unique_code,
                                                                    'sub_model_unique_code'=>$entireInstance->model_unique_code,
                                                                    'factory_name'=>'',
                                                                    'scene_workshop_unique_code'=>'',
                                                                    'station_name'=>$stations2->maintains_name,
                                                                    'status'=>'INSTALLING',
                                                                    'use_made_at'=>0,
                                                                    'date_made_at'=>'',
                                                                    'use_created_at'=>0,
                                                                    'date_created_at'=>'',
                                                                    'use_next_fixing_day'=>0,
                                                                    'date_next_fixing_day'=>'',
                                                                ]) }}">
                                                                    {{ $workshop_num }}
                                                                </a>
                                                            @endif
                                                        </td>
                                                    @else
                                                        <td>{{ $workshop_name }}</td>
                                                        <td>{{ $workshop_num }}</td>
                                                        <td>{{ $distance }}Km</td>
                                                    @endif
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-8">
                                            @if(\Illuminate\Support\Facades\DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->value('lon') && \Illuminate\Support\Facades\DB::table('maintains')->where('name', $entireInstance->maintain_station_name)->value('lat'))
                                                <div id="map"></div>
                                            @else
                                                <div><h3 class="text-center" style="color: #777; verticla-align:middle; line-height: 300px;">该车站没有经纬度信息</h3></div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <!--履历表-->
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">履历表</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="javascript:" class="btn btn-default btn-flat" onclick="modalLogReplenish()">日志补录</a>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <ul class="timeline">
                            @foreach($entireInstanceLogs as $month => $entireInstanceLog)
                                <li class="time-label"><span class="bg-red">{{$month}}</span></li>
                                @foreach(collect($entireInstanceLogs[$month])->sortByDesc('created_at') as $log)
                                    <li>
                                        {{--<i class="fa {{ \App\Model\EntireInstanceLog::$ICONS[$log->type] }} bg-aqua"></i>--}}
                                        <i class="fa bg-aqua"><small><small>{{ date('d', strtotime($log->created_at)) }}日</small></small></i>
                                        <div class="timeline-item">
                                            {{--<span class="time"><i class="fa fa-clock-o"></i> {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$log->created_at)->toDateString() }}</span>--}}
                                            @if($log->url)
                                                <span class="time">
                                                    {{ $log->created_at }}
                                                    <a href="{{ $log->url }}"><i class="fa fa-clock-o"></i> 详情</a>
                                                </span>
                                            @else
                                                <span class="time"></span>
                                            @endif
                                            <div class="timeline-body">
                                                @if(mb_strlen($log->name) < 6)
                                                    {{ $log->name }}
                                                    @for($i=0; $i<6-mb_strlen($log->name); $i++)
                                                        {!! '&emsp;' !!}
                                                    @endfor
                                                @else
                                                    {{ $log->name }}
                                                @endif
                                                {!! @$log->description !!}
                                            </div>
                                            {{--@if($log->description)--}}
                                            {{--<h3 class="timeline-header">{!! $log->url ? '<a target="_blank" href="'.$log->url.'">&nbsp;'. $log->name .'</a>' : $log->name !!}</h3>--}}
                                            {{--<div class="timeline-body">{{ $log->description }}</div>--}}
                                            {{--<div class="timeline-body">--}}
                                            {{--{{ $log->name }}：--}}
                                            {{--{{ $log->description }}--}}
                                            {{--</div>--}}
                                            {{--@else--}}
                                            {{--<h3 class="timeline-header no-border">{!! $log->url ? '<a target="_blank" href="'.$log->url.'">&nbsp;'.$log->name.'</a>' : '<span>'.$log->name.'</span>' !!}</h3>--}}
                                            {{--@endif--}}
                                        </div>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div id="divModalInstall"></div>

        <!--入所故障描述-->
        <div class="modal bs-example-modal-lg fade" id="modalCreateBreakdownLogAsWarehouseIn">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加入所故障记录</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreBreakdownLogAsWarehouseIn">
                            <input type="hidden" name="entire_instance_identity_code" value="{{ $entireInstance->identity_code }}">
                            <input type="hidden" name="submitter_name" value="{{ session('account.nickname') }}">
                            @if($breakdownTypes->isNotEmpty())
                                <div class="form-group">
                                    <label class="col-sm-3 col-md-3 control-label">故障类型</label>
                                    <div class="col-md-9 col-md-8">
                                        @foreach($breakdownTypes as $breakdownType)
                                            <div class="row">
                                                @foreach($breakdownType as $breakdownTypeId => $breakdownTypeName)
                                                    <div class="col-md-4">
                                                        <input type="checkbox" name="breakdown_type_ids[]" id="chkBreakdownTypeId_{{ $breakdownTypeId }}" value="{{ $breakdownTypeId }}">
                                                        <label for="chkBreakdownTypeId_{{ $breakdownTypeId }}">{{ $breakdownTypeName }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">上报人</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="submitter_name" id="selSubmitterName" class="form-control disabled" style="width: 100%;" disabled>
                                        <option value="{{ session('account.nickname') }}">{{ session('account.nickname') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">上报时间</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="submitted_at" class="form-control pull-right" id="dpSubmittedAtAsWarehouseIn" value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">补充</label>
                                <div class="col-sm-9 col-md-8">
                                    <textarea name="explain" id="txaStoreBreakdownLogAsStation" cols="30" rows="5" class="form-control"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreBreakdownLogAsWarehouseIn()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>

        <!--现场故障描述-->
        <div class="modal fade" id="modalCreateBreakdownLogAsStation">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加现场故障记录</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreBreakdownLogAsStation" class="form-horizontal">
                            <input type="hidden" name="entire_instance_identity_code" value="{{ $entireInstance->identity_code }}">
                            <input type="hidden" name="submitter_name" value="{{ session('account.nickname') }}">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">上报人</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="submitter_name" id="selSubmitterName" class="form-control disabled" style="width: 100%;" disabled>
                                        <option value="{{ session('account.nickname') }}">{{ session('account.nickname') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">上报时间</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="submitted_at" class="form-control pull-right" id="dpSubmittedAtAsStation" value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">道岔号</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="crossroad_number" id="txtCrossroadNumber" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">描述</label>
                                <div class="col-sm-9 col-md-8">
                                    <textarea name="explain" id="txaStoreBreakdownLogAsStation" cols="30" rows="5" class="form-control"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreBreakdownLogAsStation()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>

        <!--日志补录-->
        <div class="modal fade" id="modalLogReplenish">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">日志补录</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreBreakdownLogAsStation" class="form-horizontal">
                            <input type="hidden" name="submitter_name" value="{{ session('account.nickname') }}">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">日志类型</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="log_type" id="selLogType" class="form-control select2" style="width:100%;">
                                        <option value="设备出厂">设备出厂</option>
                                        <option value="设备入所">设备入所</option>
                                        <option value="设备出所">设备出所</option>
                                        <option value="设备入库">设备入库</option>
                                        <option value="开始检修">开始检修</option>
                                        <option value="检修完成">检修完成</option>
                                        <option value="验收完成">验收完成</option>
                                        <option value="设备上道">设备上道</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">上报时间</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="submitted_at" class="form-control pull-right" id="dpLogReplenishSubmittedAt" value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">日志内容</label>
                                <div class="col-sm-9 col-md-8">
                                    <textarea name="log_description" id="txaLogDescription" cols="30" rows="10"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreLogReplenish()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>

        <!--仓库图片弹窗-->
        <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" id="locationShow">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">位置：<span id="title"></span></h4>
                    </div>
                    <div class="modal-body">
                        <img id="location_img" class="model-body-location" alt="" style="width: 100%;">
                        <div class="spot"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="section">
        <!--现场检修图片模态框-->
        <div class="modal fade" id="modalShowTaskStationEntireInstanceImages">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">现场工作图片</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        {{--<div id="carouselShowTaskStationCheckEntireInstanceImages" class="carousel slide" data-ride="carousel">--}}
                        <div id="carouselShowTaskStationCheckEntireInstanceImages" class="carousel slide"></div>
                    </div>
                    {{--<div class="modal-footer">--}}
                    {{--    <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>--}}
                    {{--    <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnShowTaskStationEntireInstanceImages()"><i class="fa fa-check">&nbsp;</i>确定</button>--}}
                    {{--</div>--}}
                </div>
            </div>
        </div>
        <!--下载电子图纸模态框-->
        <div class="modal fade" id="modalStationElectricImages">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">电子图纸列表</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <table class="table table-condensed table-hover">
                            <thead>
                            <tr>
                                <th>名称</th>
                                <th>下载</th>
                            </tr>
                            </thead>
                            <tbody id="tbody_modalStationElectricImages"></tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/baiduOffline/js/mp_load.js"></script>
    <script type="text/javascript" src="/baiduOffline/js/bmap_offline_api_v3.0_min.js"></script>
    <script type="text/javascript" src="/baiduOffline/js/InfoBox_min.js"></script>
    <script type="text/javascript" src="/baiduOffline/js/Heatmap_min.js"></script>
    <script>

        let $btnEditInWarehouseBreakdown = $('#btnEditInWarehouseBreakdown');
        let $btnUpdateInWarehouseBreakdown = $('#btnUpdateInWarehouseBreakdown');
        let $btnCancelInWarehouseBreakdown = $('#btnCancelInWarehouseBreakdown');
        let $txaInWarehouseBreakdownExplain = $('#txaInWarehouseBreakdownExplain');
        let $modalShowTaskStationEntireInstanceImages = $('#modalShowTaskStationEntireInstanceImages');
        let $carouselShowTaskStationCheckEntireInstanceImages = $('#carouselShowTaskStationCheckEntireInstanceImages');
        let $modalStationElectricImages = $('#modalStationElectricImages');
        let $tbody_modalStationElectricImages = $('#tbody_modalStationElectricImages');

        let infoBoxTemp = null;

        /**
         * 打开电子图纸模态框
         * @param {string} maintainStationUniqueCode
         */
        function modalElectricImages(maintainStationUniqueCode = '') {
            if (maintainStationUniqueCode === '') {
                alert('该设备没有车站信息');
                return;
            }
            $.ajax({
                url: `{{ url('stationElectricImage') }}/${maintainStationUniqueCode}`,
                type: 'GET',
                async: false,
                success: function (res) {
                    console.log(`{{ url('stationElectricImage') }}/${maintainStationUniqueCode} success:`, res);
                    let {station_electric_images: stationElectricImages} = res.data;

                    let html = '';
                    if (stationElectricImages.length === 0) {
                        alert('该车站没有上传电子图纸');
                        return;
                    }
                    stationElectricImages.map(function (item) {
                        html += `<tr>`;
                        html += `<td>${item['original_filename']}</td>`;
                        html += `<td><a class="btn btn-default btn-flat btn-sm" onclick="fnDownloadElectricImage('${item['filename']}')"><i class="fa fa-download"></i></a></td>`;
                        html += `</tr>`;
                    });

                    $tbody_modalStationElectricImages.html(html);
                    $modalStationElectricImages.modal('show');
                },
                fail: function (err) {
                    console.log(`{{ url('stationElectricImage') }}/${maintainStationUniqueCode} fail:`, err);
                    if (error.responseText === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 下载电子图纸
         * @param {string} filename
         */
        function fnDownloadElectricImage(filename) {
            open(filename, '_blank');
        }

        /**
         * 百度地图
         */
        function fnMap() {
            //配置
            let map = new BMap.Map("map", {
                minZoom: 2,
                maxZoom: 12,
                enableMapClick: false
            });
            let overlayData = JSON.parse('{!! $data_as_json !!}')
            let points = Object.values(overlayData).flat()
            points.map(a => {
                if (!a['name']) {
                    a.name = a.maintains_name
                }
            })
            let point = new BMap.Point(points[0]['lon'], points[0]['lat']);
            map.centerAndZoom(point, 8);
            map.enableScrollWheelZoom(true);   //启用滚轮放大缩小，默认禁用
            map.enableContinuousZoom(true);    //启用地图惯性拖拽，默认禁用

            points.map(a => {
                addOverlay(map, a['lon'], a['lat'], "/images/tuding-red25.png", 32, 32, a)
            })
            {{--let standbyPoints = JSON.parse(`{!! $standbyPoints !!}`);--}}
            {{--let currentPoint = JSON.parse(`{!! $currentPoint !!}`);--}}
            {{--$.each(standbyPoints, function (unique_code, points) {--}}
            {{--    // 图钉--}}
            {{--    addOverlay(map, points['lon'], points['lat'], "/images/tuding-red25.png", 32, 32, unique_code, points);--}}
            {{--    // api算距离--}}
            {{--    if (currentPoint['lon'] !== '' && currentPoint['lat'] !== '') {--}}
            {{--        if (currentPoint['unique_code'] !== unique_code) {--}}
            {{--            if (points['lon'] !== '' && points['lat'] !== '') {--}}
            {{--                $('#' + unique_code).text((map.getDistance(new BMap.Point(currentPoint['lon'], currentPoint['lat']), new BMap.Point(points['lon'], points['lat'])) / 1000).toFixed(3) + ' KM');--}}
            {{--            }--}}
            {{--        }--}}
            {{--    }--}}
            {{--});--}}
            //鼠标点击关闭弹框
            map.addEventListener("mousedown", function (e) {
                if (infoBoxTemp) {
                    infoBoxTemp.close();
                }
            });
        }

        /**
         * 加载点
         */
        function addOverlay(map, lon, lat, icon, iconLength, iconWidth, points) {
            let point = new BMap.Point(lon, lat);
            let marker = null;
            if (icon != null) {
                let mapIcon = new BMap.Icon(icon, new BMap.Size(iconLength, iconWidth));
                marker = new BMap.Marker(point, {icon: mapIcon});
            } else {
                marker = new BMap.Marker(point);
            }
            map.addOverlay(marker);

            let opts = {
                boxClass: "maintainOpt",
                closeIconMargin: "1px 1px 0 0",
                align: INFOBOX_AT_TOP,
                closeIconUrl: '/images/close-bai.png'
            }
            let name = points['name'];
            if (points['parent_unique_code'] !== '') {
                if (name.indexOf("站") === -1) {
                    name += '站';
                }
            }
            let content = `<div class="maintain-content"><b>${name}</b><br>`;
            if (points['contact'] !== '') {
                content += `联系人:${points['contact'] ? points['contact'] : ''}`;
            }
            if (points['contact_phone'] !== '') {
                content += `&nbsp;&nbsp;&nbsp;电话:${points['contact_phone'] ? points['contact_phone'] : ''}`;
            }
            content += `</div>`;
            marker.addEventListener("click", function () {
                let infoWindow = new BMapLib.InfoBox(map, content, opts);
                if (infoBoxTemp) {
                    infoBoxTemp.close();
                }
                infoBoxTemp = infoWindow;
                infoBoxTemp.open(marker);
            });
        }

        $(function () {
            if (document.getElementById('map')) fnMap();

            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });

            // 上报故障日期
            let datepickerOption = {
                autoclose: true,
                todayHighlight: true,
                language: "cn",
                format: "yyyy-mm-dd",
                beforeShowDay: $.noop,
                calendarWeeks: false,
                clearBtn: true,
                daysOfWeekDisabled: [],
                endDate: Infinity,
                forceParse: true,
                keyboardNavigation: true,
                minViewMode: 0,
                orientation: "auto",
                rtl: false,
                startDate: -Infinity,
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#dpSubmittedAtAsStation').datepicker(datepickerOption);
            $('#dpSubmittedAtAsWarehouseIn').datepicker(datepickerOption);
            $('#dpLogReplenishSubmittedAt').datepicker(datepickerOption);

            // 初始化 ckeditor
            CKEDITOR.replace('txaLogDescription', {
                toolbar: [
                    // {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                    // {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']},
                    // {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                    // {name: 'colors', items: ['TextColor', 'BGColor']},
                    // {name: 'tools', items: ['Maximize', 'ShowBlocks']}
                ]
            });
        });

        /**
         * 查找位置
         * @param identity_code
         */
        function fnLocation(identity_code) {
            $.ajax({
                url: `{{url('storehouse/location/getImg')}}/${identity_code}`,
                type: 'get',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        console.log(response);
                        $('#title').text(response.data.location_full_name);
                        let location_img = response.data.location_img;
                        if (location_img) {
                            document.getElementById('location_img').src = location_img;
                            $("#locationShow").modal("show");
                        } else {
                            alert('请联系管理员，绑定位置图片');
                            // location.reload();
                        }
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    // location.reload();
                }
            });
        }

        /**
         * 打开安装出所窗口
         * @param {string} fixWorkflowSerialNumber 工单流水号
         */
        function fnCreateInstall(fixWorkflowSerialNumber) {
            $.ajax({
                url: `{{ url('measurement/fixWorkflow/install') }}`,
                type: "get",
                data: {fixWorkflowSerialNumber},
                async: false,
                success: function (response) {
                    $("#divModalInstall").html(response);
                    $("#modalInstall").modal("show");
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 打开安装窗口（没有检修单）
         * @param {string} entireInstanceIdentityCode 设备唯一编号
         */
        function fnCreateInstallWithOutFixWorkflow(entireInstanceIdentityCode) {
            $.ajax({
                url: `{{ url('entire/instance/install') }}`,
                type: "get",
                data: {entireInstanceIdentityCode},
                async: false,
                success: function (response) {
                    $("#divModalInstall").html(response);
                    $("#modalInstall").modal("show");
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 报废整件
         * @param {string} identityCode 整件身份码
         */
        function fnScrapEntireInstance(identityCode) {
            $.ajax({
                url: "{{ url('entire/instance/scrap') }}/" + identityCode,
                type: "post",
                data: {},
                async: true,
                success: function (response) {
                    alert(response);
                    location.href = "{{ url('entire/instance') }}";
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 打开入所故障描述窗口
         */
        function modalCreateBreakdownLogAsWarehouseIn() {
            $('#modalCreateBreakdownLogAsWarehouseIn').modal('show');
        }

        /**
         * 打开现场故障描述窗口
         */
        function modalCreateBreakdownLogAsStation() {
            $('#modalCreateBreakdownLogAsStation').modal('show');
        }

        /**
         * 添加入所故障记录
         */
        function fnStoreBreakdownLogAsWarehouseIn() {
            $.ajax({
                url: `{{ url('breakdownLog') }}?type=WAREHOUSE_IN`,
                type: 'post',
                data: $('#frmStoreBreakdownLogAsWarehouseIn').serialize(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('breakdownLog') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('breakdownLog') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    if (err['responseJSON']['message'].constructor === Object) {
                        let message = '';
                        for (let msg of err['responseJSON']['message']) message += `${msg}\r\n`;
                        alert(message);
                        return;
                    }
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 添加现场故障记录
         */
        function fnStoreBreakdownLogAsStation() {
            $.ajax({
                url: `{{ url('breakdownLog') }}?type=STATION`,
                type: 'post',
                data: $('#frmStoreBreakdownLogAsStation').serialize(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('breakdownLog') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('breakdownLog') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    if (err['responseJSON']['message'].constructor === Object) {
                        let message = '';
                        for (let msg of err['responseJSON']['message']) message += `${msg}\r\n`;
                        alert(message);
                        return;
                    }
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 打开补录日志窗口
         */
        function modalLogReplenish() {
            $('#modalLogReplenish').modal('show');
        }

        /**
         * 补录日志
         */
        function fnStoreLogReplenish() {
            let data = {
                type: $('#selLogType').val(),
                description: CKEDITOR.instances['txaLogDescription'].getData(),
                submitted_at: $('#dpLogReplenishSubmittedAt').val(),
            };

            $.ajax({
                url: `{{ url('entire/log/log',$entireInstance->identity_code) }}`,
                type: 'post',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/log') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('entire/log') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 显示现场检修任务图片
         * @param {int} taskStationCheckEntireInstanceId
         */
        function fnShowTaskStationCheckEntireInstanceImages(taskStationCheckEntireInstanceId = 0) {
            $.ajax({
                url: `{{ url('taskStationCheckEntireInstance') }}/${taskStationCheckEntireInstanceId}/images`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('taskStationCheckEntireInstance') }}/${taskStationCheckEntireInstanceId}/images success:`, res);
                    let {images} = res.data;
                    let htmlOl = '<ol class="carousel-indicators">';
                    let htmlItem = '<div class="carousel-inner">';
                    let htmlOlCount = 0;
                    images.map((item, idx) => {
                        htmlOl += `<li data-target="#carouselShowTaskStationCheckEntireInstanceImages" data-slide-to="${htmlOlCount}" class="${htmlOlCount === 0 ? 'active' : ''}"></li>`;
                        htmlOlCount++;

                        htmlItem += `
                        <div class="item ${htmlOlCount === 1 ? 'active' : ''}">
                            <img src="${item}" alt="${htmlOlCount}">
                            <div class="carousel-caption"></div>
                        </div>`;
                    });
                    htmlOl += `</ol>`;
                    htmlItem += `</div></div>`;
                    $carouselShowTaskStationCheckEntireInstanceImages.html(`
                    ${htmlOl}
                    ${htmlItem}
                    <a class="left carousel-control" href="#carouselShowTaskStationCheckEntireInstanceImages" data-slide="prev">
                        <span class="fa fa-angle-left"></span>
                    </a>
                    <a class="right carousel-control" href="#carouselShowTaskStationCheckEntireInstanceImages" data-slide="next">
                        <span class="fa fa-angle-right"></span>
                    </a>
                    `);
                    $modalShowTaskStationEntireInstanceImages.modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('taskStationCheckEntireInstance') }}/${taskStationCheckEntireInstanceId}/images fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }


    </script>
@endsection


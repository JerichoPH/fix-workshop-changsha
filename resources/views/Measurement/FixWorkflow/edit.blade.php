@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修单管理
            <small>详情</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li><a href="{{url('measurement/fixWorkflow')}}"><i class="fa fa-users">&nbsp;</i>检修单管理</a></li>--}}
        {{--    <li class="active">详情</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content-header">
        <div class="btn-group btn-group-sm">
            {{--<a href="{{url('measurement/fixWorkflow')}}?page={{request('page',1)}}" class="btn btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
            @if($fixWorkflow->prototype('status') != 'RETURN_FACTORY')
                @if(count($part_models))
                    <a href="javascript:" onclick="fnCreateFixWorkflowProcess('{{$fixWorkflow->serial_number}}','PART','{{$fixWorkflow->type}}')" class="btn btn-default btn-flat">部件检测</a>
                @endif
                <a href="javascript:" onclick="fnCreateFixWorkflowProcess('{{$fixWorkflow->serial_number}}','ENTIRE','{{$fixWorkflow->type}}')" class="btn btn-default btn-flat"><i class="fa fa-wrench"></i> 整件检测</a>
            @endif
            @if($fixWorkflow->prototype('status') == 'FIXED' && !(array_flip(\App\Model\EntireInstance::$STATUSES)[$fixWorkflow->EntireInstance->status] == 'INSTALLING' || array_flip(\App\Model\EntireInstance::$STATUSES)[$fixWorkflow->EntireInstance->status] == 'INSTALLED'))
                {{--<a href="javascript:" class="btn btn-default btn-flat" onclick="fnCreateInstall('{{$fixWorkflow->serial_number}}')">安装出所</a>--}}
            @elseif($fixWorkflow->prototype('status') == 'RETURN_FACTORY')
                {{--<a href="javascript:" class="btn btn-default btn-flat" onclick="fnCreateFactoryReturn('{{$fixWorkflow->serial_number}}')">返厂入所</a>--}}
            @elseif(array_flip(\App\Model\EntireInstance::$STATUSES)[$fixWorkflow->EntireInstance->status] == 'INSTALLED' || array_flip(\App\Model\EntireInstance::$STATUSES)[$fixWorkflow->EntireInstance->status] == 'INSTALLING')
                <a href="{{url('warehouse/report',$fixWorkflow->EntireInstance->last_warehouse_report_serial_number_by_out)}}" class="btn btn-default btn-flat">已安装</a>
            @else
                {{--<a href="javascript:" class="btn btn-default btn-flat" onclick="fnCreateReturnFactory('{{$fixWorkflow->serial_number}}')">返厂维修</a>--}}
            @endif
            @if(count($part_models))
                {{--<a href="javascript:" class="btn btn-default btn-flat" onclick="fnModalChangePartInstance()">部件更换管理</a>--}}
            @endif
            {{--<a href="javascript:" class="btn btn-danger btn-flat" onclick="fnScrapEntireInstance('{{$fixWorkflow->EntireInstance->identity_code}}')">报废</a>--}}
            <a href="javascript:" onclick="fnModalUploadCheck(`{{$fixWorkflow->serial_number}}`,`{{$fixWorkflow->entire_instance_identity_code}}`)" class="btn btn-default btn-flat"><i class="fa fa-wrench"></i> 上传检测</a>
        </div>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs pull-right">
                        {{--<li class="{{request('type') == 'BREAKDOWN_TYPE' ? 'active' : ''}}"><a href="#tabBreakdownType" data-toggle="tab">故障类型</a></li>--}}
                        <li class="{{request('type') == 'ENTIRE' ? 'active' : ''}}"><a href="#tabFixWorkflowProcessEntire" data-toggle="tab">整件检测</a></li>
                        @if(count($part_models))
                            <li class="{{request('type') == 'PART' ? 'active' : ''}}"><a href="#tabFixWorkflowProcessPart" data-toggle="tab">部件检测</a></li>
                        @endif
                        <li class="{{!in_array(request('type'),['ENTIRE','PART','BREAKDOWN_TYPE']) ? 'active' : ''}}"><a href="#tabFixWorkflowInfo" data-toggle="tab">检测单信息</a></li>
                        <li class="pull-left header"><i class="fa fa-wrench"></i> 检测单</li>
                    </ul>
                    <div class="tab-content">
                        <!--检修单基本信息-->
                        <div class="tab-pane {{request('type') != 'ENTIRE' && request('type') != 'PART' ? 'active' : ''}}" id="tabFixWorkflowInfo">
                            <div class="table-responsive">
                                <h3>
                                    <small><i class="fa fa-cog">&nbsp;</i>基础数据</small>
                                </h3>
                                <table class="table table-hover table-condensed">
                                    <tbody>
                                    @if($fixWorkflow->EntireInstance->serial_number)
                                        <tr>
                                            <td style="width: 25%; text-align: right;"><b>所编号：</b></td>
                                            <td>{{ $fixWorkflow->EntireInstance?$fixWorkflow->EntireInstance->serial_number : '' }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>唯一编号</b></td>
                                        <td><a href="{{ url('search',$fixWorkflow->EntireInstance->identity_code) }}">{{ $fixWorkflow->EntireInstance->identity_code }}</a></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>设备类型：</b></td>
                                        <td>{{ @$fixWorkflow->EntireInstance->EntireModel->Category->name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>设备型号：</b></td>
                                        <td>{{ @$fixWorkflow->EntireInstance->EntireModel->name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>设备状态：</b></td>
                                        <td>{{ @$fixWorkflow->EntireInstance->status }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>供应商：</b></td>
                                        <td>{{ @$fixWorkflow->EntireInstance->factory_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>厂编号：</b></td>
                                        <td>{{ @$fixWorkflow->EntireInstance->factory_device_code }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>检修单流水号：</b></td>
                                        <td>{{ @$fixWorkflow->serial_number }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>检修单生成于：</b></td>
                                        <td>{{ @$fixWorkflow->created_at }}</td>
                                    </tr>
                                    @if(in_array(array_flip(\App\Model\FixWorkflow::$STATUS)[$fixWorkflow->status],['CHECKED','WORKSHOP','SECTION']))
                                        <tr>
                                            <td style="width: 25%; text-align: right;"><b>检修单验收于：</b></td>
                                            <td>{{ @$fixWorkflow->updated_at }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>状态：</b></td>
                                        <td>{{ @$fixWorkflow->status }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>阶段：</b></td>
                                        <td>{{ @$fixWorkflow->stage }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>类型：</b></td>
                                        <td>{{ @$fixWorkflow->type }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>检修人：</b></td>
                                        <td>{{ @$last_fixer->Processor ? $last_fixer->Processor->nickname : '' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width: 25%; text-align: right;"><b>验收人：</b></td>
                                        <td>{{ @$last_checker->Processor ? $last_checker->Processor->nickname : '' }}</td>
                                    </tr>
                                    {{--<tr>--}}
                                    {{--    <td style="width: 25%; text-align: right;"><b>抽验人：</b></td>--}}
                                    {{--    <td>{{ @$last_cy->Processor ? $last_cu->Processor->nickname : '' }}</td>--}}
                                    {{--</tr>--}}
                                    </tbody>
                                </table>
                            </div>
                            <form id="frmUpdateFixWorkflowNote">
                                <h3>
                                    <small><i class="fa fa-pencil">&nbsp;</i>备注：</small>
                                </h3>
                                <div class="form-group">
                                    <textarea name="note" cols="30" rows="3" class="form-control">{{ @$fixWorkflow->note }}</textarea>
                                </div>
                                <div class="form-group">
                                    <a href="javascript:" class="btn btn-warning btn-flat" onclick="fnUpdateFixWorkflowNote()"><i class="fa fa-check">&nbsp;</i>保存备注</a>
                                </div>
                            </form>
                            @if(count($part_models))
                                <div class="table-responsive">
                                    <h3>
                                        <small><i class="fa fa-cubes">&nbsp;</i>部件数据</small>
                                        <small>
                                            <a
                                                href="javascript:"
                                                onclick="fnModalAddPartInstance('{{ @$fixWorkflow->entire_instance_identity_code }}')"
                                                class="pull-right">
                                                <i class="fa fa-plus">&nbsp;</i>
                                                新增部件
                                            </a>
                                        </small>
                                    </h3>

                                    <table class="table table-hover table-condensed">
                                        <thead>
                                        <tr>
                                            <th>编号</th>
                                            <th>型号</th>
                                            <th>种类</th>
                                            <th>厂编号</th>
                                            <th>操作</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($fixWorkflow->EntireInstance->PartInstances as $partInstance)
                                            <tr>
                                                <td>{{ @$partInstance->identity_code }}</td>
                                                <td>{{ @$partInstance->PartModel->name }}</td>
                                                <td>{{ @$partInstance->PartCategory->name }}</td>
                                                <td>{{ @$partInstance->factory_device_code }}</td>
                                                <td>
                                                    <a href="javascript:" onclick="fnModalChangePartInstance('{{ @$partInstance->identity_code }}','{{ @$partInstance->PartModel->unique_code }}','{{ @$partInstance->part_category_id }}')">更换部件</a>
                                                    <a href="javascript:" onclick="fnUninstallPartInstance('{{ @$partInstance->identity_code }}')"><span class="text-danger">拆卸</span></a>
                                                    {{--<a href="javascript:" onclick="fnScrapPartInstance('{{ $partInstance->identity_code }}')" class="text-danger">报废</a>--}}
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                        <!--部件检测-->
                        @if(count($part_models))
                            <div class="tab-pane table-responsive {{request('type') == 'PART' ? 'active' : ''}}" id="tabFixWorkflowProcessPart">
                                {{--<a href="javascript:" onclick="fnCreateFixWorkflowProcess('{{$fixWorkflow->serial_number}}','PART')" class="btn btn-default btn-flat pull-right btn-lg">添加</a>--}}
                                <table class="table table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>阶段</th>
                                        <th>说明</th>
                                        <th>结果</th>
                                        <th>操作</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($fixWorkflowProcesses_part as $fixWorkflowProcess)
                                        <tr>
                                            <td>{{$fixWorkflowProcess->stage}}</td>
                                            <td>{{$fixWorkflowProcess->auto_explain}}</td>
                                            <td>{!! $fixWorkflowProcess->is_allow ? '<span style="color: green;">合格</span>' : '<span style="color: red;">不合格</span>' !!}</td>
                                            <td>
                                                <div class="btn-group">
                                                    @if($fixWorkflowProcess->check_type == 'DB')
                                                        <a href="{{url('measurement/fixWorkflowProcess',$fixWorkflowProcess->serial_number)}}/edit?fixWorkflowSerialNumber={{$fixWorkflow->serial_number}}&type=PART&page={{request('page',1)}}" class="btn btn-flat btn-primary">改</a>
                                                    @endif
                                                    <a href="javascript:" onclick="fnDeleteFixWorkflowProcess('{{$fixWorkflowProcess->serial_number}}')" class="btn btn-flat btn-danger">删</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                    @endif
                    <!--整件检测-->
                        <div class="tab-pane table-responsive {{request('type') == 'ENTIRE' ? 'active' : ''}}" id="tabFixWorkflowProcessEntire">
                            <table class="table table-hover table-condensed">
                                <thead>
                                <tr>
                                    <th>阶段</th>
                                    <th>说明</th>
                                    <th>结果</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($fixWorkflowProcesses_entire as $fixWorkflowProcess)
                                    <tr>
                                        <td>{{$fixWorkflowProcess->stage}}</td>
                                        <td>{{$fixWorkflowProcess->auto_explain}}</td>
                                        <td>{!! $fixWorkflowProcess->is_allow ? '<span style="color: green;">合格</span>' : '<span style="color: red;">不合格</span>' !!}</td>
                                        <td>
                                            <div class="btn-group">
                                                @if($fixWorkflowProcess->check_type == 'DB')
                                                    <a href="{{url('measurement/fixWorkflowProcess',$fixWorkflowProcess->serial_number)}}/edit?fixWorkflowSerialNumber={{$fixWorkflow->serial_number}}&type=ENTIRE&page={{request('page',1)}}" class="btn btn-primary btn-flat">改</a>
                                                @endif
                                                <a href="javascript:" onclick="fnDeleteFixWorkflowProcess('{{$fixWorkflowProcess->serial_number}}')" class="btn btn-danger btn-flat">删</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane table-responsive {{request('type') == 'BREAKDOWN_TYPE' ? 'active' : ''}}" id="tabBreakdownType">
                            <!--入所故障描述-->
                            <h3>
                                <small><i class="fa fa-pencil">&nbsp;</i>入所故障描述：</small>
                            </h3>
                            <div class="pull-right btn-group btn-group-sm">
                                <a href="javascript:" id="btnEditInWarehouseBreakdown" class="btn btn-flat btn-warning" onclick="fnEditInWarehouseBreakdown()">编辑</a>
                                <a href="javascript:" style="display: none;" id="btnUpdateInWarehouseBreakdown" class="btn btn-flat btn-success" onclick="fnUpdateInWarehouseBreakdown('{{ $fixWorkflow->entire_instance_identity_code }}')">保存</a>
                                <a href="javascript:" style="display: none;" id="btnCancelInWarehouseBreakdown" class="btn btn-flat btn-default" onclick="fnCancelInWarehouseBreakdown()">取消</a>
                            </div>
                            <textarea class="form-control" name="in_warehouse_breakdown_explain" id="txaInWarehouseBreakdownExplain" cols="30" rows="5" disabled>{{ $fixWorkflow->EntireInstance->in_warehouse_breakdown_explain }}</textarea>

                            <!--故障类型-->
                            {{--<form id="frmUpdateBreakdownType">--}}
                            {{--<h3>--}}
                            {{--<small><i class="fa fa-pencil">&nbsp;</i>故障类型：</small>--}}
                            {{--</h3>--}}
                            {{--<div class="pull-right btn-group btn-group-sm">--}}
                            {{--<a href="javascript:" id="btnEditBreakdownType" class="btn btn-flat btn-warning" onclick="fnEditBreakdownType()">编辑</a>--}}
                            {{--<a href="javascript:" id="btnUpdateBreakdownType" style="display: none;" class="btn btn-success btn-flat" onclick="fnUpdateBreakdownType()"><i class="fa fa-check">&nbsp;</i>保存故障类型</a>--}}
                            {{--<a href="javascript:" id="btnCancelBreakdownType" style="display: none;" class="btn btn-flat btn-default" onclick="fnCancelBreakdownType()">取消</a>--}}
                            {{--</div>--}}
                            {{--<div id="divShowBreakdownType">--}}
                            {{--<ol>--}}
                            {{--@foreach($breakdownTypes as $chunk)--}}
                            {{--@foreach($chunk as $id => $name)--}}
                            {{--@if(in_array($id, $breakdownTypeIds))--}}
                            {{--<li>{{ $name }}</li>--}}
                            {{--@endif--}}
                            {{--@endforeach--}}
                            {{--@endforeach--}}
                            {{--</ol>--}}
                            {{--</div>--}}
                            {{--<div style="display: none;" id="divChkBreakdownType">--}}
                            {{--<div class="form-group">--}}
                            {{--<table>--}}
                            {{--@foreach($breakdownTypes as $chunk)--}}
                            {{--<tr>--}}
                            {{--@foreach($chunk as $id=>$name)--}}
                            {{--<td>&nbsp;&nbsp;&nbsp;&nbsp;<label style="font-weight: normal;"><input type="checkbox" name="breakdown_type[]" value="{{$id}}" {{in_array($id,$breakdownTypeIds) ? 'checked' : ''}}>{{$name}}</label>&nbsp;&nbsp;&nbsp;&nbsp;</td>--}}
                            {{--@endforeach--}}
                            {{--</tr>--}}
                            {{--@endforeach--}}
                            {{--</table>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</form>--}}
                        </div>
                    </div>
                </div>
            </div>

            <!--标准参照-->
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3>最后检测结果</h3>
                    </div>
                    @if($lastFixWorkflowRecodeEntire)
                        @switch($lastFixWorkflowRecodeEntire->check_type)
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
                                                <td>{{ @$fixWorkflowRecords->Measurement->key }}</td>
                                                <td>{{ @$fixWorkflowRecords->Measurement->allow_min == $fixWorkflowRecords->Measurement->allow_max ? $fixWorkflowRecords->Measurement->allow_min .' ~ ' : '' }}{{ $fixWorkflowRecords->Measurement->allow_max.' '.$fixWorkflowRecords->Measurement->unit }}</td>
                                                <td>{{ @$fixWorkflowRecords->Measurement->operation }}</td>
                                                <td><span style="color: {{ @$fixWorkflowRecords->is_allow ? 'green' : 'red' }};">{{ @$fixWorkflowRecords->measured_value. ' '.@$fixWorkflowRecords->Measurement->unit }}</span></td>
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
            </div>
        </div>
    </section>
    <section class="content">
        <div id="divModalInstall"></div>
        <div id="divModalForceInstall"></div>
        <div id="divModalFixWorkflowInOnce"></div>
        <div id="divModalReturnFactory"></div>
        <div id="divModalFactoryReturn"></div>
        <div id="divModalCreateFixWorkflowProcess"></div>
        <div id="divModalUploadCheck"></div>
    </section>
@endsection
@section('script')
    <script>
        $txaInWarehouseBreakdownExplain = $('#txaInWarehouseBreakdownExplain');
        $btnEditInWarehouseBreakdown = $('#btnEditInWarehouseBreakdown');
        $btnUpdateInWarehouseBreakdown = $('#btnUpdateInWarehouseBreakdown');
        $btnCancelInWarehouseBreakdown = $('#btnCancelInWarehouseBreakdown');
        $btnEditBreakdownType = $('#btnEditBreakdownType');
        $btnUpdateBreakdownType = $('#btnUpdateBreakdownType');
        $btnCancelBreakdownType = $('#btnCancelBreakdownType');
        $divShowBreakdownType = $('#divShowBreakdownType');
        $divChkBreakdownType = $('#divChkBreakdownType');

        /**
         * 上传检测文件
         */
        function fnModalUploadCheck(fixWorkflowSerialNumber, entireInstanceIdentityCode) {
            $.ajax({
                url: "{{url('measurement/fixWorkflow/uploadCheck')}}",
                type: "get",
                data: {
                    fixWorkflowSerialNumber: fixWorkflowSerialNumber,
                    entireInstanceIdentityCode: entireInstanceIdentityCode,
                },
                async: true,
                success: function (response) {
                    $("#divModalUploadCheck").html(response);
                    $("#modalUploadCheck").modal("show");
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }


        /**
         * 打开安装出所窗口
         * @param {string} fixWorkflowSerialNumber 检修单流水号
         */
        function fnCreateInstall(fixWorkflowSerialNumber) {
            $.ajax({
                url: "{{url('measurement/fixWorkflow/install')}}",
                type: "get",
                data: {fixWorkflowSerialNumber: fixWorkflowSerialNumber},
                async: false,
                success: function (response) {
                    // console.log(response);
                    // return null;
                    $("#divModalInstall").html(response);
                    $("#modalInstall").modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 打开强制出所页面
         */
        function fnModalForceInstall(fixWorkflowSerialNumber) {
            $.ajax({
                url: "{{url('measurement/fixWorkflow/forceInstall')}}",
                type: "get",
                data: {fixWorkflowSerialNumber: fixWorkflowSerialNumber},
                async: false,
                success: function (response) {
                    // console.log(response);
                    // return null;
                    $("#divModalForceInstall").html(response);
                    $("#modalForceInstall").modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 标记检修单：已完成
         * @param {string} fixWorkflowSerialNumber 检修单序列号
         */
        function fnFixedFixWorkflow(fixWorkflowSerialNumber) {
            $.ajax({
                url: "{{url('measurement/fixWorkflow/fixed')}}/" + fixWorkflowSerialNumber,
                type: "put",
                data: {},
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 保存检修单备注
         */
        function fnUpdateFixWorkflowNote() {
            $.ajax({
                url: "{{url('measurement/fixWorkflow',$fixWorkflow->serial_number)}}",
                type: "put",
                data: $("#frmUpdateFixWorkflowNote").serialize(),
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.href = `{{ url('measurement/fixWorkflow') }}`
                    // location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 打开增加部件窗口
         */
        function fnModalAddPartInstance(entireInstanceIdentityCode) {
            $.ajax({
                url: `{{ route('addPartInstance.get') }}`,
                type: 'get',
                data: {
                    entireInstanceIdentityCode,
                    fixWorkflowSerialNumber: "{{$fixWorkflow->serial_number}}",
                },
                async: true,
                success: function (res) {
                    console.log(`{{ route('addPartInstance.get') }} success:`, res);
                    $('#divModal').html(res);
                    $('#modalAddPartInstance').modal('show');
                },
                error: function (err) {
                    console.log(`{{ route('addPartInstance.get') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 打开更换部件窗口
         */
        function fnModalChangePartInstance(partInstanceIdentityCode) {
            $.ajax({
                url: "{{route('changePartInstance.get')}}",
                type: "get",
                data: {
                    partInstanceIdentityCode,
                    fixWorkflowSerialNumber: "{{$fixWorkflow->serial_number}}",
                    entireInstanceIdentityCode: "{{ $fixWorkflow->entire_instance_identity_code }}"
                },
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    $("#divModal").html(response);
                    $("#modalChangePartInstance").modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 卸载部件
         * @param {string} partInstanceIdentityCode 部件身份码
         */
        function fnUninstallPartInstance(partInstanceIdentityCode) {
            $.ajax({
                url: "{{ route('uninstallPartInstance.post') }}",
                type: "post",
                data: {
                    partInstanceIdentityCode: partInstanceIdentityCode,
                    entireInstanceIdentityCode: "{{$fixWorkflow->EntireInstance->identity_code}}",
                    fixWorkflowSerialNumber: "{{$fixWorkflow->serial_number}}",
                },
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.href = "?page={{request('page',1)}}";
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 报废部件
         * @param identityCode
         */
        function fnScrapPartInstance(identityCode) {
            $.ajax({
                url: "{{url('scrapPartInstance')}}",
                type: "post",
                data: {
                    fixWorkflowSerialNumber: "{{$fixWorkflow->serial_number}}",
                    entireInstanceIdentityCode: "{{$fixWorkflow->EntireInstance->identity_code}}",
                    partInstanceIdentityCode: identityCode
                },
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
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
                url: "{{url('entire/instance/scrap')}}/" + identityCode,
                type: "post",
                data: {},
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.href = "{{url('measurement/fixWorkflow')}}?page={{request('page',1)}}";
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 安装出所窗口
         */
        function fnCreateInstall2(fixWorkflowSerialNumber) {
            $("#modalInstall").show("show");
        }

        /**
         * 打开返厂维修窗口
         */
        function fnCreateReturnFactory(fixWorkflowSerialNumber) {
            $.ajax({
                url: "{{url('measurement/fixWorkflow/returnFactory')}}/" + fixWorkflowSerialNumber,
                type: "get",
                data: {},
                async: true,
                success: function (response) {
                    $('#divModalReturnFactory').html(response);
                    $('#modalReturnFactory').modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 返厂入所
         */
        function fnCreateFactoryReturn(fixWorkflowSerialNumber) {
            $.ajax({
                url: "{{url('measurement/fixWorkflow/factoryReturn')}}/" + fixWorkflowSerialNumber,
                type: "get",
                data: {},
                async: true,
                success: function (response) {
                    console.log('success:', response);
                    // alert(response);
                    // location.reload();
                    $("#divModalFactoryReturn").html(response);
                    $("#modalFactoryReturn").modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 检修单：入所
         */
        function fnCreateFixWorkflowInOnce(fixWorkflowSerialNumber) {
            $.ajax({
                url: "{{url('measurement/fixWorkflow/in')}}/" + fixWorkflowSerialNumber,
                type: "get",
                data: {},
                async: true,
                success: function (response) {
                    console.log('success:', response);
                    // alert(response);
                    // location.reload();
                    $("#divModalFixWorkflowInOnce").html(response);
                    $("#modalFixWorkflowInOnce").modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 打开创建检测记录窗口
         * @param {string} fixWorkflowSerialNumber 检修单流水号
         * @param {string} type 检测单类型
         * @param {string} fixWorkflowType 检测单类型
         */
        function fnCreateFixWorkflowProcess(fixWorkflowSerialNumber, type, fixWorkflowType) {
            $.ajax({
                url: "{{url('measurement/fixWorkflowProcess/create')}}",
                type: "get",
                data: {fixWorkflowSerialNumber: fixWorkflowSerialNumber, type: type, fixWorkflowType: fixWorkflowType},
                async: false,
                success: function (response) {
                    console.log(response);
                    $("#divModalCreateFixWorkflowProcess").html(response);
                    $("#modalStoreFixWorkflowProcess").modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 删除检测单
         * @param fixWorkflowProcessSerialNumber
         */
        function fnDeleteFixWorkflowProcess(fixWorkflowProcessSerialNumber) {
            $.ajax({
                url: "{{url('measurement/fixWorkflowProcess')}}/" + fixWorkflowProcessSerialNumber,
                type: "delete",
                data: {},
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    // alert(response);
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 保存错误类型
         */
        function fnUpdateBreakdownType() {
            $.ajax({
                url: `{{url('measurement/fixWorkflow/updateBreakdownType',$fixWorkflow->serial_number)}}`,
                type: 'post',
                data: $("#frmUpdateBreakdownType").serialize(),
                async: true,
                success: response => {
                    console.log(response);
                    location.reload();
                },
                fail: error => {
                    console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        }

        /**
         * 显示编辑入所故障描述
         * @param entireInstanceIdentityCode
         */
        function fnEditInWarehouseBreakdown(entireInstanceIdentityCode) {
            $btnEditInWarehouseBreakdown.hide();
            $btnUpdateInWarehouseBreakdown.show();
            $btnCancelInWarehouseBreakdown.show();
            $txaInWarehouseBreakdownExplain.removeAttr('disabled');
        }

        /**
         * 放弃编辑
         */
        function fnCancelInWarehouseBreakdown() {
            location.reload();
        }

        /**
         * 保存入所故障描述
         * @param entireInstanceIdentityCode
         */
        function fnUpdateInWarehouseBreakdown(entireInstanceIdentityCode) {
            $.ajax({
                url: `{{ url('entire/instance') }}/${entireInstanceIdentityCode}`,
                type: 'put',
                data: {
                    in_warehouse_breakdown_explain: $txaInWarehouseBreakdownExplain.val()
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/instance') }} success:`, res);
                },
                error: function (err) {
                    console.log(`{{ url('entire/instance') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
            $.ajax({
                url: `{{ url('entire/log') }}`,
                type: 'post',
                data: {
                    entire_instance_identity_code: entireInstanceIdentityCode,
                    in_warehouse_breakdown_explain: $txaInWarehouseBreakdownExplain.val(),
                },
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
         * 显示编辑故障类型
         */
        function fnEditBreakdownType() {
            $btnEditBreakdownType.hide();
            $btnUpdateBreakdownType.show();
            $btnCancelBreakdownType.show();
            $divShowBreakdownType.hide();
            $divChkBreakdownType.show();
        }

        /**
         * 取消编辑故障类型
         */
        function fnCancelBreakdownType() {
            location.reload();
        }
    </script>
@endsection

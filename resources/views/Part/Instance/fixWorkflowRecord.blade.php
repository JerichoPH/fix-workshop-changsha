@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection
@section('content')
    @include('Layout.alert')
        <!-- 面包屑 -->
    <section class="content-header">
      <h1>
        部件管理
        <small>检修记录</small>
      </h1>
{{--      <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li><a href="{{url('part/instance')}}"><i class="fa fa-users">&nbsp;</i>部件管理</a></li>--}}
{{--        <li class="active">检修记录</li>--}}
{{--      </ol>--}}
    </section>
    <form class="form-horizontal" id="frmUpdatePartFixWorkflow">
        <section class="content">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h1 class="box-title">检测记录</h1>
                    {{--右侧最小化按钮--}}
                    <div class="box-tools pull-right"></div>
                </div>
                <br>
                <div class="box-body">
                    <input type="hidden" name="part_instance_identity_code" value="{{$partFixWorkflowRecords[0]->PartInstance->identity_code}}">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">检测人：</label>
                        <div class="col-sm-8 col-md-8">
                            <select name="processor_id" class="form-control select2" style="width:100%;">
                                <option value="">请选择</option>
                                @foreach(\App\Model\Account::orderByDesc('id')->get() as $account)
                                    <option value="{{$account->id}}" {{$account->id == $partFixWorkflowRecords[0]->processor_id ? 'selected' : ''}}>{{$account->nickname}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">检测时间：</label>
                        <div class="col-sm-8 col-md-8">
                            <div class="input-group date">
                                <div class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </div>
                                <input name="processed_at" type="text" class="form-control pull-right" id="datepicker" value="{{$partFixWorkflowRecords[0]->processed_at}}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                        </div>
                    </div>
                    <table class="table table-condensed table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>出厂编号</th>
                            <th>型号</th>
                            <th>测试项</th>
                            <th>标准值</th>
                            <th>实测值</th>
                            {{--<th>检测人</th>--}}
                            {{--<th>检测时间</th>--}}
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 0;?>
                        @foreach($partFixWorkflowRecords as $fixWorkflowRecord)
                            <tr>
                                <td>{{++$i}}</td>
                                <td>{{$fixWorkflowRecord->PartInstance->factory_device_code}}</td>
                                <td>{{$fixWorkflowRecord->PartInstance->PartModel->name}}（{{$fixWorkflowRecord->PartInstance->PartModel->unique_code}}）</td>
                                <td>
                                    {{$fixWorkflowRecord->Measurement->character}}
                                    {{$fixWorkflowRecord->Measurement->key ? '（'.$fixWorkflowRecord->Measurement->key : ''}}
                                    {{$fixWorkflowRecord->Measurement->operation ? '：'.$fixWorkflowRecord->Measurement->operation : ''}}
                                    {{$fixWorkflowRecord->Measurement->key ? '）' : ''}}
                                    {{$fixWorkflowRecord->Measurement->is_extra_tag ? $fixWorkflowRecord->Measurement->extra_tag : ''}}
                                    {{--                                    <a href="javascript:" onclick="fnDeleteFixWorkflowRecord('{{$fixWorkflowRecord->serial_number}}')" style="color: red;"><i class="fa fa-trash"></i></a>--}}
                                </td>
                                @if(($fixWorkflowRecord->Measurement->allow_min == null) && ($fixWorkflowRecord->Measurement->allow_max == null))
                                    <td>{{$fixWorkflowRecord->Measurement->allow_explain}}</td>
                                    <td>
                                        {{$fixWorkflowRecord->measured_value}}&nbsp;&nbsp;{!! $fixWorkflowRecord->measured_value != null ? $fixWorkflowRecord->is_allow == 1 ? '<span style="color:green">合格</span>' : '<span style="color:red">未通过</span>' : '未检测' !!}
                                        <a href="javascript:" onclick="fnCreateSaveMeasuredExplain('{{$fixWorkflowRecord->Measurement->identity_code}}','{{$fixWorkflowRecord->serial_number}}')">记录实测模糊描述</a>
                                    </td>
                                @else
                                    <td>
                                        @if($fixWorkflowRecord->Measurement->allow_min == null && $fixWorkflowRecord->Measurement->allow_max != null)
                                            ≤&nbsp;&nbsp;{{$fixWorkflowRecord->Measurement->allow_max}}&nbsp;&nbsp;{{$fixWorkflowRecord->Measurement->unit}}
                                        @elseif($fixWorkflowRecord->Measurement->allow_min != null && $fixWorkflowRecord->Measurement->allow_max == null)
                                            ≥&nbsp;&nbsp;{{$fixWorkflowRecord->Measurement->allow_min}}&nbsp;&nbsp;{{$fixWorkflowRecord->Measurement->unit}}
                                        @else
                                            {{$fixWorkflowRecord->Measurement->allow_min != $fixWorkflowRecord->Measurement->allow_max ? $fixWorkflowRecord->Measurement->allow_min.'～': ''}}{{$fixWorkflowRecord->Measurement->allow_max}}&nbsp;&nbsp;{{$fixWorkflowRecord->Measurement->unit}}
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" name="{{$fixWorkflowRecord->id}}" value="{{$fixWorkflowRecord->measured_value}}" onchange="frmStoreFixWorkflowProcessPart('{{$fixWorkflowRecord->part_instance_identity_code}}',this.name,this.value,'{{$fixWorkflowRecord->Measurement->identity_code}}')">
                                        <span class="span-response" id="span_{{$fixWorkflowRecord->id}}">{!! $fixWorkflowRecord->measured_value != null ? $fixWorkflowRecord->is_allow == 1 ? '<span style="color:green">合格</span>' : '<span style="color:red">未通过</span>' : '未检测' !!}</span>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    {{--                    <a href="{{url('measurement/fixWorkflow',$fixWorkflowProcess->fix_workflow_serial_number)}}/edit?type={{request('type')}}" class="btn btn-default btn-flat pull-left btn-lg"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="javascript:" onclick="fnUpdateFixWorkflowProcessProcess()" class="btn btn-warning btn-flat pull-right btn-lg"><i class="fa fa-check">&nbsp;</i>保存</a>
                </div>
            </div>
        </section>
    </form>
    <section>
        <div id="divModalBindingFixWorkflowProcess"></div>
    </section>
@endsection
@section('script')
    <script src="/AdminLTE/bower_components/select2/dist/js/select2.full.min.js"></script>
    <!-- iCheck 1.0.1 -->
    <script src="/AdminLTE/plugins/iCheck/icheck.min.js"></script>
    <!-- bootstrap datepicker -->
    <script src="/AdminLTE/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
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

        /**
         * 保存
         */
        fnUpdateFixWorkflowProcessProcess = function () {
            $.ajax({
                url: "{{url('part/instance',$partFixWorkflowRecords[0]->PartInstance->identity_code)}}",
                type: "put",
                data: $("#frmUpdatePartFixWorkflow").serialize(),
                success: function (response) {
                    location.href = "{{url('part/instance')}}?page={{session('page',1)}}";
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.responseText == 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        };

        /**
         * 更新部件检测数据
         * @param {string} partInstanceIdentityCode 部件编号
         * @param {int} partFixWorkflowRecordId 测试项编号
         * @param {string} measuredValue 测试值
         * @param {string} measurementIdentityCode 测试模板身份码
         */
        frmStoreFixWorkflowProcessPart = function (partInstanceIdentityCode, partFixWorkflowRecordId, measuredValue, measurementIdentityCode) {
            $.ajax({
                url: "{{url('part/instance/saveMeasuredValue')}}/" + partInstanceIdentityCode,
                type: "post",
                data: {id: partFixWorkflowRecordId, measured_value: measuredValue, measurement_identity_code: measurementIdentityCode},
                async: true,
                success: function (response) {
                    for (let i in response) {
                        html = response[i].measured_value != null ? response[i].is_allow == 1 ? '<span style="color: green;">合格</span>' : '<span style="color: red;">不合格</span>' : '未检测';
                        $("#span_" + response[i].id).html(html);
                    }
                },
                error: function (error) {
                    if (error.status == 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };
    </script>
@endsection

@extends('Layout.index') @section('style')
<!-- Select2 -->
<link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection @section('content') @include('Layout.alert')
<!-- 面包屑 -->
<section class="content-header">
    <h1>
        临时生产任务 <small>详情</small>
    </h1>
{{--    <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li><a href="{{ url('temporaryTask/production/main') }}?page={{ request('page', 1) }}"><i--}}
{{--                    class="fa fa-users">&nbsp;</i>临时生产任务</a></li>--}}
{{--        <li class="active">详情</li>--}}
{{--    </ol>--}}
</section>
<div class="row">
    <div class="col-md-4">
        <section class="content">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        {{ $main_task['title'] }}&nbsp;&nbsp; <small>当前阶段：{{
							$main_task['stage_name'] }}</small>
                    </h3>
                    {{--右侧最小化按钮--}}
                    <div class="box-tools pull-right"></div>
                </div>
                <br>
                <div class="box-body">
                    <dl class="dl-horizontal">
                        <dt>发件人</dt>
                        <dd>{{ $main_task['initiator_affiliation_name'] }}:{{
							$main_task['initiator_name'] }}</dd>
                        <dt>段负责人</dt>
                        <dd>{{ $main_task['paragraph_principal_name'] }}</dd>
                        @if($main_task['stage_103_message'])
                        <dt>电务段下达任务留言：</dt>
                        <dd>{!! $main_task['stage_103_message'] !!}</dd>
                        @endif
                        <hr>
                        <dt>段车间主任</dt>
                        <dd>{{ $main_task['paragraph_workshop_name'] }}</dd>
                        @if($main_task['stage_104_message'])
                        <dt>指定盯控干部留言：</dt>
                        <dd>{!! $main_task['stage_104_message'] !!}</dd>
                        @endif
                        <hr>
                        <dt>段盯控干部</dt>
                        <dd>{{ $main_task['paragraph_monitoring_name'] }}</dd>
                        <hr>
                        @if($main_task['expire_at'])
                        <dt>截止日期</dt>
                        <dd>{{ \Carbon\Carbon::parse($main_task['expire_at'])->format('Y-m-d') }}</dd>
                        @endif
                        @if($main_task['finished_at'])
                        <dt>完成时间</dt>
                        <dd>{{ \Carbon\Carbon::parse($main_task['finished_at'])->format('Y-m-d H:i:s') }}</dd>
                        @endif
                        <dt>详情</dt>
                        <dd>{!! $main_task['content'] !!}</dd>
                        <hr>
                        <p class="text-center" style="font-size: 18px;">任务清单</p>
                        @if($main_task['models'])
                        <?php $model_codes_count = 0; ?>
                        <?php $finish_count = 0; ?>
                        @if(isset($main_task['models'][session('account.work_area')]))
                        @foreach($main_task['models'][session('account.work_area')] as $key=>$value)
                        <dt>{{ $value['name3'] }}</dt>
                        <dd>目标：{{ $value['number'] }}&nbsp;&nbsp;完成：{{
							isset($model_codes_fixed_count[$value['code3']]) ?
							$model_codes_fixed_count[$value['code3']] : 0 }}</dd>
                        <?php $model_codes_count++; ?>
                        <?php if (isset($model_codes_fixed_count[$value['code3']])): ?>
                        <?php if ($value['number'] == $model_codes_fixed_count[$value['code3']]) $finish_count++; ?>
                        <?php endif; ?>
                        @endforeach
                        @endif
                        @endif
                    </dl>
                </div>
                @switch(session('account.temp_task_position'))
                @case('工区工长') @if($model_codes_count == $finish_count)
                <div class="box-footer">
                    <a class="btn btn-success btn-flat btn-sm pull-right" onclick="modalMakeSubTaskFinish()"><i
                            class="fa fa-check">&nbsp;</i>工区工长确认完成</a>
                </div>
                @endif @break @endswitch
            </div>
        </section>
    </div>

    <div class="col-md-8">
        <section class="content">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">扫码填充成品设备</h3>
                    {{--右侧最小化按钮--}}
                    <div class="box-tools pull-right"></div>
                </div>
                <br>
                <div class="box-body">
                    <div class="form-group form-group-lg">
                        <input type="text" name="identity_code" id="txtEntireInstanceIdentityCode" class="form-control"
                            onkeydown="if(event.keyCode===13) fnScan(this.value)" autofocus placeholder="扫码前点击">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-condensed">
                            <thead>
                                <tr>
                                    <td>唯一编号</td>
                                    <td>型号</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entire_instances as $entire_instance)
                                <tr>
                                    <td>{{ $entire_instance->entire_instance_identity_code }}</td>
                                    <td>{{ $entire_instance->model_name }}
                                        <a class="btn btn-flat btn-danger btn-sm pull-right"
                                            onclick="fnCut('{{ $entire_instance->id }}')"><i
                                                class="fa fa-times"></i></a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($entire_instances->hasPages())
                <div class="box-footer">{{ $entire_instances->links() }}</div>
                @endif
            </div>
        </section>

        <section class="content">
            <div id="divModalMakeSubTaskFinish"></div>
        </section>
    </div>
</div>
@endsection @section('script')
<script>
    /**
     * 打开任务完成窗口
     */
    function modalMakeSubTaskFinish(){
        $.ajax({
            url:`{{ url ('temporaryTask/production/sub/makeSubTaskFinish',$sub_task['id']) }}`,
            type:'get',
            data:{},
            success:function(res){
                // console.log(res);
                $('#divModalMakeSubTaskFinish').html(res);
                $('#modalMakeSubTaskFinish').modal('show');
            },
            error:function(err){
                console.log(`{{ url('temporaryTask/production/sub/makeSubTaskFinish',$sub_task['id']) }} fail:`, err);
                if (err.status === 401) location.href = "{{ url('login') }}";
                alert(err['responseJSON']['message']);
            }
        })
    }

    /**
     * 添加成品设备
     * @param {string} entireInstanceIdentityCode
     */
    function fnScan(entireInstanceIdentityCode) {
        $.ajax({
            url: `{{ url('temporaryTask/production/sub/process',$sub_task['id']) }}`,
            type: 'post',
            data: {entireInstanceIdentityCode, modelCodes: '{!! $model_codes !!}', mainTaskId: "{{ $main_task['id'] }}"},
            async: true,
            success: function (res) {
                console.log(`{{ url('temporaryTask/production/sub/process',$sub_task['id']) }} success:`, res);
                location.reload();
            },
            error: function (err) {
                console.log(`{{ url('temporaryTask/production/sub/process',$sub_task['id']) }} fail:`, err);
                if (err.status === 401) location.href = "{{ url('login') }}";
                alert(err['responseJSON']['message']);
            }
        });
    }

    /**
     * 删除已经存在的设备
     * @param id
     */
    function fnCut(id) {
        $.ajax({
            url: `{{ url('temporaryTask/production/sub/cutEntireInstance') }}/${id}`,
            type: 'delete',
            data: {},
            async: true,
            success: function (res) {
                console.log(`{{ url('temporaryTask/production/sub/cutEntireInstance') }}/${id} success:`, res);
                location.reload();
            },
            error: function (err) {
                console.log(`{{ url('temporaryTask/production/sub/cutEntireInstance') }}/${id} fail:`, err);
                if (err.status === 401) location.href = "{{ url('login') }}";
                alert(err['responseJSON']['message']);
            }
        });
    }
</script>
@endsection

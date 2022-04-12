@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection
@section('content')
    @include('Layout.alert')

    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            临时生产任务
            <small>详情</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('temporaryTask/production/main') }}?page={{ request('page', 1) }}">--}}
{{--                    <i class="fa fa-users">&nbsp;</i>临时生产任务</a>--}}
{{--            </li>--}}
{{--            <li class="active">详情</li>--}}
{{--        </ol>--}}
    </section>
    <div class="row">
        <div class="col-md-8">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">
                            {{ $main_task['title'] }}&nbsp;&nbsp;
                            <small class="text-info">当前阶段：{{ $main_task['stage_name'] }}</small>
                        </h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>任务发起人</dt>
                            <dd>{{ $main_task['initiator_affiliation_name'] }}:{{ $main_task['initiator_name'] }}</dd>
                            <dt>接收段</dt>
                            <dd>{{ $main_task['paragraph_name'] }}</dd>
                            <dt>段负责人</dt>
                            <dd>{{ $main_task['paragraph_principal_name'] }}</dd>
                            @if($main_task['stage_103_message'])
                                <dt>段负责人留言</dt>
                                <dd>{!! $main_task['stage_103_message'] !!}</dd>
                            @endif
                            <hr>
                            <dt>段车间主任</dt>
                            <dd>{{ $main_task['paragraph_workshop_name'] }}</dd>
                            @if($main_task['stage_104_message'])
                                <dt>段车间主任留言</dt>
                                <dd>{!! $main_task['stage_104_message'] !!}</dd>
                            @endif
                            <hr>
                            <dt>段盯控干部</dt>
                            <dd>{{ $main_task['paragraph_monitoring_name'] }}</dd>
                            @if($main_task['expire_at'])
                                <dt>截止日期</dt>
                                <dd>{{ \Carbon\Carbon::parse($main_task['expire_at'])->format('Y-m-d') }}</dd>
                            @endif
                            @if($main_task['finished_at'])
                                <dt>完成时间</dt>
                                <dd>{{ \Carbon\Carbon::parse($main_task['finished_at'])->format('Y-m-d H:i:s') }}</dd>
                            @endif
                            <dt>任务内容</dt>
                            <dd>{!! $main_task['content'] !!}</dd>
                        </dl>
                    </div>
                    <div class="box-footer">
                        <a href="{{ url('temporaryTask/production/sub') }}?page={{ request('page', 1) }}"
                           class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        @switch($main_task['stage'].session('account.temp_task_position'))
                            @case('102段负责人')
                            <a class="btn btn-info btn-flat btn-sm pull-right" onclick="modelMakeMainTask103('{{ $main_task['id'] }}')"><i class="fa fa-check">&nbsp;</i>下达任务到检修基地</a>
                            @break
                            @case('103车间主任')
                            <a class="btn btn-info btn-flat btn-sm pull-right" onclick="modelMakeMainTask104('{{ $main_task['id'] }}')"><i class="fa fa-check">&nbsp;</i>指定盯控干部</a>
                            @break
                            @case('104盯控干部')
                            <a class="btn btn-info btn-flat btn-sm pull-right" onclick="modelMakeSubTask105('{{ $main_task['id'] }}')"><i class="fa fa-check">&nbsp;</i>创建工区子任务</a>
                            @break
                            @case('105工区工长')
                            {{--@if($sub_task['status'] != 'FINISH')--}}
                            {{--@if($sub_task['checked_at'])--}}
                            {{--<a href="{{ url('temporaryTask/production/sub/process',$sub_task['id']) }}" class="btn btn-info btn-flat btn-sm pull-right">工作汇报</a>--}}
                            {{--<a href="{{ url('temporaryTask/production/sub/plan',$sub_task['id']) }}" class="btn btn-info btn-flat btn-sm pull-right">制作计划</a>--}}
                            {{--@else--}}
                            {{--<a href="javascript:" class="btn btn-primary btn-flat btn-sm pull-right" onclick="fnChecked({{ $main_task['id'] }})"><i class="fa fa-check">&nbsp;</i>确认收到任务</a>--}}
                            {{--@endif--}}
                            {{--@endif--}}
                            @if($sub_task['status'] == 'FINISH')
                                {{-- <span class="text-success pull-right">任务完成，等待段盯控干部确认</span> --}}
                                <span class="text-success pull-right">任务完成，等待电务部确认</span>
                            @else
                                <a href="{{ url('temporaryTask/production/sub/process',$sub_task['id']) }}" class="btn btn-info btn-flat btn-sm pull-right">工作汇报</a>
                                <a href="{{ url('temporaryTask/production/sub/plan',$sub_task['id']) }}" class="btn btn-info btn-flat btn-sm pull-right">制定计划</a>
                            @endif
                            @break
                            @case('106工区工长')
                            @case('201工区工长')
                            @case('202工区工长')
                            @case('203工区工长')
                            @case('204工区工长')
                            @if($sub_task['status'] == 'FINISH')
                                <span class="text-success pull-right">{{ $main_task['stage_name'] }}</span>
                            @endif
                            @break
                        @endswitch
                    </div>
                </div>
            </section>

            @if (session('account.temp_task_position') == '段负责人')
                <section class="content">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">留言</h3>
                            {{--右侧最小化按钮--}}
                            <div class="box-tools pull-right"></div>
                        </div>
                        <br>
                        <div class="box-body">
                            <form class="form-horizontal" id="frmCreate">
                                <div class="form-group">
                                    <div class="row">
                                        <label class="col-sm-2 control-label">内容：</label>
                                        <div class="col-md-9">
                                            <textarea id="txaContent" name="content" rows="10" cols="80"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="box-footer">
                            <a onclick="fnCreateNote()" class="btn btn-success pull-right btn-flat btn-sm"><i
                                    class="fa fa-check">&nbsp;</i>留言</a>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <div class="col-md-4">
            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">任务清单</h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            @if($main_task['models'])
                                @if(isset($main_task['models'][session('account.work_area')]))
                                    @foreach($main_task['models'][session('account.work_area')] as $key=>$value)
                                        <dt>{{ $value['name3'] }}</dt>
                                        <dd>目标：{{ $value['number'] }}&nbsp;&nbsp;完成：{{ isset($model_codes_fixed_count[$value['code3']]) ? $model_codes_fixed_count[$value['code3']] : 0 }}
                                        </dd>
                                    @endforeach
                                @endif
                            @endif
                        </dl>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="content">
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">留言板</h3>
            </div>
            <br>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <ul class="timeline">
                            @foreach($main_task_notes['data'] as $main_task_note)
                                <li>
                                    <i class="fa fa-comments bg-yellow"></i>

                                    <div class="timeline-item">
                                <span class="time"><i class="fa fa-clock-o"></i>
                                    {{\Carbon\Carbon::parse($main_task_note['created_at'])->format("Y-m-d H:i:s")}}</span>

                                        <h3 class="timeline-header">发件人：<a href="javascript:" class="label label-info">{{$main_task_note['sender_affiliation_name']}}:{{$main_task_note['sender_name']}}</a></h3>

                                        <div class="timeline-body">
                                            {!! $main_task_note['content'] !!}
                                        </div>
                                        <div class="timeline-footer">
                                            @if($main_task_note['sender_id'] == session('account.id') && $main_task_note['sender_affiliation'] == env('ORGANIZATION_CODE'))
                                                <a class="text-sm text-danger" onclick="fnDeleteNote('{{$main_task_note['id']}}')">
                                                    <i class="fa fa-times">&nbsp;</i>删除
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                            <li>
                                <i class="fa fa-clock-o bg-gray"></i>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <ul class="pagination pagination-sm no-margin pull-right" role="navigation">
                    {{-- Previous Page Link --}}
                    <li>
                        <a href="?page={{$main_task_notes['current_page'] - 1 > 0 ?: 1}}" rel="prev"
                           aria-label="@lang('pagination.previous')">&lsaquo;</a>
                    </li>

                    {{-- Pagination Elements --}}
                    @for($i=$main_task_notes['current_page']; $i <= $main_task_notes['max_page']; $i++)
                        @if($i==$main_task_notes['current_page'])
                            <li class="active" aria-current="page">
                                <span>{{ $i }}</span></li>
                        @else
                            <li><a href="?{{http_build_query(['page'=>$i])}}">{{ $i }}</a></li>
                        @endif
                    @endfor

                    {{-- Next Page Link --}}
                    <li>
                        <a href="?{{http_build_query(['page'=>$main_task_notes['current_page'] + 1 <= $main_task_notes['max_page'] ?: $main_task_notes['max_page']])}}"
                           rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <section class="content">
        <div id="divModalMakeMainTask103"></div>
        <div id="divModalMakeMainTask104"></div>
        <div id="divModalMakeSubTask105"></div>
    </section>
@endsection
@section('script')
    <script>
        $(function () {
            @if (session('account.temp_task_position') == '段负责人')
            // 初始化 ckeditor
            CKEDITOR.replace('txaContent', {
                toolbar: [
                    {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                    {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']},
                    {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                    {name: 'colors', items: ['TextColor', 'BGColor']},
                    {name: 'tools', items: ['Maximize', 'ShowBlocks']}
                ]
            });
            @endif
        });

        /**
         * 新建留言
         */
        function fnCreateNote() {
            let data = {
                main_task_id: "{{ $main_task['id'] }}",
                main_task_title: "{{ $main_task['title'] }}",
                main_task_initiator_id: "{{ $main_task['initiator_id'] }}",
                main_task_initiator_affiliation: "{{ $main_task['initiator_affiliation'] }}",
                content: CKEDITOR.instances['txaContent'].getData(),
            };

            $.ajax({
                url: "{{ url('temporaryTask/production/main/note',$main_task['id']) }}",
                type: "post",
                async: false,
                data: data,
                success: function (response) {
                    console.log(response);
                    alert(response['message']);
                    location.reload();
                },
                error: function (err) {
                    console.log('fail:', err);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 删除主任务留言
         * @param mainTaskNoteId
         */
        function fnDeleteNote(mainTaskNoteId) {
            $.ajax({
                url: `{{ url('/temporaryTask/production/main/note') }}/${mainTaskNoteId}`,
                type: 'delete',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('/temporaryTask/production/main/note') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('/temporaryTask/production/main/note') }} fail:`, err);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 发布
         * @param mainTaskId
         * @param mainTaskTitle
         */
        function modelMakeMainTask103(mainTaskId, mainTaskTitle) {
            $.ajax({
                url: `{{ url('temporaryTask/production/main/makeMainTask103') }}/${mainTaskId}`,
                type: "get",
                data: {},
                async: false,
                success: function (response) {
                    // console.log('success:', response);
                    $("#divModalMakeMainTask103").html(response);
                    $("#modalMakeMainTask").modal('show');
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                },
            });
        }

        /**
         * 指定盯控干部
         * @param mainTaskId
         * @param mainTaskTitle
         */
        function modelMakeMainTask104(mainTaskId, mainTaskTitle) {
            $.ajax({
                url: `{{ url('temporaryTask/production/main/makeMainTask104') }}/${mainTaskId}`,
                type: "get",
                data: {},
                async: false,
                success: function (response) {
                    // console.log('success:', response);
                    $("#divModalMakeMainTask104").html(response);
                    $("#modalMakeMainTask").modal('show');
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                },
            });
        }

        /**
         * 创建工区子任务
         * @param mainTaskId
         * @param mainTaskTitle
         */
        function modelMakeSubTask105(mainTaskId, mainTaskTitle) {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/makeSubTask105') }}/${mainTaskId}`,
                type: "get",
                data: {},
                async: false,
                success: function (response) {
                    // console.log('success:', response);
                    $("#divModalMakeSubTask105").html(response);
                    $("#modalMakeSubTask").modal('show');
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                },
            });
        }

        /**
         * 确认收到任务
         * @param subTaskId
         */
        function fnChecked(subTaskId) {
            $.ajax({
                url: `{{ url('temporaryTask/production/sub/checked',$sub_task['id']) }}`,
                type: 'put',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/sub/checked',$sub_task['id']) }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/sub/checked',$sub_task['id']) }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

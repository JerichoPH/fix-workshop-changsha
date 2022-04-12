@extends('Layout.index')
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
{{--            <li><a href="{{ url('temporaryTask/production/main') }}?page={{ request('page', 1) }}"><i class="fa fa-users">&nbsp;</i>临时生产任务</a></li>--}}
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
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>任务发起人</dt>
                            <dd>{{ $main_task['initiator_affiliation_name'] }}:{{ $main_task['initiator_name'] }}</dd>
                            <dt>电务段负责人</dt>
                            <dd>{{ $main_task['paragraph_name'] }}:{{ $main_task['paragraph_principal_name'] }}</dd>
                            @if($main_task['stage_103_message'])
                                <dt></dt>
                                <dd>{!! $main_task['stage_103_message'] !!}</dd>
                            @endif
                            <hr>
                            {{--<hr>--}}
                            {{--<dt>车间负责人</dt>--}}
                            {{--<dd>{{ $main_task['paragraph_workshop_name'] }}</dd>--}}
                            {{--@if($main_task['stage_104_message'])--}}
                            {{--<dt></dt>--}}
                            {{--<dd>{!! $main_task['stage_104_message'] !!}</dd>--}}
                            {{--@endif--}}
                            {{--<hr>--}}
                            {{--<dt>盯控干部</dt>--}}
                            {{--<dd>{{ $main_task['paragraph_monitoring_name'] }}</dd>--}}
                            <dt>任务状态</dt>
                            <dd>{{ $main_task['status_name'] }}</dd>
                            @if($main_task['expire_at'])
                                <dt>截止日期</dt>
                                <dd>{{ \Carbon\Carbon::parse($main_task['expire_at'])->toDateString() }}</dd>
                            @endif
                            @if($main_task['finished_at'])
                                <dt>完成时间</dt>
                                <dd>{{ \Carbon\Carbon::parse($main_task['finished_at'])->toDateString() }}</dd>
                            @endif
                            <dt>任务说明</dt>
                            <dd>{!! $main_task['content'] !!}</dd>
                        </dl>
                    </div>
                    <div class="box-footer">
                        <a href="{{ url('temporaryTask/production/main') }}?page={{ request('page', 1) }}" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        @switch(session('account.temp_task_position'))
                            {{--@case('电务段科长')--}}
                            {{--<a class="btn btn-warning btn-flat btn-sm pull-right"--}}
                            {{--onclick="modelMakeMainTask103('{{ $main_task['id'] }}')">下达任务到检修基地&nbsp;<i class="fa fa-arrow-right"></i></a>--}}
                            {{--@break--}}
                            {{--@case('车间主任')--}}
                            {{--<a class="btn btn-warning btn-flat btn-sm pull-right"--}}
                            {{--onclick="modelMakeMainTask104('{{ $main_task['id'] }}')">指定盯控干部&nbsp;<i class="fa fa-arrow-right"></i></a>--}}
                            {{--@break--}}
                            @case('车间工程师')
                            @if($main_task['is_allot_sub_task'] == false)
                                <a href="javascript:" class="btn btn-warning btn-flat btn-sm pull-right" onclick="modalMakeSubTask105()">
                                    任务分配到工区&nbsp;<i class="fa fa-arrow-right"></i>
                                </a>
                            @endif
                            @break
                        @endswitch
                        @if($main_task['status'] !== 'FINISH' && $all_sub_task_is_finish)
                            <span class="text-success pull-right">任务完成，等待任务发起人确认</span>
                        @endif
                    </div>
                </div>
            </section>


            <section class="content">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">留言</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        <form class="form-horizontal" id="frmCreate">
                            <textarea id="txaContent" name="content" rows="10" cols="80" class="form-control"></textarea>
                        </form>
                    </div>
                    <div class="box-footer">
                        <a onclick="fnCreateNote()" class="btn btn-success pull-right btn-flat btn-sm"><i class="fa fa-check">&nbsp;</i>留言</a>
                    </div>
                </div>
            </section>

            <!-- 留言板 -->
            @if(!empty($main_task_notes['data']))
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
                                                    <span class="time"><i class="fa fa-clock-o"></i>{{ \Carbon\Carbon::parse($main_task_note['created_at'])->format("Y-m-d H:i:s") }}</span>

                                                    <h3 class="timeline-header">发件人：<a href="javascript:" class="label label-info">{{ $main_task_note['sender_affiliation_name'] }}:{{ $main_task_note['sender_name'] }}</a></h3>

                                                    <div class="timeline-body">
                                                        {!! $main_task_note['content'] !!}
                                                    </div>
                                                    <div class="timeline-footer">
                                                        @if($main_task_note['sender_id'] == session('account.id') &&
                                                        $main_task_note['sender_affiliation'] == env('ORGANIZATION_CODE'))
                                                            <a href="javascript:" class="text-sm text-danger" onclick="fnDeleteNote('{{ $main_task_note['id'] }}')">
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
                            <ul class="pagination no-margin pull-right" role="navigation">
                                {{-- Previous Page Link --}}
                                <li>
                                    <a href="?page={{ $main_task_notes['current_page'] - 1 > 0 ?: 1 }}" rel="prev"
                                       aria-label="@lang('pagination.previous')">&lsaquo;</a>
                                </li>

                                {{-- Pagination Elements --}}
                                @for($i=$main_task_notes['current_page']; $i <= $main_task_notes['max_page']; $i++)
                                    @if($i==$main_task_notes['current_page'])
                                        <li class="active" aria-current="page">
                                            <span>{{ $i }}</span></li>
                                    @else
                                        <li><a href="?{{ http_build_query(['page'=>$i]) }}">{{ $i }}</a></li>
                                    @endif
                                @endfor

                                {{-- Next Page Link --}}
                                <li>
                                    <a href="?{{ http_build_query(['page'=>$main_task_notes['current_page'] + 1 <= $main_task_notes['max_page'] ?: $main_task_notes['max_page']]) }}"
                                       rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                                </li>
                            </ul>
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
                        @if($sub_tasks)
                            <div class="table-responsive">
                                <table class="table table-condensed table-striped">
                                    <thead>
                                    <tr>
                                        <th>工区</th>
                                        <th>任务</th>
                                        <th>状态</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($sub_tasks as $sub_task)
                                        <tr>
                                            <td>{{ @$sub_task['receiver_work_area_name'] }}</td>
                                            <td>
                                                <a href="{{ url('temporaryTask/production/sub',$sub_task['workshop_serial_number']) }}?type={{ $sub_task['type'] }}&direction={{ $sub_task['direction'] }}">
                                                    {{ @$sub_task['intro'] }}
                                                </a>
                                            </td>
                                            @switch($sub_task['status'])
                                                @case('FINISH')
                                                <td><span class="text-success">{{ $sub_task['status_name'] }}</span></td>
                                                @break
                                                @case ('REJECT')
                                                <td><span class="text-danger">{{ $sub_task['status_name'] }}</span></td>
                                                @break
                                                @default
                                                <td>{{ $sub_task['status_name'] }}</td>
                                                @break
                                            @endswitch
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="content">
        <div id="divModalMakeMainTask103"></div>
        <div id="divModalMakeMainTask104"></div>
        <div id="divModalMessage">
            <div class="modal fade" id="modalMessage">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="modalMessageTitle"></h4>
                        </div>
                        <div class="modal-body form-horizontal" id="modalMessageContent"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat btn-sm" data-dismiss="modal">
                                <i class="fa fa-check">&nbsp;</i>关闭
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="divModalSelectSubTaskType">
            <div class="modal fade" id="modalSelectSubTaskType">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="modalSelectSubTaskTypeTitle">创建工区子任务</h4>
                        </div>
                        <div class="modal-body form-horizontal" id="modalSelectSubTaskTypeContent">
                            <label for="selSubTaskType">选择任务类型：</label>
                            <select name="sub_task_type" id="selSubTaskType" class="select2 form-control" style="width: 100%;">
                                <option value="NEW_STATION">新站</option>
                                <option value="FULL_FIX">大修</option>
                                <option value="HIGH_FREQUENCY">高频修</option>
                                <option value="EXCHANGE_MODEL">更换型号</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat btn-sm" data-dismiss="modal"><i class="fa fa-check">&nbsp;</i>关闭</button>
                            <a href="javascript:" onclick="fnMakeSubTask105()" class="btn bg-purple btn-flat btn-sm"><i class="fa fa-arrow-right">&nbsp;</i>下一步</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $selSubTaskType = $('#selSubTaskType');
        let $divStation = $('#divStation');

        $(function () {
            $('.select2').select2();

            // 初始化 ckeditor
            CKEDITOR.replace('txaContent', {
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
         * 选择子任务类型
         */
        // $selSubTaskType.on('change', function () {
        //     if($(this).val() === 'FULL_FIX'){
        //         $divStation.show();
        //     }
        // });

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

            if (!data['content']) {
                alert('留言不能为空');
                return null;
            }

            $.ajax({
                url: "{{ url('temporaryTask/production/main/note',$main_task['id']) }}",
                type: "post",
                async: false,
                data: data,
                success: function (response) {
                    console.log(response);
                    // alert(response['message']);
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
         * 打开创建工区子任务窗口
         */
        function modalMakeSubTask105() {
            $('#modalSelectSubTaskType').modal('show');
            $('.select2').select2();
        }

        /**
         * 创建工区子任务
         */
        function fnMakeSubTask105() {
            location.href = `{{ url('temporaryTask/production/sub/create') }}?mainTaskId={{$main_task['id']}}&type=${$selSubTaskType.val()}`;
        }

        /**
         * 检修基地盯控干部确认任务完成
         */
        function fnMakeMainTask201() {
            $.ajax({
                url: `{{ url('temporaryTask/production/main/makeMainTask201',$main_task['id']) }}`,
                type: 'put',
                data: {},
                async: false,
                success: function (res) {
                    console.log('success:', res);
                    alert(res['message']);
                    location.reload();
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                },
            });
        }

        /**
         * 检修基地车间主任确认任务完成
         */
        function fnMakeMainTask202() {
            $.ajax({
                url: `{{ url('temporaryTask/production/main/makeMainTask202',$main_task['id']) }}`,
                type: "put",
                data: {},
                async: false,
                success: function (res) {
                    console.log('success:', res);
                    alert(res['message']);
                    location.reload();
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                },
            });
        }

        /**
         * 电务段确认任务完成
         */
        function fnMakeMainTask203() {
            $.ajax({
                url: `{{ url('temporaryTask/production/main/makeMainTask203',$main_task['id']) }}`,
                type: "put",
                data: {},
                async: false,
                success: function (res) {
                    console.log('success:', res);
                    alert(res['message']);
                    location.reload();
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err['status'] === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                },
            });
        }

        /**
         * 打开查看完成汇报和驳回说明窗口
         */
        function modalMessage(title, content) {
            $('#modalMessageTitle').text(title);
            $('#modalMessageContent').html(content);
            $('#modalMessage').modal('show');
        }
    </script>
@endsection

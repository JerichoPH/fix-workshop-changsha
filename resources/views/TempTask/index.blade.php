@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            临时生产任务
            <small>{{ $subTitle }}</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">临时生产任务 列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">临时生产任务 ({{ $subTitle }})</h3>
                {{--右侧最小化按钮--}}
                <div class="btn-group btn-group-sm pull-right">
                    <a href="{{ url('tempTask') }}" class="btn btn-flat btn-{{ request('target', '') == '' ? 'info' : 'default' }}">已发布</a>
                    <a href="{{ url('tempTask') }}?target=mineCreate" class="btn btn-flat btn-{{ request('target') == 'mineCreate' ? 'info' : 'default' }}">我创建的</a>
                    <a href="{{ url('tempTask') }}?target=minePrincipal" class="btn btn-flat btn-{{ request('target') == 'minePrincipal' ? 'info' : 'default' }}">我负责的</a>
                    @if(in_array(array_flip(\App\Model\Account::$TEMP_TASK_POSITIONS)[session('account.temp_task_position')],['ParagraphPrincipal','ParagraphCrew','WorkshopPrincipal','ParagraphEngineer']))
                        <a href="{{ url('tempTask/create') }}?page{{ request('page',1) }}" class="btn btn-flat btn-sm btn-success"><i class="fa fa-plus">&nbsp;</i> 新建</a>
                    @endif
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>任务编号&标题</th>
                        <th>发起人</th>
                        <th>接收单位</th>
                        <th>负责人</th>
                        <th>状态&阶段</th>
                        <th>类型</th>
                        <th>截止日期</th>
                        <th>完成日期</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($tempTasks)
                        @foreach($tempTasks as $tempTask)
                            <tr>
                                <td>[{{ $tempTask->serial_number }}] {{ $tempTask->title }}</td>
                                <td>[{{ $tempTask->mode }}] {{ $tempTask->initiator->nickname ?? '' }}</td>
                                <td>{{ $tempTask->receive_paragraph->name }}</td>
                                <td>{{ $tempTask->principal->nickname ?? '' }}</td>
                                <td>{{ $tempTask->status }}</td>
                                <td>{{ $tempTask->type }}</td>
                                <td>{{ $tempTask->expire_at ? \Carbon\Carbon::parse($tempTask->expire_at)->format('Y-m-d') : '' }}</td>
                                @if($tempTask->finish_at ? \Carbon\Carbon::parse($tempTask->finish_at)->timestamp : 0 < time())
                                    <td><span style="color: red;">{{ $tempTask->finish_at ? \Carbon\Carbon::parse($tempTask->finish_at)->format('Y-m-d') : '' }}</span></td>
                                @else
                                    <td><span>{{ $tempTask->finish_at ? \Carbon\Carbon::parse($tempTask->finish_at)->format('Y-m-d') : '' }}</span></td>
                                @endif
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if(array_flip($tempTaskStatuses)[$tempTask->status] == '101_UN_PUBLISH')
                                            <a href="{{ url('tempTask',$tempTask->id) }}/edit" class="btn btn-warning btn-flat"><i class="fa fa-pencil">&nbsp;</i>编辑</a>
                                            <a href="javascript:" class="btn btn-danger btn-flat" onclick="fnDelete({{ $tempTask->id }})"><i class="fa fa-times">&nbsp;</i>删除</a>
                                            <a href="javascript:" class="btn btn-success btn-flat" onclick="fnPublish({{ $tempTask->id  }})"><i class="fa fa-share-alt">&nbsp;</i>发布</a>
                                        @else
                                            <a href="{{ url('tempTask', $tempTask->id) }}" class="btn btn-default btn-flat">详情</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
            @if($tempTasks->hasPages())
                <div class="box-footer">
                    {{ $tempTasks->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        /**
         * 删除
         * @param id
         */
        function fnDelete(id) {
            if (confirm('删除不可恢复，是否确认删除'))
                $.ajax({
                    url: `{{ url('tempTask') }}/${id}`,
                    type: 'DELETE',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('tempTask') }}/${id} success:`, res);
                        alert(res['msg']);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTask') }}/${id} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
        }

        /**
         * 发布
         * @param id
         */
        function fnPublish(id) {
            if (confirm('发布后不可撤回，是否确认发布'))
                $.ajax({
                    url: `{{ url('tempTask') }}/${id}/publish`,
                    type: 'PUT',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('tempTask') }}/${id}/publish success:`, res);
                        alert(res['msg']);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTask') }}/${id}/publish fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
        }
    </script>
@endsection

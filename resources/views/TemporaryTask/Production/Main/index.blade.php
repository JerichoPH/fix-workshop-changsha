@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            {{session('workshopName')}}
            <small>临时生产任务</small>
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
                <h3 class="box-title">临时生产任务 列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right"></div>
            </div>
            <div class="box-body">
                <div class="table-responsive table-responsive-sm table-responsive-md table-responsive-lg">
                    <table class="table table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>标题</th>
                            <th>发起人</th>
                            <th>接收单位</th>
                            <th>段负责人</th>
                            <th>任务编号</th>
                            <th>状态</th>
                            <th>阶段</th>
                            <th>截止日期/完成时间</th>

                        </tr>
                        </thead>
                        <tbody>
                        @if ($main_tasks)
                            @foreach($main_tasks as $main_task)
                                <tr>
                                    <td><a href="{{url('temporaryTask/production/main',$main_task['id'])}}?page={{request('page',1)}}">{{$main_task['title']}}</a></td>
                                    <td>{{ $main_task['initiator_affiliation_name'] }} : {{ $main_task['initiator_name'] }}</td>
                                    <td>{{ $main_task['paragraph_name'] }}</td>
                                    <td>{{ $main_task['paragraph_principal_name'] }}</td>
                                    <td>{{ $main_task['serial_num']}}</td>
                                    <td>{{ $main_task['status_name']}}</td>
                                    <td>{{ $main_task['stage_name']}}</td>
                                    <td>{{ $main_task['expire_at'] ? \Carbon\Carbon::parse($main_task['expire_at'])->format('Y-m-d') : '' }}{{ $main_task['finished_at'] ? '/'.\Carbon\Carbon::parse($main_task['finished_at'])->format('Y-m-d') : ''}}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @if($main_tasks->hasPages())
                <div class="box-footer">
                    {{ $main_tasks->appends(['page'=>request('page',1)])->links() }}
                </div>
            @endif
        </div>
    </section>

    <section class="content">
        <div id="divModalMakeSubTask103"></div>
    </section>
@endsection
@section('script')
    <script>
        /**
         * 发布
         * @param mainTaskId
         * @param mainTaskTitle
         */
        function modelMakeSubTask103(mainTaskId, mainTaskTitle) {
            $.ajax({
                url: `{{url('temporaryTask/production/main/makeSubTask103')}}/${mainTaskId}`,
                type: "get",
                data: {},
                async: false,
                success: function (res) {
                    // console.log('success:', res);
                    $("#divModalMakeSubTask103").html(res);
                    $("#modalMakeSubTask").modal('show');
                },
                error: function (err) {
                    // console.log('fail:', err);
                    if (err['status'] === 444) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                },
            });
        }
    </script>
@endsection

@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            现场检修任务项目管理
            <small>列表</small>
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">现场检修任务项目列表</h3>
                    <!--右侧最小化按钮-->
                    <div class="pull-right btn-group">
                        <a href="{{url('task/checkProject/create')}}" class="btn btn-flat btn-success"><i class="fa fa-plus-square"></i> 新建项目</a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>项目类型</th>
                            <th>项目名称</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($checkProjects as $checkProject)
                            <tr>
                                <td>{{ $checkProject->type['text'] }}</td>
                                <td>{{ $checkProject->name }}</td>
                                <td>
                                    <a href="{{url('task/checkProject',$checkProject->id)}}/edit" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                    <a href="javascript:" onclick="fnDelete('{{$checkProject->id}}')" class="btn btn-danger btn-flat">删除</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($checkProjects->hasPages())
                    <div class="box-footer">
                        {{ $checkProjects->links() }}
                    </div>
                @endif
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        $(function () {

        });

        function fnDelete(id) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: `{{url('task/checkProject')}}/${id}`,
                    type: 'delete',
                    success: function (response) {
                        console.log(`success：`, response)
                        location.href = "{{url('task/checkProject')}}";
                    },
                    error: function (error) {
                        console.log(`error:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error['responseJSON']['msg']);
                        location.reload();
                    }
                });
        }
    </script>
@endsection

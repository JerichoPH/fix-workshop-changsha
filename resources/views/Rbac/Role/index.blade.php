@extends('Layout.index')
@section('content')
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">角色列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('rbac/role/create')}}" class="btn btn-default btn-flat">新建</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <theader>
                        <tr>
                            <th>名称</th>
                            <th>操作</th>
                        </tr>
                    </theader>
                    <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>{{$role->name}}</td>
                            <td>
                                <a href="{{url('rbac/role',$role->id)}}/edit?page={{request('page')}}" class="btn btn-default btn-sm btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                <a href="javascript:" onclick="fnDelete({{$role->id}})" class="btn btn-danger btn-sm btn-flat">删除</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($roles->hasPages())
                <div class="box-footer">
                    {{ $roles->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        /**
         * 删除
         * @param {int} id 编号
         */
        fnDelete = function (id) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: "{{url('rbac/role')}}/" + id,
                    type: "delete",
                    data: {id: id},
                    success: function (response) {
                        console.log('success:', response);
                        alert(response);
                        location.reload();
                    },
                    error: function (error) {
                        // console.log('fail:', error);
                        alert(error.responseText);
                        if (error.status == 401) location.href = "{{ url('login') }}";
                    }
                });
        };
    </script>
@endsection

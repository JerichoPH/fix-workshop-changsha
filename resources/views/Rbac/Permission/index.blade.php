@extends('Layout.index')
@section('content')
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">权限列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('rbac/permission/create')}}" class="btn btn-default btn-flat">新建</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>名称</th>
                        <th>访问方法</th>
                        <th>访问路径</th>
                        <th>所属分组</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($permissions as $permission)
                        <tr>
                            <td>{{$permission->id}}</td>
                            <td>{{$permission->name}}</td>
                            <td>{{$permission->http_method}}</td>
                            <td>{{$permission->http_path}}</td>
                            <td>{{$permission->permissionGroup?$permission->permissionGroup->name:''}}</td>
                            <td>
                                <a href="{{url('rbac/permission',$permission->id)}}/edit?page={{request()->page}}" class="btn btn-default btn-sm btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                <a href="javascript:" onclick="fnDelete({{$permission->id}})" class="btn btn-danger btn-sm btn-flat">删除</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($permissions->hasPages())
                <div class="box-footer">
                    {{ $permissions->links() }}
                </div>
            @endif
        </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        /**
         * 删除
         * @param {int} id 编号
         */
        fnDelete = function (id) {
            $.ajax({
                url: "{{url('rbac/permission')}}/" + id,
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

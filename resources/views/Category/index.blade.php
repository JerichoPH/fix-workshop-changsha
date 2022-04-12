@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            种类管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">设备种类列表</h3>
                {{--右侧最小化按钮--}}
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ url('category/create') }}?page={{ request('page',1) }}" class="btn btn-success btn-flat">新建</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>名称</th>
                        <th>唯一标识</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->unique_code }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ url('category',$category->id) }}/edit?page={{ request('page',1) }}" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                    <a href="javascript:" onclick="fnDelete({{ $category->id }})" class="btn btn-danger btn-flat">删除</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($categories->hasPages())
                <div class="box-footer">
                    {{ $categories->appends(['page'=>request('page',1)])->links() }}
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
                    url: `{{url('category')}}/${id}`,
                    type: "delete",
                    data: {id: id},
                    success: function (response) {
                        console.log('success:', response);
                        alert(response);
                        location.reload();
                    },
                    error: function (error) {
                        console.log('fail:', error);
                    }
                });
        };

        /**
         * 从数据中台同步到本地
         */
        function fnSynchronization() {
            $.ajax({
                url: `{{ url('basic/category/backupFromSPAS') }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('basic/category/backupFromSPAS') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('basic/category/backupFromSPAS') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

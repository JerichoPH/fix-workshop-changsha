@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            供应商管理
            <small>列表</small>
        </h1>
        {{--        <ol class="breadcrumb">--}}
        {{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--            <li class="active">列表</li>--}}
        {{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">供应商列表</h3>
                {{--右侧最小化按钮--}}
                <div class="pull-right btn-group btn-group-sm">
                    {{--<a href="javascript:" class="btn btn-flat btn-primary" onclick="fnSynchronization()"><i class="fa fa-exclamation">&nbsp;</i>覆盖同步</a>--}}
                    {{--<a href="{{ url('factory/batch') }}?page={{ request('page',1) }}" class="btn btn-flat btn-default">批量导入</a>--}}
                    {{--<a href="{{ url('factory/create') }}?page={{ request('page',1) }}" class="btn btn-flat btn-success">新建</a>--}}
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>名称</th>
                        <th>联系电话</th>
                        <th>官网地址</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($factories as $factory)
                        <tr>
                            <td>{{ $factory->name }}</td>
                            <td>{{ $factory->unique_code }}</td>
                            <td>{{ $factory->phone }}</td>
                            <td>
                                @if($factory->official_home_link)
                                    <a href="http://{{ $factory->official_home_link }}" target="_blank">打开官网</a>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    {{--<a href="{{ url('factory',$factory->id) }}/edit?page={{ request('page',1) }}" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>--}}
                                    {{--<a href="javascript:" onclick="fnDelete({{ $factory->id }})" class="btn btn-danger btn-flat">删除</a>--}}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($factories->hasPages())
                <div class="box-footer">
                    {{ $factories->links() }}
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
        function fnDelete(id) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: "{{url('factory')}}/" + id,
                    type: "delete",
                    data: {id: id},
                    success: function (response) {
                        console.log('success:', response);
                        // alert(response);
                        location.reload();
                    },
                    error: function (error) {
                        console.log('fail:', error);
                    }
                });
        }

        /**
         * 从数据中台同步到本地
         */
        function fnSynchronization() {
            $.ajax({
                url: `{{ url('factory/backupFromSPAS') }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('factory/backupFromSPAS') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('factory/backupFromSPAS') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

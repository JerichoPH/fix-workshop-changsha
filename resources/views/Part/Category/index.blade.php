@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            部件种类管理
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
                <h3 class="box-title">部件种类列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('/part/category/create')}}" class="btn btn-success btn-flat btn-sm">新建</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>名称</th>
                        <th>所属种类</th>
                        <th>所属器材类型</th>
                        <th>关键部件</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($part_categories as $part_category)
                        <tr>
                            <td>{{ $part_category->name }}</td>
                            <td>{{ $part_category->Category ? $part_category->Category->name : '' }}</td>
                            <td>
                                {{ $part_category->EntireModel ? ($part_category->EntireModel->Category ? $part_category->EntireModel->Category->name.'&emsp;»&emsp;' : '') : '' }}
                                {{ $part_category->EntireModel ? $part_category->EntireModel->name : '' }}
                            </td>
                            <td>{{ $part_category->is_main == 1 ? '是' : '否' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('partCategory.edit',$part_category->id) }}" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                    <a href="javascript:" class="btn btn-danger btn-flat" onclick="fnDelete({{ $part_category->id }})">删除</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($part_categories->hasPages())
                <div class="box-footer">
                    {{ $part_categories->appends(["page"=>request("page")])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');

        $(function () {
            if ($select2.length > 0) $select2.select2();
        });

        /**
         * 删除
         * @param {int} id 编号
         */
        fnDelete = function (id) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: "{{url('/part/category')}}/" + id,
                    type: "delete",
                    data: {id: id},
                    success: function (response) {
                        console.log('success:', response);
                        location.reload();
                    },
                    error: function (error) {
                        console.log('fail:', error);
                    }
                });
        };
    </script>
@endsection

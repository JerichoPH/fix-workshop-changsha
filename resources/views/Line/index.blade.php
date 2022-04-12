@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
      <h1>
        线别管理
        <small>列表</small>
      </h1>
{{--      <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-home"></i> 首页</a></li>--}}
{{--        <li class="active">基础数据</li>--}}
{{--        <li class="active">线别管理</li>--}}
{{--      </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">线别列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('line/create')}}" class="btn btn-flat btn-success">新建</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>名称</th>
                            <th>线别编码</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lines as $line)
                        <tr>
                            <td>{{ $line->id }}</td>
                            <td>{{ $line->name }}</td>
                            <td>{{ $line->unique_code }}</td>
                            <td>
                                <a href="{{ url('line') }}/{{$line->id}}/edit" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i>编辑</a>
                                <a href="javascript:" class="btn btn-danger btn-flat" onclick="fnDelete('{{$line->id}}')">删除</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($lines->hasPages())
                <div class="box-footer">
                    {{ $lines->links() }}
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
            if (confirm('删除不可恢复，是否确认？')) {
                $.ajax({
                    url: "{{url('line')}}/" + id,
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
            }
        }

    </script>
@endsection

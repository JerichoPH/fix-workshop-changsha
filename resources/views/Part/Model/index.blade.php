@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            部件型号管理
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
                <h3 class="box-title">部件类型列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <div class="btn-group btn-group-sm">
                        <a href="?download=1" target="_blank" class="btn btn-default btn-flat">下载</a>
                        <a href="{{ url('part/model/create') }}?page={{request('page',1)}}" class="btn btn-success btn-flat btn-sm">新建</a>
                    </div>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>名称</th>
                        <th>类型代码</th>
                        <th>设备类型</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($partModels as $partModel)
                        <tr>
                            <td>{{$partModel->id}}</td>
                            <td>{{$partModel->name}}</td>
                            <td>{{$partModel->unique_code}}</td>
                            <td>
                                @if($partModel->Category)
                                    <a href="{{url('category',$partModel->category_unique_code)}}" target="_blank">{{$partModel->Category ? $partModel->Category->name : ''}}</a>
                                @else
                                    {{$partModel->category_unique_code}}
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{url('part/model',$partModel->unique_code)}}/edit?page={{request('page',1)}}" class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                    <a href="javascript:" onclick="fnDelete({{$partModel->id}})" class="btn btn-danger btn-flat">删除</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($partModels->hasPages())
                <div class="box-footer">
                    {{ $partModels->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        $(function () {
            $select2.select2();
        });

        /**
         * 删除
         * @param {int} id 编号
         */
        fnDelete = function (id) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: "{{url('part/model')}}/" + id,
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
    </script>
@endsection

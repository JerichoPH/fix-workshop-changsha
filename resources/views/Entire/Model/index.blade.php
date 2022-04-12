@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            类型管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')

        <form action="" method="get">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">搜索</h3>
                    <div class="btn-group btn-group-sm pull-right">
                        <button type="submit" class="btn btn-flat btn-default"><i class="fa fa-search">&nbsp;</i>搜索</button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <div class="input-group-addon">代码：</div>
                                <input type="text" name="unique_code" id="txtUniqueCode" class="form-control" value="{{ request('unique_code') }}" onfocus="fnTxtUniqueCodeFocus()">
                                <div class="input-group-addon">名称：</div>
                                <input type="text" name="name" id="txtName" class="form-control" value="{{ request('name') }}" onfocus="fnTxtNameFocus()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">整件类型列表</h3>
                <!--右侧最小化按钮-->
                <div class="box-tools pull-right">
                    <div class="btn-group btn-group-sm">
                        <a href="?download=1" target="_blank" class="btn btn-flat btn-default">下载</a>
                        <a href="{{url('entire/model/batch')}}?page={{request('page',1)}}" class="btn btn-flat btn-default">导入</a>
                        <a href="{{url('entire/model/batchFactory')}}?page={{request('page',1)}}" class="btn btn-flat btn-default">批量导入供应商关系</a>
                        <a href="{{url('entire/model/create')}}?page={{request('page',1)}}" class="btn btn-flat btn-success">新建</a>
                    </div>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>名称</th>
                        <th>类型代码</th>
                        <th>设备类型</th>
                        <th>检修周期</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($entireModels as $entireModel)
                        <tr>
                            <td>{{ $entireModel->name }}</td>
                            <td>{{ $entireModel->unique_code }}</td>
                            <td>
                                @if($entireModel->Category)
                                    <a href="{{ url('category',$entireModel->category_unique_code) }}">{{ $entireModel->Category ? $entireModel->Category->name : '' }}</a>
                                @else
                                    {{ $entireModel->category_unique_code }}
                                @endif
                            </td>
                            <td>{{ $entireModel->fix_cycle_value.$entireModel->fix_cycle_unit }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ url('entire/model',$entireModel->unique_code) }}/edit?page={{ request('page',1) }}" class="btn btn-dafault btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                    <a href="javascript:" onclick="fnDelete({{ $entireModel->id }})" class="btn btn-danger btn-flat">删除</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($entireModels->hasPages())
                <div class="box-footer">
                    {{ $entireModels->appends(['page'=>request('page',1)])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select();
        });

        /**
         * 删除
         * @param {int} id 编号
         */
        fnDelete = id => {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: `{{url('entire/model')}}/${id}`,
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
         * 代码输入框获取焦点
         */
        function fnTxtUniqueCodeFocus() {
            $('#txtName').val('');
        }

        /**
         * 名称获取焦点
         */
        function fnTxtNameFocus() {
            $('#txtUniqueCode').val('');
        }
    </script>
@endsection

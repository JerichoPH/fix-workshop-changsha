@extends('Layout.index')
@section('content')
    <section class="content">
        @include('Layout.alert')
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">标准值列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    @if($type == 'product')
                        <a href="{{url('measurements/create')}}?page={{request('page',1)}}&warehouseProductId={{$warehouseProductId}}&type=product" class="btn btn-default btn-sm btn-flat">新建</a>
                    @elseif($type=='self')
                        <a href="{{url('measurements/batch')}}?page={{request('page',1)}}" class="btn btn-default btn-sm btn-flat">批量导入</a>
                        <a href="{{url('measurements/create')}}?page={{request('page',1)}}&type=self" class="btn btn-default btn-sm btn-flat">新建</a>
                    @endif
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>编号</th>
                        <th>种类</th>
                        <th>类型</th>
                        <th>测试项</th>
                        <th>允许值范围</th>
                        <th>参照描述</th>
                        <th>行为</th>
                        <th>额外测试项</th>
                        <th>特性</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($measurements as $measurement)
                        <tr>
                            <td>{{$measurement->identity_code}}</td>
                            <td>{{@$measurement->EntireModel->Category->name}}</td>
                            <td>{{@$measurement->EntireModel->name}}</td>
                            <td>{{@$measurement->key}}</td>
                            @if($measurement->allow_min == $measurement->allow_max)
                                <td>{{$measurement->allow_min}}&nbsp;&nbsp;{{$measurement->unit}}</td>
                            @else
                                <td>{{$measurement->allow_min}}&nbsp;&nbsp;～&nbsp;{{$measurement->allow_max}}&nbsp;&nbsp;{{$measurement->unit}}</td>
                            @endif
                            <td>{{$measurement->allow_explain}}</td>
                            <td>{{$measurement->operation}}</td>
                            <td>{{$measurement->extra_tag}}</td>
                            <td>{{$measurement->character}}</td>
                            <td class="btn-group btn-group-sm">
                                <a href="{{url('measurements',$measurement->id)}}/edit?page={{request('page',1)}}" class="btn btn-dafault btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                <a href="javascript:" onclick="fnDelete({{$measurement->id}})" class="btn btn-danger btn-flat">删除</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($measurements->hasPages())
                <div class="box-footer">
                    {{ $measurements->links() }}
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
                    url: `{{url('measurements')}}/${id}`,
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
         * 打开添加测试模板窗口
         */
        fnCreateMeasurement = function (warehouseProductId) {
            $.ajax({
                url: "{{url('measurements/create')}}",
                type: "get",
                data: {warehouseProductId: warehouseProductId, type: 'product'},
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    // alert(response);
                    // location.reload();
                    $("#divModal").html(response);
                    $("#modal").modal('show');
                },
                error: function (error) {
                    // console.log('fail:', error);
                    alert(error.responseText);
                    if (error.status == 401) location.href = "{{ url('login') }}";
                },
            });
        };
    </script>
@endsection

@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            站改管理
            <small>{{ request('direction','IN') === 'IN' ? '入所' : '出所' }}计划列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            @if(request('direction','IN') === 'IN')--}}
{{--                <li><a href="{{ url('repairBase/stationReform') }}?direction=OUT">出所计划列表</a></li>--}}
{{--            @else--}}
{{--                <li><a href="{{ url('repairBase/stationReform') }}?direction=IN">入所计划列表</a></li>--}}
{{--            @endif--}}
{{--            <li class="active">{{ request('direction','IN') === 'IN' ? '入所' : '出所' }}计划列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">站改{{ request('direction','IN') == 'IN' ? '入所' : '出所' }}列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    @if(request('direction','IN') == 'IN')
                        <a href="javascript:" onclick="create()" class="btn btn-flat btn-success"><i class="fa fa-plus">&nbsp;</i>新建入所记录</a>
                    @endif
                </div>
            </div>
            <div class="box-body table-responsive">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>编号</th>
                            <th>状态</th>
                            <th>车站</th>
                            <th>经办人</th>
                            <th>时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($station_reforms as $station_reform)
                            <tr>
                                <td>{{$station_reform->serial_number}}</td>
                                <td>{{$station_reform->status['text']}}</td>
                                <td>{{@$station_reform->WithStation->name}}</td>
                                <td>{{@$station_reform->WithAccount->nickname}}</td>
                                <td>{{$station_reform->created_at->format('Y-m')}}</td>
                                <td>
                                    操作
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($station_reforms->hasPages())
                <div class="box-footer">
                    {{ $station_reforms->appends([
                        'direction'=>request('direction','IN'),
                    ])->links() }}
                </div>
            @endif
        </div>
        <div id="create"></div>
    </section>
@endsection
@section('script')
    <script>
        $(function () {

        });

        function create() {
            $.ajax({
                url: "{{url('repairBase/stationReform/create')}}",
                type: "get",
                async: true,
                success: function (response) {
                    $("#create").html(response);
                    $("#stationReform").modal("show");
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }
    </script>
@endsection

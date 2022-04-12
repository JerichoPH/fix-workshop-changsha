@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备详情
            <small>时间线</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('search')}}"><i class="fa fa-users">&nbsp;</i>设备详情</a></li>--}}
{{--            <li class="active">设备履历</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">设备履历</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <form class="form-horizontal" id="frmCreate">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <ul class="timeline">
                                @foreach($entireInstanceLogs as $month => $entireInstanceLog)
                                    <li class="time-label"><span class="bg-red">{{$month}}</span></li>
                                    @foreach($entireInstanceLogs[$month] as $log)
                                        <li>
                                            <i class="fa {{$icons[$log->type]}} bg-aqua"></i>
                                            <div class="timeline-item">
                                                <span class="time"><i class="fa fa-clock-o"></i> {{\Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$log->created_at)->toDateString()}}</span>
                                                @if($log->description)
                                                    <h3 class="timeline-header">{!! $log->url ? '<a target="_blank" href="'.$log->url.'">&nbsp;查看详情</a>' : '' !!}</h3>
                                                    <div class="timeline-body">{{$log->description}}</div>
                                                @else
                                                    <h3 class="timeline-header no-border">{!! $log->url ? '<a target="_blank" href="'.$log->url.'">&nbsp;'.$log->name.'</a>' : '<span>'.$log->name.'</span>' !!}</h3>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>
        $(function () {
            $('.select2').select2();
        });

        /**
         * 新建
         */
        fnCreate = function () {
            $.ajax({
                url: "{{url('search')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

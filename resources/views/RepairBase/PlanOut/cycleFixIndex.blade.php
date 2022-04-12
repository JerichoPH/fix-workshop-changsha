@extends('Layout.index')
@section('style')

@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            周期修
            <small>出所任务</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">周期修出所任务</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        {{--周期修任务出所--}}
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h1 class="box-title">周期修出所任务</h1>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right"></div>
                    </div>
                    <div class="box-body">
                        <div class="content text-center">
                            <a class="btn btn-default btn-lg" style="padding:65px;font-size:25px;" href="{{url('repairBase/planOut/cycleFixWithStation')}}">按照车站筛选进行周期修出所</a>
                            <a class="btn btn-default btn-lg" style="padding:65px;font-size:25px;" href="{{url('repairBase/planOut/cycleFixWithMonth')}}">按照月份筛选进行周期修出所</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/layer/layer.js"></script>
    <script>
        $(function () {
            $('.select2').select2();
        });


    </script>
@endsection

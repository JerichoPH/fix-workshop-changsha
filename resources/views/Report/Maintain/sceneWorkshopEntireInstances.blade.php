@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/skins/_all-skins.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            台账设备
            <small>列表</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li><a href="{{url('report/sceneWorkshop2',$currentSceneWorkshop)}}?categoryUniqueCode={{request('categoryUniqueCode')}}&entireModelUniqueCode={{request('entireModelUniqueCode')}}&status={{request('status')}}"> 台账</a></li>--}}
        {{--    <li class="active">列表</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')

        {{--查询--}}
        <div class="row">
            <form id="frmScreen">
                <div class="col-md-12">
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h1 class="box-title">查询</h1>
                            {{--右侧最小化按钮--}}
                            <div class="box-tools pull-right">
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                            </div>
                        </div>
                        <div class="box-body form-horizontal">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-addon">现场车间</div>
                                        <select
                                            id="selSceneWorkshop"
                                            name="scene_workshop_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                            @foreach($sceneWorkshops as $sceneWorkshopUniqueCode => $sceneWorkshopName)
                                                <option value="{{$sceneWorkshopUniqueCode}}" {{$sceneWorkshopUniqueCode == $currentSceneWorkshop ? 'selected' : ''}}>{{$sceneWorkshopName}}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select
                                            id="selCategory"
                                            name="category_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectCategory(this.value)"
                                        >
                                            @foreach($categories as $categoryUniqueCode => $categoryName)
                                                <option value="{{$categoryUniqueCode}}" {{$categoryName == $currentCategory ? 'selected' : ''}}>{{$categoryName}}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">类型</div>
                                        <select
                                            id="selEntireModel"
                                            name="entire_model_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                            onchange="fnSelectEntireModel(this.value)"
                                        >
                                            @foreach($entireModels as $entireModelUniqueCode => $entireModelName)
                                                <option value="{{$entireModelUniqueCode}}" {{$entireModelName == $currentEntireModel ? 'selected' : ''}}>{{$entireModelName}}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">型号</div>
                                        <select
                                            id="selSubModel"
                                            name="sub_model_name"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                            @foreach($subModels as $subModelUniqueCode => $subModelName)
                                                <option value="{{$subModelUniqueCode}}" {{$subModelName == $currentSubModel ? 'selected' : ''}}>{{$subModelName}}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">状态</div>
                                        <select
                                            id="selStatus"
                                            name="status"
                                            class="form-control select2"
                                            style="width:100%;"
                                        >
                                            @foreach($statuses as $statusCode => $statusName)
                                                <option value="{{$statusName}}" {{$statusCode == $currentStatus ? 'selected' : ''}}>{{$statusName}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">台账设备</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <div class="box-body table-responsive">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>设备编号</th>
                            <th>型号</th>
                            <th>状态</th>
                            <th>位置</th>
                            <th>下次检修时间</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entireInstances as $entireInstance)
                            <tr>
                                <td>{{$entireInstance->identity_code}}</td>
                                <td>
                                    {{$entireInstance->category_name}}
                                    {{$entireInstance->entire_model_name}}
                                    {{$entireInstance->sub_model_name}}
                                </td>
                                <td>{{$statuses[$entireInstance->status]}}</td>
                                <td>
                                    {{$entireInstance->maintain_station_name}}
                                    {{$entireInstance->maintain_location_code}}
                                    {{$entireInstance->crossroad_number}}
                                    {{$entireInstance->open_direction}}
                                    {{$entireInstance->to_direction}}
                                    {{$entireInstance->line_name}}
                                    {{$entireInstance->said_rod}}
                                </td>
                                <td>{{$entireInstance->next_fixing_day}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        $select2 = $('.select2');
        $(function () {
            if ($select2.length > 0) $select2.select2();

        });

    </script>
@endsection

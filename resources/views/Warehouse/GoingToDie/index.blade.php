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
            即将到期
            <small>列表</small>
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        {{--查询--}}
        <div class="row">
            <form id="frmScreen">
                <div class="col-md-12">
                    {{--<div class="box box-default collapsed-box">--}}
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
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-addon">现场车间</div>
                                        <select id="selSceneWorkshop" name="scene_workshop_unique_code" class="form-control select2" style="width:100%;" onchange="fnGetStationBySceneWorkshop(this.value)">
                                            @if($sceneWorkshops)
                                                <option value="">全部</option>
                                                @foreach($sceneWorkshops as $uniqueCode=>$name)
                                                    <option value="{{$uniqueCode}}" {{request('scene_workshop_unique_code') == $uniqueCode ? 'selected' : ''}}>{{$name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="input-group-addon">车站</div>
                                        <select id="selStation" name="station_name" class="form-control select2" style="width:100%;">

                                        </select>
                                        <div class="input-group-addon">种类</div>
                                        <select id="selCategory" name="category_unique_code" class="form-control select2" style="width:100%;" onchange="fnSelectCategory(this.value)">
                                            <option value="">全部</option>
                                            @foreach($categories as $categoryUniqueCode=>$categoryName)
                                                <option value="{{ $categoryUniqueCode }}" {{ request('category_unique_code') == $categoryUniqueCode ? 'selected' : '' }}>{{ $categoryName }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">型号</div>
                                        <select id="selSubModel" name="sub_model_unique_code" class="form-control select2" style="width:100%;">

                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">排期列表 数量 {{$entireInstances->total()}}</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>到期时间</th>
                        <th>设备类型</th>
                        <th>设备型号</th>
                        <th>唯一标识</th>
                        <th>厂编号</th>
                        <th>所编号</th>
                        <th>站场</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($entireInstances as $entireInstance)
                        <tr>
                            <td>
                                {{\Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$entireInstance->next_fixing_day)->format('Y-m-d')}}
                            </td>
                            <td>{{$entireInstance->category_name}}</td>
                            <td>{{$entireInstance->entire_model_name}}({{$entireInstance->entire_model_unique_code}})</td>
                            <td><a href="{{url('search',$entireInstance->identity_code)}}">{{$entireInstance->identity_code}}</a></td>
                            <td>{{$entireInstance->factory_device_code}}</td>
                            <td>{{$entireInstance->serial_number}}</td>
                            <td>{{$entireInstance->maintain_station_name}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($entireInstances->hasPages())
                <div class="box-footer">
                    {{ $entireInstances->appends([
                        'scene_workshop_unique_code'=>request('scene_workshop_unique_code'),
                        'station_name'=>request('station_name'),
                        'category_unique_code'=>request('category_unique_code'),
                        'sub_model_unique_code'=>request('sub_model_unique_code'),
                    ])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
            //Date picker
            $('#datepicker').datepicker({
                locale: {
                    format: "YYYY-MM-DD",
                    separator: "~",
                    daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                    monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"]
                }
            });
            fnGetStationBySceneWorkshop(`{{request('scene_workshop_unique_code','')}}`);
            fnSelectCategory(`{{request('category_unique_code','')}}`);
        });

        /**
         * 选择种类，获取类型列表
         * @param {string} categoryUniqueCode
         */
        fnSelectCategory = categoryUniqueCode => {
            let html = '<option value="">全部<option>';
            if (categoryUniqueCode && categoryUniqueCode !== '') {
                $.ajax({
                    url: `{{url('category/getSubModelWithCategory')}}`,
                    type: 'get',
                    data: {
                        category_unique_code: categoryUniqueCode
                    },
                    async: false,
                    success: res => {
                        console.log(res);
                        $.each(res.data, (k, subModel) => {
                            html += `<option value=${subModel.unique_code} ${"{{request('sub_model_unique_code')}}" === subModel.unique_code ? 'selected' : ''}>${subModel.name}</option>`;
                        });
                    },
                    fail: err => {
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            }
            $('#selSubModel').html(html);
        };


        /**
         * 通过现场车间获取车站
         * @param uniqueCode
         */
        fnGetStationBySceneWorkshop = uniqueCode => {
            let html = `<option value="">全部</option>`;
            if (uniqueCode && uniqueCode !== '') {
                $.ajax({
                    url: `{{url('maintain/station')}}`,
                    type: 'get',
                    data: {
                        scene_workshop_unique_code: uniqueCode
                    },
                    dataType: 'json',
                    async: false,
                    success: response => {
                        console.log(response);
                        if (response.status === 200) {
                            $.each(response.data, function (k, station) {
                                html += `<option value="${station.name}" ${station.name === "{{request('station_name')}}" ? 'selected' : ''}>${station.name}</option>`;
                            })
                        } else {
                            alert(response.message);
                            location.reload();
                        }
                    },
                    fail: error => {
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    }
                });
            }
            $('#selStation').html(html);
        };

        /**
         * 查询
         */
        fnScreen = () => {
            location.href = `?page={{request('page',1)}}&scene_workshop_unique_code=${$('#selSceneWorkshop').val()}&station_name=${$('#selStation').val()}&category_unique_code=${$('#selCategory').val()}&sub_model_unique_code=${$('#selSubModel').val()}`;
        };
    </script>
@endsection

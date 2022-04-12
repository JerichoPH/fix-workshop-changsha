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
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">周期修出所任务</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
    @include('Layout.alert')
    <!--周期修任务出所-->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-4">
                                <h1 class="box-title">{{ $stations[$currentStationUniqueCode] ?? '' }} 周期修出所任务</h1>
                            </div>
                            <div class="col-md-8">
                                <div class="pull-right">
                                    <div class="input-group">
                                        <div class="input-group-addon">年</div>
                                        <select id="selYear" class="select2" style="width: 100%;" onchange="fnCurrentYear(this.value)">
                                            @foreach($yearLists as $year)
                                                <option value="{{ $year }}" {{$currentYear == $year ? 'selected' : ''}}>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">车站</div>
                                        <select id="selStation" class="select2" style="width: 100%;" onchange="fnCurrentStation(this.value)">
                                            @foreach($stations as $stationUniqueCode=>$stationName)
                                                <option value="{{ $stationUniqueCode }}" {{ $currentStationUniqueCode == $stationUniqueCode ? 'selected' : '' }}>{{ $stationName }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">工区</div>
                                        <select id="selWorkArea" class="select2" style="width: 100%;" onchange="fnCurrentWorkArea(this.value)">
                                            @foreach($workAreas as $workAreaId => $workAreaName)
                                                <option value="{{ $workAreaId }}" {{ $currentWorkAreaId == $workAreaId ? 'selected' : '' }}>{{ $workAreaName }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon"><a class="" href="{{url('repairBase/planOut/cycleFixWithMonth')}}">按照月份周期修出所</a></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right">
                        </div>
                    </div>
                    <div class="box-body table-responsive table-responsive-xl table-responsive-sm table-responsive-md table-responsive-lg">
                        <table class="table table-bordered table-hover table-condensed text-sm" id="table">
                            <thead>
                            <tr>
                                <th>月份</th>
                                <th>任务总数</th>
                                @foreach($subModels as $subModelName)
                                    <th>{{ $subModelName }}</th>
                                @endforeach
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($planWithMonths as $month => $planWithMonth)
                                <tr>
                                    <td>{{ $month }}</td>
                                    <td>{{ $planTotalWithMonths[$month] ?? 0 }}</td>
                                    @foreach($subModels as $subModelUniqueCode=>$subModelName)
                                        @if(array_key_exists($subModelUniqueCode,$planWithMonth['subModels']))
                                            <td class="
                                            @if($planWithMonth['billStatus'] =='')
                                            @if($planWithMonth['subModels'][$subModelUniqueCode]['style'] == 1)
                                                bg-green
@elseif($planWithMonth['subModels'][$subModelUniqueCode]['style'] == 2)
                                                bg-danger
@endif
                                            @endif
                                                ">
                                                {{ $planWithMonth['subModels'][$subModelUniqueCode]['count'] }}
                                            </td>
                                        @else
                                            <td>0</td>
                                        @endif
                                    @endforeach
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($planWithMonth['billId'] == '0' && $planWithMonth['billStatus'] == '')
                                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnMakeRepairBasePlanOutCycleFix(`{{$month}}`,`{{ $planTotalWithMonths[$month] ?? 0 }}`,{{json_encode($planWithMonth['subModels'],256)}})">添加出所单</a>
                                            @else
                                                @if($planWithMonth['billStatus'] == 'FINISH')
                                                    <a href="javascript:" class="btn btn-danger btn-flat" disabled>任务结束</a>
                                                @elseif($planWithMonth['billStatus'] == 'CLOSE')
                                                    <a href="javascript:" onclick="fnBillOpen(`{{ $planWithMonth['billId'] }}`)" class="btn btn-primary btn-flat">开启任务</a>
                                                @else
                                                    <a href="{{url('repairBase/planOut/cycleFix')}}/{{$planWithMonth['billId']}}" class="btn btn-primary btn-flat">继续出所</a>
                                                    <a href="javascript:" onclick="fnBillClose(`{{ $planWithMonth['billId'] }}`)" class="btn btn-danger btn-flat">关闭任务</a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
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

        /**
         * 生成
         */
        function fnMakeRepairBasePlanOutCycleFix(month, number, subModels) {
            let loading = layer.msg('任务生成中');
            $.ajax({
                url: `{{ url('repairBase/planOut/cycleFix') }}`,
                type: 'post',
                data: {
                    year: `{{$currentYear}}`,
                    month: month,
                    workAreaId: `{{$currentWorkAreaId}}`,
                    stationUniqueCode: `{{ $currentStationUniqueCode }}`,
                    number: number,
                    subModels: subModels,
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('repairBase/planOut/cycleFix') }} success:`, res);
                    if (res.status === 200) {
                        location.href = res.data.href;
                    } else {
                        alert(res.message);
                        location.reload();
                    }
                    layer.close(loading);
                },
                error: function (err) {
                    console.log(`{{ url('repairBase/planOut/makeCycleFix') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 选择工区
         * @param workAreaId
         */
        function fnCurrentWorkArea(workAreaId) {
            location.href = `?year=${$("#selYear").val()}&stationUniqueCode=${$('#selStation').val()}&workAreaId=${workAreaId}`;
        }

        /**
         * 切换年份
         * @param year
         */
        function fnCurrentYear(year) {
            location.href = `?year=${year}&workAreaId=${$('#selWorkArea').val()}&stationUniqueCode=${$('#selStation').val()}`;
        }

        /**
         * 切换车站
         * @param station
         */
        function fnCurrentStation(station) {
            location.href = `?year=${$('#selYear').val()}&stationUniqueCode=${station}&workAreaId=${$('#selWorkArea').val()}`;
        }

        /**
         * 关闭任务
         * @param billId
         */
        function fnBillClose(billId) {
            if (confirm('任务关闭无法恢复，是否确认？'))
                $.ajax({
                    url: `{{ url('repairBase/planOut/billWithClose') }}/${billId}`,
                    type: 'get',
                    data: {},
                    async: true,
                    success: response => {
                        console.log(`success:`, response);
                        location.reload();
                    },
                    error: error => {
                        console.log(`fail:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error['responseJSON']['msg']);
                        location.reload();
                    }
                });
        }

        /**
         * 开启任务
         * @param billId
         */
        function fnBillOpen(billId) {
            $.ajax({
                url: `{{ url('repairBase/planOut/billWithOpen') }}/${billId}`,
                type: 'get',
                data: {},
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    location.href = `{{ url('repairBase/planOut/cycleFix') }}/${billId}`
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
        }

    </script>
@endsection

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
                        <div class="row">
                            <div class="col-md-4">
                                <h1 class="box-title">{{$currentYear}}年{{ $currentMonth }}月份 周期修出所任务</h1>
                            </div>
                            <div class="col-md-8">
                                <div class="pull-right">
                                    <div class="input-group">
                                        <div class="input-group-addon">年</div>
                                        <select name="year" id="selYear" class="select2" style="width: 100%;" onchange="fnCurrentYear(this.value)">
                                            @foreach($yearLists as $year)
                                                <option value="{{ $year }}" {{$currentYear == $year ? 'selected' : ''}}>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">月</div>
                                        <select name="month" id="selMonth" class="select2" style="width: 100%;" onchange="fnCurrentMonth(this.value)">
                                            @for($i=1; $i<=12; $i++)
                                                <option value="{{ $i }}" {{ $currentMonth == $i ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                        <div class="input-group-addon">工区</div>
                                        <select name="work_area" id="selWorkArea" class="select2" style="width: 100%;" onchange="fnCurrentWorkArea(this.value)">
                                            @foreach($workAreas as $workAreaId => $workAreaName)
                                                <option value="{{ $workAreaId }}" {{ $currentWorkAreaId == $workAreaId ? 'selected' : '' }}>{{ $workAreaName }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon"><a class="" href="{{url('repairBase/planOut/cycleFixWithStation')}}">按照车站周期修出所</a></div>

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
                                <th>车站</th>
                                <th>任务总数</th>
                                @foreach($subModels as $subModelName)
                                    <th>{{ $subModelName }}</th>
                                @endforeach
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($planWithStations as $stationUniqueCode => $planWithStation)
                                <tr>
                                    <td>{{ $planWithStation['stationName'] }}</td>
                                    <td>{{ $planTotalWithStations[$stationUniqueCode] ?? 0 }}</td>
                                    @foreach($subModels as $subModelUniqueCode=>$subModelName)
                                        @if(array_key_exists($subModelUniqueCode,$planWithStation['subModels']))
                                            <td class="
                                            @if($planWithStation['billStatus'] =='')
                                            @if($planWithStation['subModels'][$subModelUniqueCode]['style'] == 1)
                                                bg-green
@elseif($planWithStation['subModels'][$subModelUniqueCode]['style'] == 2)
                                                bg-danger
@endif
                                            @endif
                                                ">
                                                {{ $planWithStation['subModels'][$subModelUniqueCode]['count'] }}
                                            </td>
                                        @else
                                            <td>0</td>
                                        @endif
                                    @endforeach
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($planWithStation['billId'] == '0' && $planWithStation['billStatus'] == '')
                                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnMakeRepairBasePlanOutCycleFix(`{{ $stationUniqueCode }}`,`{{ $planTotalWithStations[$stationUniqueCode] ?? 0 }}`,{{json_encode($planWithStation['subModels'],256)}})">添加出所单</a>
                                            @else
                                                @if($planWithStation['billStatus'] == 'FINISH')
                                                    <a href="javascript:" class="btn btn-danger btn-flat" disabled>任务结束</a>
                                                @elseif($planWithStation['billStatus'] == 'CLOSE')
                                                    <a href="javascript:" onclick="fnBillOpen(`{{ $planWithStation['billId'] }}`)" class="btn btn-primary btn-flat">开启任务</a>
                                                @else
                                                    <a href="{{url('repairBase/planOut/cycleFix')}}/{{$planWithStation['billId']}}" class="btn btn-primary btn-flat">继续出所</a>
                                                    <a href="javascript:" onclick="fnBillClose(`{{ $planWithStation['billId'] }}`)" class="btn btn-danger btn-flat">关闭任务</a>
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
        function fnMakeRepairBasePlanOutCycleFix(stationUniqueCode, number, subModels) {
            let loading = layer.msg('任务生成中');
            $.ajax({
                url: `{{ url('repairBase/planOut/cycleFix') }}`,
                type: 'post',
                data: {
                    year: `{{$currentYear}}`,
                    month: `{{$currentMonth}}`,
                    workAreaId: `{{$currentWorkAreaId}}`,
                    stationUniqueCode: stationUniqueCode,
                    number: number,
                    subModels: subModels,
                },
                async: true,
                success: function (res) {
                    if (res.status === 200) {
                        location.href = res.data.href;
                    } else {
                        alert(res.message);
                        location.reload();
                    }
                    layer.close(loading);
                },
                error: function (err) {
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
            location.href = `?year=${$("#selYear").val()}&month=${$('#selMonth').val()}&workAreaId=${workAreaId}`;
        }

        /**
         * 切换年份
         * @param year
         */
        function fnCurrentYear(year) {
            location.href = `?year=${year}&month=${$('#selMonth').val()}&workAreaId=${$('#selWorkArea').val()}`;
        }

        /**
         * 切换月份
         * @param month
         */
        function fnCurrentMonth(month) {
            location.href = `?year=${$('#selYear').val()}&month=${month}&workAreaId=${$('#selWorkArea').val()}`;
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

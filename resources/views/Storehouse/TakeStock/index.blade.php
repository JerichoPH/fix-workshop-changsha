@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            盘点历史
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">盘点历史</li>--}}
{{--        </ol>--}}
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
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <div class="input-group-addon">状态</div>
                                        <select id="state" name="state" class="form-control select2" style="width:100%;">
                                            <option value="">全部</option>
                                            @foreach($states as $state=>$stateName)
                                                <option value="{{$state}}" {{request('state') == $state ? 'selected' : ''}}>{{$stateName}}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-addon">结果</div>
                                        <select id="result" name="result" class="form-control select2" style="width:100%;">
                                            <option value="">全部</option>
                                            @foreach($results as $result=>$resultName)
                                                <option value="{{$result}}" {{request('result') == $result ? 'selected' : ''}}>{{$resultName}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-addon"><label style="font-weight: normal;"><input type="checkbox" id="chkMadeAt" value="1" {{request('use_made_at') == '1' ? 'checked' : ''}}>时间</label></div>
                                        <input name="updated_at" type="text" class="form-control pull-right" id="updatedAt" value="{{request('updated_at')}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="box">
            <div class="box-header">
                <h3 class="box-title">盘点列表</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">

                </div>
            </div>

            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <tbody>
                    <tr>
                        <th>盘点名称</th>
                        <th>时间</th>
                        <th>盘点人</th>
                        <th>状态</th>
                        <th>结果</th>
                        <th>差异</th>
                    </tr>
                    @foreach($takeStocks as $takeStock)
                        <tr>
                            <td>
                                @if ($takeStock->state == '盘点结束')
                                    <a href="{{url('storehouse/takeStock',$takeStock->unique_code)}}">{{ $takeStock->name }}盘点</a>
                                @else
                                    {{ $takeStock->name }}盘点
                                @endif
                            </td>
                            <td>{{$takeStock->updated_at}}</td>
                            <td>{{@$takeStock->WithAccount->account}}</td>
                            <td>{{$takeStock->state}}</td>
                            <td>{{$takeStock->result}}</td>
                            <td>
                                <a href="{{url('takeStock',$takeStock->unique_code)}}" style="color:{{$takeStock->result == '无差异' ? 'green' : 'red'}}">
                                    @if ($takeStock->result == '无差异')
                                        0
                                    @else
                                        @if ($takeStock->stock_diff >0)
                                            -{{$takeStock->stock_diff}}
                                        @endif
                                        @if ($takeStock->real_stock_diff >0)
                                            +{{$takeStock->real_stock_diff}}
                                        @endif
                                    @endif
                                </a>
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($takeStocks->hasPages())
                <div class="box-footer">
                    {{ $takeStocks->appends([
                            "state"=>request("state"),
                            "result"=>request("result"),
                            "updated_at"=>request("updated_at"),
                            "use_made_at"=>request("use_made_at"),
                            "area_id"=>request("area_id"),
                        ])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");

        $(function () {
            if ($select2.length > 0) $select2.select2();
            $('#updatedAt').daterangepicker({
                locale: {
                    format: "YYYY-MM-DD",
                    separator: "~",
                    daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                    monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    applyLabel: "确定",
                    cancelLabel: "取消",
                    fromLabel: "开始时间",
                    toLabel: "结束时间",
                    customRangeLabel: "自定义",
                    weekLabel: "W",
                },
                startDate: "{{$originAt}}",
                endDate: "{{$finishAt}}"
            });
        });


        /**
         * 查询
         */
        function fnScreen() {
            let state = $("#state").val();
            let result = $("#result").val();
            let updated_at = $("#updatedAt").val();
            let use_made_at = $("#chkMadeAt").is(":checked") ? "1" : "0";
            let area_id = $("#area_id").val();

            location.href = `{{url('storehouse/takeStock')}}?state=${state}&result=${result}&updated_at=${updated_at}&use_made_at=${use_made_at}&area_id=${area_id}`
        }

    </script>
@endsection

@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            报废管理
        </h1>
    </section>
    <form>
    <section class="content">
        @include('Layout.alert')
            {{--查询--}}
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-header">
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
                                        <div class="input-group-addon">
                                            <label style="font-weight: normal;">
                                                <input type="checkbox" id="chkMadeAt" value="1" {{request('use_made_at') == '1' ? 'checked' : ''}}>报废时间
                                            </label>
                                        </div>
                                        <input name="updated_at" type="text" class="form-control pull-right" id="updatedAt" value="{{request('updated_at')}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">
                        报废单
                    </h3>
                    {{--右侧最小化按钮--}}
                    <div class="box-tools pull-right">
                        <a href="{{url('storehouse/index/scrap/instance')}}" class="btn btn-success">报废</a>
                    </div>
                </div>

                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed">
                        <tbody>
                        <tr>
                            <th>报废时间</th>
                            <th>统计（种类）</th>
                            <th>操作人</th>
                            <th>联系人</th>
                        </tr>
                        @foreach($warehouses as $warehouse)
                            <tr>
                                <td>
                                    <a href="{{url('storehouse/index',$warehouse->id)}}">{{$warehouse->updated_at}}</a>
                                </td>
                                <td>
                                    @if(!empty($statistics) && array_key_exists($warehouse->unique_code,$statistics))
                                        <p>
                                            @foreach($statistics[$warehouse->unique_code] as $item)
                                                （{{$item['category_name']}}）：{{$item['count']}}<br>
                                            @endforeach
                                        </p>
                                    @endif
                                </td>
                                <td>{{$warehouse->WithAccount->nickname}}</td>
                                <td>{{$warehouse->WithAccount->phone}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($warehouses->hasPages())
                    <div class="box-footer">
                        {{ $warehouses->appends([
                                "direction"=>request("direction"),
                                "updated_at"=>request("updated_at"),
                                "use_made_at"=>request("use_made_at"),
                            ])->links() }}
                    </div>
                @endif
            </div>
    </section>
    </form>
@endsection
@section('script')
    <script>
        $(function () {
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
            let updated_at = $("#updatedAt").val();
            let use_made_at = $("#chkMadeAt").is(":checked") ? "1" : "0";
            location.href = `{{url('storehouse/index/scrap/order')}}?updated_at=${updated_at}&use_made_at=${use_made_at}`
        }

    </script>
@endsection

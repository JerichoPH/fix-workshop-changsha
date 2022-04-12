@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            送修管理
        </h1>
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
                                        <div class="input-group-addon">
                                            <label style="font-weight: normal;">
                                                <input type="checkbox" id="chkMadeAt" value="1" {{request('use_made_at') == '1' ? 'checked' : ''}}>送修时间
                                            </label>
                                        </div>
                                        <input name="updated_at" type="text" class="form-control pull-right" id="updatedAt" value="{{request('updated_at')}}">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="input-group">
                                        <div class="input-group-addon">根据设备编码查询送修单</div>
                                        <input type="text" id="material_unique_code" name="material_unique_code" class="form-control" value="{{request('material_unique_code')}}">
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
                <h3 class="box-title">
                    送修单
                    <small>送修单数量：{{$sendRepairs->total()}}；送修单设备数量：{{$material_counts}}</small>
                </h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('storehouse/sendRepair/sendRepairWithCheck')}}" class="btn btn-flat btn-success">验收送修设备</a>
                </div>
            </div>

            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <tbody>
                    <tr>
                        <th>送修时间</th>
                        <th>统计（类型）</th>
                        <th>经办人</th>
                        <th>送修单位</th>
                        <th>接收单位</th>
                        <th>状态</th>
                        <th>超期</th>
                        <th>操作</th>
                    </tr>
                    @foreach($sendRepairs as $sendRepair)
                        <tr class="
                        @if($sendRepair->state['value'] != 'END')
                        {{empty($sendRepair->repair_due_at) ? '' : $sendRepair->repair_due_at < \Carbon\Carbon::now()? 'danger':''}}
                        @endif
                            ">
                            <td>
                                <a href="{{url('storehouse/sendRepair',$sendRepair->unique_code)}}">{{$sendRepair->updated_at}}</a>
                            </td>
                            <td>
                                @if(!empty($statistics) && array_key_exists($sendRepair->unique_code,$statistics))
                                    <p>
                                        @foreach($statistics[$sendRepair->unique_code] as $item)
                                            （{{$item['category_name']}}）{{$item['model_name']}}：{{$item['count']}}<br>
                                        @endforeach
                                    </p>
                                @endif
                            </td>
                            <td>{{@$sendRepair->WithAccount->nickname}}</td>
                            <td>{{@$sendRepair->WithFromMaintain->name}}</td>
                            <td>{{empty($sendRepair->WithToFactory) ? empty($sendRepair->WithToMaintain) ? '' : $sendRepair->WithToMaintain->name : $sendRepair->WithToFactory->name}}</td>
                            <td>{{$sendRepair->state['text']}}</td>
                            <td>{{empty($sendRepair->repair_due_at) ? '' : $sendRepair->repair_due_at < \Carbon\Carbon::now()? '超期未返回':''}}</td>
                            <td>
                                <a href="{{url('storehouse/sendRepair/instanceWithSendRepair',$sendRepair->unique_code)}}" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($sendRepairs->hasPages())
                <div class="box-footer">
                    {{ $sendRepairs->appends([
                            "updated_at"=>request("updated_at"),
                            "material_unique_code"=>request("material_unique_code"),
                            "use_made_at"=>request("use_made_at"),
                        ])->links() }}
                </div>
            @endif
        </div>

    </section>
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
            let material_unique_code = $("#material_unique_code").val();
            let use_made_at = $("#chkMadeAt").is(":checked") ? "1" : "0";
            location.href = `{{url('storehouse/sendRepair')}}?updated_at=${updated_at}&use_made_at=${use_made_at}&material_unique_code=${material_unique_code}`
        }

    </script>
@endsection

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
            整件管理
            <small>列表</small>
        </h1>
<!--        <ol class="breadcrumb">-->
<!--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>-->
<!--            <li class="active">列表</li>-->
<!--        </ol>-->
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            {{--当月--}}
            <div class="col-md-4">
                <div class="box box-danger" style="height: 430px;">
                    <div class="box-header with-border">
                        <h3>设备动态统计（当月）</h3>
                    </div>
                    <div class="box-body chart-responsive" style="height: 350px;">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <div class="chart" id="chartDeviceDynamicStatusCurrentMonth" style="height: 300px; position: relative;"></div>
                            </div>
                            <div class="col-sm-4 col-md-4">
                                <p>&nbsp;</p>
                                <br>
                                <p style="font-size: 20px;">总数：{{json_decode($deviceDynamicStatusCurrentMonth,true)[0]}}</p>
                                <p style="font-size: 20px;">在用：{{json_decode($deviceDynamicStatusCurrentMonth,true)[1][0]['value']}}</p>
                                <p style="font-size: 20px;">备用：{{json_decode($deviceDynamicStatusCurrentMonth,true)[1][3]['value']}}</p>
                                <p style="font-size: 20px;">送检：{{json_decode($deviceDynamicStatusCurrentMonth,true)[1][2]['value']}}</p>
                                <p style="font-size: 20px;">维修：{{json_decode($deviceDynamicStatusCurrentMonth,true)[1][1]['value']}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{--近三个月--}}
            <div class="col-md-4">
                <div class="box box-warning" style="height: 430px;">
                    <div class="box-header with-border">
                        <h3>设备动态统计（近三个月）</h3>
                    </div>
                    <div class="box-body chart-responsive" style="height: 350px;">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <div class="chart" id="chartDeviceDynamicStatusNearlyThreeMonth" style="height: 300px; position: relative;"></div>
                            </div>
                            <div class="col-sm-4 col-md-4">
                                <p>&nbsp;</p>
                                <br>
                                <p style="font-size: 20px;">总数：{{json_decode($deviceDynamicStatusNearlyThreeMonth,true)[0]}}</p>
                                <p style="font-size: 20px;">在用：{{json_decode($deviceDynamicStatusNearlyThreeMonth,true)[1][0]['value']}}</p>
                                <p style="font-size: 20px;">备用：{{json_decode($deviceDynamicStatusNearlyThreeMonth,true)[1][3]['value']}}</p>
                                <p style="font-size: 20px;">送检：{{json_decode($deviceDynamicStatusNearlyThreeMonth,true)[1][2]['value']}}</p>
                                <p style="font-size: 20px;">维修：{{json_decode($deviceDynamicStatusNearlyThreeMonth,true)[1][1]['value']}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{--查询条件--}}
            <div class="col-md-4">
                <div class="box box-info" style="height: 430px;">
                    <div class="box-header with-border">
                        <h3>设备动态统计</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-horizontal" style="font-size: 18px;">
                            <p>&nbsp;</p>
                            <p>&nbsp;</p>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">种类：</label>
                                <div class="col-sm-8 col-md-8">
                                    <select id="selCategoryUniqueCode" class="form-control select2" style="width:100%;" onchange="fnCurrentPage()">
                                        <option value="">请选择</option>
                                        @foreach($categories as $category)
                                            <option value="{{$category->unique_code}}" {{request("categoryUniqueCode") == $category->unique_code ? "selected" : ""}}>{{$category->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p>&nbsp;</p>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">型号：</label>
                                <div class="col-sm-8 col-md-8">
                                    <select id="selEntireModelUniqueCode" class="form-control select2" style="width:100%;" onchange="fnCurrentPage()">
                                        <option value="">请选择</option>
                                        @foreach($entireModels as $entireModel)
                                            <option value="{{$entireModel->unique_code}}" {{request("entireModelUniqueCode") == $entireModel->unique_code ? "selected" : ""}}>{{$entireModel->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p>&nbsp;</p>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">日期：</label>
                                <div class="col-sm-8 col-md-8">
                                    <select id="selUpdatedAt" class="form-control select2" style="width:100%;" onchange="fnCurrentPage()">
                                        <option value="0" {{request('updatedAt') == 0 ? 'selected' : ''}}>当月</option>
                                        <option value="1" {{request('updatedAt') == 1 ? 'selected' : ''}}>上月</option>
                                        <option value="3" {{request('updatedAt') == 3 ? 'selected' : ''}}>近三个月</option>
                                        <option value="6" {{request('updatedAt') == 6 ? 'selected' : ''}}>近六个月</option>
                                        <option value="12" {{request('updatedAt') == 12 ? 'selected' : ''}}>近十二个月</option>
                                    </select>
                                </div>
                            </div>
                            <p>&nbsp;</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h1 class="box-title">设备列表&nbsp;&nbsp;总数：{{$total}}&nbsp;&nbsp;总数：{{$using}}&nbsp;&nbsp;在用：{{$fixed}}&nbsp;&nbsp;送检：{{$returnFactory}}&nbsp;&nbsp;在修：{{$fixing}}</h1>
                        {{--右侧最小化按钮--}}
                        <div class="pull-right">
{{--                            <a href="{{url('entire/instance/batch')}}?page={{request('page',1)}}" class="btn btn-default btn-lg btn-flat" target="_blank">批量新入所</a>--}}
{{--                            <a href="{{url('entire/instance/old')}}?page={{request('page',1)}}" class="btn btn-default btn-lg btn-flat" target="_blank">旧设备导入</a>--}}
                            {{--                            <label style="font-weight: normal;"><input onclick="fnCurrentPage('')" type="radio" name="status" value="" {{request('status') == null ? 'checked' : ''}}>全部</label>--}}
                            {{--                            <label style="font-weight: normal;"><input onclick="fnCurrentPage('BUY_IN')" type="radio" name="status" value="" {{request('status') == 'BUY_IN' ? 'checked' : ''}}>新入所</label>--}}
                            {{--                            <label style="font-weight: normal;"><input onclick="fnCurrentPage('INSTALLING')" type="radio" name="status" value="INSTALLING" {{request('status') == 'INSTALLING' ? 'checked' : ''}}>安装中</label>--}}
                            {{--                            <label style="font-weight: normal;"><input onclick="fnCurrentPage('INSTALLED')" type="radio" name="status" value="INSTALLED" {{request('status') == 'INSTALLED' ? 'checked' : ''}}>已安装</label>--}}
                            {{--                            <label style="font-weight: normal;"><input onclick="fnCurrentPage('FIXING')" type="radio" name="status" value="FIXING" {{request('status') == 'FIXING' ? 'checked' : ''}}>检修中</label>--}}
                            {{--                            <label style="font-weight: normal;"><input onclick="fnCurrentPage('FIXED')" type="radio" name="status" value="FIXED" {{request('status') == 'FIXED' ? 'checked' : ''}}>可用</label>--}}
                            {{--                            <label style="font-weight: normal;"><input onclick="fnCurrentPage('RETURN_FACTORY')" type="radio" name="status" value="RETURN_FACTORY" {{request('status') == 'RETURN_FACTORY' ? 'checked' : ''}}>返厂维修</label>--}}
                            {{--                            <label style="font-weight: normal;"><input onclick="fnCurrentPage('FACTORY_RETURN')" type="radio" name="status" value="FACTORY_RETURN" {{request('status') == 'FACTORY_RETURN' ? 'checked' : ''}}>返厂入所</label>--}}
                            <label style="font-weight: normal;">
                                <select name="status" id="selStatus" class="form-control select2" onchange="fnCurrentPage(this.value)" style="width: 100%;">
                                    <option value="" {{request('status') == '' ? 'selected' : ''}}>全部</option>
                                    <option value="FIXED" {{request('status') == 'FIXED' ? 'selected' : ''}}>成品</option>
                                    <option value="FIXING" {{request('status') == 'FIXING' ? 'selected' : ''}}>待修</option>
                                </select>
                            </label>
                            <a href="{{url('warehouse/report/scanInBatch')}}?page={{request('page',1)}}" class="btn btn-default btn-flat" target="_blank">批量扫码</a>
                            <a href="{{url('entire/instance/create')}}?page={{request('page',1)}}" class="btn btn-default btn-flat">新设备</a>
                        </div>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-hover table-condensed" id="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>型号</th>
                                <th>类型</th>
                                <th>所编号</th>
                                <th>供应商</th>
                                <th>厂编号</th>
                                <th>安装位置</th>
                                <th>安装时间</th>
                                <th>主/备用</th>
                                <th>状态</th>
<!--                                <th>在库状态</th>-->
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($entireInstances as $entireInstance)
                                <tr>
                                    <td>{{$entireInstance->id}}</td>
                                    <td><a href="{{url('search',$entireInstance->identity_code)}}" target="_blank">{{$entireInstance->EntireModel ? $entireInstance->EntireModel->name.'（'.$entireInstance->entire_model_unique_code.'）' : ''}}</a></td>
                                    <td>{{$entireInstance->Category->name}}</td>
                                    <td>{{$entireInstance->serial_number}}</td>
                                    <td>{{$entireInstance->factory_name}}</td>
                                    <td><a href="{{url('barcode',$entireInstance->identity_code)}}" target="_blank">{{$entireInstance->identity_code}}</a></td>
                                    <td>{{$entireInstance->maintain_station_name.'&nbsp;'.$entireInstance->maintain_location_code}}</td>
                                    <td>{{$entireInstance->last_installed_time ? date('Y-m-d',$entireInstance->last_installed_time) : ''}}</td>
                                    <td>{{$entireInstance->is_main ? '主用' : '备用'}}</td>
                                    <td>{{$entireInstance->status}}</td>
<!--                                    <td>{{$entireInstance->in_warehouse ? '在库' : '库外'}}</td>-->
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{url('entire/instance',$entireInstance->identity_code)}}/edit?page={{request('page',1)}}" class="btn btn-primary btn-flat">编辑</a>
                                            @if($entireInstance->FixWorkflow)
                                                @if(array_flip(\App\Model\FixWorkflow::$STATUS)[$entireInstance->FixWorkflow->status] != 'FIXED')
                                                    {{--查看检修单--}}
                                                    <a href="{{url('measurement/fixWorkflow',$entireInstance->fix_workflow_serial_number)}}/edit?page={{request('page',1)}}" class="btn btn-warning btn-flat">检修</a>
                                                @else
                                                    {{--新建检修单--}}
                                                    <a href="{{url('measurement/fixWorkflow/create')}}?page={{request('page',1)}}&type=FIX&identity_code={{$entireInstance->identity_code}}" class="btn btn-warning btn-flat">新检修</a>
                                                @endif
                                            @else
                                                {{--新建检修单--}}
                                                <a href="{{url('measurement/fixWorkflow/create')}}?page={{request('page',1)}}&type=FIX&identity_code={{$entireInstance->identity_code}}" class="btn btn-warning btn-flat">新检修</a>
                                            @endif
                                            @if(array_flip(\App\Model\EntireInstance::$STATUS)[$entireInstance->status] == 'INSTALLED' || array_flip(\App\Model\EntireInstance::$STATUS)[$entireInstance->status] == 'INSTALLING')
                                                <a href="javascript:" onclick="fnFixingIn('{{$entireInstance->identity_code}}')" class="btn btn-default btn-flat">入所</a>
                                            @else
                                                <a href="javascript:" class="btn btn-default btn-flat disabled" disabled="disabled">入所</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($entireInstances->hasPages())
                        <div class="box-footer">
                            {{ $entireInstances->appends(['categoryUniqueCode'=>request('categoryUniqueCode'),'entireModelUniqueCode'=>request('entireModelUniqueCode'),'updatedAt'=>request('updatedAt')])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{--模态框--}}
        <div class="divModalEntireInstanceFixing"></div>
        <div id="divModalFixWorkflowInOnce"></div>
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
                autoclose: true,
                format: 'yyyy-mm-dd'
            });

            // 当月报表
            new Morris.Donut({
                element: 'chartDeviceDynamicStatusCurrentMonth',
                resize: true,
                colors: ["#3c8dbc", "#f56954", "#00a65a", "#CA195A"],
                data: JSON.parse('{!! $deviceDynamicStatusCurrentMonth !!}')[1],
                hideHover: 'auto'
            });

            console.log(JSON.parse('{!! $deviceDynamicStatusNearlyThreeMonth !!}'));

            // 近三个月报表
            new Morris.Donut({
                element: 'chartDeviceDynamicStatusNearlyThreeMonth',
                resize: true,
                colors: ["#3c8dbc", "#f56954", "#00a65a", "#CA195A"],
                data: JSON.parse('{!! $deviceDynamicStatusNearlyThreeMonth !!}')[1],
                hideHover: 'auto'
            });
        });

        /**
         * 打开入所窗口
         * @param entireInstanceIdentityCode
         */
        fnFixingIn = (entireInstanceIdentityCode) => {
            $.ajax({
                url: "{{url('entire/instance/fixingIn')}}/" + entireInstanceIdentityCode,
                type: "get",
                data: {},
                async: true,
                success: function (response) {
                    console.log('success:', response);
                    $("#divModalFixWorkflowInOnce").html(response);
                    $("#modalFixWorkflowInOnce").modal("show");
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        fnCurrentPage = status => {
            location.href = `?categoryUniqueCode=${$("#selCategoryUniqueCode").val()}&entireModelUniqueCode=${$("#selEntireModelUniqueCode").val()}&updatedAt=${$("#selUpdatedAt").val()}&status=${status}`;
        };
    </script>
@endsection

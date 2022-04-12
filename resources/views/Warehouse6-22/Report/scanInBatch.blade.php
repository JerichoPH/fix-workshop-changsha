@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            @if(request('type','OUT') == 'OUT')
                出所
            @endif
            @if(request('type','OUT') == 'IN')
                入所
            @endif
            <small>批量扫码</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">批量扫码</li>--}}
{{--        </ol>--}}
    </section>
    {{--    <section class="content" onclick="$('#txtQrCodeContent').focus()">--}}
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">扫描结果列表 数量 {{$warehouseBatchReports->count()}}</h3>
                        {{--右侧最小化按钮--}}
                        <div class="pull-right">
                            <div class="btn-group btn-group-sm">
{{--                                <a href="{{url('warehouse/report')}}?page={{request('page',1)}}&direction={{request('direction')}}&created_at={{request('created_at')}}&category_unique_code={{request('category_unique_code')}}&type={{request('type')}}" class="btn btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                {{-- @if(request('type','OUT') == 'NEW')
                                     <a href="http://www.rfid.com/?type={{request('type')}}" target="_blank" class="btn btn-default btn-flat">RFID绑定</a>
                                 @endif
                                 <a href="http://www.rfid.com/?type={{request('type')}}" target="_blank" class="btn btn-default btn-flat">RFID绑定</a>
                                 <a href="http://www.rfid.com/plrs?type={{request('type')}}" target="_blank" class="btn btn-default btn-flat">RFID扫码</a>--}}
                                {{--                            <a href="javascript:" onclick="fnMakeFixWorkflowBatch()" class="btn btn-default btn-flat btn-lg"><i class="fa fa-wrench">&nbsp;</i>批量生成检修单</a>--}}
                                @if(request('type','OUT') == 'IN')
                                    <a href="{{ url('part/instance/buyIn') }}" class="btn btn-default btn-flat"><i class="fa fa-upload">&nbsp;</i>部件采购导入</a>
                                    <a href="javascript:" class="btn btn-default btn-flat" onclick="fnInBatch()"><i class="fa fa-sign-in">&nbsp;</i>设备入所</a>
                                    <a href="{{url('warehouse/report/printNormalLabel')}}?type=BUY_IN" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-barcode">&nbsp;</i>打印标签</a>
                                @endif
                                @if(request('type','OUT') == 'OUT')
                                    <a href="javascript:" class="btn btn-default btn-flat" onclick="modelOutBatch()"><i class="fa fa-sign-out">&nbsp;</i>设备出所</a>
                                    {{--<a href="{{url('warehouse/report/printNormalLabel')}}?type=CYCLE_FIX" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-barcode">&nbsp;</i>打印标签(周期修)</a>--}}
                                    <a href="{{url('warehouse/report/printNormalLabel')}}?type=OUT" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-barcode">&nbsp;</i>打印标签(备品或状态修)</a>
                                @endif
                                <a href="javascript:" class="btn btn-default btn-flat" onclick="fnCleanBatch()"><i class="fa fa-times">&nbsp;</i>清空</a>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="input-group input-group-lg">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">{{request('searchType','唯一编号')}}
                                    <span class="fa fa-caret-down"></span></button>
                                <ul class="dropdown-menu">
                                    <li><a href="javascript:" class="btn btn-lg" onclick="fnChangeSearchType('唯一编号')">唯一编号</a></li>
                                    <li><a href="javascript:" class="btn btn-lg" onclick="fnChangeSearchType('厂编号')">厂编号</a></li>
                                    <li><a href="javascript:" class="btn btn-lg" onclick="fnChangeSearchType('所编号')">所编号</a></li>
                                </ul>
                            </div>

                            <input
                                type="text"
                                name="qr_code_content"
                                id="txtQrCodeContent"
                                autofocus
                                class="form-control"
                                onkeydown="if(event.keyCode===13) fnScan(this.value)"
                                onchange="fnScan(this.value)"
                                placeholder="扫码前点击"
                            >
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-condensed" style="font-size: 18px;">
                                <thead>
                                <tr>
                                    <th>设备类型</th>
                                    <th>设备型号</th>
                                    <th>唯一标识</th>
                                    {{--<th>EPC</th>--}}
                                    <th>所编号</th>
                                    <th>厂编号</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if($warehouseBatchReports)
                                    @foreach($warehouseBatchReports as $warehouseBatchReport)
                                        <tr>
                                            <td>{{@$warehouseBatchReport->EntireInstance->Category->name}}</td>
                                            <td>{{@$warehouseBatchReport->EntireInstance->EntireModel->name}}</td>
                                            <td>{{@$warehouseBatchReport->EntireInstance->identity_code}}</td>
                                            {{--<td>{{\App\Facades\Code::identityCodeToHex($warehouseBatchReport->EntireInstance->identity_code)}}</td>--}}
                                            <td>{{@$warehouseBatchReport->EntireInstance->serial_number}}</td>
                                            <td>{{@$warehouseBatchReport->EntireInstance->factory_device_code}}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    {{--<a href="javascript:" onclick="fnMakeFixWorkflow('{{$warehouseBatchReport->EntireInstance->identity_code}}')" class="btn {{$warehouseBatchReport->fix_workflow_serial_number ? 'btn-default' : 'btn-warning'}} btn-flat {{$warehouseBatchReport->fix_workflow_serial_number ? 'disabled' : ''}}" {{$warehouseBatchReport->fix_workflow_serial_number ? 'disabled' : ''}}>新建检修单</a>--}}
                                                    @if($warehouseBatchReport->EntireInstance)
                                                        @if(substr($warehouseBatchReport->EntireInstance->identity_code,0,1) == 'S')
                                                            <a href="javascript:" onclick="modelPointSwitchModifyLocation('{{$warehouseBatchReport->EntireInstance->identity_code}}')" class="btn btn-default btn-flat btn-sm">绑定位置</a>
                                                        @endif
                                                    @endif
                                                    <a href="javascript:" onclick="fnDeleteBatch('{{$warehouseBatchReport->id}}')" class="btn btn-danger btn-flat btn-sm">删除</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="form-group">
                            {{--@foreach($qrCodeContents as $qrCodeContent)--}}
                            {{--<img src="data:image/png;base64, {!! base64_encode($qrCodeContent) !!} " style="width: 100%;">--}}
                            {{--@endforeach--}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div id="divModalInstall"></div>
    </section>
    <section class="content">
        <div id="divModelPointSwitchModifyLocation"></div>
    </section>
@endsection
@section('script')
    <script>
        $(function () {
            $("#txtQrCodeContent").val('');
        });

        /**
         * 打开转辙机绑定位置页面
         * @param {string} identityCode
         */
        function modelPointSwitchModifyLocation(identityCode) {
            $.ajax({
                url: `{{ url('warehouse/report/pointSwitchModifyLocation') }}/${identityCode}`,
                type: "get",
                data: {},
                async: false,
                success: res => {
                    $("#divModalInstall").html(res);
                    $("#modalPointSwitchModifyLocation").modal("show");
                },
                error: err => {
                    console.log("错误：", err);
                }
            })
        }

        /**
         * 扫码进入批量表
         * @param qrCodeContent
         */
        function fnScan(qrCodeContent) {
            if (qrCodeContent.length > 0) {
                $.ajax({
                    url: `{{ url('warehouse/report/scanInBatch') }}?type={{ request('type') }}&searchType={{ request('searchType','唯一编号') }}`,
                    type: "post",
                    data: {qrCodeContent: qrCodeContent, type: "{{ request('type','IN') }}"},
                    async: true,
                    success: function (response) {
                        console.log(response);
                        location.reload();
                    },
                    error: function (error) {
                        // console.log('fail:', error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    },
                });
                $("#txtQrCodeContent").val('');
            }
        }

        /**
         *  清空批量表
         */
        function fnCleanBatch() {
            $.ajax({
                url: `{{ url('warehouse/report/cleanBatch') }}`,
                type: "post",
                data: {id: 1, type: "{{ request('type','IN') }}"},
                async: true,
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 生成检修单
         * @param {string} entireInstanceIdentityCode
         */
        function fnMakeFixWorkflow(entireInstanceIdentityCode) {
            $.ajax({
                url: `{{ url('warehouse/report/makeFixWorkflow') }}`,
                type: "post",
                data: {entireInstanceIdentityCode: entireInstanceIdentityCode},
                async: true,
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                    location.reload();
                },
            });
        }

        /**
         * 删除批量单项
         * @param {string} id
         */
        function fnDeleteBatch(id) {
            $.ajax({
                url: `{{ url('warehouse/report/deleteBatch') }}`,
                type: "post",
                data: {id: id, type: "{{ request('type','IN') }}"},
                async: true,
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 批量入所
         */
        function fnInBatch() {
            $.ajax({
                url: `{{ url('warehouse/report/inBatch') }}`,
                type: "post",
                data: {type: "{{ request('type','IN') }}"},
                async: true,
                success: function (response) {
                    console.log('success:', response);
                    alert(response.message);
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 批量生成检修单
         */
        function fnMakeFixWorkflowBatch() {
            $.ajax({
                url: `{{ url('warehouse/report/makeFixWorkflowBatch') }}`,
                type: "post",
                data: {},
                async: true,
                success: function (response) {
                    // console.log('success:', response);
                    // alert(response);
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 批量出所窗口
         */
        function modelOutBatch() {
            $.ajax({
                url: `{{ url('warehouse/report/modelOutBatch') }}`,
                type: "get",
                data: {},
                async: false,
                success: res => {
                    $("#divModalInstall").html(res);
                    $("#modalInstall").modal("show");
                },
                error: err => {
                    console.log("错误：", err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                }
            })
        }

        /**
         * 切换搜索类型
         * @param type
         */
        function fnChangeSearchType(type) {
            location.href = `?type={{ request('type') }}&searchType=${type}`;
        }
    </script>
@endsection

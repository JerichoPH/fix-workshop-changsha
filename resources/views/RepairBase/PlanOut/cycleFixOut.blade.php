@extends('Layout.index')
@section('style')
    <link rel="stylesheet" href="/EasyWeb/spa/assets/libs/layui/css/layui.css"/>
    <link rel="stylesheet" href="/EasyWeb/spa/assets/css/lite.css"/>
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            周期修出所
            <small>批量扫码</small>
        </h1>
    {{--<ol class="breadcrumb">--}}
    {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
    {{--    <li class="active">批量扫码</li>--}}
    {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">扫描结果列表 数量 {{$cycleFixEntireInstances->count()}} 已扫 {{$cycleFixEntireInstances->where('is_scan', 1)->count()}}</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right">
                            @if($isOnclick)
                                <a href="javascript:" class="btn btn-default btn-flat" onclick="modelOutBatch()"><i class="fa fa-sign-out">&nbsp;</i>确认出所</a>
                            @else
                                <a href="javascript:" class="btn btn-default btn-flat" disabled><i class="fa fa-sign-out">&nbsp;</i>确认出所</a>
                            @endif
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="input-group input-group-lg">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">唯一编码</button>
                            </div>
                            <input type="text" name="qr_code_content" id="txtQrCodeContent" autofocus class="form-control" onkeydown="if(event.keyCode===13) fnScan(this.value)" onchange="fnScan(this.value)" placeholder="扫码前点击">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-condensed" style="font-size: 18px;">
                                <thead>
                                <tr>
                                    <th>设备类型</th>
                                    <th>设备型号</th>
                                    <th>唯一标识</th>
                                    <th>所编号</th>
                                    <th>厂编号</th>
                                    <th>仓库位置</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if($cycleFixEntireInstances)
                                    @foreach($cycleFixEntireInstances as $cycleFixEntireInstance)
                                        <tr class="{{$cycleFixEntireInstance->is_scan == 1 ? 'bg-success':''}}">
                                            <td>{{$cycleFixEntireInstance->WithEntireInstance ? $cycleFixEntireInstance->WithEntireInstance->Category->name : ''}}</td>
                                            <td>{{$cycleFixEntireInstance->WithEntireInstance ? $cycleFixEntireInstance->WithEntireInstance->EntireModel->name : ''}}</td>
                                            <td>{{$cycleFixEntireInstance->WithEntireInstance ? $cycleFixEntireInstance->WithEntireInstance->identity_code : ''}}</td>
                                            <td>{{$cycleFixEntireInstance->WithEntireInstance ? $cycleFixEntireInstance->WithEntireInstance->serial_number : ''}}</td>
                                            <td>{{$cycleFixEntireInstance->WithEntireInstance ? $cycleFixEntireInstance->WithEntireInstance->factory_device_code : ''}}</td>
                                            <td>{{$cycleFixEntireInstance->WithEntireInstance->WithPosition ? $cycleFixEntireInstance->WithEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $cycleFixEntireInstance->WithEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $cycleFixEntireInstance->WithEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $cycleFixEntireInstance->WithEntireInstance->WithPosition->WithTier->WithShelf->name . $cycleFixEntireInstance->WithEntireInstance->WithPosition->WithTier->name . $cycleFixEntireInstance->WithEntireInstance->WithPosition->name :''}}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

<div class="modal fade" id="cycleFixOut">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">周期修出所<h4>
            </div>
            <div class="modal-body form-horizontal">
                <form id="frmCycleFixOut">
                    <input type="hidden" name="bill_id" value="{{$current_bill_id}}">
                    <input type="hidden" name="processor_id" value="{{session('account.id')}}">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">联系人：</label>
                        <div class="col-sm-10 col-md-8">
                            <input class="form-control" type="text" autofocus onkeydown="if(event.keyCode==13){return false;}" name="connection_name" placeholder="联系人" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">联系电话：</label>
                        <div class="col-md-8">
                            <input class="form-control" type="text" autofocus onkeydown="if(event.keyCode==13){return false;}" name="connection_phone" placeholder="电话" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">经办人：</label>
                        <div class="col-sm-10 col-md-8">
                            <input class="form-control" type="text" name="" placeholder="经办人" value="{{session('account.account')}}" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">出所日期：</label>
                        <div class="col-sm-10 col-md-8">
                            <div class="input-group date">
                                <div class="input-group-addon" style="font-size: 18px;"><i class="fa fa-calendar"></i></div>
                                <input name="processed_at" type="text" class="form-control pull-right" id="processed_at" value="{{date('Y-m-d')}}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnOut()"><i class="fa fa-check">&nbsp;</i>确定</button>
            </div>
        </div>
    </div>
</div>

@section('script')
    <script type="text/javascript" src="/EasyWeb/spa/assets/libs/layui/layui.js"></script>
    <script>

        layui.config({
            base: '/EasyWeb/spa/assets/module/'
        }).use(['layer', 'form', 'table', 'util', 'admin', 'laydate', 'formX'], function () {
            let laydate = layui.laydate;
            laydate.render({
                elem: '#processed_at',
                trigger: 'click',
                range: false,
                type: 'datetime'
            });
            /*end*/
        });

        $(function () {
            $("#txtQrCodeContent").val('');
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
        });


        /**
         * 扫码进入批量表
         * @param qrCodeContent
         */
        function fnScan(qrCodeContent) {
            if (qrCodeContent.length > 0) {
                $.ajax({
                    url: `{{url('repairBase/planOut/scanCycleFixOut',$current_bill_id)}}`,
                    type: "put",
                    data: {
                        qrCodeContent: qrCodeContent,
                    },
                    async: true,
                    success: function (response) {
                        console.log(response);
                        if (response.status === 200) {
                            location.reload();
                        } else {
                            alert(response.message);
                            location.reload();
                        }
                    },
                    error: function (error) {
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.message);
                    },
                });
                $("#txtQrCodeContent").val('');
            }
        }


        /**
         * 删除批量单项
         * @param {string} id
         */
        function fnDeleteBatch(id) {
            $.ajax({
                url: `{{url('repairBase/planOut/scanCycleFixOut')}}/${id}`,
                type: "delete",
                async: true,
                success: function (response) {
                    console.log(response);
                    if (response.status === 200) {
                        location.reload();
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                },
            });
        }

        /**
         * 确认出所
         */
        function fnOut() {
            $.ajax({
                url: `{{url('repairBase/planOut/scanCycleFixOut')}}`,
                type: "post",
                data: $("#frmCycleFixOut").serialize(),
                async: true,
                success: function (response) {
                    console.log(response);
                    if (response.status === 200) {
                        location.href = response.data.url;
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                },
            });
        }

        /**
         * 批量出所窗口
         */
        function modelOutBatch() {
            $("#cycleFixOut").modal("show");
        }

    </script>
@endsection

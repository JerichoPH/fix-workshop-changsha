@extends('Layout.index')
@section('style')

@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            状态修出所
            <small>批量扫码</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">批量扫码</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">扫描结果列表 数量 {{$out_entire_instance_correspondences->count()}} 已扫 {{$out_entire_instance_correspondences->where('is_scan', 1)->count()}}</h3>
                        {{--右侧最小化按钮--}}
                        <div class="pull-right">
                            <div class="btn-group btn-group-sm">
                                <a href="javascript:" class="btn btn-default btn-flat" onclick="modelOutBatch()"><i class="fa fa-sign-out">&nbsp;</i>设备状态修出所</a>
                                <a href="{{url('warehouse/breakdownOrder/outWithEntireInstance')}}" target="_blank" class="btn btn-default btn-flat"><i class="fa fa-barcode">&nbsp;</i>打印标签(备品或状态修)</a>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="input-group input-group-lg">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">{{request('searchType','唯一编号')}}<span class="fa fa-caret-down"></span></button>
                                <ul class="dropdown-menu">
                                    <li><a href="javascript:" class="btn btn-lg" onclick="fnChangeSearchType('唯一编号')">唯一编号</a></li>
                                    <li><a href="javascript:" class="btn btn-lg" onclick="fnChangeSearchType('厂编号')">厂编号</a></li>
                                    <li><a href="javascript:" class="btn btn-lg" onclick="fnChangeSearchType('所编号')">所编号</a></li>
                                </ul>
                            </div>
                            <input type="text" name="qr_code_content" id="txtQrCodeContent" autofocus class="form-control" onkeydown="if(event.keyCode===13) fnScan(this.value)" onchange="fnScan(this.value)" placeholder="扫码前点击">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-condensed" style="font-size: 18px;">
                                <thead>
                                <tr>
                                    <th>设备类型</th>
                                    <th>设备型号</th>
                                    <th>唯一编码</th>
                                    <th>所编号</th>
                                    <th>厂编号</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if($out_entire_instance_correspondences)
                                    @foreach($out_entire_instance_correspondences as $out_entire_instance_correspondence)
                                        <tr class="{{$out_entire_instance_correspondence->is_scan == 1 ? 'bg-success':''}}">
                                            <td>{{@$out_entire_instance_correspondence->WithEntireInstanceNew->Category->name}}</td>
                                            <td>{{@$out_entire_instance_correspondence->WithEntireInstanceNew->EntireModel->name}}</td>
                                            <td>{{@$out_entire_instance_correspondence->new}}</td>
                                            <td>{{@$out_entire_instance_correspondence->WithEntireInstanceNew->serial_number}}</td>
                                            <td>{{@$out_entire_instance_correspondence->WithEntireInstanceNew->factory_device_code}}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="javascript:" onclick="fnDeleteBatch('{{$out_entire_instance_correspondence->id}}')" class="btn btn-danger btn-flat btn-sm">删除</a>
                                                </div>
                                            </td>
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
         * 扫码
         * @param qrCodeContent
         */
        function fnScan(qrCodeContent) {
            $.ajax({
                url: `{{url('warehouse/breakdownOrder/outWithScan')}}/${qrCodeContent}?searchType={{request('searchType','唯一编号')}}`,
                type: "get",
                async: true,
                success: function (response) {
                    if (response.status === 200) {

                    } else {
                        alert(response.message);
                    }
                    location.reload();
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
            $("#txtQrCodeContent").val('');
        }


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
         * 删除批量单项
         * @param {string} id
         */
        function fnDeleteBatch(id) {
            $.ajax({
                url: `{{url('warehouse/breakdownOrder/outWithEntireInstance')}}`,
                type: "delete",
                data: {ids: [id]},
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
         * 批量出所窗口
         */
        function modelOutBatch() {
            $.ajax({
                url: `{{url('warehouse/breakdownOrder/outWithModal')}}`,
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
            location.href = `?searchType=${type}`;
        }
    </script>
@endsection

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
            入库
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
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">扫描结果列表</h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right">
                            <a href="javascript:" onclick="fnStorageIn()" class="btn btn-default btn-lg btn-flat"><i class="fa fa-sign-in">&nbsp;</i>入库</a>
                            <a href="http://www.rfid.com/plrk" class="btn btn-default btn-lg btn-flat">RFID扫码</a>
                            <a href="javascript:" class="btn btn-default btn-lg btn-flat" onclick="fnDeleteAll()"><i class="fa fa-times">&nbsp;</i>清空</a>
                            <a href="javascript:" class="btn btn-default btn-lg btn-flat" onclick="fnTest()">测试</a>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
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
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                <form action="" id="frmStorageIn">
                                    @foreach($entireInstances as $entireInstance)
                                        <tr>
                                            <td>{{$entireInstance->category_name}}</td>
                                            <td>{{$entireInstance->entire_model_name}}</td>
                                            <td>{{$entireInstance->identity_code}}</td>
                                            <td>{{$entireInstance->serial_number}}</td>
                                            <td>{{$entireInstance->factory_device_code}}</td>
                                            <td><input type="text" name="{{$entireInstance->identity_code}}"></td>
                                            <td><a href="javascript:" onclick="fnDeleteOne('{{$entireInstance->id}}')" class="btn btn-flat btn-lg btn-danger">删除</a></td>
                                        </tr>
                                    @endforeach
                                </form>
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
@endsection
@section('script')
    <script>
        /**
         * 清空
         */
        fnDeleteAll = () => {
            $.ajax({
                url: `{{url('warehouse/storage')}}`,
                type: 'delete',
                data: null,
                async: true,
                success: response => {
                    // console.log(response);
                    location.reload();
                },
                fail: error => {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        };

        /**
         * 删除单独记录
         * @param id
         */
        fnDeleteOne = id => {
            $.ajax({
                url: `{{url('warehouse/storage')}}/${id}`,
                type: 'delete',
                data: null,
                async: true,
                success: response => {
                    // console.log(response);
                    location.reload();
                },
                fail: error => {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        };

        /**
         * 测试导入
         */
        fnTest = () => {
            $.ajax({
                url: `{{url('warehouse/storage/scanInBatch')}}`,
                type: 'post',
                data: {
                    identity_codes: [
                        {
                            identity_code: 'Q011406B04900000001',
                            maintain_station_name: '北京站',
                            maintain_location_code: 'ZQ3-1-1-1',
                            to_direction: '去向',
                            crossroad_number: '道岔',
                            traction: '牵引',
                            open_direction: '开向',
                            said_rod: '表示杆特征',
                            line_name: '线制'
                        },
                        {
                            identity_code: 'Q011406B04900000002',
                            maintain_station_name: '北京站',
                            maintain_location_code: 'ZQ3-1-1-2',
                            to_direction: '去向',
                            crossroad_number: '道岔',
                            traction: '牵引',
                            open_direction: '开向',
                            said_rod: '表示杆特征',
                            line_name: '线制'
                        },
                        {
                            identity_code: 'Q011406B04900000003',
                            maintain_station_name: '北京站',
                            maintain_location_code: 'ZQ3-1-1-3',
                            to_direction: '去向',
                            crossroad_number: '道岔',
                            traction: '牵引',
                            open_direction: '开向',
                            said_rod: '表示杆特征',
                            line_name: '线制'
                        }
                    ]
                },
                dataType: 'text',
                async: true,
                success: response => {
                    console.log(response);
                    // location.reload();
                },
                fail: error => {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        };

        /**
         * 批量入库
         */
        fnStorageIn = () => {
            $.ajax({
                url: `{{url('warehouse/storage')}}`,
                type: 'post',
                data: $("#frmStorageIn").serialize(),
                dataType: 'text',
                async: true,
                success: response => {
                    // console.log(response);
                    alert(response);
                    location.reload();
                },
                fail: error => {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

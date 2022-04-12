@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            出入所
            <small>打印标签</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">出入所</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">出入所打印标签</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right">
                    <a href="javascript:" onclick="printLabel('identity_code',1)" class="btn btn-default btn-flat">打印编码标签(35*20)</a>
                    <a href="javascript:" onclick="printLabel('identity_code',2)" class="btn btn-default btn-flat">打印编码标签(20*12)</a>
                    <a href="javascript:" onclick="printLabel('identity_code',3)" class="btn btn-default btn-flat">打印编码标签(40*25)</a>
                    <a href="javascript:" onclick="printLabel('location')" class="btn btn-default btn-flat">打印位置标签</a>
                </div>
            </div>

            <div class="box-body material-message">
                <table class="table table-hover table-condensed">
                    <tbody>
                    <tr>
                        <th><input type="checkbox" checked class="checkbox-toggle"></th>
                        <th>唯一编号</th>
                        <th>所编号</th>
                        <th>设备型号</th>
                        <th>车站</th>
                        <th>安装位置</th>
                    </tr>
                    @foreach($warehouse_report_entire_instances as $warehouse_report_entire_instance)
                        <tr>
                            <td><input type="checkbox" checked name="entire_instance_identity_code" value="{{ $warehouse_report_entire_instance->entire_instance_identity_code }}"></td>
                            <td><a href="{{ url('search',$warehouse_report_entire_instance->entire_instance_identity_code) }}">{{ $warehouse_report_entire_instance->entire_instance_identity_code }}</a></td>
                            <td>{{ $warehouse_report_entire_instance->ei_sn }}</td>
                            <td>{{ @$warehouse_report_entire_instance->model_name }}</td>
                            <td>{{ @$warehouse_report_entire_instance->maintain_station_name }}</td>
                            <td>
                                {{ @$warehouse_report_entire_instance->maintain_location_code }}
                                {{ @$warehouse_report_entire_instance->crossroad_number }}
                                {{ @$warehouse_report_entire_instance->traction }}
                                {{ @$warehouse_report_entire_instance->line_name }}
                                {{ @$warehouse_report_entire_instance->open_direction }}
                                {{ @$warehouse_report_entire_instance->said_rod }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>

        $(function () {
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });
        });

        /**
         * 打印标签
         * @param {string} type
         * @param {int} sizeType
         */
        function printLabel(type, sizeType = 1) {
            //处理数据
            let identityCodes = [];
            $("input[type='checkbox'][name='entire_instance_identity_code']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') identityCodes.push(new_code);
            });
            if (identityCodes.length <= 0) {
                alert('请选择打印标签设备');
                return false;
            }
            $.ajax({
                url: `{{ url('warehouse/report/identityCodeWithPrint') }}`,
                type: 'post',
                data: {identityCodes,},
                async: true,
                success: function (response) {
                    console.log(`success:`, response);
                    if (response.status === 200) {
<<<<<<< HEAD
                        let params = $.param({direction: '{{ $direction }}', size_type: sizeType});
                        if (type === 'identity_code') window.open(`{{ url('qrcode/printQrCode') }}?${params}`);
                        if (type === 'location') window.open(`{{ url('qrcode/printLabel') }}?${params}`);
=======
                        let params = $.param({direction: '{{ $direction }}', size_type: sizeType})
                        if (type === 'identity_code') window.open(`{{url('qrcode/printQrCode')}}?${params}`);
                        if (type === 'location') window.open(`{{url('qrcode/printLabel')}}?${params}`);
>>>>>>> dev
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (error) {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }
    </script>
@endsection

@extends('Layout.index')
@section('style')
    <style>
    </style>
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            送修设备列表
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">
                    设备列表 <small>({{$sendRepairUniqueCode}})</small>
                </h3>
                <div class="box-tools pull-right">
                    <a href="{{ url('storehouse/index/scrap/instance') }}" class="btn btn-success btn-flat">报废</a>
                </div>
            </div>

            <div class="box-body material-message">
                <table class="table table-hover table-condensed">
                    <tbody>
                    <tr>
                        <th>
                            <input type="checkbox" class="checkbox-toggle">
                        </th>
                        <th>唯一编码</th>
                        <th>设备状态</th>
                        <th>种类</th>
                        <th>型号</th>
                        <th>供应商</th>
                        <th>检测结果</th>
                        <th>操作</th>
                    </tr>
                    @foreach($sendRepairInstances as $sendRepairInstance)
                        <tr>
                            <td><input type="checkbox" name="materialUniqueCodes" value="{{ $sendRepairInstance->material_unique_code }}"></td>
                            <td>{{ $sendRepairInstance->material_unique_code }}</td>
                            @if($sendRepairInstance->material_type == 'ENTIRE')
                                <td>{{ $sendRepairInstance->WithEntireInstance->status }}</td>
                                <td>{{ $sendRepairInstance->WithEntireInstance->category_name ?? '' }}</td>
                                <td>{{ $sendRepairInstance->WithEntireInstance->model_name ?? '' }}</td>
                                <td>{{ $sendRepairInstance->WithEntireInstance->factory_name ?? '' }}</td>
                            @else
                                <td>{{ $sendRepairInstance->WithPartInstance->status }}</td>
                                <td>
                                    {{ $sendRepairInstance->WithPartInstance->Category->name ?? '' }}
                                    {{ $sendRepairInstance->WithPartInstance->PartCategory->name ?? '' }}
                                </td>
                                <td>{{ $sendRepairInstance->WithPartInstance->part_model_name ?? '' }}</td>
                                <td>{{ $sendRepairInstance->WithPartInstance->factory_name ?? '' }}</td>
                            @endif
                            <td>
                                <select id="selFaultStatus" class="form-control select2" style="width:100%;" onchange="updateSendRepairWithInstanceFaultStatus(`{{ $sendRepairInstance->material_unique_code }}`)">
                                    @foreach ($faultStatus as $key=>$value)
                                        <option value="{{$key}}" {{$sendRepairInstance->fault_status['value'] == $key ? 'selected' : ''}}>{{$value}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <a href="javascript:" onclick="fnModalUploadRepair(`{{$sendRepairInstance->send_repair_unique_code}}`,`{{$sendRepairInstance->material_unique_code}}`)" class="btn btn-default btn-flat">上传报告</a>
                                @if(!empty($sendRepairInstance->repair_report_url))
                                    <a href="{{ url('storehouse/sendRepair/downloadSendRepairFile',$sendRepairInstance->id) }}/report" target="_blank"><i class="fa fa-download"></i> 下载报告</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div id="divModalUploadRepair"></div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select2();
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });
        });

        /**
         * 打开上传检测记录文件窗口
         */
        function fnModalUploadRepair(send_repair_unique_code, material_unique_code) {
            $.ajax({
                url: `{{ url('storehouse/sendRepair/uploadRepair') }}`,
                type: 'get',
                data: {
                    send_repair_unique_code: send_repair_unique_code,
                    material_unique_code: material_unique_code,
                },
                async: true,
                success: function (res) {
                    console.log(`success:`, res);
                    $('#divModalUploadRepair').html(res);
                    $('#modalUploadRepair').modal('show');
                },
                error: function (err) {
                    console.log(`error:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 更改故障状态
         */
        function updateSendRepairWithInstanceFaultStatus(uniqueCode) {
            let faultStatus = $("#selFaultStatus").val();
            $.ajax({
                url: `{{ url('storehouse/sendRepair/updateSendRepairWithInstanceFaultStatus') }}`,
                type: 'put',
                data: {
                    send_repair_unique_code: `{{$sendRepairUniqueCode}}`,
                    material_unique_code: uniqueCode,
                    fault_status: faultStatus
                },
                async: true,
                success: function (response) {
                    console.log(`success:`, response);
                    location.reload();
                },
                error: function (error) {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
        }

    </script>
@endsection

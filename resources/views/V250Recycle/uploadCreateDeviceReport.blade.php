@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            上传设备赋码Excel
            <small>结果</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/edit?page={{ request('page',1) }}">任务详情</a></li>--}}
{{--            <li class="active">上传设备赋码Excel结果</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">上传设备赋码Excel结果</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/edit?page={{ request('page',1) }}&type={{ request('type') }}" class="btn btn-default btn-flat">返回任务详情</a>
                            <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnPrint('printQrCode')">打印二维码</a>
                            <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnPrint('printLabel')">打印位置标签</a>
                            @if($hasCreateDeviceError)
                                <p>
                                    <span class="text-danger">上传设备赋码Excel有错误</span>，<a href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/downloadCreateDeviceErrorExcel?{{ http_build_query(['path'=>$createDeviceErrorFilename]) }}" target="_blank">下载错误报告</a>
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-condensed table-hover table-bordered">
                                <thead>
                                <tr>
                                    <th><input type="checkbox" id="chkAllCheck" checked></th>
                                    <th>设备编号</th>
                                    <th>所编号</th>
                                    <th>型号</th>
                                    <th>厂家</th>
                                    <th>厂编号</th>
                                    <th>生产日期</th>
                                    <th>上道位置</th>
                                    <th>检测/检修人</th>
                                    <th>检测/检修时间</th>
                                    <th>验收人</th>
                                    <th>验收时间</th>
                                    <th>抽验人</th>
                                    <th>抽验时间</th>
                                    <th>状态</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($taskEntireInstances as $taskEntireInstance)
                                    <tr>
                                        <td><input type="checkbox" name="chk_identity_code" class="chk-entire-instances" value="{{ $taskEntireInstance->entire_instance_identity_code }}" checked></td>
                                        <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->identity_code : ''}}</td>
                                        <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->serial_number : ''}}</td>
                                        <td>
                                            {{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->SubModel ? $taskEntireInstance->EntireInstance->SubModel->name : '') : '' }}
                                            {{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->PartModel ? $taskEntireInstance->EntireInstance->PartModel->name : '') : '' }}
                                        </td>
                                        <td>{{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->Factory ? $taskEntireInstance->EntireInstance->Factory->name : ''): '' }}</td>
                                        <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->factory_device_code : '' }}</td>
                                        <td>{{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($taskEntireInstance->EntireInstance->made_at)) : '') : '' }}</td>
                                        <td>
                                            {{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->maintain_location_code : '' }}
                                            {{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->crossrod_number : '' }}
                                        </td>
                                        <td>{{ $taskEntireInstance->Fixer ? $taskEntireInstance->Fixer->nickname : '' }}</td>
                                        <td>{{ $taskEntireInstance->fixed_at ? date('Y-m-d',strtotime($taskEntireInstance->fixed_at)) : '' }}</td>
                                        <td>{{ $taskEntireInstance->Checker ? $taskEntireInstance->Checker->nickname : '' }}</td>
                                        <td>{{ $taskEntireInstance->checked_at ? date('Y-m-d',strtotime($taskEntireInstance->checked_at)) : '' }}</td>
                                        <td>{{ $taskEntireInstance->SpotChecker ? $taskEntireInstance->SpotChecker->nickname : '' }}</td>
                                        <td>{{ $taskEntireInstance->spot_checked_at ? date('Y-m-d',strtotime($taskEntireInstance->spot_checked_at)) : '' }}</td>
                                        <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->status : '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($taskEntireInstances->hasPages())
                        <div class="box-footer">
                            {{ $taskEntireInstances->appends(['page'=>request('page',1),'type'=>request('type')])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $selType = $('#selType');
        let $chkAllCheck = $('#chkAllCheck');

        /**
         * 全选多选框绑定
         * @param {string} allCheckId
         * @param {string} checkClassName
         */
        function fnAllCheckBind(allCheckId, checkClassName) {
            $(allCheckId).on('click', function () {
                $(checkClassName).prop('checked', $(allCheckId).prop('checked'));
            });
            $('.chk-entire-instances').on('click', function () {
                $(allCheckId).prop('checked', $(`${checkClassName}:checked`).length === $(checkClassName).length);
            });
        }

        fnAllCheckBind('#chkAllCheck', '.chk-entire-instances');

        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 打印标签
         * @param type
         */
        function fnPrint(type) {
            // 处理数据
            let identityCodes = [];
            $(".chk-entire-instances:checked").each((index, item) => {
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
                        window.open(`/qrcode/${type}`);
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

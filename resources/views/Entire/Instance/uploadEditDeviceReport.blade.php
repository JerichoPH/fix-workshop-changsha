@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            上传设备数据补充
            <small>结果</small>
        </h1>
        {{--        <ol class="breadcrumb">--}}
        {{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--            <li class="active">上传设备数据补充结果</li>--}}
        {{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">上传设备数据补充结果</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            {{--                            <a href="{{ url('entire/instance/uploadEditDevice') }}" class="btn btn-default btn-flat">返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat">返回</a>
                            <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnPrint('printQrCode')">打印二维码</a>
                            <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnPrint('printLabel')">打印位置标签</a>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-condensed table-hover table-bordered">
                                <thead>
                                <tr>
                                    <th><input type="checkbox" id="chkAllCheck" checked/></th>
                                    <th>设备编号</th>
                                    <th>所编号</th>
                                    <th>型号</th>
                                    <th>厂编号</th>
                                    <th>厂家</th>
                                    <th>生产日期</th>
                                    <th>寿命</th>
                                    <th>车站</th>
                                    <th>上道位置</th>
                                    <th>开向</th>
                                    <th>线制</th>
                                    <th>表示干特征</th>
                                    <th>道岔类型</th>
                                    <th>防挤压保护罩</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($entireInstances as $entireInstance)
                                    <tr>
                                        <td><input type="checkbox" name="chk_identity_code" class="chk-entire-instances" value="{{ $entireInstance->identity_code }}" checked/></td>
                                        <td>{{ $entireInstance->identity_code }}</td>
                                        <td>{{ $entireInstance->serial_number }}</td>
                                        <td>
                                            {{ $entireInstance->SubModel ? $entireInstance->SubModel->name : ''  }}
                                            {{ $entireInstance->PartModel ? $entireInstance->PartModel->name : '' }}
                                        </td>
                                        <td>{{ $entireInstance->factory_device_code }}</td>
                                        <td>{{ $entireInstance->Factory->name }}</td>
                                        <td>{{ $entireInstance->made_at ? date('Y-m-d',strtotime($entireInstance->made_at)) : '' }}</td>
                                        <td>{{ $entireInstance->life_year }}</td>
                                        <td>
                                            {{ $entireInstance->Station ? ($entireInstance->Station->Parent ? $entireInstance->Station->Parent->name : '') :'' }}
                                            {{ $entireInstance->Station ? $entireInstance->Station->name : '' }}
                                        </td>
                                        <td>
                                            {{ $entireInstance->maintain_location_code }}
                                            {{ $entireInstance->crossroad_number }}
                                        </td>
                                        <td>{{ $entireInstance->open_direction }}</td>
                                        <td>{{ $entireInstance->line_name }}</td>
                                        <td>{{ $entireInstance->said_ord }}</td>
                                        <td>{{ $entireInstance->crossroad_type }}</td>
                                        <td>{{ $entireInstance->extrusion_protect ? '是' : '否'}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{--@if($entireInstances->hasPages())--}}
                    {{--    <div class="box-footer">--}}
                    {{--        {{ $entireInstances->appends(['page'=>request('page',1),'type'=>request('type')])->links() }}--}}
                    {{--    </div>--}}
                    {{--@endif--}}
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

@extends('Layout.index')
@section('content')
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="box-title">待入所设备列表</h3>
                    </div>
                    <div class="col-md-6">
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm">
                            <div class="input-group">
                                <div class="input-group-addon">唯一/所编号</div>
                                <input type="text" name="code" id="txtCode" class="form-control" placeholder="扫码前点击" autofocus required onkeyup="if(event.keyCode ===13) fnQrCode('{{ $taskOrderSerialNumber }}' , this.value)">
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-flat btn-default" onclick="fnPrintQrCode()">打印入所标签</a>
                                    <a href="javascript:" onclick="fnDeleteAll('{{ $taskOrderSerialNumber }}')" class="btn btn-danger btn-flat"><i class="fa fa-times">&nbsp;</i>清空</a>
                                    <a href="javascript:" onclick="modalCreateWarehouse()" class="btn btn-primary btn-flat"><i class="fa fa-sign-{{ strtolower(request('direction')) }}">&nbsp;</i>入所</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="checkbox-toggle"></th>
                                <th>设备编号</th>
                                <th>所编号</th>
                                <th>型号</th>
                                <th>厂家</th>
                                <th>厂编号</th>
                                <th>生产日期</th>
                                <th>出所日期</th>
                                <th>上道位置</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                        <tbody>
                        @foreach($taskEntireInstances as $taskEntireInstance)
                            <tr>
                                <td><input type="checkbox" name="labelChecked" value="{{ $taskEntireInstance->entire_instance_identity_code }}"/></td>
                                <td>{{ $taskEntireInstance->entire_instance_identity_code }}</td>
                                <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->serial_number : ''}}</td>
                                <td>{{ $taskEntireInstance->EntireInstance->SubModel ? $taskEntireInstance->EntireInstance->SubModel->name : '' }}{{ $taskEntireInstance->EntireInstance->PartModel ? $taskEntireInstance->EntireInstance->PartModel->name : '' }}</td>
                                <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->factory_name : '' }}</td>
                                <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->factory_device_code : '' }}</td>
                                <td>{{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($taskEntireInstance->EntireInstance->made_at)) : '') : '' }}</td>
                                <td>
                                    @if($taskEntireInstance->is_out)
                                        <a href="{{ url('warehouse',$taskEntireInstance->out_warehouse_sn) }}">{{ $taskEntireInstance->out_at ? $taskEntireInstance->out_at : ''}}</a>
                                    @endif
                                </td>
                                <td>{{ $taskEntireInstance->EntireInstance->maintain_location_code }}</td>
                                <td>{{ $taskEntireInstance->EntireInstance->status ?? '' }}</td>
                                <td>
                                    <a href="javascript:" onclick="fnDelete({{ $taskEntireInstance->id }})" class="btn btn-flat btn-danger btn-sm"><i class="fa fa-times">&nbsp;</i>删除</a>
                                </td>
                            </tr>
                        @endforeach
                            </tbody>
                        </table>
                </div>
            </div>
        </div>

        <!--设备入所模态框-->
        <div class="modal fade" id="modalCreateWarehouse">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">设备入所</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreWarehouse">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_name" id="txtConnectionName" class="form-control" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">联系电话：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" name="connection_phone" id="txtConnectionPhone" class="form-control" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">经办人：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="processor_id" id="selProcessorId" class="form-control disabled" style="width: 100%;" disabled>
                                        <option value="{{ session('account.id') }}">{{ session('account.nickname') }}</option>
                                    </select>
                                </div>
                            </div>
{{--                            <div class="form-group">--}}
{{--                                <label class="col-sm-3 col-md-3 control-label">入所时间：</label>--}}
{{--                                <div class="col-sm-9 col-md-8">--}}
{{--                                    <div class="input-group">--}}
{{--                                        <div class="input-group-addon">日期</div>--}}
{{--                                        <input type="text" class="form-control pull-right" id="dpProcessedDate" value="{{ date('Y-m-d') }}">--}}
{{--                                        <div class="input-group-addon">时间</div>--}}
{{--                                        <input type="text" class="form-control timepicker" id="tpProcessedTime">--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreWarehouse('{{ $taskOrderSerialNumber }}')"><i>&nbsp;</i>入所</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $txtConnectionName = $('#txtConnectionName');
        let $txtConnectionPhone = $('#txtConnectionPhone');
        let $dpProcessedDate = $('#dpProcessedDate');
        let $tpProcessedTime = $('#tpProcessedTime');

        /**
         * 光标定位闪烁
         */
        $(function () {
            var inputBox = document.getElementById('txtCode');
            inputBox.selectionStart = inputBox.value.length - 2;
            inputBox.selectionEnd = inputBox.value.length;
            inputBox.focus();
        });

        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,  // 分页器
                    lengthChange: true,
                    searching: false,  // 搜索框
                    ordering: false,  // 列排序
                    info: true,
                    autoWidth: false,  // 自动宽度
                    order: [[0, 'desc']],  // 排序依据
                    iDisplayLength: "{{ env('PAGE_SIZE', 50) }}",  // 默认分页数
                    aLengthMenu: [50, 100, 200],  // 分页下拉框选项
                    language: {
                        sInfoFiltered: "从_MAX_中过滤",
                        sProcessing: "正在加载中...",
                        info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "每页显示_MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "筛选：",
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });
            }

            // 入所日期
            let datepickerOption = {
                autoclose: true,
                todayHighlight: true,
                language: "cn",
                format: "yyyy-mm-dd",
                beforeShowDay: $.noop,
                calendarWeeks: false,
                clearBtn: true,
                daysOfWeekDisabled: [],
                endDate: Infinity,
                forceParse: true,
                keyboardNavigation: true,
                minViewMode: 0,
                orientation: "auto",
                rtl: false,
                startDate: -Infinity,
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#dpProcessedDate').datepicker(datepickerOption);

            // 入所时间
            $tpProcessedTime.timepicker({
                showInputs: true,
                showMeridian: false,
            });

            // iCheck for checkbox and radio inputs
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $("#table input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $("#table input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });
        });

        // 扫码判断
        function fnQrCode(sn, code) {
            if (!code) {
                alert('请填写唯一/所编号');
                return false;
            }
            $.ajax({
                url: `{{ url('v250WorkshopIn') }}/${sn}/scanCode`,
                type: 'post',
                data: {'code': code},
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250WorkshopIn') }}/${sn}/scanCode success:`, res);
                    if (res.code == 0) {
                        alert(res.msg);
                        location.reload();
                    }else {
                        location.reload();
                    }
                },
                error: function (err) {
                    console.log(`{{ url('v250WorkshopIn') }}/${sn}/scanCode fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认')) {
                $.ajax({
                    url: `{{ url('v250WorkshopIn') }}/${id}`,
                    type: 'delete',
                    data: {},
                    success: function (res) {
                        console.log(`{{ url('v250WorkshopIn') }}/${id} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('v250WorkshopIn') }}/${id} fail:`, err);
                        if (err.responseText === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 清空
         * @param sn
         */
        function fnDeleteAll(sn) {
            if (confirm('清除不能恢复，是否确认')) {
                $.ajax({
                    url: `{{ url('v250WorkshopIn') }}/${sn}/destroyAll`,
                    type: 'post',
                    data: {},
                    success: function (res) {
                        console.log(`{{ url('v250WorkshopIn') }}/${sn}/destroyAll success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('v250WorkshopIn') }}/${sn}/destroyAll fail:`, err);
                        if (err.responseText === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            }
        }

        /**
         * 打开入所窗口
         */
        function modalCreateWarehouse() {
            // let identityCodes = [];
            // $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
            //     let new_code = $(item).val();
            //     if (new_code !== '') identityCodes.push(new_code);
            // });
            // if (identityCodes.length <= 0) {
            //     alert('请先选择设备');
            //     return false;
            // }
            $('#modalCreateWarehouse').modal('show');
        }

        /**
         * 设备入所
         */
        function fnStoreWarehouse(sn) {
            // let identityCodes = [];
            // $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
            //     let new_code = $(item).val();
            //     if (new_code !== '') identityCodes.push(new_code);
            // });
            // if (identityCodes.length <= 0) {
            //     alert('请先选择设备');
            //     return false;
            // }

            $.ajax({
                url: `{{ url('v250WorkshopIn') }}/${sn}/workshopIn`,
                type: 'post',
                data: {
                    connectionName: $txtConnectionName.val(),
                    connectionPhone: $($txtConnectionPhone).val(),
                    processedDate: $dpProcessedDate.val(),
                    processedTime: $tpProcessedTime.val(),
                    // identityCodes: identityCodes,
                },
                async: true,
                success: function (res) {
                    alert(res.msg);
                    console.log(`{{ url('v250WorkshopIn') }}/${sn}/workshopIn success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('warehouse/report/scanBatchWarehouse') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 打印设备标签
         */
        function fnPrintQrCode(){
            // 处理数据
            let identityCodes = [];

            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') identityCodes.push(new_code);
            });
            if (identityCodes.length <= 0) {
                alert('请先选择设备');
                return false;
            }

            // 保存需要打印的数据
            $.ajax({
                url: `{{ url('/warehouse/report/identityCodeWithPrint') }}`,
                type: 'post',
                data: {identityCodes},
                async: false,
                success: function (res) {
                    console.log(`{{ url('/warehouse/report/identityCodeWithPrint') }} success:`, res);
                    window.open(`{{url('qrcode/printQrCode')}}`, '_blank');
                    {{--window.location.href = `{{url('qrcode/printQrCode')}}`, '_blank';--}}
                },
                error: function (err) {
                    console.log(`{{ url('/warehouse/report/identityCodeWithPrint') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

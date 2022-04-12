@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            故障类型管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">故障类型列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="javascript:" onclick="modalCreateBreakdownType()" class="btn btn-flat btn-success"><i class="fa fa-plus">&nbsp;</i>新建</a>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>故障类型名称</th>
                            <th>所属种类</th>
                            <th>所属工区</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($breakdown_types as $breakdown_type)
                            <tr>
                                <td>{{ $breakdown_type->name  }}</td>
                                <td>{{ $breakdown_type->Category ? $breakdown_type->Category->name : '' }}</td>
                                <td>{{ $breakdown_type->work_area->name }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="javascript:" onclick="modalEditBreakdownType({{ $breakdown_type->id }})" class="btn btn-warning btn-flat btn-sm"><i class="fa fa-pencil"></i></a>
                                        <a class="btn btn-danger btn-flat btn-sm" onclick="fnDelete('{{ $breakdown_type->id }}')"><i class="fa fa-times"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($breakdown_types->hasPages())
                <div class="box-footer">
                    {{ $breakdown_types->appends(['page'=>request('page',1)])->links() }}
                </div>
            @endif
        </div>
    </section>

    <section class="section">
        <!--添加故障类型模态框-->
        <div class="modal fade" id="modalCreateBreakdownType">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加故障类型</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmCreateBreakdownType">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">故障类型名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="" id="txtName_modalCreateBreakdownType">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属种类：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="category_unique_code" id="selCategory_modalCreateBreakdownType" class="form-control select2" style="width:100%;">
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->unique_code }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属工区：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="work_area_type" id="selWorkArea_modalCreateBreakdownType" class="form-control select2" style="width:100%;">
                                        @foreach ($work_area_types as $work_area_type_code => $work_area_type_name)
                                            <option value="{{ $work_area_type_code }}">{{ $work_area_type_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreBreakdownType()"><i class="fa fa-check">&nbsp;</i>保存</button>
                    </div>
                </div>
            </div>
        </div>

        <!--编辑故障类型模态框-->
        <div class="modal fade" id="modalEditBreakdownType">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">编辑故障类型</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmUpdateBreakdownType">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">故障类型名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="" id="txtName_modalEditBreakdownType">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属种类：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="category_unique_code" id="selCategory_modalEditBreakdownType" class="form-control select2" style="width:100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属工区：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="work_area_type" id="selWorkArea_modalEditBreakdownType" class="form-control select2" style="width:100%;"></select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-warning btn-flat btn-sm" onclick="fnUpdateBreakdownType()"><i class="fa fa-check">&nbsp;</i>保存</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let categories = JSON.parse('{!! $categories_as_json !!}');
        let workAreaTypes = JSON.parse('{!! $work_area_types_as_json !!}');
        let currentEditBreakdownTypeId = 0;

        let $select2 = $('.select2');
        let $modalCreateBreakdownType = $('#modalCreateBreakdownType');
        let $frmCreateBreakdownType = $('#frmCreateBreakdownType');
        let $modalEditBreakdownType = $('#modalEditBreakdownType');
        let $frmUpdateBreakdownType = $('#frmUpdateBreakdownType');
        let $txtName_modalEditBreakdownType = $('#txtName_modalEditBreakdownType');
        let $selCategory_modalEditBreakdownType = $('#selCategory_modalEditBreakdownType');
        let $selWorkArea_modalEditBreakdownType = $('#selWorkArea_modalEditBreakdownType');

        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,  // 分页器
                    lengthChange: false,
                    searching: false,  // 搜索框
                    ordering: false,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
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

            $('#reservation').daterangepicker({
                locale: {
                    format: "YYYY-MM-DD",
                    separator: "~",
                    daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                    monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    applyLabel: "确定",
                    cancelLabel: "取消",
                    fromLabel: "开始时间",
                    toLabel: "结束时间",
                    customRangeLabel: "自定义",
                    weekLabel: "W",
                },
                startDate: originAt,
                endDate: finishAt
            });
        });

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('breakdownType') }}/${id}`,
                    type: 'delete',
                    data: {id: id},
                    success: function (res) {
                        console.log(`{{ url('breakdownType')}}/${id} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('breakdownType')}}/${id} fail:`, err);
                        if (err.responseText === 401) location.href = "{{ url('login') }}";
                        if (err['responseJSON']['msg'].constructor === Object) {
                            let message = '';
                            for (let msg of err['responseJSON']['msg']) message += `${msg}\r\n`;
                            alert(message);
                            return;
                        }
                        alert(err['responseJSON']['msg']);
                    }
                });
        }

        /**
         * 打开新建故障类型模态窗
         */
        function modalCreateBreakdownType() {
            $modalCreateBreakdownType.modal('show');
        }

        /**
         * 新建故障类型
         */
        function fnStoreBreakdownType() {
            $.ajax({
                url: `{{ url('breakdownType') }}`,
                type: 'post',
                data: $frmCreateBreakdownType.serializeArray(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('breakdownType') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('breakdownType') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 填充种类
         * @param {string} categoryUniqueCode
         */
        function fnFillCategory_modalEditBreakdownType(categoryUniqueCode) {
            let html = '';
            $.each(categories, function (idx, category) {
                html += `<option value="${category.unique_code}" ${category.unique_code === categoryUniqueCode ? 'selected' : ''}>${category.name}</option>`;
            });
            $selCategory_modalEditBreakdownType.html(html);
        }

        /**
         * 填充工区
         */
        function fnFillWorkArea_modalEditBreakdownType(workAreaTypeId) {
            let html = '';
            $.each(workAreaTypes, function (code, name) {
                html += `<option value="${code}" ${code === workAreaTypeId ? 'selected' : ''}>${name}</option>`;
            });
            $selWorkArea_modalEditBreakdownType.html(html);
        }

        /**
         * 打开编辑故障类型模态框
         * @param {int} id
         */
        function modalEditBreakdownType(id) {
            $.ajax({
                url: `{{ url('breakdownType') }}/${id}`,
                type: 'get',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('breakdownType') }}/${id} success:`, res);

                    let {breakdown_type: breakdownType,} = res.data;

                    $txtName_modalEditBreakdownType.val(breakdownType.name);
                    currentEditBreakdownTypeId = id;
                    fnFillCategory_modalEditBreakdownType(breakdownType.category_unique_code);
                    fnFillWorkArea_modalEditBreakdownType(breakdownType.work_area.code);

                    $modalEditBreakdownType.modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('breakdownType') }}/${id} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }

        /**
         * 编辑故障类型
         */
        function fnUpdateBreakdownType() {
            let data = $frmUpdateBreakdownType.serializeArray();

            $.ajax({
                url: `{{ url('breakdownType') }}/${currentEditBreakdownTypeId}`,
                type: 'put',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('breakdownType') }}/${currentEditBreakdownTypeId} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('breakdownType') }}/${currentEditBreakdownTypeId} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseJSON.msg);
                }
            });
        }
    </script>
@endsection

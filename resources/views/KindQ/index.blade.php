@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            器材种类型管理
            <small></small>
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">种类(总计：<span id="spanCategoryCount"></span>个)</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm"></div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-addon">种类</div>
                            <select name="category_unique_code" id="selCategory" class="form-control select2"
                                    style="width: 100%;" onchange="fnSelectCategory(this.value)">
                            </select>
                            <div class="input-group-btn">
                                <a href="javascript:" class="btn btn-info btn-flat" onclick="modalEditCategory()"><i class="fa fa-pencil">&nbsp;</i>编辑</a>
                                <a href="javascript:" class="btn btn-success btn-flat" onclick="modalCreateCategory()"><i class="fa fa-plus">&nbsp;</i>添加种类</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6"></div>
                    <div class="col-md-12">
                        <hr>
                    </div>
                    <div class="col-md-6">
                        <h4 class="box-title">
                            类型(总计：{{ $entire_models->count() }}个)&emsp;&emsp;
                            <a href="javascript:" class="btn btn-flat btn-success btn-sm"
                               onclick="modalCreateEntireModel()"><i class="fa fa-plus">&nbsp;</i>添加类型</a>
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-condensed" id="tblEntireModel">
                                <thead>
                                <tr>
                                    <th>名称</th>
                                    <th>代码</th>
                                    <th>周期修(年)</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($entire_models as $entire_model)
                                    <tr
                                        class="{{ $current_entire_model_unique_code == $entire_model->unique_code ? 'bg-orange' : '' }}">
                                        <td>
                                            @if($current_entire_model_unique_code == $entire_model->unique_code)
                                                <span
                                                    style="color: {{ $current_entire_model_unique_code == $entire_model->unique_code ? '#FFFFFF;' : ''  }}">{{ $entire_model->name }}</span>
                                            @else
                                                <a href="javascript:"
                                                   onclick="fnSelectEntireModel('{{ $entire_model->unique_code }}')">{{ $entire_model->name }}</a>
                                            @endif
                                        </td>
                                        <td>{{ $entire_model->unique_code }}</td>
                                        <td>{{ $entire_model->fix_cycle_value }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="javascript:" class="btn btn-primary btn-flat btn-sm" onclick="modalEditEntireModel('{{ $entire_model->unique_code }}')"><i class="fa fa-pencil">&nbsp;</i>编辑</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="divSubModel">
                            <h4 class="box-title">
                                型号(总计：{{ $sub_models->count() }}个)&emsp;&emsp;
                                <a href="javascript:" class="btn btn-flat btn-success btn-sm"
                                   onclick="modalCreateSubModel()"><i class="fa fa-plus">&nbsp;</i>添加型号</a>
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed" id="tblSubModel">
                                    <thead>
                                    <tr>
                                        <th>名称</th>
                                        <th>代码</th>
                                        <th>周期修(年)</th>
                                        <th>操作</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($sub_models as $sub_model)
                                        <tr>
                                            <td>{{ $sub_model->name }}</td>
                                            <td>{{ $sub_model->unique_code }}</td>
                                            <td>{{ $sub_model->fix_cycle_value }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="javascript:" class="btn btn-warning btn-flat btn-sm" onclick="modalEditSubModel('{{ $sub_model->unique_code }}')"><i class="fa fa-pencil">&nbsp;</i>编辑</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <!--添加种类模态框-->
        <div class="modal fade" id="modalCreateCategory">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加种类</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreCategory">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">种类名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="" id="txtName_modalCreateCategory">
                                </div>
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreCategory()"><i class="fa fa-check">&nbsp;</i>保存</button>
                    </div>
                </div>
            </div>
        </div>

        <!--编辑种类模态框-->
        <div class="modal fade" id="modalEditCategory">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加种类</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmUpdateCategory">
                            <input type="hidden" name="unique_code" id="hdnCategoryUniqueCode_modalEditCategoryUniqueCode">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">种类名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="" id="txtName_modalEditCategory">
                                </div>
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i
                                class="fa fa-times">&nbsp;</i>关闭
                        </button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdateCategory()"><i
                                class="fa fa-check">&nbsp;</i>保存
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!--添加类型模态框-->
        <div class="modal fade" id="modalCreateEntireModel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加类型</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreEntireModel">
                            <input type="hidden" name="category_unique_code" value="{{ $current_category_unique_code }}">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属种类：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="category_unique_code" id="selCategory_modalCreateEntireModel" class="form-control select2" style="width: 100%;" disabled></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">类型名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">周期修(年)：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="number" min="0" step="1" class="form-control" name="cycle_fix_value" value="0">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreEntireModel()"><i class="fa fa-check">&nbsp;</i>保存</button>
                    </div>
                </div>
            </div>
        </div>

        <!--编辑类型模态框-->
        <div class="modal fade" id="modalEditEntireModel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">编辑类型</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmUpdateEntireModel">
                            <input type="hidden" name="unique_code" id="hdnEntireModelUniqueCode_modalEditEntireModel">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">类型名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="" id="txtName_modalEditEntireModel">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">周期修(年)：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="number" min="0" step="1" class="form-control" name="fix_cycle_value" value="0" id="numFixCycleValue_modalEditEntireModel">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i
                                class="fa fa-times">&nbsp;</i>关闭
                        </button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdateEntireModel()"><i
                                class="fa fa-check">&nbsp;</i>保存
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!--添加型号模态框-->
        <div class="modal fade" id="modalCreateSubModel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加型号</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreSubModel">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属种类：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="category_unique_code" class="form-control select2" id="selCategory_modalCreateSubModel" style="width: 100%;" onchange="fnFillEntireModel(this.value)" disabled></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属类型：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="entire_model_unique_code" class="form-control select2" id="selEntireModel_modalCreateSubModel" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">型号名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">周期修(年)：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="number" min="0" step="1" class="form-control" name="fix_cycle_value" value="0">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnCreateSubModel()"><i class="fa fa-check">&nbsp;</i>保存</button>
                    </div>
                </div>
            </div>
        </div>

        <!--编辑型号模态框-->
        <div class="modal fade" id="modalEditSubModel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">编辑型号</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmUpdateSubModel">
                            <input type="hidden" name="entire_model_unique_code" id="hdnEntireModelUniqueCode_modalEditSubModel">
                            <input type="hidden" name="unique_code" id="hdnSubModelUniqueCode_modalEditSubModel">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">型号名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="" id="txtName_modalEditSubModel">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">周期修(年)：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="number" min="0" step="1" class="form-control" name="fix_cycle_value" value="0" id="numFixCycleFixValue_modalEditSubModel">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdateSubModel()"><i class="fa fa-check">&nbsp;</i>保存</button>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
@section('script')
    <script>
        let categories = {!! $categories_as_json !!};
        let entireModels = {!! $entire_models_as_json !!};
        let subModels = {!! $sub_models_as_json !!};

        let $select2 = $('.select2');
        let $selCategory = $('#selCategory');
        let $spanCategoryCount = $('#spanCategoryCount');
        let $txtName_modalCreateCategory = $('#txtName_modalCreateCategory');
        let $hdnCategoryUniqueCode_modalCreateEntireModel = $('#hdnCategoryUniqueCode_modalCreateEntireModel');
        let $selCategory_modalCreateEntireModel = $('#selCategory_modalCreateEntireModel');
        let $selCategory_modalCreateSubModel = $('#selCategory_modalCreateSubModel');
        let $selEntireModel_modalCreateSubModel = $('#selEntireModel_modalCreateSubModel');
        let $txtName_modalEditCategory = $('#txtName_modalEditCategory');
        let $txtName_modalEditEntireModel = $('#txtName_modalEditEntireModel');
        let $numFixCycleValue_modalEditEntireModel = $('#numFixCycleValue_modalEditEntireModel');
        let $txtName_modalEditSubModel = $('#txtName_modalEditSubModel');
        let $numFixCycleFixValue_modalEditSubModel = $('#numFixCycleFixValue_modalEditSubModel');

        /**
         * 填充种类
         */
        function fnFillCategory() {
            let html = `<option value="" disabled selected>无</option>`;
            $.each(categories, function (key, category) {
                html += `<option value="${category['unique_code']}" ${'{{ $current_category_unique_code }}' === category['unique_code'] ? 'selected' : ''}>${category['name']}</option>`;
            });
            $selCategory.html(html);
            $selCategory_modalCreateEntireModel.html(html);
            $selCategory_modalCreateSubModel.html(html);
            $spanCategoryCount.text(categories.length);
            fnFillEntireModel('{{ $current_category_unique_code }}');
        }

        /**
         * 填充类型
         */
        function fnFillEntireModel(categoryUniqueCode) {
            let html = `<option value="" disabled selected>无</option>`;
            if (categoryUniqueCode) {
                if (entireModels.hasOwnProperty(categoryUniqueCode)) {
                    $.each(entireModels[categoryUniqueCode], function (key, entireModel) {
                        html += `<option value="${entireModel['unique_code']}" ${'{{ $current_entire_model_unique_code }}' === entireModel['unique_code'] ? 'selected' : ''}>${entireModel['name']}</option>`;
                    });
                }
            }
            $selEntireModel_modalCreateSubModel.html(html);
        }

        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: true,  // 搜索框
                    ordering: true,  // 列排序
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

            fnFillCategory();  // 填充种类
        });

        /**
         * 选择种类
         */
        function fnSelectCategory(categoryUniqueCode) {
            location.href = `?category_unique_code=${categoryUniqueCode}`;
        }

        /**
         * 选择类型
         */
        function fnSelectEntireModel(entireModelUniqueCode) {
            location.href = `?category_unique_code=${entireModelUniqueCode.substr(0, 3)}&entire_model_unique_code=${entireModelUniqueCode}`;
        }

        /**
         * 打开添加种类弹框
         */
        function modalCreateCategory() {
            $('#modalCreateCategory').modal('show');
        }

        /**
         * 添加种类
         */
        $txtName_modalCreateCategory.on('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                fnStoreCategory();
            }
        });

        /**
         * 添加种类
         */
        function fnStoreCategory() {
            $.ajax({
                url: `{{ url('kindQ/category') }}`,
                type: 'POST',
                data: $('#frmStoreCategory').serialize(),
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/category') }} success:`, res);
                    location.href = `{{ url('kindQ') }}?category_unique_code=${res.data.category.unique_code}`;
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/category') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 打开编辑种类模态框
         */
        function modalEditCategory() {
            $.ajax({
                url: `{{ url('kindQ/category') }}/${$selCategory.val()}`,
                type: 'GET',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/category') }}/${$selCategory.val()} success:`, res);
                    $('#txtName_modalEditCategory').val(res.data.category.name);
                    $('#hdnCategoryUniqueCode_modalEditCategoryUniqueCode').val(res.data.category.unique_code);
                    $('#modalEditCategory').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/category') }}/${$selCategory.val()} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑种类
         */
        $txtName_modalEditCategory.on('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                fnUpdateCategory();
            }
        });

        /**
         * 编辑种类
         */
        function fnUpdateCategory() {
            let data = $('#frmUpdateCategory').serializeArray();

            $.ajax({
                url: `{{ url('kindQ/category') }}`,
                type: 'PUT',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/category') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/category') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 添加类型模态框
         */
        function modalCreateEntireModel() {
            $('#modalCreateEntireModel').modal('show');
        }

        /**
         * 添加类型
         */
        function fnStoreEntireModel() {
            let data = $('#frmStoreEntireModel').serializeArray();
            $.ajax({
                url: `{{ url('kindQ/entireModel') }}`,
                type: 'POST',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/entireModel') }} success:`, res);
                    {{--location.href = `{{ url('kindQ') }}?category_unique_code=${res.data.entire_model.category_unique_code}&entire_model_unique_code=${res.data.entire_model.unique_code}`;--}}
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/entireModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 打开编辑类型模态框
         */
        function modalEditEntireModel(uniqueCode) {
            $.ajax({
                url: `{{ url('kindQ/entireModel') }}/${uniqueCode}`,
                type: 'GET',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/entireModel') }}/${uniqueCode} success:`, res);
                    $('#txtName_modalEditEntireModel').val(res.data.entire_model.name);
                    $('#numFixCycleValue_modalEditEntireModel').val(res.data.entire_model.fix_cycle_value);
                    $('#hdnEntireModelUniqueCode_modalEditEntireModel').val(res.data.entire_model.unique_code);
                    $('#modalEditEntireModel').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/entireModel') }}/${uniqueCode} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑类型
         */
        $txtName_modalEditEntireModel.on('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                fnUpdateEntireModel();
            }
        });
        /**
         * 编辑类型
         */
        $numFixCycleValue_modalEditEntireModel.on('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                fnUpdateEntireModel();
            }
        });

        /**
         * 编辑类型
         */
        function fnUpdateEntireModel() {
            let data = $('#frmUpdateEntireModel').serializeArray();
            $.ajax({
                url: `{{ url('kindQ/entireModel') }}`,
                type: 'PUT',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/entireModel') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/entireModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 添加型号模态框
         */
        function modalCreateSubModel() {
            $('#modalCreateSubModel').modal('show');
        }

        /**
         * 添加型号
         */
        function fnCreateSubModel() {
            let data = $('#frmStoreSubModel').serializeArray();
            $.ajax({
                url: `{{ url('kindQ/subModel') }}`,
                type: 'POST',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/subModel') }} success:`, res);
                    location.href = `{{ url('kindQ') }}?category_unique_code=${res.data.sub_model.category_unique_code}&entire_model_unique_code=${res.data.sub_model.parent_unique_code}`;
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/subModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑型号模态框
         */
        function modalEditSubModel(uniqueCode) {
            $.ajax({
                url: `{{ url('kindQ/subModel') }}/${uniqueCode}`,
                type: 'GET',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/subModel') }}/${uniqueCode} success:`, res);
                    $('#txtName_modalEditSubModel').val(res.data.sub_model.name);
                    $('#numFixCycleFixValue_modalEditSubModel').val(res.data.sub_model.fix_cycle_value);
                    $('#hdnEntireModelUniqueCode_modalEditSubModel').val(res.data.sub_model.parent_unique_code);
                    $('#hdnSubModelUniqueCode_modalEditSubModel').val(res.data.sub_model.unique_code);
                    $('#modalEditSubModel').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/subModel') }}/${uniqueCode} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑型号
         */
        $txtName_modalEditSubModel.on('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                fnUpdateSubModel();
            }
        });
        /**
         * 编辑型号
         */
        $numFixCycleFixValue_modalEditSubModel.on('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                fnUpdateSubModel();
            }
        });

        /**
         * 编辑型号
         */
        function fnUpdateSubModel() {
            let data = $('#frmUpdateSubModel').serializeArray();
            $.ajax({
                url: `{{ url('kindQ/subModel') }}`,
                type: 'PUT',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindQ/subModel') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('kindQ/subModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

    </script>
@endsection

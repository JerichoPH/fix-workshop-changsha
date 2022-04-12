@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备种类型管理
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
                                <a href="javascript:" class="btn btn-info btn-flat" onclick="modalEditCategory()"><i
                                        class="fa fa-pencil">&nbsp;</i>编辑</a>
                                <a href="javascript:" class="btn btn-success btn-flat" onclick="modalCreateCategory()"><i
                                        class="fa fa-plus">&nbsp;</i>添加种类</a>
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
                                部件型号(总计：{{ $part_models->count() }}个)&emsp;&emsp;
                                <a href="javascript:" class="btn btn-flat btn-success btn-sm" onclick="modalCreatePartModel()"><i class="fa fa-plus">&nbsp;</i>添加部件型号</a>
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed" id="tblSubModel">
                                    <thead>
                                    <tr>
                                        <th>名称</th>
                                        <th>代码</th>
                                        <th>操作</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($part_models as $part_model)
                                        <tr>
                                            <td>{{ $part_model->name }}</td>
                                            <td>{{ $part_model->unique_code }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="javascript:" class="btn btn-warning btn-flat btn-sm" onclick="modalEditPartModel('{{ $part_model->unique_code }}')"><i class="fa fa-pencil">&nbsp;</i>编辑</a>
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
                                    <select name="category_unique_code" id="selCategory_modalCreateEntireModal" class="form-control select2" style="width: 100%;" disabled></select>
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
                                    <input type="number" min="0" step="1" class="form-control" name="fix_cycle_value" value="0">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭
                        </button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnStoreEntireModel()"><i class="fa fa-check">&nbsp;</i>保存
                        </button>
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
                                    <input type="text" class="form-control" name="name" value="" id="txtName_modalEditEntireModal">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">周期修(年)：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="number" min="0" step="1" class="form-control" name="fix_cycle_value" value="0" id="numFixCycleValue_modalEditEntireModal">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdateEntireModel()"><i class="fa fa-check">&nbsp;</i>保存</button>
                    </div>
                </div>
            </div>
        </div>

        <!--添加部件型号模态框-->
        <div class="modal fade" id="modalCreatePartModel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">添加部件型号</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreSubModel">
                            <input type="hidden" name="category_unique_code" value="{{ $current_category_unique_code }}">
                            <input type="hidden" name="entire_model_unique_code" value="{{ $current_entire_model_unique_code }}">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属种类：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="category_unique_code" class="form-control select2" id="selCategory_modalCreatePartModal" style="width: 100%;" onchange="fnFillEntireModel(this.value)" disabled></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属类型：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="entire_model_unique_code" class="form-control select2" id="selEntireModel_modalCreatePartModal" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属部件种类：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="part_category_id" class="form-control select2" id="selPartCategory_modalCreatePartModal" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">部件型号名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="">
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

        <!--编辑部件型号模态框-->
        <div class="modal fade" id="modalEditPartModel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">编辑部件型号</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmUpdatePartModel">
                            <input type="hidden" name="entire_model_unique_code" id="hdnEntireModelUniqueCode_modalEditPartModel">
                            <input type="hidden" name="unique_code" id="hdnSubModelUniqueCode_modalEditPartModel">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">部件型号名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="" id="txtName_modalEditPartModal">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdatePartModel()"><i class="fa fa-check">&nbsp;</i>保存</button>
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
        let partModels = {!! $part_models_as_json !!};
        let partCategories = {!! $part_categories_as_json !!};

        let $select2 = $('.select2');
        let $selCategory = $('#selCategory');
        let $spanCategoryCount = $('#spanCategoryCount');
        let $txtName_modalCreateCategory = $('#txtName_modalCreateCategory');
        let $selCategory_modalCreateEntireModal = $('#selCategory_modalCreateEntireModal');
        let $selCategory_modalCreatePartModal = $('#selCategory_modalCreatePartModal');
        let $selEntireModel_modalCreatePartModal = $('#selEntireModel_modalCreatePartModal');
        let $txtName_modalEditCategory = $('#txtName_modalEditCategory');
        let $txtName_modalEditEntireModal = $('#txtName_modalEditEntireModal');
        let $numFixCycleValue_modalEditEntireModal = $('#numFixCycleValue_modalEditEntireModal');
        let $txtName_modalEditPartModal = $('#txtName_modalEditPartModal');
        let $numFixCycleFixValue_modalEditPartModal = $('#numFixCycleFixValue_modalEditPartModal');
        let $selPartCategory_modalCreatePartModal = $('#selPartCategory_modalCreatePartModal');

        /**
         * 填充种类
         */
        function fnFillCategory() {
            let html = `<option value="" disabled selected>无</option>`;
            $.each(categories, function (key, category) {
                html += `<option value="${category['unique_code']}" ${'{{ $current_category_unique_code }}' === category['unique_code'] ? 'selected' : ''}>${category['name']}</option>`;
            });
            $selCategory.html(html);
            $selCategory_modalCreateEntireModal.html(html);
            $selCategory_modalCreatePartModal.html(html);
            $spanCategoryCount.text(categories.length);
            fnFillEntireModel('{{ $current_category_unique_code }}');
            fnFillPartCategory('{{ $current_category_unique_code }}');
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
            $selEntireModel_modalCreatePartModal.html(html);
        }

        /**
         * 填充部件种类
         */
        function fnFillPartCategory(categoryUniqueCode) {
            let html = '<option value="" selected disabled>无</option>';
            if (partCategories.hasOwnProperty(categoryUniqueCode)){
                $.each(partCategories[categoryUniqueCode], function (key, partCategory) {
                    html += `<option value="${partCategory['id']}">${partCategory['name']}</option>`;
                });
            }
            $selPartCategory_modalCreatePartModal.html(html);
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
                url: `{{ url('kindS/category') }}`,
                type: 'POST',
                data: $('#frmStoreCategory').serialize(),
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/category') }} success:`, res);
                    location.href = `{{ url('kindS') }}?category_unique_code=${res.data.category.unique_code}`;
                },
                error: function (err) {
                    console.log(`{{ url('kindS/category') }} fail:`, err);
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
                url: `{{ url('kindS/category') }}/${$selCategory.val()}`,
                type: 'GET',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/category') }}/${$selCategory.val()} success:`, res);
                    $('#txtName_modalEditCategory').val(res.data.category.name);
                    $('#hdnCategoryUniqueCode_modalEditCategoryUniqueCode').val(res.data.category.unique_code);
                    $('#modalEditCategory').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('kindS/category') }}/${$selCategory.val()} fail:`, err);
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
                url: `{{ url('kindS/category') }}`,
                type: 'PUT',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/category') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('kindS/category') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 添加类型
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
                url: `{{ url('kindS/entireModel') }}`,
                type: 'POST',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/entireModel') }} success:`, res);
                    location.href = `{{ url('kindS') }}?category_unique_code=${res.data.entire_model.category_unique_code}&entire_model_unique_code=${res.data.entire_model.unique_code}`;
                },
                error: function (err) {
                    console.log(`{{ url('kindS/entireModel') }} fail:`, err);
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
                url: `{{ url('kindS/entireModel') }}/${uniqueCode}`,
                type: 'GET',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/entireModel') }}/${uniqueCode} success:`, res);
                    $('#txtName_modalEditEntireModal').val(res.data.entire_model.name);
                    $('#numFixCycleValue_modalEditEntireModal').val(res.data.entire_model.fix_cycle_value);
                    $('#hdnEntireModelUniqueCode_modalEditEntireModel').val(res.data.entire_model.unique_code);
                    $('#modalEditEntireModel').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('kindS/entireModel') }}/${uniqueCode} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑类型
         */
        $txtName_modalEditEntireModal.on('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                fnUpdateEntireModel();
            }
        });

        /**
         * 编辑类型
         */
        $numFixCycleValue_modalEditEntireModal.on('keydown', function (e) {
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
                url: `{{ url('kindS/entireModel') }}`,
                type: 'PUT',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/entireModel') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('kindS/entireModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 添加部件型号模态框
         */
        function modalCreatePartModel() {
            $('#modalCreatePartModel').modal('show');
        }

        /**
         * 添加部件型号
         */
        function fnCreateSubModel() {
            let data = $('#frmStoreSubModel').serializeArray();
            $.ajax({
                url: `{{ url('kindS/partModel') }}`,
                type: 'POST',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/partModel') }} success:`, res);
                    location.href = `{{ url('kindS') }}?category_unique_code=${res.data.part_model.category_unique_code}&entire_model_unique_code=${res.data.part_model.entire_model_unique_code}`;
                },
                error: function (err) {
                    console.log(`{{ url('kindS/partModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑部件型号模态框
         */
        function modalEditPartModel(uniqueCode) {
            $.ajax({
                url: `{{ url('kindS/partModel') }}/${uniqueCode}`,
                type: 'GET',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/partModel') }}/${uniqueCode} success:`, res);
                    $('#txtName_modalEditPartModal').val(res.data.part_model.name);
                    $('#numFixCycleFixValue_modalEditPartModal').val(res.data.part_model.fix_cycle_value);
                    $('#hdnEntireModelUniqueCode_modalEditPartModel').val(res.data.part_model.entire_model_unique_code);
                    $('#hdnSubModelUniqueCode_modalEditPartModel').val(res.data.part_model.unique_code);
                    $('#modalEditPartModel').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('kindS/partModel') }}/${uniqueCode} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑部件型号
         */
        $txtName_modalEditPartModal.on('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                fnUpdatePartModel();
            }
        });

        /**
         * 编辑部件型号
         */
        function fnUpdatePartModel() {
            let data = $('#frmUpdatePartModel').serializeArray();
            $.ajax({
                url: `{{ url('kindS/partModel') }}`,
                type: 'PUT',
                data: data,
                async: false,
                success: function (res) {
                    console.log(`{{ url('kindS/partModel') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('kindS/partModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

    </script>
@endsection

@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            部件种类型管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">设备种类型列表(总计：{{ $categories->count() }}个)</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm"></div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-addon">种类</div>
                            <select name="category_unique_code" id="selCategory" class="form-control select2" style="width: 100%;" onchange="fnSelectCategory()">
                                <option value="" selected disabled>请选择</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->unique_code }}" {{ $current_category_unique_code == $category->unique_code ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="input-group-btn">
                                <a href="javascript:" class="btn btn-info btn-flat" onclick="modalEditCategory()"><i class="fa fa-pencil">&nbsp;</i>编辑</a>
                                <a href="javascript:" class="btn btn-success btn-flat" onclick="modalCreateCategory()"><i class="fa fa-check">&nbsp;</i>添加种类</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-addon">所属型号</div>

                        </div>
                    </div>
                    <div class="col-md-12">
                        <hr>
                    </div>
                    <div class="col-md-6">
                        <h4 class="box-title">
                            类型列表(总计：{{ $entire_models->count() }}个)&emsp;&emsp;
                            <a href="javascript:" class="btn btn-flat btn-success btn-sm" onclick="modalCreateEntireModel()"><i class="fa fa-plus">&nbsp;</i>添加类型</a>
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
                                    <tr class="{{ $current_entire_model_unique_code == $entire_model->unique_code ? 'bg-orange' : '' }}">
                                        <td>
                                            @if($current_entire_model_unique_code == $entire_model->unique_code)
                                                <span style="color: {{ $current_entire_model_unique_code == $entire_model->unique_code ? '#FFFFFF;' : ''  }}">{{ $entire_model->name }}</span>
                                            @else
                                                <a href="javascript:" onclick="fnSelectEntireModel('{{ $entire_model->unique_code }}')">{{ $entire_model->name }}</a>
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
                        <div id="divSubModel" style="display: none;">
                            <h4 class="box-title">
                                型号列表(总计：{{ $sub_models->count() }}个)&emsp;&emsp;
                                <a href="javascript:" class="btn btn-flat btn-success btn-sm" onclick="modalCreateSubModel()"><i class="fa fa-plus">&nbsp;</i>添加型号</a>
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
                                <label class="col-sm-3 col-md-3 control-label">类型：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="radio" name="race" value="S" id="rdoCreateCategoryEquipment" checked><label for="rdoCreateCategoryEquipment">设备</label>&emsp;&emsp;
                                    <input type="radio" name="race" value="Q" id="rdoCreateCategoryDevice"><label for="rdoCreateCategoryDevice">器材</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">种类名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="name" value="">
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
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdateCategory()"><i class="fa fa-check">&nbsp;</i>保存</button>
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
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属种类：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="category_unique_code" class="form-control" style="width: 100%;">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->unique_code }}" {{ $current_category_unique_code == $category->unique_code ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
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
                            <input type="hidden" name="category_unique_code" id="hdnCategoryUniqueCode_modalEditEntireModel">
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
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-flat btn-sm" onclick="fnUpdateEntireModel()"><i class="fa fa-check">&nbsp;</i>保存</button>
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
                                    <select name="category_unique_code" class="form-control select2" style="width: 100%;">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->unique_code }}" {{ $current_category_unique_code == $category->unique_code ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">所属类型：</label>
                                <div class="col-sm-9 col-md-8">
                                    <select name="entire_model_unique_code" class="form-control select2" style="width: 100%;">
                                        @foreach($entire_models as $entire_model)
                                            <option value="{{ $entire_model->unique_code }}" {{ $current_entire_model_unique_code == $entire_model->unique_code ? 'selected' : '' }}>{{ $entire_model->name }}</option>
                                        @endforeach
                                    </select>
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
                            <input type="hidden" name="category_unique_code" id="hdnCategoryUniqueCode_modalEditSubModel">
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
        let $select2 = $('.select2');
        let $selCategory = $('#selCategory');

        $(function () {
            if ($selCategory.val().substr(0, 1) === 'S') {
                $('#divSubModel').hide();
            } else {
                $('#divSubModel').show();
            }

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
        });

        /**
         * 选择种类
         */
        function fnSelectCategory() {
            location.href = `{{ url('entire/kind') }}?category_unique_code=${$selCategory.val()}`;
        }

        /**
         * 选择类型
         * @param {string} entireModelUniqueCode
         */
        function fnSelectEntireModel(entireModelUniqueCode) {
            location.href = `{{ url('entire/kind') }}?category_unique_code=${$selCategory.val()}&entire_model_unique_code=${entireModelUniqueCode}`;
        }

        /**
         * 打开添加种类模态框
         */
        function modalCreateCategory() {
            $('#modalCreateCategory').modal('show');
        }

        /**
         * 添加种类
         */
        function fnStoreCategory() {
            $.ajax({
                url: `{{ url('entire/kind/category') }}`,
                type: 'POST',
                data: $('#frmStoreCategory').serialize(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/kind/category') }} success:`, res);
                    location.href = `{{ url('entire/kind') }}?category_unique_code=${res.data.category.unique_code}`;
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind/category') }} fail:`, err);
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
                url: `{{ url('entire/kind') }}/${$selCategory.val()}/category`,
                type: 'GET',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('entire/kind') }}/${$selCategory.val()}/category success:`, res);
                    $('#txtName_modalEditCategory').val(res.data.category.name);
                    $('#hdnCategoryUniqueCode_modalEditCategoryUniqueCode').val(res.data.category.unique_code);
                    $('#modalEditCategory').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind') }}/${$selCategory.val()}/category fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑种类
         */
        function fnUpdateCategory() {
            let data = $('#frmUpdateCategory').serializeArray();

            $.ajax({
                url: `{{ url('entire/kind/category') }}`,
                type: 'PUT',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/kind/category') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind/category') }} fail:`, err);
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
                url: `{{ url('entire/kind/entireModel') }}`,
                type: 'POST',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/kind/entireModel') }} success:`, res);
                    location.href = `{{ url('entire/kind') }}?category_unique_code=${res.data.entire_model.category_unique_code}&entire_model_unique_code=${res.data.entire_model.unique_code}`;
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind/entireModel') }} fail:`, err);
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
                url: `{{ url('entire/kind') }}/${uniqueCode}/entireModel`,
                type: 'GET',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ url('entire/kind') }}/${uniqueCode}/entireModel success:`, res);
                    $('#txtName_modalEditEntireModel').val(res.data.entire_model.name);
                    $('#numFixCycleValue_modalEditEntireModel').val(res.data.entire_model.fix_cycle_value);
                    $('#hdnCategoryUniqueCode_modalEditEntireModel').val(res.data.entire_model.category_unique_code);
                    $('#hdnEntireModelUniqueCode_modalEditEntireModel').val(res.data.entire_model.unique_code);
                    $('#modalEditEntireModel').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind') }}/${uniqueCode}/entireModel fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑类型
         */
        function fnUpdateEntireModel() {
            let data = $('#frmUpdateEntireModel').serializeArray();

            $.ajax({
                url: `{{ url('entire/kind/entireModel') }}`,
                type: 'PUT',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/kind/entireModel') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind/entireModel') }} fail:`, err);
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
                url: `{{ url('entire/kind/subModel') }}`,
                type: 'POST',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/kind/subModel') }} success:`, res);
                    location.href = `{{ url('entire/kind') }}?category_unique_code=${res.data.sub_model.category_unique_code}&entire_model_unique_code=${res.data.sub_model.parent_unique_code}`;
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind/subModel') }} fail:`, err);
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
                url: `{{ url('entire/kind') }}/${uniqueCode}/subModel`,
                type: 'GET',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/kind/subModel') }}/${uniqueCode}/subModel success:`, res);
                    $('#txtName_modalEditSubModel').val(res.data.sub_model.name);
                    $('#numFixCycleFixValue_modalEditSubModel').val(res.data.sub_model.fix_cycle_value);
                    $('#hdnCategoryUniqueCode_modalEditSubModel').val(res.data.sub_model.category_unique_code);
                    $('#hdnEntireModelUniqueCode_modalEditSubModel').val(res.data.sub_model.parent_unique_code);
                    $('#hdnSubModelUniqueCode_modalEditSubModel').val(res.data.sub_model.unique_code);
                    $('#modalEditSubModel').modal('show');
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind/subModel') }}/${uniqueCode}/subModel fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 编辑型号
         */
        function fnUpdateSubModel() {
            let data = $('#frmUpdateSubModel').serializeArray();
            $.ajax({
                url: `{{ url('entire/kind/subModel') }}`,
                type: 'PUT',
                data: data,
                async: true,
                success: function (res) {
                    console.log(`{{ url('entire/kind/subModel') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('entire/kind/subModel') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

    </script>
@endsection

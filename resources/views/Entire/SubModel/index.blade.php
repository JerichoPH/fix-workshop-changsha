@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            整件型号管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form action="" method="get">
            <div class="box box-solid">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <div class="input-group-addon">种类</div>
                                <select
                                    id="selCategory"
                                    name="category_unique_code"
                                    class="form-control select2"
                                    style="width: 100%;"
                                    onchange="fnFillEntireModel(this.value)"></select>
                                <div class="input-group-addon">类型</div>
                                <select
                                    id="selEntireModel"
                                    name="entire_model_unique_code"
                                    class="form-control select2"
                                    style="width: 100%;"
                                    onchange=""></select>
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-flat btn-default">搜索</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>

        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">整件型号列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ url('entire/subModel/create') }}" class="btn btn-flat btn-success">新建</a>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>名称</th>
                            <th>代码</th>
                            <th>类型</th>
                            <th>周期修年限</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($subModels as $subModel)
                            <tr>
                                <td>{{ $subModel->name }}</td>
                                <td>{{ $subModel->unique_code }}</td>
                                <td>{{ $subModel->Parent->name ?? '' }}</td>
                                <td>{{ $subModel->fix_cycle_value }}{{ $subModel->fix_cycle_unit }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ url('entire/subModel',$subModel->id) }}/edit?page={{ request('page', 1) }}" class="btn btn-dafault btn-flat btn-sm"><i class="fa fa-pencil"></i> 编辑</a>
                                        {{--<a class="btn btn-danger btn-flat btn-sm" onclick="fnDelete('{{ $subModel->id }}')">删除</a>--}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($subModels->hasPages())
                <div class="box-footer">
                    {{ $subModels->appends([
                                            'page'=>request('page',1),
                                            'category_unique_code'=>request('category_unique_code'),
                                            'entire_model_unique_code'=>request('entire_model_unique_code'),
                                            ])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let categories = JSON.parse('{!! $categoriesAsJson !!}');
        let entireModels = JSON.parse('{!! $entireModelsAsJson !!}');
        let $selCategory = $('#selCategory');
        let $selEntireModel = $('#selEntireModel');
        let $txtUniqueCode = $('#txtUniqueCode');
        let $hidUniqueCode = $('#hidUniqueCode');

        /**
         * 填充种类列表
         */
        function fnFillCategory() {
            let html = '<option value="">全部</option>';
            $.each(categories, (idx, c) => {
                html += `<option value="${c['unique_code']}" ${'{{ request('category_unique_code') }}' === c['unique_code'] ? 'selected' : ''}>${c['name']}</option>`;
            });
            $selCategory.html(html);
        }

        /**
         * 填充类型列表
         */
        function fnFillEntireModel(categoryUniqueCode = null) {
            let html = '<option value="">全部</option>';
            if (categoryUniqueCode) {
                $.each(entireModels[categoryUniqueCode], (cu, em) => {
                    console.log(em);
                    html += `<option value="${em['unique_code']}" ${'{{ request('entire_model_unique_code') }}' === em['unique_code'] ? 'selected' : ''}>${em['name']}</option>`;
                });
            }

            $selEntireModel.html(html);
        }

        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            if ($select2.length > 0) $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,  // 分页器
                    lengthChange: true,
                    searching: true,  // 搜索框
                    ordering: true,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
                    order: [],  // 排序依据
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

            // 填充种类列表
            fnFillCategory();
            // 填充类型列表
            fnFillEntireModel($selCategory.val());

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
                    url: `{{ url('entire/subModel') }}/${id}`,
                    type: 'delete',
                    data: {id: id},
                    success: function (res) {
                        console.log(`{{ url('entire/subModel')}}/${id} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('entire/subModel')}}/${id} fail:`, err);
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
    </script>
@endsection

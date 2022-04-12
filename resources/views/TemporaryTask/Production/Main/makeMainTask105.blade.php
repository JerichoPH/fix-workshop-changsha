@extends('Layout.index')
@section('content')
@include('Layout.alert')
<!-- 面包屑 -->
<section class="content-header">
    <h1>
        分配工区子任务
        <small>新建</small>
    </h1>
{{--    <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li><a href="{{ url('temporaryTask/production/main') }}?page={{ request('page',1) }}"><i--}}
{{--                    class="fa fa-users">&nbsp;</i>分配工区子任务</a></li>--}}
{{--        <li class="active">新建</li>--}}
{{--    </ol>--}}
</section>
<section class="content">
    {{--任务描述--}}
    <div class="col-md-5">
        <div class="box box-solid">
            <div class="box-header ">
                <h3 class="box-title">分配子任务</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>任务编号：</dt>
                    <dd>{{ $main_task['serial_num'] }}</dd>
                    <dt>任务标题：</dt>
                    <dd>{{ $main_task['title'] }}</dd>
                    <dt>接收人：</dt>
                    <dd>{{ $main_task['paragraph_name'] }}:{{ $main_task['paragraph_principal_name'] }}</dd>
                    <dt>说明：</dt>
                    <dd>{!! $main_task['content'] !!}</dd>
                </dl>
            </div>
            <div class="box-footer">
                <a href="{{ url('temporaryTask/production/main',$main_task['id']) }}"
                    class="btn btn-sm btn-default btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                <a onclick="fnSaveMainTaskModels()" class="btn btn-warning pull-right btn-flat btn-sm">确认分配到工区&nbsp;<i
                    class="fa fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    {{--任务内容--}}
    <div class="col-md-7">
        <div class="box box-solid">
            <div class="box-header ">
                <h3 class="box-title">任务内容</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <form class="form-horizontal" id="frmSaveMainTaskModels">
                <div class="box-body">
                    <div class="input-group">
                        <div class="input-group-addon">种类*</div>
                        <select id="selCategory" class="form-control select2" style="width: 100%;"
                            onchange="fnSelectCategory(this.value)">
                            <option value="">未选择</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->unique_code }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="input-group-addon">类型*</div>
                        <select id="selEntireModel" class="form-control select2" style="width: 100%;"
                            onchange="fnSelectEntireModel(this.value)">
                            <option value="">未选择</option>
                        </select>
                        <div class="input-group-addon">子类或型号*</div>
                        <select id="selModel" class="form-control select2" style="width: 100%;">
                            <option value="">未选择</option>
                        </select>
                        <div class="input-group-btn">
                            <a class="btn btn-info btn-flat" onclick="fnAdd()"><i class="fa fa-plus-circle"></i></a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-dark">
                            <thead>
                                <tr>
                                    <th>名称</th>
                                    <th>数量</th>
                                </tr>
                            </thead>
                            <tbody id="tbody"></tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
@section('script')
<script>
    let $select2 = $('.select2');
        let $selCategory = $('#selCategory');
        let $selEntireModel = $('#selEntireModel');
        let $selModel = $('#selModel');
        let thisCategoryUniqueCode = '';
        let modelUniqueCodes = [];

        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 选择种类填充类型列表
         * @param {int} categoryUniqueCode
         */
        function fnSelectCategory(categoryUniqueCode) {
            let html = '<option value="">未选择</option>';
            if (categoryUniqueCode === '') {
                $selEntireModel.html(html);
                return null;
            }

            $.ajax({
                url: `{{ url('temporaryTask/production/main/entireModelsByCategoryUniqueCode') }}/${categoryUniqueCode}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/main/entireModelsByCategoryUniqueCode') }}/${categoryUniqueCode} success:`, res);
                    $.each(res, function (index, item) {
                        html += `<option value="${item['unique_code']}">${item['name']}</option>`;
                    });
                    $selEntireModel.html(html);
                    fnSelectEntireModel('');
                    thisCategoryUniqueCode = categoryUniqueCode;
                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/main/entireModelsByCategoryUniqueCode') }}/${categoryUniqueCode} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 选择类型填充子类或部件类型列表
         * @param {int} entireModelUniqueCode
         */
        function fnSelectEntireModel(entireModelUniqueCode) {
            let html = '<option value="">未选择</option>';
            if (entireModelUniqueCode === '') {
                $selModel.html(html);
                return null;
            }

            // 获取类型详情
            switch (thisCategoryUniqueCode.substr(0, 1)) {
                case 'S':
                    $.ajax({
                        url: `{{ url('temporaryTask/production/main/partModelsByEntireModelUniqueCode') }}/${entireModelUniqueCode}`,
                        type: 'get',
                        data: {order_by: 'id'},
                        async: false,
                        success: function (res) {
                            console.log(`{{ url('temporaryTask/production/main/partModelsByEntireModelUniqueCode') }}/${entireModelUniqueCode} success:`, res);
                            $.each(res, function (index, item) {
                                html += `<option value="${item['unique_code']}">${item['name']}</option>`;
                            });
                            $selModel.html(html);
                        },
                        error: function (err) {
                            console.log(`{{ url('temporaryTask/production/main/partModelsByEntireModelUniqueCode') }}/${entireModelUniqueCode} fail:`, err);
                            if (err.status === 401) location.href = "{{ url('login') }}";
                            alert(err['responseJSON']['message']);
                        }
                    });
                    break;
                case 'Q':
                    $.ajax({
                        url: `{{ url('temporaryTask/production/main/subModelsByEntireModelUniqueCode') }}/${entireModelUniqueCode}`,
                        type: 'get',
                        data: {order_by: 'id'},
                        async: false,
                        success: function (res) {
                            console.log(`{{ url('temporaryTask/production/main/subModelsByEntireModelUniqueCode') }}/${entireModelUniqueCode} success:`, res);
                            if (res) {
                                $.each(res, function (index, item) {
                                    html += `<option value="${item['unique_code']}">${item['name']}</option>`;
                                });
                                $selModel.html(html);
                            } else {

                            }
                        },
                        error: function (err) {
                            console.log(`{{ url('temporaryTask/production/main/subModelsByEntireModelUniqueCode') }}/${entireModelUniqueCode} fail:`, err);
                            if (err.status === 401) location.href = "{{ url('login') }}";
                            alert(err['responseJSON']['message']);
                        }
                    });
                    break;
                default:
                    $selModel.html(html);
                    break;
            }
        }

        /**
         * 添加到列表
         */
        function fnAdd() {
            // 获取当前选择的型号编号，并获取名称，跳过未选择
            let modelUniqueCode = $selModel.val();  // 当前选中的型号

            // 判断这个型号是否已经存在列表
            if (modelUniqueCodes.indexOf(modelUniqueCode) > -1) {
                alert('不能重复添加');
                return null;
            }

            let number = prompt('请输入数量', 1);  // 获取数据量，数量必须大于0
            if (number > 0) {
                let url = '';
                switch (modelUniqueCode.substr(0, 1)) {
                    case 'S':
                        // 按照子类处理
                        url = `{{ url('temporaryTask/production/main/partModelByUniqueCode') }}/${modelUniqueCode}`;
                        break;
                    case 'Q':
                        // 按照部件类型处理
                        url = `{{ url('temporaryTask/production/main/subModelByUniqueCode') }}/${modelUniqueCode}`;
                        break;
                    default:
                        break;
                }

                if (modelUniqueCode) {
                    let html = '';
                    $.ajax({
                        url: url,
                        type: 'get',
                        data: {},
                        async: true,
                        success: function (res) {
                            console.log(`${url} success:`, res);
                            let $tbody = $('#tbody');
                            let tbodyText = $tbody.html();
                            tbodyText += `
<tr id="trItem_${res['code3']}">
<td>${res['name3']}</td>
<td>
<div class="input-group">
<input type="text" value="${number}" name="${res['code1']}丨${res['code2']}丨${res['code3']}丨${res['name1']}丨${res['name2']}丨${res['name3']}" class="form-control">
<div class="input-group-btn">
<a onclick="fnCut('${res['code3']}')" class="btn btn-flat btn-sm btn-danger"><i class="fa fa-minus-circle"></i></a>
</div>
</div>
</td>
</tr>
`;
                            $tbody.html(tbodyText);
                            modelUniqueCodes.push(modelUniqueCode);
                        },
                        error: function (err) {
                            console.log(`${url} fail:`, err);
                            if (err.status === 401) location.href = "{{ url('login') }}";
                            alert(err['responseJSON']['message']);
                        }
                    });
                }
            }
        }

        /**
         * 从列表中去掉
         * @param {string} uniqueCode
         */
        function fnCut(uniqueCode) {
            if (uniqueCode) {
                $(`#trItem_${uniqueCode}`).remove();
                delete modelUniqueCodes[modelUniqueCodes.indexOf(uniqueCode)];  // 删除数组中记录已经存在的项目
            }
        }

        /**
         * 保存任务内容&分配工区子任务
         */
        function fnSaveMainTaskModels() {
            if (confirm('分配工区子任务不可撤销，是否分配？'))
            $.ajax({
                url: `{{ url('temporaryTask/production/main/saveMainTaskFile',$main_task['id']) }}`,
                type: 'post',
                data: $('#frmSaveMainTaskModels').serialize(),
                async: false,
                success: function (res) {
                    console.log(`{{ url('temporaryTask/production/main/saveMainTaskFile',$main_task['id']) }} success:`, res);

                    // 生成各工区子任务
                    $.ajax({
                        url:`{{ url('temporaryTask/production/sub/makeSubTask105',$main_task['id']) }}`,
                        type:'post',
                        data:{},
                        async:false,
                        success:function(res){
                            console.log(`{{ url('temporaryTask/production/sub/makeSubTask105',$main_task['id']) }} success:`,res);
                            location.href = `{{ url('temporaryTask/production/main', $main_task['id']) }}`;
                        },
                        error:function(err){
                            console.log(`{{ url('temporaryTask/production/sub/makeSubTask105',$main_task['id']) }} fail:`,err);
                            if(err.status === 401) location.href=`{{ url('login') }}`;
                            alert(err['responseJSON']['message']);
                        }
                    });

                },
                error: function (err) {
                    console.log(`{{ url('temporaryTask/production/main/saveMainTaskFile',$main_task['id']) }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
</script>
@endsection

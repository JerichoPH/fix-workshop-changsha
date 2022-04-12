@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            部件型号管理
            <small>编辑</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('part/model')}}"><i class="fa fa-users">&nbsp;</i>部件型号管理</a></li>--}}
{{--            <li class="active">编辑</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">保存部件类型</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <form class="form-horizontal" id="frmUpdate">
                <div class="form-group">
                    <label class="col-sm-3 control-label">名称：</label>
                    <div class="col-sm-10 col-md-8">
                        <input
                            placeholder="名称"
                            class="form-control"
                            type="text"
                            required
                            autofocus
                            onkeydown="if(event.keyCode==13){return false;}"
                            name="name"
                            value="{{$part_model->name}}"
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">唯一代码：</label>
                    <div class="col-sm-10 col-md-8">
                        <input
                            placeholder="唯一代码"
                            class="form-control disabled"
                            type="text"
                            required
                            onkeydown="if(event.keyCode==13){return false;}"
                            name="unique_code"
                            disabled
                            value="{{$part_model->unique_code}}"
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">设备种类：</label>
                    <div class="col-sm-10 col-md-8">
                        <select
                            id="selCategory"
                            name="category_unique_code"
                            class="form-control select2"
                            style="width: 100%;"
                            onchange="fnSelectCategory(this.value)"
                        >
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">部件种类：</label>
                    <div class="col-sm-10 col-md-8">
                        <select
                            id="selEntireModel"
                            name="entire_model_unique_code"
                            class="form-control select2"
                            style="width: 100%;"
                        >
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">设备类型：</label>
                    <div class="col-sm-10 col-md-8">
                        <select
                            id="selPartCategory"
                            name="part_category_id"
                            class="form-control select2"
                            style="width: 100%;"
                        >
                        </select>
                    </div>
                </div>
                <div class="box-footer">
{{--                    <a href="{{url('part/model')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    <a href="javascript:" onclick="fnUpdate()" class="btn btn-warning btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>保存</a>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        let $selCategory = $("#selCategory");
        let $selEntireModel = $("#selEntireModel");
        let $selPartCategory = $("#selPartCategory");
        let categories = JSON.parse('{!! $categories !!}');
        let entireModels = JSON.parse('{!! $entire_models !!}');
        let partCategories = JSON.parse('{!! $part_categories !!}');

        $(function () {
            $select2.select2();

            // 填充种类下拉列表
            fnFillSelect($selCategory, categories, "", "{{$part_model->category_unique_code}}", true);
            // 填充类型
            fnFillSelect($selEntireModel, entireModels[categories["{{$part_model->category_unique_code}}"]], "", "{{$part_model->entire_model_unique_code}}", true);
            // 填充部件种类下拉列表
            fnFillSelect($selPartCategory, partCategories[categories["{{$part_model->category_unique_code}}"]], "0", "{{$part_model->part_category_id}}", true);
        });

        /**
         * 选择种类
         * @param {string} categoryUniqueCode
         */
        let fnSelectCategory = categoryUniqueCode => {
            if (categoryUniqueCode) {
                // 填充设备类型下拉列表
                fnFillSelect($selEntireModel, entireModels[categories[categoryUniqueCode]], "", $selEntireModel.val(), true);
                // 填充部件种类下拉列表
                fnFillSelect($selPartCategory, partCategories[categories[categoryUniqueCode]], "0", $selPartCategory.val(), true);
            } else {
                fnFillSelect($selEntireModel, [], "", "", false);
                fnFillSelect($selPartCategory, [], "0", "", false);
            }
        };

        let fnFillSelect = ($obj, items, defaultValue = "", current, useKey = true) => {
            let html = `<option value="${defaultValue}">未选择</option>`;
            $.each(items, (index, item) => {
                let value = useKey ? index : item;
                html += `<option value="${index}" ${value === current ? "selected" : ""}>${item}</option>`;
            });
            $obj.html(html);
        };

        /**
         * 保存
         */
        let fnUpdate = function () {
            $.ajax({
                url: "{{ url('part/model',$part_model->unique_code) }}",
                type: "put",
                data: $("#frmUpdate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    // alert(response);
                    location.href = "{{ url('part/model') }}?page{{ request('page',1) }}";
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.responseText === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            部件种类管理
            <small>编辑</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('/part/category')}}"><i class="fa fa-users">&nbsp;</i>部件种类管理</a></li>--}}
{{--            <li class="active">编辑</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">保存部件种类</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <form class="form-horizontal" id="frmModify">
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">名称：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="name" type="text" class="form-control" placeholder="名称" required value="{{$part_category->name}}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">所属设备种类：</label>
                        <div class="col-sm-10 col-md-8">
                            <select name="category_unique_code" class="form-control select2" style="width: 100%;">
                                @foreach($categories_S as $category_S)
                                    <option value="{{ $category_S->unique_code }}">{{ $category_S->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">所属器材种类：</label>
                        <div class="col-sm-10 col-md-8">
                            <select class="form-control select2" id="selCategoryQ" style="width: 100%;" onchange="fnFillEntireModel(this.value)"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">所属器材类型：</label>
                        <div class="col-sm-10 col-md-8">
                            <select name="entire_model_unique_code" id="selEntireModel" class="form-control select2" style="width: 100%;"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">关键部件：</label>
                        <div class="col-sm-10 col-md-8">
                            <label style="font-weight: normal;"><input type="radio" name="is_main" id="rdoIsMain" value="1" {{$part_category->is_main ? 'checked' : ''}}>是</label>
                            &nbsp;&nbsp;
                            <label style="font-weight: normal;"><input type="radio" name="is_main" id="rdoIsMain" value="0" {{!$part_category->is_main ? 'checked' : ''}}>否</label>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
{{--                    <a href="{{url('/part/category')}}?page={{request('page',1)}}" class="btn btn-default pull-left btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default pull-left btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    <a href="javascript:" onclick="fnModify()" class="btn btn-warning pull-right btn-flat"><i class="fa fa-check">&nbsp;</i>保存</a>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>

        let $selCategoryQ = $('#selCategoryQ');
        let $selEntireModel = $('#selEntireModel');
        let categoriesQ = {!! $categories_Q_as_json !!};
        let entireModels = {!! $entire_models_as_json !!};

        /**
         * 填充器材种类
         */
        function fnFillCategory() {
            let html = '<option value="" disabled selected>无</option>';
            $.each(categoriesQ, function (key, categoryQ) {
                html += `<option value="${categoryQ['unique_code']}" ${'{{ substr($part_category->entire_model_unique_code,0,3) }}' === categoryQ['unique_code'] ? 'selected' : '' }>${categoryQ['name']}</option>`;
            });
            $selCategoryQ.html(html);
            fnFillEntireModel($selCategoryQ.val());
        }

        /**
         * 填充器材类型
         */
        function fnFillEntireModel(categoryUniqueCode) {
            let html = '<option value="" disabled selected>无</option>';
            if (entireModels.hasOwnProperty(categoryUniqueCode)) {
                $.each(entireModels[categoryUniqueCode], function (key, entireModel) {
                    html += `<option value="${entireModel['unique_code']}" ${'{{ $part_category->entire_model_unique_code }}' === entireModel['unique_code'] ? 'selected' : '' }>${entireModel['name']}</option>`;
                });
            }
            $selEntireModel.html(html);
        }

        $(function () {
            $('.select2').select2();

            fnFillCategory();  // 填充种类
        });

        /**
         * 保存
         */
        fnModify = function () {
            $.ajax({
                url: "{{url('/part/category',$part_category->id)}}",
                type: "put",
                data: $("#frmModify").serialize(),
                success: function (response) {
                    // console.log('success:', response);
                    location.href = "{{url('/part/category')}}?page={{request('page',1)}}";
                },
                error: function (error) {
                    alert(error.responseText);
                }
            });
        };
    </script>
@endsection

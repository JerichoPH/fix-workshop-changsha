@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            整件型号管理
            <small>新建</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('entire/model')}}"><i class="fa fa-users">&nbsp;</i>类型管理</a></li>--}}
{{--            <li class="active">新建</li>--}}
{{--        </ol>--}}
    </section>
    <form class="form-horizontal" id="frmCreate">
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">添加整件型号</h3>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right"></div>
                        </div>
                        <br>
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-3 control-label">名称：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        id="txtName"
                                        placeholder="名称"
                                        class="form-control"
                                        type="text"
                                        required
                                        autofocus
                                        onkeydown="if(event.keyCode===13){$('#txtUniqueCode').val(this.value);$('#txtUniqueCode').focus();}"
                                        name="name"
                                        value="">
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
                                        onchange="fnFillEntireModel(this.value)"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">设备类型：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select
                                        id="selEntireModel"
                                        name="entire_model_unique_code"
                                        class="form-control select2"
                                        style="width: 100%;"
                                        onchange="fnNextUniqueCode(this.value)"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="unique_code" id="hidUniqueCode" value="">
                                <label class="col-sm-3 control-label">型号统一代码：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        id="txtUniqueCode"
                                        placeholder="设备类型统一代码"
                                        class="form-control disabled"
                                        type="text"
                                        required
                                        onkeydown="if(event.keyCode===13){fnCreate();}"
                                        name="unique_code"
                                        disabled
                                        value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">周期修年：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        placeholder="周期修年"
                                        class="form-control"
                                        type="number"
                                        min="0"
                                        max="99"
                                        step="1"
                                        required
                                        onkeydown="if(event.keyCode===13){return false;}"
                                        name="fix_cycle_value" value="0">
                                </div>
                            </div>
                            <div class="box-footer">
                                @if(request('categoryUniqueCode'))
{{--                                    <a href="{{ url('category',request('categoryUniqueCode')) }}?page={{ request('page',1) }}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                @else
{{--                                    <a href="{{ url('entire/subModel') }}?page={{ request('page',1) }}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                @endif

                                <a href="javascript:" onclick="fnCreate()" class="btn btn-success btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </form>
@endsection
@section('script')
    <script>
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
            let html = '';
            $.each(categories, (idx, c) => {
                html += `<option value="${c['unique_code']}">${c['name']}</option>`;
            });
            $selCategory.html(html);
        }

        /**
         * 填充类型列表
         */
        function fnFillEntireModel(categoryUniqueCode = null) {
            let html = '';
            $.each(categoryUniqueCode ? entireModels[categoryUniqueCode] : entireModels, (cu, em) => {
                html += `<option value="${em['unique_code']}">${em['name']}</option>`;
            });
            $selEntireModel.html(html);
            let currentEntireModels = categoryUniqueCode ? entireModels[categoryUniqueCode] : entireModels;
            if (currentEntireModels.length > 0){
                fnNextUniqueCode(currentEntireModels[0]['unique_code']);
            }else{
                $txtUniqueCode.val('');
                $hidUniqueCode.val('');
            }
        }

        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
            //Date picker
            $('#datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd'
            });

            // 填充种类列表
            fnFillCategory();
            // 填充类型列表
            fnFillEntireModel($selCategory.val());
            // 获取下一个子类代码
            fnNextUniqueCode($selEntireModel.val());
        });

        /**
         * 新建
         */
        function fnCreate() {
            $.ajax({
                url: "{{url('entire/subModel')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    // alert(response);
                    // $('#txtName').focus();
                    location.reload();
                },
                error: function (error) {
                    console.log('fail:', error);
                    if (error.responseText === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        }

        /**
         * 根据设备类型获取零件类型
         * @param {string} entireModelUniqueCode
         */
        function fnNextUniqueCode(entireModelUniqueCode) {
            // 获取类型信息
            $.ajax({
                url: `{{ url('entire/subModel/nextUniqueCode') }}/${entireModelUniqueCode}`,
                type: "get",
                date: {},
                async: true,
                success: res => {
                    let {next} = res;
                    // console.log(res);
                    $txtUniqueCode.val(next);
                    $hidUniqueCode.val(next);
                },
                error: err => {
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseText);
                }
            });
        }
    </script>
@endsection

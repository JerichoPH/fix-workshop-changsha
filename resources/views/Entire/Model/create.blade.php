@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            类型管理
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
                <div class="col-md-6">
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h3 class="box-title">添加整件类型</h3>
                            {{--右侧最小化按钮--}}
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
                                        onchange="fnGetPartModelByCategoryUniqueCode(this.value)"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="unique_code" id="hidUniqueCode" value="">
                                <label class="col-sm-3 control-label">类型统一代码：</label>
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
                                <label class="col-sm-3 control-label">维修周期单位：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="fix_cycle_unit" class="form-control select2" style="width: 100%;">
                                        @foreach(\App\Model\EntireModel::$FIX_CYCLE_UNIT as $fixCycleUnitKey => $fixCycleUnitValue)
                                            <option value="{{$fixCycleUnitKey}}">{{$fixCycleUnitValue}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">周期修年：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        placeholder="周期修年"
                                        class="form-control"
                                        type="number"
                                        min="1"
                                        max="99"
                                        step="1"
                                        required
                                        onkeydown="if(event.keyCode===13){return false;}"
                                        name="fix_cycle_value" value="1">
                                </div>
                            </div>
                            <div class="box-footer">
                                @if(request('categoryUniqueCode'))
{{--                                    <a href="{{url('category',request('categoryUniqueCode'))}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                @else
{{--                                    <a href="{{url('entire/model')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                @endif

                                <a href="javascript:" onclick="fnCreate()" class="btn btn-success btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title">部件类型管理</h3>
                                    {{--右侧最小化按钮--}}
                                    <div class="box-tools pull-right">
                                        {{--                                <a href="javascript:" onclick="fnCreatePartModel()" class="btn-box-tool"><i class="fa fa-plus"></i></a>--}}
                                        <a href="{{url('part/model/create')}}" class="btn btn-default btn-lg">新建</a>
                                    </div>
                                </div>
                                <br>
                                <div class="box-body">
                                    <div class="row" id="divPartModel"></div>
                                </div>
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
        let $selCategory = $('#selCategory');
        let $txtUniqueCode = $('#txtUniqueCode');
        let $hidUniqueCode = $('#hidUniqueCode');

        /**
         * 填充种类列表
         */
        function fnFillCategory() {
            let html = '';
            $.each(categories, (cu, cn) => {
                html += `<option value="${cu}">${cn}</option>`;
            });
            $selCategory.html(html);
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
            // 刷新部件列表
            fnGetPartModelByCategoryUniqueCode($('#selCategory').val());
        });

        /**
         * 打开新建部件类型窗口
         */
        fnCreatePartModel = () => {
            $.ajax({
                url: "{{url('part/modal/create')}}",
                type: "get",
                data: {},
                async: true,
                success: function (response) {
                    console.log('success:', response);
                    // alert(response);
                    // location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        /**
         * 新建
         */
        fnCreate = function () {
            $.ajax({
                url: "{{url('entire/model')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    // console.log('success:', response);
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
        };

        /**
         * 根据设备类型获取零件类型
         * @param {string} categoryUniqueCode 设备类型统一代码
         */
        fnGetPartModelByCategoryUniqueCode = categoryUniqueCode => {
            // 获取类型信息
            $.ajax({
                url: `{{ url('entire/model/nextEntireModelUniqueCode') }}/${categoryUniqueCode}`,
                type: "get",
                date: {},
                async: true,
                success: res => {
                    let {next} = res;
                    $txtUniqueCode.val(next);
                    $hidUniqueCode.val(next);
                },
                error: err => {
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseText);
                }
            });

            // 获取部件信息
            $.ajax({
                url: "{{url('part/model')}}",
                type: "get",
                data: {
                    type: 'category_unique_code',
                    category_unique_code: categoryUniqueCode,
                },
                async: true,
                success: function (response) {
                    html = '';
                    for (let key in response) {
                        html += '<div class="col-md-6"><label class="control-label" style="text-align: left; font-weight: normal; font-size: 18px;"><input name="part_model_unique_code[]" type="checkbox" class="minimal" value="' + response[key].unique_code + '" id="' + response[key].unique_code + '">' + response[key].name + '</label></div>';
                        // html += `<div class="col-md-6"><label class="control-label" style="text-align: left; font-weight: normal; font-size: 18px;"><input name="part_model_unique_code[]" type="checkbox" class="minimal" value="${response[key].unique_code}" id="${response[key].unique_code}">${response[key].name}</label></div>`;
                    }
                    $('#divPartModel').html(html);
                    $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                        checkboxClass: 'icheckbox_minimal-blue',
                        radioClass: 'iradio_minimal-blue'
                    });
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };
    </script>
@endsection

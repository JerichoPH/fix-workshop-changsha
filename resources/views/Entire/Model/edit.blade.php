@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            类型管理
            <small>编辑</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('entire/model')}}"><i class="fa fa-users">&nbsp;</i>类型管理</a></li>--}}
{{--            <li class="active">编辑</li>--}}
{{--        </ol>--}}
    </section>
    <form class="form-horizontal" id="frmUpdate">
        <section class="content">
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <!--类型编辑-->
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">保存整件类型</h3>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right"></div>
                        </div>
                        <br>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">名称：</label>
                            <div class="col-sm-10 col-md-8">
                                <input placeholder="名称" class="form-control" type="text" required autofocus onkeydown="if(event.keyCode===13){return false;}"
                                       name="name" value={{$entireModel->name}}>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">设备类型统一代码：</label>
                            <div class="col-sm-10 col-md-8">
                                <input placeholder="设备类型统一代码" class="form-control disabled" disabled type="text" required onkeydown="if(event.keyCode===13){return false;}"
                                       name="unique_code" value={{$entireModel->unique_code}}>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">设备种类：</label>
                            <div class="col-sm-10 col-md-8">
                                <select id="selCategory" name="category_unique_code" class="form-control select2" style="width: 100%;" onchange="fnGetPartModelByCategoryUniqueCode(this.value)">
                                    @foreach($categories as $categoryUniqueCode => $categoryName)
                                        <option value="{{$categoryUniqueCode}}" {{$categoryUniqueCode == $entireModel->category_unique_code ? 'selected' : ''}}>{{$categoryUniqueCode .' ： '.$categoryName}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{--<div class="form-group">--}}
                        {{--<label class="col-sm-3 control-label">维修周期单位：</label>--}}
                        {{--<div class="col-sm-10 col-md-8">--}}
                        {{--<select name="fix_cycle_unit" class="form-control select2" style="width: 100%;">--}}
                        {{--@foreach(\App\Model\EntireModel::$FIX_CYCLE_UNIT as $fixCycleUnitKey => $fixCycleUnitValue)--}}
                        {{--<option value="{{$fixCycleUnitKey}}" {{$fixCycleUnitKey == $entireModel->prototype('fix_cycle_unit') ? 'selected' : ''}}>{{$fixCycleUnitValue}}</option>--}}
                        {{--@endforeach--}}
                        {{--</select>--}}
                        {{--</div>--}}
                        {{--</div>--}}
                        <div class="form-group">
                            <label class="col-sm-3 control-label">周期修年限：</label>
                            <div class="col-sm-10 col-md-8">
                                <input
                                    placeholder="周期修年限"
                                    class="form-control"
                                    type="number"
                                    min="1"
                                    max="99"
                                    step="1"
                                    required
                                    onkeydown="if(event.keyCode===13){return false;}"
                                    name="fix_cycle_value"
                                    value="{{$entireModel->fix_cycle_value}}"
                                >
                            </div>
                        </div>
                        <div class="box-footer">
{{--                            <a href="{{url('entire/model')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a href="javascript:" onclick="fnUpdate()" class="btn btn-warning btn-flat pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <!--部件编辑-->
                    <div class="box box-solid" style="display: none;">
                        <div class="box-header">
                            <h3 class="box-title">部件类型管理</h3>
                            <!--右侧最小化按钮-->
                            <div class="pull-right">
                                <div class="btn-group btn-group-sm">
                                    <a href="javascript:" class="btn btn-sm btn-flat btn-default" onclick="fnModalEntireModelAndPartModelBindingNumber()">绑定数量管理</a>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="box-body">
                            <div class="row" id="divPartModel"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="box box-solid" style="display: none;">
                        <div class="box-header">
                            <h3 class="box-title">整件型号管理</h3>
                            <!--右侧最小化按钮-->
                            <div class="pull-right">
                                <div class="btn-group btn-group-sm">
                                    <a href="javascript:" class="btn btn-flat btn-default" onclick="fnCreateEntireModelIdCode('{{$entireModel->category_unique_code}}','{{$entireModel->unique_code}}')">新建</a>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="box-body">
                            <div id="divEntireModelIdCode"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" style="display: none;">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">绑定额外测试标签</h3>
                            <!--右侧最小化按钮-->
                            <div class="pull-right">
                                <div class="btn-group btn-group-sm">
                                    <a href="javascript:" onclick="fnBindingExtraTagToEntireModel($('#txtExtraTag').val())" class="btn btn-default btn-flat btn-lg">绑定</a>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="box-body">
                            <div class="form-horizontal">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">额外测试标签：</label>
                                    <div class="col-md-9">
                                        <input id="txtExtraTag" type="text" name="extra_tag" class="form-control" placeholder="额外测试标签" value="">
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <h4>已绑定</h4>
                            <div class="table-responsive">
                                <table class="table table-condensed table-hover">
                                    <thead>
                                    <tr>
                                        <th>名称</th>
                                        <th>操作</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($boundExtraTags as $boundExtraTag)
                                        <tr>
                                            <td>{{$boundExtraTag}}</td>
                                            <td><a href="javascript:" onclick="fnCancelBoundExtraTagFromEntireModel('{{$boundExtraTag}}')"><i class="fa fa-times" style="color: red;"></i></a></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" style="display: none;">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">绑定供应商</h3>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right">
                                <a href="javascript:" class="btn btn-default btn-flat btn-lg" onclick="fnBindingFactoryToEntireModel($('#selFactory').val())">绑定</a>
                            </div>
                        </div>
                        <br>
                        <div class="box-body">
                            <div class="form-horizontal">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">供应商：</label>
                                    <div class="col-sm-9 col-md-9">
                                        <select id="selFactory" name="factory_name" class="form-control select2" style="width:100%;">
                                            @foreach(\App\Model\Factory::all() as $factory)
                                                <option value="{{$factory->name}}">{{$factory->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-condensed table-hover">
                                    <thead>
                                    <tr>
                                        <th>名称</th>
                                        <th>操作</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($boundFactories as $boundFactory)
                                        <tr>
                                            <td>{{$boundFactory->name}}</td>
                                            <td><a href="javascript:" style="color: red;" onclick="fnCancelBoundFactoryFromEntireModel('{{$boundFactory->name}}')"><i class="fa fa-times"></i></a></td>
                                        </tr>
                                    @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </form>
    <section>
        <div id="divModalCreateModelIdCode"></div>
        <div id="divModalEntireModelAndPartModelBindingNumber"></div>
    </section>
@endsection
@section('script')
    <script>
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

            // 刷新部件列表
            fnGetPartModelByCategoryUniqueCode($('#selCategory').val());

            // 刷新整件型号列表
            // fnGetEntireModelIdCodeByEntireModelUniqueCode();
        });

        /**
         * 保存
         */
        fnUpdate = function () {
            $.ajax({
                url: `{{url('entire/model',$entireModel->id)}}`,
                type: "put",
                data: $('#frmUpdate').serialize(),
                success: function (res) {
                    location.href = `{{url("entire/model")}}?page={{request('page',1)}}`;
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err.responseText === 401) location.href = "{{ url('login') }}";
                    alert(err.responseText);
                }
            });
        };

        let currentPartModels = '{!! $partModels !!}';

        /**
         * 根据设备类型获取零件类型
         * @param {string} categoryUniqueCode 设备类型统一代码
         */
        fnGetPartModelByCategoryUniqueCode = function (categoryUniqueCode) {
            $.ajax({
                url: `{{url('part/model')}}`,
                type: "get",
                data: {
                    type: 'category_unique_code',
                    category_unique_code: categoryUniqueCode,
                },
                async: false,
                success: function (response) {
                    html = '';
                    for (let key in response) {
                        html += `
<div class="col-md-6">
    <label class="control-label" style="text-align: left; font-weight: normal;">
        <input
            name="part_model_unique_code[]"
            type="checkbox"
            class="minimal"
            value="${response[key].unique_code}"
            id="${response[key].unique_code}"
            ${currentPartModels.indexOf(response[key].unique_code) > -1 ? 'checked' : ''}>
                ${response[key].name}
    </label>
</div>`;
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

        /**
         * 根据类型获取型号列表
         */
        fnGetEntireModelIdCodeByEntireModelUniqueCode = () => {
            return null;
            $.ajax({
                url: `{{url('entire/modelIdCode')}}`,
                type: "get",
                data: {
                    entire_model_unique_code: "{{$entireModel->unique_code}}",
                    category_unique_code: "{{$entireModel->category_unique_code}}"
                },
                async: false,
                success: function (response) {
                    html = '';
                    for (let key in response) {
                        html += `
<label class="control-label" style="text-align: left; font-weight: normal;">${response[key].code}</label>
<a href="javascript:" onclick="fnDeleteEntireModelIdCode('${response[key].code}')"><i class="fa fa-times" style="color: red;"></i></a>
`;
                    }
                    $("#divEntireModelIdCode").html(html);

                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        /**
         * 新建整件型号
         * @param categoryUniqueCode
         * @param entireModelUniqueCode
         */
        fnCreateEntireModelIdCode = (categoryUniqueCode, entireModelUniqueCode) => {
            $.ajax({
                url: `{{url('entire/modelIdCode/create')}}`,
                type: "get",
                data: {
                    categoryUniqueCode: categoryUniqueCode,
                    entireModelUniqueCode: entireModelUniqueCode
                },
                async: false,
                success: function (response) {
                    $("#divModalCreateModelIdCode").html(response);
                    $("#modalStoreEntireModelIdCode").modal("show");
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        /**
         * 绑定额外测试标签到整件类型
         */
        fnBindingExtraTagToEntireModel = (extraTag) => {
            if (extraTag !== '') {
                $.ajax({
                    url: `{{url('pivotEntireModelAndExtraTag')}}`,
                    type: "post",
                    data: {extra_tag: extraTag, entire_model_unique_code: "{{$entireModel->unique_code}}"},
                    async: false,
                    success: function (response) {
                        location.reload();
                    },
                    error: function (error) {
                        // console.log('fail:', error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    },
                });
            }
        };

        /**
         * 解绑额外测试项和类型绑定关系
         * @param extraTag
         */
        fnCancelBoundExtraTagFromEntireModel = (extraTag) => {
            $.ajax({
                url: `{{url('pivotEntireModelAndExtraTag',$entireModel->unique_code)}}?extra_tag=${extraTag}`,
                type: "delete",
                data: {},
                async: false,
                success: function (response) {
                    console.log('success:', response);
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        /**
         * 绑定工厂到类型
         */
        fnBindingFactoryToEntireModel = (factoryName) => {
            $.ajax({
                url: `{{url('pivotEntireModelAndFactory')}}`,
                type: "post",
                data: {factory_name: factoryName, entire_model_unique_code: "{{$entireModel->unique_code}}"},
                async: false,
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        /**
         * 解除工厂与类型绑定关系
         * @param factoryName
         */
        fnCancelBoundFactoryFromEntireModel = (factoryName) => {
            $.ajax({
                url: `{{url('pivotEntireModelAndFactory',$entireModel->unique_code)}}?factory_name=${factoryName}`,
                type: "delete",
                data: {},
                async: true,
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        /**
         * 打开整件与部件绑定数量管理窗口
         */
        fnModalEntireModelAndPartModelBindingNumber = () => {
            $.ajax({
                url: `{{url('pivotEntireModelAndPartModel',$entireModel->unique_code)}}/edit`,
                type: "get",
                data: {},
                async: true,
                success: function (response) {
                    $("#divModalEntireModelAndPartModelBindingNumber").html(response);
                    $("#modalEntireModelAndPartModelBindingNumber").modal("show");
                },
                error: function (error) {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };
    </script>
@endsection

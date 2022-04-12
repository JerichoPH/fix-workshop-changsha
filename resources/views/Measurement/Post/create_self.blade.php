@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
@endsection
@section('content')
    <section class="content">
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">新建测试模板</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <div class="box-body">
                <form id="frmCreate" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">种类：</label>
                        <div class="col-sm-10 col-md-9">
                            <select id="selCategory" class="form-control select2" style="width: 100%;" onchange="fnGetEntireModelByCategoryUniqueCode()">
                                @foreach(\App\Model\Category::all() as $category)
                                    <option value="{{$category->unique_code}}">{{$category->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">类型：</label>
                        <div class="col-sm-10 col-md-9">
                            <select
                                id="selEntireModel"
                                name="entire_model_unique_code"
                                class="form-control select2"
                                style="width: 100%;"
                                onchange="fnGetWarehouseProductPartByWarehouseProductId(this.value)">
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">部件型号：</label>
                        <div class="col-sm-10 col-md-9">
                            <select
                                id="selPartModel"
                                name="part_model_unique_code"
                                class="form-control select2"
                                style="width: 100%;">
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">额外测试项：</label>
                        <div class="col-sm-10 col-md-8">
                            <div class="input-group">
                                <select id="selExtraTag" name="extra_tag" class="form-control select2" style="width:100%;"></select>
                                <div class="input-group-addon">
                                    <label style="font-weight: normal;"><input type="checkbox" name="is_extra_tag" value="1">使用额外测试项</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">测试项：</label>
                        <div class="col-sm-10 col-md-9">
                            <input class="form-control" type="text" required onkeydown="if(event.keyCode===13){return false;}"
                                   name="key" placeholder="测试项" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">标准值：</label>
                        <div class="col-sm-10 col-md-9">
                            <div class="input-group">
                                <input class="form-control" type="number" onkeydown="if(event.keyCode===13){return false;}"
                                       name="allow_min" placeholder="最小值" value="">
                                <div class="input-group-addon">～</div>
                                <input class="form-control" type="number" onkeydown="if(event.keyCode===13){return false;}"
                                       name="allow_max" placeholder="最大值" value="">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">标准值描述：</label>
                        <div class="col-sm-10 col-md-9">
                            <textarea name="allow_explain" class="form-control" cols="30" rows="5" placeholder="无法用数值描述的内容"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">单位：</label>
                        <div class="col-sm-10 col-md-9">
                            <input class="form-control" type="text" onkeydown="if(event.keyCode===13){return false;}"
                                   name="unit" placeholder="单位" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">行为：</label>
                        <div class="col-sm-10 col-md-9">
                            <input class="form-control" type="text" onkeydown="if(event.keyCode===13){return false;}"
                                   name="operation" placeholder="行为" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">特性：</label>
                        <div class="col-sm-10 col-md-9">
                            <input placeholder="例如：电气特性" class="form-control" type="text" required onkeydown="if(event.keyCode===13){return false;}"
                                   name="character" value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">说明：</label>
                        <div class="col-sm-10 col-md-9">
                            <textarea name="explain" cols="30" rows="5" class="form-control"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="box-footer">
{{--                <a href="{{url('measurements')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                <a href="javascript:" onclick="fnCreate()" class="btn btn-success btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
            </div>
        </div>
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

            // 根据种类获取类型
            fnGetEntireModelByCategoryUniqueCode();

            // 根据整件编号获取零件编号
            fnGetWarehouseProductPartByWarehouseProductId($("#selEntireModel").val());
        });

        /**
         * 新建
         */
        fnCreate = function () {
            $.ajax({
                url: "{{url('measurements')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    location.reload();
                },
                error: function (error) {
                    // console.log('fail:', error);
                    alert(error.responseText);
                }
            });
        };

        /**
         * 根据整件种类获取类型
         */
        fnGetEntireModelByCategoryUniqueCode = () => {
            $.ajax({
                url: `{{url('entire/model')}}`,
                type: "get",
                data: {
                    type: 'category_unique_code',
                    category_unique_code: $("#selCategory").val()
                },
                async: true,
                success: function (response) {
                    html = '';
                    for (let key in response) {
                        html += `<option value="${response[key].unique_code}">${response[key].name}</option>`;
                    }
                    $("#selEntireModel").html(html);
                    fnGetWarehouseProductPartByWarehouseProductId($("#selEntireModel").val());
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        };

        /**
         * 根据整件编号获取零件列表
         * @param {int} entireModelUniqueCode 整件编号
         */
        fnGetWarehouseProductPartByWarehouseProductId = function (entireModelUniqueCode) {
            // 根据整件类型获取零件列表
            $.ajax({
                url: `{{url('pivotEntireModelAndPartModel')}}`,
                type: "get",
                data: {
                    type: 'entire_model_unique_code',
                    entire_model_unique_code: entireModelUniqueCode,
                },
                async: true,
                success: function (response) {
                    console.log('ok',response);
                    let html = '<option value="">整件测试</option>';
                    for (let key in response) {
                        html += `<option value="${response[key].unique_code}">${response[key].name}</option>`;
                    }
                    $("#selPartModel").html(html);
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });

            // 根据整件类型获取额外标签
            $.ajax({
                url: `{{url('pivotEntireModelAndExtraTag')}}`,
                type: "get",
                data: {
                    type: 'entire_model_unique_code',
                    entire_model_unique_code: entireModelUniqueCode,
                },
                async: true,
                success: function (response) {
                    html = '';
                    for (let key in response) {
                        html += `<option value=${response[key].extra_tag}>${response[key].extra_tag}</option>`;
                    }
                    $("#selExtraTag").html(html);
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

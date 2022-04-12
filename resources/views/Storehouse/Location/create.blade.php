@extends('Layout.index')
@section('style')
    <link rel="stylesheet" href="/EasyWeb/spa/assets/libs/layui/css/layui.css"/>
    <link rel="stylesheet" href="/EasyWeb/spa/assets/css/lite.css"/>
    <style>
        #storehouseTable + .layui-table-view .layui-table-tool-temp {
            padding-right: 0;
        }

        #storehouseTable + .layui-table-view .layui-table-body tbody > tr td {
            cursor: pointer;
        }

        #storehouseTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click {
            background-color: #fff3e0;
        }

        #storehouseTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click td:last-child > div:before {
            position: absolute;
            right: 6px;
            content: "\e602";
            font-size: 12px;
            font-style: normal;
            font-family: layui-icon !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        #areaTable + .layui-table-view .layui-table-tool-temp {
            padding-right: 0;
        }

        #areaTable + .layui-table-view .layui-table-body tbody > tr td {
            cursor: pointer;
        }

        #areaTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click {
            background-color: #fff3e0;
        }

        #areaTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click td:last-child > div:before {
            position: absolute;
            right: 6px;
            content: "\e602";
            font-size: 12px;
            font-style: normal;
            font-family: layui-icon !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }


        #platoonTable + .layui-table-view .layui-table-tool-temp {
            padding-right: 0;
        }

        #platoonTable + .layui-table-view .layui-table-body tbody > tr td {
            cursor: pointer;
        }

        #platoonTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click {
            background-color: #fff3e0;
        }

        #platoonTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click td:last-child > div:before {
            position: absolute;
            right: 6px;
            content: "\e602";
            font-size: 12px;
            font-style: normal;
            font-family: layui-icon !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        #shelfTable + .layui-table-view .layui-table-tool-temp {
            padding-right: 0;
        }

        #shelfTable + .layui-table-view .layui-table-body tbody > tr td {
            cursor: pointer;
        }

        #shelfTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click {
            background-color: #fff3e0;
        }

        #shelfTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click td:last-child > div:before {
            position: absolute;
            right: 6px;
            content: "\e602";
            font-size: 12px;
            font-style: normal;
            font-family: layui-icon !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        #tierTable + .layui-table-view .layui-table-tool-temp {
            padding-right: 0;
        }

        #tierTable + .layui-table-view .layui-table-body tbody > tr td {
            cursor: pointer;
        }

        #tierTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click {
            background-color: #fff3e0;
        }

        #tierTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click td:last-child > div:before {
            position: absolute;
            right: 6px;
            content: "\e602";
            font-size: 12px;
            font-style: normal;
            font-family: layui-icon !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }


        #positionTable + .layui-table-view .layui-table-tool-temp {
            padding-right: 0;
        }

        #positionTable + .layui-table-view .layui-table-body tbody > tr td {
            cursor: pointer;
        }

        #positionTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click {
            background-color: #fff3e0;
        }

        #positionTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click td:last-child > div:before {
            position: absolute;
            right: 6px;
            content: "\e602";
            font-size: 12px;
            font-style: normal;
            font-family: layui-icon !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .layui-form-label {
            width: 130px;
        }

        .layui-input-block {
            margin-left: 130px;
        }


    </style>
@stop
@section('content')
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">库房位置管理</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('storehouse/location')}}" class="btn btn-default"><i class="fa fa-list-alt" aria-hidden="true"></i> 库房位置列表</a>
                </div>
            </div>
            <!--数据表格-->
            <div class="layui-fluid" style="padding-bottom: 0;">
                <div style="width:100%;overflow-x:scroll;white-space: nowrap;">
                    <div style="width: 400px;display :inline-block">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">仓</div>
                                <table id="storehouseTable" lay-filter="storehouseTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="areaBox" style="width: 400px;display :none">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">区</div>
                                <table id="areaTable" lay-filter="areaTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="platoonBox" style="width: 400px;display :none">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">排</div>
                                <table id="platoonTable" lay-filter="platoonTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="shelfBox" style="width: 400px;display :none">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">架</div>
                                <table id="shelfTable" lay-filter="shelfTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="tierBox" style="width: 400px;display :none">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">层</div>
                                <table id="tierTable" lay-filter="tierTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="positionBox" style="width: 400px;display :none;">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">位</div>
                                <table id="positionTable" lay-filter="positionTable"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 表单弹窗 仓 -->
            <script type="text/html" id="storehouseEditDialog">
                <form id="storehouseEditForm" lay-filter="storehouseEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">仓名称:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="请输入仓名称" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="storehouseEditSubmit" lay-submit>保存</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">取消</button>
                    </div>
                </form>
            </script>
            <!-- 表单弹窗 区 -->
            <script type="text/html" id="areaDialog">
                <form id="areaEditForm" lay-filter="areaEditForm" class="layui-form model-form" style="height: 300px">
                    <input name="id" type="hidden"/>
                    <input name="storehouse_unique_code" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">区名称:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="请输入区名称" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">区类型:</label>
                        <div class="layui-input-block">
                            <select name="type" id="type" lay-verType="tips" lay-verify="required" required>
                                @foreach($areaTypes as $typeUniqueCode=>$typeName)
                                    <option value="{{$typeUniqueCode}}">{{$typeName}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="areaEditSubmit" lay-submit>保存</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">取消</button>
                    </div>
                </form>
            </script>
            <!-- 表单弹窗 排 -->
            <script type="text/html" id="platoonDialog">
                <form id="platoonEditForm" lay-filter="platoonEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <input name="area_unique_code" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">排名称:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="请输入排名称" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="platoonEditSubmit" lay-submit>保存</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">取消</button>
                    </div>
                </form>
            </script>
            <!-- 表单弹窗 架 -->
            <script type="text/html" id="shelfDialog">
                <form id="shelfEditForm" lay-filter="shelfEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <input name="platoon_unique_code" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">架名称:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="请输入架名称" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>

                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="shelfEditSubmit" lay-submit>保存</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">取消</button>
                    </div>
                </form>
            </script>
            <!-- 表单弹窗 层 -->
            <script type="text/html" id="tierDialog">
                <form id="tierEditForm" lay-filter="tierEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <input name="shelf_unique_code" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">层名称:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="请输入层名称" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="tierEditSubmit" lay-submit>保存</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">取消</button>
                    </div>
                </form>
            </script>
            <!-- 表单弹窗 位 -->
            <script type="text/html" id="positionDialog">
                <form id="positionEditForm" lay-filter="positionEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <input name="tier_unique_code" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">位名称:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="请输入位名称" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="positionEditSubmit" lay-submit>保存</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">取消</button>
                    </div>
                </form>
            </script>
        </div>
        <div class="modal fade" id="divShelfImageUpload">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">上传图片<h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form enctype="multipart/form-data" id="frmShelfImage">
                            <input type="hidden" id="shelf_id" name="shelf_id">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">图片：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="file" name="file">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                                <button type="button" class="btn btn-success btn-flat pull-right btn-sm" onclick="fnUploadImage()"><i class="fa fa-upload">&nbsp;</i>上传</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" id="divShelfImageShow">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">位置：<span id="title"></span></h4>
                    </div>
                    <div class="modal-body">
                        <img id="location_img" class="model-body-location" alt="" style="width: 100%;">
                        <div class="spot"></div>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/EasyWeb/spa/assets/libs/layui/layui.js"></script>
    <script>
        layui.config({
            base: '/EasyWeb/spa/assets/module/'
        }).use(['layer', 'form', 'table', 'util', 'admin'], function () {
            let $ = layui.jquery;
            let layer = layui.layer;
            let form = layui.form;
            let table = layui.table;
            let admin = layui.admin;
            let selStorehouseObj;  // 仓选中数据
            let selAreaObj;  // 区选中数据
            let selPlatoonObj;  // 排选中数据
            let selShelfObj;  // 架选中数据
            let selTierObj;  // 层选中数据
            let selPositionObj; // 位选中数据

            /* 渲染仓表格 */
            let storehouseTb = table.render({
                elem: '#storehouseTable',
                url: `{{url('storehouse/location/storehouse')}}`,
                height: 'full-240',
                toolbar: ['<p>',
                    '<button lay-event="add" class="layui-btn layui-btn-sm icon-btn"><i class="layui-icon">&#xe654;</i></button>&nbsp;',
                    '<button lay-event="edit" class="layui-btn layui-btn-sm layui-btn-warm icon-btn"><i class="layui-icon">&#xe642;</i></button>&nbsp;',
                    '<button lay-event="del" class="layui-btn layui-btn-sm layui-btn-danger icon-btn"><i class="layui-icon">&#xe640;</i></button>',
                    '</p>'].join(''),
                defaultToolbar: [],
                cols: [[
                    {type: 'numbers'},
                    {field: 'name', title: '仓名称', width: 160},
                    {field: 'unique_code', title: '仓编码', width: 200}
                ]],
            });

            /* 监听行单击事件 */
            table.on('row(storehouseTable)', function (obj) {
                selStorehouseObj = obj;
                selAreaObj = false;
                selPlatoonObj = false;
                selShelfObj = false;
                selTierObj = false;
                selPositionObj = false;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
                $('#areaBox').css('display', 'inline-block');
                $('#platoonBox').hide();
                $('#shelfBox').hide();
                $('#tierBox').hide();
                $('#positionBox').hide();
                areaModelTb.reload({
                    url: `{{url('storehouse/location/area')}}?storehouse_unique_code=${obj.data.unique_code}`,
                });

            });

            /* 仓表格头工具栏点击事件 */
            table.on('toolbar(storehouseTable)', function (obj) {
                if (obj.event === 'add') { // 添加
                    showStorehouseEditModel(obj.event);
                } else if (obj.event === 'edit') { // 修改
                    showStorehouseEditModel(obj.event, selStorehouseObj);
                } else if (obj.event === 'del') { // 删除
                    doStorehouseDel(selStorehouseObj);
                }
            });

            /* 显示仓表单弹窗 */
            function showStorehouseEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('请选择仓', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '修改' : '添加') + '仓',
                    content: $('#storehouseEditDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        if (obj_event === 'edit') {
                            //修改
                            url = `{{url('storehouse/location/storehouse')}}/${obj.data.id}`;
                            // 回显表单数据
                            form.val('storehouseEditForm', obj.data);
                            type = 'PUT';
                        } else {
                            url = `{{url('storehouse/location/storehouse')}}`;
                            type = 'POST';
                        }

                        // 表单提交事件
                        form.on('submit(storehouseEditSubmit)', function (data) {
                            let loadIndex = layer.load(2);
                            $.ajax({
                                url: url,
                                type: type,
                                data: data.field,
                                async: true,
                                success: response => {
                                    console.log(`success:`, response);
                                    layer.close(loadIndex);
                                    layer.close(dIndex);
                                    layer.msg(response.msg, {icon: 1});
                                    if (obj_event === 'edit') {
                                        obj.update(data.field);
                                    } else {
                                        storehouseTb.reload({
                                            url: `{{url('storehouse/location/storehouse')}}`,
                                        })
                                    }
                                },
                                error: error => {
                                    console.log(`error:`, error);
                                    layer.msg(error['responseJSON']['msg'], {icon: 2});
                                    location.reload();
                                }
                            });

                            return false;
                        });
                    }
                });
            }

            /* 删除仓 */
            function doStorehouseDel(obj) {
                if (!obj) {
                    layer.msg('请选择仓', {icon: 2});
                    return false;
                }
                layer.confirm('确定要删除此仓吗？', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('storehouse/location/storehouse')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });

                });
            }


            /* 渲染区表格 */
            let areaModelTb = table.render({
                elem: '#areaTable',
                height: 'full-240',
                toolbar: ['<p>',
                    '<button lay-event="add" class="layui-btn layui-btn-sm icon-btn"><i class="layui-icon">&#xe654;</i></button>&nbsp;',
                    '<button lay-event="edit" class="layui-btn layui-btn-sm layui-btn-warm icon-btn"><i class="layui-icon">&#xe642;</i></button>&nbsp;',
                    '<button lay-event="del" class="layui-btn layui-btn-sm layui-btn-danger icon-btn"><i class="layui-icon">&#xe640;</i></button>',
                    '</p>'].join(''),
                defaultToolbar: [],
                cols: [[
                    {type: 'numbers'},
                    {field: 'name', title: '区名称', width: 160},
                    {field: 'unique_code', title: '区编码', width: 200}
                ]],
            });

            /* 区监听行单击事件 */
            table.on('row(areaTable)', function (obj) {
                selAreaObj = obj;
                selPlatoonObj = false;
                selShelfObj = false;
                selTierObj = false;
                selPositionObj = false;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
                $('#platoonBox').css('display', 'inline-block');
                $('#shelfBox').hide();
                $('#tierBox').hide();
                $('#positionBox').hide();
                platoonTb.reload({
                    url: `{{url('storehouse/location/platoon')}}?area_unique_code=${obj.data.unique_code}`,
                });
            });

            /* 区表格头工具栏点击事件 */
            table.on('toolbar(areaTable)', function (obj) {
                if (obj.event === 'add') {
                    showAreaEditModel(obj.event);
                } else if (obj.event === 'edit') {
                    showAreaEditModel(obj.event, selAreaObj);
                } else if (obj.event === 'del') {
                    doAreaDel(selAreaObj);
                }
            });

            /* 显示区表单弹窗 */
            function showAreaEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('请选择区', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '修改' : '添加') + '区',
                    content: $('#areaDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // 回显表单数据
                        if (obj_event === 'edit') {
                            form.val('areaEditForm', obj.data);
                            url = `{{url('storehouse/location/area')}}/${obj.data.id}`;
                            type = 'PUT';
                        } else {
                            form.val('areaEditForm', {storehouse_unique_code: selStorehouseObj.data.unique_code});
                            url = `{{url('storehouse/location/area')}}`;
                            type = 'POST';
                        }

                        // 表单提交事件
                        form.on('submit(areaEditSubmit)', function (data) {
                            let loadIndex = layer.load(2);
                            $.ajax({
                                url: url,
                                type: type,
                                data: data.field,
                                async: true,
                                success: response => {
                                    console.log(`success:`, response);
                                    layer.close(loadIndex);
                                    layer.close(dIndex);
                                    layer.msg(response.msg, {icon: 1});
                                    if (obj_event === 'edit') {
                                        obj.update(data.field);
                                    } else {
                                        areaModelTb.reload({
                                            url: `{{url('storehouse/location/area')}}?storehouse_unique_code=${selStorehouseObj.data.unique_code}`,
                                        })
                                    }
                                },
                                error: error => {
                                    console.log(`error:`, error);
                                    layer.msg(error['responseJSON']['msg'], {icon: 2});
                                    location.reload();
                                }
                            });

                            return false;
                        });
                    }
                });
            }

            /* 删除区 */
            function doAreaDel(obj) {
                if (!obj) {
                    layer.msg('请选择区', {icon: 2});
                    return false;
                }
                layer.confirm('确定要删除此区吗？', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('storehouse/location/area')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });

                });
            }


            /* 渲染排表格 */
            let platoonTb = table.render({
                elem: '#platoonTable',
                height: 'full-240',
                toolbar: ['<p>',
                    '<button lay-event="add" class="layui-btn layui-btn-sm icon-btn"><i class="layui-icon">&#xe654;</i></button>&nbsp;',
                    '<button lay-event="edit" class="layui-btn layui-btn-sm layui-btn-warm icon-btn"><i class="layui-icon">&#xe642;</i></button>&nbsp;',
                    '<button lay-event="del" class="layui-btn layui-btn-sm layui-btn-danger icon-btn"><i class="layui-icon">&#xe640;</i></button>',
                    '</p>'].join(''),
                defaultToolbar: [],
                cols: [[
                    {type: 'numbers'},
                    {field: 'name', title: '排名称', width: 160},
                    {field: 'unique_code', title: '排编码', width: 200}
                ]],
            });

            /* 排表格头工具栏点击事件 */
            table.on('toolbar(platoonTable)', function (obj) {
                if (obj.event === 'add') {
                    showplatoonEditModel(obj.event);
                } else if (obj.event === 'edit') {
                    showplatoonEditModel(obj.event, selPlatoonObj);
                } else if (obj.event === 'del') {
                    doplatoonDel(selPlatoonObj);
                }
            });

            /* 显示排表单弹窗 */
            function showplatoonEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('请选择排', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '修改' : '添加') + '排',
                    content: $('#platoonDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // 回显表单数据
                        if (obj_event === 'edit') {
                            form.val('platoonEditForm', obj.data);
                            url = `{{url('storehouse/location/platoon')}}/${obj.data.id}`;
                            type = 'PUT';
                        } else {
                            form.val('platoonEditForm', {'area_unique_code': selAreaObj.data.unique_code});
                            url = `{{url('storehouse/location/platoon')}}`;
                            type = 'POST';
                        }

                        // 表单提交事件
                        form.on('submit(platoonEditSubmit)', function (data) {
                            let loadIndex = layer.load(2);
                            $.ajax({
                                url: url,
                                type: type,
                                data: data.field,
                                async: true,
                                success: response => {
                                    console.log(`success:`, response);
                                    layer.close(loadIndex);
                                    layer.close(dIndex);
                                    layer.msg(response.msg, {icon: 1});
                                    if (obj_event === 'edit') {
                                        obj.update(data.field);
                                    } else {
                                        platoonTb.reload({
                                            url: `{{url('storehouse/location/platoon')}}?area_unique_code=${selAreaObj.data.unique_code}`,
                                        });
                                    }
                                },
                                error: error => {
                                    console.log(`error:`, error);
                                    layer.msg(error['responseJSON']['msg'], {icon: 2});
                                    location.reload();
                                }
                            });

                            return false;
                        });
                    }
                });
            }

            /* 删除排 */
            function doplatoonDel(obj) {
                if (!obj) {
                    layer.msg('请选择排', {icon: 2});
                    return false;
                }
                layer.confirm('确定要删除此排吗？', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('storehouse/location/platoon')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });

                });
            }

            /* 排监听行单击事件 */
            table.on('row(platoonTable)', function (obj) {
                selPlatoonObj = obj;
                selShelfObj = false;
                selTierObj = false;
                selPositionObj = false;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
                $('#shelfBox').css('display', 'inline-block');
                $('#tierBox').hide();
                $('#positionBox').hide();
                shelfTb.reload({
                    url: `{{url('storehouse/location/shelf')}}?platoon_unique_code=${obj.data.unique_code}`,
                });
            });

            /* 渲染架表格 */
            let shelfTb = table.render({
                elem: '#shelfTable',
                height: 'full-240',
                toolbar: ['<p>',
                    '<button lay-event="add" class="layui-btn layui-btn-sm icon-btn"><i class="layui-icon">&#xe654;</i></button>&nbsp;',
                    '<button lay-event="edit" class="layui-btn layui-btn-sm layui-btn-warm icon-btn"><i class="layui-icon">&#xe642;</i></button>&nbsp;',
                    '<button lay-event="del" class="layui-btn layui-btn-sm layui-btn-danger icon-btn"><i class="layui-icon">&#xe640;</i></button>',
                    '<button lay-event="uploadImage" class="layui-btn layui-btn-sm layui-btn-primary icon-btn" id="test1"><i class="layui-icon">&#xe681;</i></button>&nbsp;',
                    '<button lay-event="showImage" class="layui-btn layui-btn-sm layui-btn-primary icon-btn"><i class="layui-icon">&#xe601;</i></button>&nbsp;',
                    '</p>'].join(''),
                defaultToolbar: [],
                cols: [[
                    {type: 'numbers'},
                    {field: 'name', title: '架名称', width: 160},
                    {field: 'unique_code', title: '架编码', width: 200}
                ]],
            });

            /* 层表格头工具栏点击事件 */
            table.on('toolbar(shelfTable)', function (obj) {
                switch (obj.event) {
                    case 'add':
                        showShelfEditModel(obj.event);
                        break;
                    case 'edit':
                        showShelfEditModel(obj.event, selShelfObj);
                        break;
                    case 'del':
                        doShelfDel(selShelfObj);
                        break;
                    case 'uploadImage':
                        uploadImageShelf(selShelfObj);
                        break;
                    case 'showImage':
                        showShelfImage(selShelfObj);
                        break;


                }
            });

            /* 显示架表单弹窗 */
            function showShelfEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('请选择架', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '修改' : '添加') + '架',
                    content: $('#shelfDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // 回显表单数据
                        if (obj_event === 'edit') {
                            form.val('shelfEditForm', obj.data);
                            url = `{{url('storehouse/location/shelf')}}/${obj.data.id}`;
                            type = 'PUT';
                        } else {
                            form.val('shelfEditForm', {'platoon_unique_code': selPlatoonObj.data.unique_code});
                            url = `{{url('storehouse/location/shelf')}}`;
                            type = 'POST';
                        }

                        // 表单提交事件
                        form.on('submit(shelfEditSubmit)', function (data) {
                            let loadIndex = layer.load(2);
                            $.ajax({
                                url: url,
                                type: type,
                                data: data.field,
                                async: true,
                                success: response => {
                                    console.log(`success:`, response);
                                    layer.close(loadIndex);
                                    layer.close(dIndex);
                                    layer.msg(response.msg, {icon: 1});
                                    if (obj_event === 'edit') {
                                        obj.update(data.field);
                                    } else {
                                        shelfTb.reload({
                                            url: `{{url('storehouse/location/shelf')}}?platoon_unique_code=${selPlatoonObj.data.unique_code}`,
                                        });
                                    }
                                },
                                error: error => {
                                    console.log(`error:`, error);
                                    layer.msg(error['responseJSON']['msg'], {icon: 2});
                                    location.reload();
                                }
                            });

                            return false;
                        });
                    }
                });
            }

            /* 删除架 */
            function doShelfDel(obj) {
                if (!obj) {
                    layer.msg('请选择架', {icon: 2});
                    return false;
                }
                layer.confirm('确定要删除此架吗？', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('storehouse/location/shelf')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });
                });
            }

            /**
             * 架-上传图片
             */
            function uploadImageShelf(obj) {
                if (!obj) {
                    layer.msg('请选择架', {icon: 2});
                    return false;
                }
                //执行实例
                $('#shelf_id').val(obj.data.id);
                $('#divShelfImageUpload').modal("show");
            }

            /**
             * 展示图片
             */
            function showShelfImage(obj) {
                if (!obj) {
                    layer.msg('请选择架', {icon: 2});
                    return false;
                }
                let location_img = obj.data.location_img;
                if (location_img) {
                    document.getElementById('location_img').src = location_img;
                    $("#divShelfImageShow").modal("show");
                } else {
                    alert('请联系管理员，绑定位置图片');
                }
            }

            /* 架监听行单击事件 */
            table.on('row(shelfTable)', function (obj) {
                selShelfObj = obj;
                selTierObj = false;
                selPositionObj = false;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
                $('#tierBox').css('display', 'inline-block');
                $('#positionBox').hide();
                tierTb.reload({
                    url: `{{url('storehouse/location/tier')}}?shelf_unique_code=${obj.data.unique_code}`,
                });
            });

            /* 渲染层表格 */
            let tierTb = table.render({
                elem: '#tierTable',
                height: 'full-240',
                toolbar: ['<p>',
                    '<button lay-event="add" class="layui-btn layui-btn-sm icon-btn"><i class="layui-icon">&#xe654;</i></button>&nbsp;',
                    '<button lay-event="edit" class="layui-btn layui-btn-sm layui-btn-warm icon-btn"><i class="layui-icon">&#xe642;</i></button>&nbsp;',
                    '<button lay-event="del" class="layui-btn layui-btn-sm layui-btn-danger icon-btn"><i class="layui-icon">&#xe640;</i></button>',
                    '</p>'].join(''),
                defaultToolbar: [],
                cols: [[
                    {type: 'numbers'},
                    {field: 'name', title: '层名称', width: 160},
                    {field: 'unique_code', title: '层编码', width: 200}
                ]],
            });

            /* 层表格头工具栏点击事件 */
            table.on('toolbar(tierTable)', function (obj) {
                if (obj.event === 'add') {
                    showTierEditModel(obj.event);
                } else if (obj.event === 'edit') {
                    showTierEditModel(obj.event, selTierObj);
                } else if (obj.event === 'del') {
                    doTierDel(selTierObj);
                }
            });

            /* 显示层表单弹窗 */
            function showTierEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('请选择层', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '修改' : '添加') + '层',
                    content: $('#tierDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // 回显表单数据
                        if (obj_event === 'edit') {
                            form.val('tierEditForm', obj.data);
                            url = `{{url('storehouse/location/tier')}}/${obj.data.id}`;
                            type = 'PUT';
                        } else {
                            form.val('tierEditForm', {'shelf_unique_code': selShelfObj.data.unique_code});
                            url = `{{url('storehouse/location/tier')}}`;
                            type = 'POST';
                        }

                        // 表单提交事件
                        form.on('submit(tierEditSubmit)', function (data) {
                            let loadIndex = layer.load(2);
                            $.ajax({
                                url: url,
                                type: type,
                                data: data.field,
                                async: true,
                                success: response => {
                                    console.log(`success:`, response);
                                    layer.close(loadIndex);
                                    layer.close(dIndex);
                                    layer.msg(response.msg, {icon: 1});
                                    if (obj_event === 'edit') {
                                        obj.update(data.field);
                                    } else {
                                        tierTb.reload({
                                            url: `{{url('storehouse/location/tier')}}?shelf_unique_code=${selShelfObj.data.unique_code}`,
                                        });
                                    }
                                },
                                error: error => {
                                    console.log(`error:`, error);
                                    layer.msg(error['responseJSON']['msg'], {icon: 2});
                                    location.reload();
                                }
                            });

                            return false;
                        });
                    }
                });
            }

            /* 删除层 */
            function doTierDel(obj) {
                if (!obj) {
                    layer.msg('请选择层', {icon: 2});
                    return false;
                }
                layer.confirm('确定要删除此层吗？', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('storehouse/location/tier')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });

                });
            }

            /* 层监听行单击事件 */
            table.on('row(tierTable)', function (obj) {
                selTierObj = obj;
                selPositionObj = false;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
                $('#positionBox').css('display', 'inline-block');
                positionTb.reload({
                    url: `{{url('storehouse/location/position')}}?tier_unique_code=${obj.data.unique_code}`,
                });
            });


            /*渲染位表格*/
            let positionTb = table.render({
                elem: '#positionTable',
                height: 'full-240',
                toolbar: ['<p>',
                    '<button lay-event="add" class="layui-btn layui-btn-sm icon-btn"><i class="layui-icon">&#xe654;</i></button>&nbsp;',
                    '<button lay-event="edit" class="layui-btn layui-btn-sm layui-btn-warm icon-btn"><i class="layui-icon">&#xe642;</i></button>&nbsp;',
                    '<button lay-event="del" class="layui-btn layui-btn-sm layui-btn-danger icon-btn"><i class="layui-icon">&#xe640;</i></button>',
                    '</p>'].join(''),
                defaultToolbar: [],
                cols: [[
                    {type: 'numbers'},
                    {field: 'name', title: '位名称', width: 160},
                    {field: 'unique_code', title: '位编码', width: 200}
                ]],
            });

            /* 位监听行单击事件 */
            table.on('row(positionTable)', function (obj) {
                selPositionObj = obj;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
            });

            /* 位表格头工具栏点击事件 */
            table.on('toolbar(positionTable)', function (obj) {
                if (obj.event === 'add') {
                    showPositionEditModel(obj.event);
                } else if (obj.event === 'edit') {
                    showPositionEditModel(obj.event, selPositionObj);
                } else if (obj.event === 'del') {
                    doPositionrDel(selPositionObj);
                }
            });

            /* 显示位置表单弹窗 */
            function showPositionEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('请选择型号', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '修改' : '添加') + '位',
                    content: $('#positionDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // 回显表单数据
                        if (obj_event === 'edit') {
                            form.val('positionEditForm', obj.data);
                            url = `{{url('storehouse/location/position')}}/${obj.data.id}`;
                            type = 'put';
                        } else {
                            form.val('positionEditForm', {'tier_unique_code': selTierObj.data.unique_code});
                            url = `{{url('storehouse/location/position')}}`;
                            type = 'post';
                        }

                        // 表单提交事件
                        form.on('submit(positionEditSubmit)', function (data) {
                            let loadIndex = layer.load(2);
                            $.ajax({
                                url: url,
                                type: type,
                                data: data.field,
                                async: true,
                                success: response => {
                                    console.log(`success:`, response);
                                    layer.close(loadIndex);
                                    layer.close(dIndex);
                                    layer.msg(response.msg, {icon: 1});
                                    if (obj_event === 'edit') {
                                        obj.update(data.field);
                                    } else {
                                        positionTb.reload({
                                            url: `{{url('storehouse/location/position')}}?tier_unique_code=${selTierObj.data.unique_code}`,
                                        });
                                    }
                                },
                                error: error => {
                                    console.log(`error:`, error);
                                    layer.msg(error['responseJSON']['msg'], {icon: 2});
                                    location.reload();
                                }
                            });

                            return false;
                        });
                    }
                });
            }

            /* 删除位 */
            function doPositionrDel(obj) {
                if (!obj) {
                    layer.msg('请选择位', {icon: 2});
                    return false;
                }
                layer.confirm('确定要删除此位吗？', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('storehouse/location/position')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });

                });
            }

            /*end*/
        });

        /**
         * 上传架图片
         */
        function fnUploadImage() {
            let formData = new FormData($('#frmShelfImage')[0]);
            $.ajax({
                url: `{{ url('storehouse/location/shelf/uploadImage') }}`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    $('#divShelfImageUpload').modal('hide');
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
        }
    </script>
@endsection

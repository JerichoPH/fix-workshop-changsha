@extends('Layout.index')
@section('style')
    <link rel="stylesheet" href="/EasyWeb/spa/assets/libs/layui/css/layui.css"/>
    <link rel="stylesheet" href="/EasyWeb/spa/assets/css/lite.css"/>
    <style>
        #roomTable + .layui-table-view .layui-table-tool-temp {
            padding-right: 0;
        }

        #roomTable + .layui-table-view .layui-table-body tbody > tr td {
            cursor: pointer;
        }

        #roomTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click {
            background-color: #fff3e0;
        }

        #roomTable + .layui-table-view .layui-table-body tbody > tr.layui-table-click td:last-child > div:before {
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
                <h3 class="box-title">??????????????????</h3>
                <div class="box-tools pull-right">
                    <a href="{{url('installLocation')}}" class="btn btn-default"><i class="fa fa-list-alt" aria-hidden="true"></i> ??????????????????</a>
                </div>
            </div>
            <!--????????????-->
            <div class="layui-fluid" style="padding-bottom: 0;">
                <div style="width:100%;overflow-x:scroll;white-space: nowrap;">
                    <div style="width: 355px;display :inline-block">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">??????</div>
                                <table id="roomTable" lay-filter="roomTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="platoonBox" style="width: 200px;display :none">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">???</div>
                                <table id="platoonTable" lay-filter="platoonTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="shelfBox" style="width: 423px;display :none">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">??????</div>
                                <table id="shelfTable" lay-filter="shelfTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="tierBox" style="width: 200px;display :none">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">???</div>
                                <table id="tierTable" lay-filter="tierTable"></table>
                            </div>
                        </div>
                    </div>
                    <div id="positionBox" style="width: 200px;display :none;">
                        <div class="layui-card">
                            <div class="layui-card-body" style="padding: 10px;">
                                <div class="layui-body-header-title">???</div>
                                <table id="positionTable" lay-filter="positionTable"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ???????????? ?????? -->
            <script type="text/html" id="roomEditDialog">
                <form id="roomEditForm" lay-filter="roomEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">??????:</label>
                        <div class="layui-input-block">
                            <input id="room_type" name="type" placeholder="?????????" value="{{ $firstRoomTypes }}" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">??????/??????:</label>
                        <div class="layui-input-block">
                            <input id="maintain_unique_code" name="maintain_unique_code" placeholder="?????????" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="roomEditSubmit" lay-submit>??????</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">??????</button>
                    </div>
                </form>
            </script>
            <!-- ???????????? ??? -->
            <script type="text/html" id="platoonDialog">
                <form id="platoonEditForm" lay-filter="platoonEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <input name="install_room_unique_code" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">?????????:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="??????????????????" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="platoonEditSubmit" lay-submit>??????</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">??????</button>
                    </div>
                </form>
            </script>
            <!-- ???????????? ?????? -->
            <script type="text/html" id="shelfDialog">
                <form id="shelfEditForm" lay-filter="shelfEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <input name="install_platoon_unique_code" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">????????????:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="?????????????????????" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="shelfEditSubmit" lay-submit>??????</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">??????</button>
                    </div>
                </form>
            </script>
            <!-- ???????????? ??? -->
            <script type="text/html" id="tierDialog">
                <form id="tierEditForm" lay-filter="tierEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <input name="install_shelf_unique_code" type="hidden"/>
                    <div class="layui-form-item">
                        <label class="layui-form-label layui-form-required">?????????:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="??????????????????" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="tierEditSubmit" lay-submit>??????</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">??????</button>
                    </div>
                </form>
            </script>
            <!-- ???????????? ??? -->
            <script type="text/html" id="positionDialog">
                <form id="positionEditForm" lay-filter="positionEditForm" class="layui-form model-form">
                    <input name="id" type="hidden"/>
                    <input name="install_tier_unique_code" type="hidden"/>
                    <div class="layui-form-item" id="position_count_div">
                        <label class="layui-form-label layui-form-required">?????????:</label>
                        <div class="layui-input-block">
                            <input name="position_count" placeholder="??????????????????" id="position_count" type="number" value="1" oninput="checkNum(this.value,'position_count')" class="layui-input" lay-verType="tips" lay-verify="required" required/>
                        </div>
                    </div>
                    <div class="layui-form-item" id="position_name_div">
                        <label class="layui-form-label layui-form-required">?????????:</label>
                        <div class="layui-input-block">
                            <input name="name" placeholder="??????????????????" class="layui-input" lay-verType="tips"/>
                        </div>
                    </div>
                    <div class="layui-form-item text-right">
                        <button class="layui-btn" lay-filter="positionEditSubmit" lay-submit>??????</button>
                        <button class="layui-btn layui-btn-primary" type="button" ew-event="closeDialog">??????</button>
                    </div>
                </form>
            </script>
        </div>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/EasyWeb/spa/assets/libs/layui/layui.js"></script>
    <script>
        layui.config({
            base: '/EasyWeb/spa/assets/module/'
        }).use(['layer', 'form', 'table', 'util', 'admin', 'cascader'], function () {
            let $ = layui.jquery;
            let layer = layui.layer;
            let form = layui.form;
            let table = layui.table;
            let admin = layui.admin;
            let cascader = layui.cascader;
            let selRoomObj;  // ??????????????????
            let selPlatoonObj;  // ???????????????
            let selShelfObj;  // ??????????????????
            let selTierObj;  // ???????????????
            let selPositionObj; // ???????????????

            /**
             * ??????????????????
             */
            let installRoomTb = table.render({
                elem: '#roomTable',
                url: `{{url('installLocation/room')}}`,
                height: 'full-240',
                toolbar: ['<p>',
                    '<button lay-event="add" class="layui-btn layui-btn-sm icon-btn"><i class="layui-icon">&#xe654;</i></button>&nbsp;',
                    '<button lay-event="del" class="layui-btn layui-btn-sm layui-btn-danger icon-btn"><i class="layui-icon">&#xe640;</i></button>',
                    '</p>'].join(''),
                defaultToolbar: [],
                cols: [[
                    {field: 'workshop_name', title: '??????', width: 130},
                    {field: 'station_name', title: '??????', width: 120},
                    {field: 'type_name', title: '??????', width: 80}
                ]],
            });

            /**
             * ?????????????????????
             */
            table.on('row(roomTable)', function (obj) {
                selRoomObj = obj;
                console.log(selRoomObj)
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
                    url: `{{url('installLocation/platoon')}}?install_room_unique_code=${obj.data.unique_code}`,
                });
            });

            /**
             * ????????????????????????????????????
             */
            table.on('toolbar(roomTable)', function (obj) {
                if (obj.event === 'add') { // ??????
                    showRoomEditModel(obj.event);
                } else if (obj.event === 'del') { // ??????
                    doRoomDel(selRoomObj);
                }
            });

            /**
             * ????????????????????????
             */
            function showRoomEditModel() {
                admin.open({
                    type: 1,
                    title: '????????????',
                    content: $('#roomEditDialog').html(),
                    success: function (layero, dIndex) {
                        let maintains = JSON.parse(`{!! $maintains !!}`);
                        let roomTypes = JSON.parse(`{!! $roomTypes !!}`);
                        cascader.render({
                            elem: '#room_type',
                            data: roomTypes,
                        });
                        cascader.render({
                            elem: '#maintain_unique_code',
                            data: maintains,
                            filterable: true,
                            changeOnSelect: true
                        });
                        // ????????????????????????
                        $(layero).children('.layui-layer-content').css('overflow', 'visible');

                        // ??????????????????
                        form.on('submit(roomEditSubmit)', function (data) {
                            let loadIndex = layer.load(2);
                            $.ajax({
                                url: `{{url('installLocation/room')}}`,
                                type: 'POST',
                                data: data.field,
                                async: true,
                                success: response => {
                                    console.log(`success:`, response);
                                    layer.close(loadIndex);
                                    layer.close(dIndex);
                                    layer.msg(response.msg, {icon: 1});
                                    installRoomTb.reload({
                                        url: `{{url('installLocation/room')}}`,
                                    })
                                    $('#platoonBox').hide();
                                    $('#shelfBox').hide();
                                    $('#tierBox').hide();
                                    $('#positionBox').hide();
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

            /**
             * ????????????
             */
            function doRoomDel(obj) {
                if (!obj) {
                    layer.msg('???????????????', {icon: 2});
                    return false;
                }
                layer.confirm('??????????????????????????????', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('installLocation/room')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                            $('#platoonBox').hide();
                            $('#shelfBox').hide();
                            $('#tierBox').hide();
                            $('#positionBox').hide();
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
             * ???????????????
             */
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
                    {field: 'name', title: '?????????', width: 136},
                ]],
            });

            /**
             * ?????????????????????????????????
             */
            table.on('toolbar(platoonTable)', function (obj) {
                if (obj.event === 'add') {
                    showPlatoonEditModel(obj.event);
                } else if (obj.event === 'edit') {
                    showPlatoonEditModel(obj.event, selPlatoonObj);
                } else if (obj.event === 'del') {
                    doPlatoonDel(selPlatoonObj);
                }
            });

            /**
             * ?????????????????????
             */
            function showPlatoonEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('????????????', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '??????' : '??????') + '???',
                    content: $('#platoonDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // ??????????????????
                        if (obj_event === 'edit') {
                            form.val('platoonEditForm', obj.data);
                            url = `{{url('installLocation/platoon')}}/${obj.data.id}`;
                            type = 'PUT';
                        } else {
                            form.val('platoonEditForm', {'install_room_unique_code': selRoomObj.data.unique_code});
                            url = `{{url('installLocation/platoon')}}`;
                            type = 'POST';
                        }

                        // ??????????????????
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
                                    platoonTb.reload({
                                        url: `{{url('installLocation/platoon')}}?install_room_unique_code=${selRoomObj.data.unique_code}`,
                                    });
                                    $('#shelfBox').hide();
                                    $('#tierBox').hide();
                                    $('#positionBox').hide();
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

            /**
             *?????????
             */
            function doPlatoonDel(obj) {
                if (!obj) {
                    layer.msg('????????????', {icon: 2});
                    return false;
                }
                layer.confirm('???????????????????????????', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('installLocation/platoon')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                            $('#shelfBox').hide();
                            $('#tierBox').hide();
                            $('#positionBox').hide();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });

                });
            }

            /* ???????????????????????? */
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
                    url: `{{url('installLocation/shelf')}}?install_platoon_unique_code=${obj.data.unique_code}`,
                });
            });

            /* ?????????????????? */
            let shelfTb = table.render({
                elem: '#shelfTable',
                height: 'full-240',
                toolbar: ['<p>',
                    '<button lay-event="add" class="layui-btn layui-btn-sm icon-btn"><i class="layui-icon">&#xe654;</i></button>&nbsp;',
                    '<button lay-event="edit" class="layui-btn layui-btn-sm layui-btn-warm icon-btn"><i class="layui-icon">&#xe642;</i></button>&nbsp;',
                    '<button lay-event="del" class="layui-btn layui-btn-sm layui-btn-danger icon-btn"><i class="layui-icon">&#xe640;</i></button>',
                    '</p>'].join(''),
                defaultToolbar: [],
                cols: [[
                    {type: 'numbers'},
                    {field: 'name', title: '????????????', width: 360},
                ]],
            });

            /* ????????????????????????????????? */
            table.on('toolbar(shelfTable)', function (obj) {
                if (obj.event === 'add') {
                    showShelfEditModel(obj.event);
                } else if (obj.event === 'edit') {
                    showShelfEditModel(obj.event, selShelfObj);
                } else if (obj.event === 'del') {
                    doShelfDel(selShelfObj);
                }
            });

            /* ???????????????????????? */
            function showShelfEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('???????????????', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '??????' : '??????') + '??????',
                    content: $('#shelfDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // ??????????????????
                        if (obj_event === 'edit') {
                            form.val('shelfEditForm', obj.data);
                            url = `{{url('installLocation/shelf')}}/${obj.data.id}`;
                            type = 'PUT';
                        } else {
                            form.val('shelfEditForm', {'install_platoon_unique_code': selPlatoonObj.data.unique_code});
                            url = `{{url('installLocation/shelf')}}`;
                            type = 'POST';
                        }
                        // ????????????????????????
                        $(layero).children('.layui-layer-content').css('overflow', 'visible');

                        // ??????????????????
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
                                    shelfTb.reload({
                                        url: `{{url('installLocation/shelf')}}?install_platoon_unique_code=${selPlatoonObj.data.unique_code}`,
                                    });
                                    $('#tierBox').hide();
                                    $('#positionBox').hide();
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

            /* ???????????? */
            function doShelfDel(obj) {
                if (!obj) {
                    layer.msg('???????????????', {icon: 2});
                    return false;
                }
                layer.confirm('??????????????????????????????', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('installLocation/shelf')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                            $('#tierBox').hide();
                            $('#positionBox').hide();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });

                });
            }

            /* ??????????????????????????? */
            table.on('row(shelfTable)', function (obj) {
                selShelfObj = obj;
                selTierObj = false;
                selPositionObj = false;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
                $('#tierBox').css('display', 'inline-block');
                $('#positionBox').hide();
                tierTb.reload({
                    url: `{{url('installLocation/tier')}}?install_shelf_unique_code=${obj.data.unique_code}`,
                });
            });

            /* ??????????????? */
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
                    {field: 'name', title: '?????????', width: 136},
                ]],
            });

            /* ????????????????????????????????? */
            table.on('toolbar(tierTable)', function (obj) {
                if (obj.event === 'add') {
                    showTierEditModel(obj.event);
                } else if (obj.event === 'edit') {
                    showTierEditModel(obj.event, selTierObj);
                } else if (obj.event === 'del') {
                    doTierDel(selTierObj);
                }
            });

            /* ????????????????????? */
            function showTierEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('????????????', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '??????' : '??????') + '???',
                    content: $('#tierDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // ??????????????????
                        if (obj_event === 'edit') {
                            form.val('tierEditForm', obj.data);
                            url = `{{url('installLocation/tier')}}/${obj.data.id}`;
                            type = 'PUT';
                        } else {
                            form.val('tierEditForm', {'install_shelf_unique_code': selShelfObj.data.unique_code});
                            url = `{{url('installLocation/tier')}}`;
                            type = 'POST';
                        }

                        // ??????????????????
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
                                    tierTb.reload({
                                        url: `{{url('installLocation/tier')}}?install_shelf_unique_code=${selShelfObj.data.unique_code}`,
                                    });
                                    $('#positionBox').hide();
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

            /* ????????? */
            function doTierDel(obj) {
                if (!obj) {
                    layer.msg('????????????', {icon: 2});
                    return false;
                }
                layer.confirm('???????????????????????????', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('installLocation/tier')}}/${obj.data.id}`,
                        type: 'delete',
                        async: true,
                        success: response => {
                            console.log(`success:`, response);
                            layer.close(loadIndex);
                            layer.msg(response.msg, {icon: 1});
                            obj.del();
                            $('#positionBox').hide();
                        },
                        error: error => {
                            console.log(`error:`, error);
                            layer.msg(error['responseJSON']['msg'], {icon: 2});
                            location.reload();
                        }
                    });

                });
            }

            /* ???????????????????????? */
            table.on('row(tierTable)', function (obj) {
                selTierObj = obj;
                selPositionObj = false;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
                $('#positionBox').css('display', 'inline-block');
                positionTb.reload({
                    url: `{{url('installLocation/position')}}?install_tier_unique_code=${obj.data.unique_code}`,
                });
            });


            /**
             * ???????????????
             */
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
                    {field: 'name', title: '?????????', width: 136}
                ]],
            });

            /* ???????????????????????? */
            table.on('row(positionTable)', function (obj) {
                selPositionObj = obj;
                obj.tr.addClass('layui-table-click').siblings().removeClass('layui-table-click');
            });

            // ?????????????????????????????????
            table.on('toolbar(positionTable)', function (obj) {
                if (obj.event === 'add') {
                    showPositionEditModel(obj.event);
                } else if (obj.event === 'edit') {
                    showPositionEditModel(obj.event, selPositionObj);
                } else if (obj.event === 'del') {
                    doPositionDel(selPositionObj);
                }
            });

            /* ???????????????????????? */
            function showPositionEditModel(obj_event, obj) {
                if (obj_event === 'edit' && !obj) {
                    layer.msg('???????????????', {icon: 2});
                    return false;
                }
                admin.open({
                    type: 1,
                    title: (obj_event === 'edit' ? '??????' : '??????') + '???',
                    content: $('#positionDialog').html(),
                    success: function (layero, dIndex) {
                        let url = '';
                        let type = '';
                        // ??????????????????
                        if (obj_event === 'edit') {
                            $('#position_count_div').hide();
                            form.val('positionEditForm', obj.data);
                            url = `{{url('installLocation/position')}}/${obj.data.id}`;
                            type = 'PUT';
                        } else {
                            $('#position_name_div').hide();
                            form.val('positionEditForm', {'install_tier_unique_code': selTierObj.data.unique_code});
                            url = `{{url('installLocation/position')}}`;
                            type = 'POST';
                        }
                        // ????????????????????????
                        $(layero).children('.layui-layer-content').css('overflow', 'visible');
                        // ??????????????????
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
                                    positionTb.reload({
                                        url: `{{url('installLocation/position')}}?install_tier_unique_code=${selTierObj.data.unique_code}`,
                                    });
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

            /* ????????? */
            function doPositionDel(obj) {
                if (!obj) {
                    layer.msg('????????????', {icon: 2});
                    return false;
                }
                layer.confirm('???????????????????????????', {
                    skin: 'layui-layer-admin',
                    shade: .1
                }, function (i) {
                    layer.close(i);
                    let loadIndex = layer.load(2);
                    $.ajax({
                        url: `{{url('installLocation/position')}}/${obj.data.id}`,
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
    </script>
@endsection

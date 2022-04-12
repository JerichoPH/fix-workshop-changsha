@extends('Layout.index')
@section('style')
    <link rel="stylesheet" href="/EasyWeb/spa/assets/libs/layui/css/layui.css"/>
    <link rel="stylesheet" href="/EasyWeb/spa/assets/css/lite.css"/>
@stop
@section('content')
    <section class="content">
        <form>
        @include('Layout.alert')
            <div class="layui-fluid">
                <div class="layui-card">
                    <div class="layui-card-body">
                        <div class="layui-tab layui-steps layui-steps-readonly" lay-filter="stepsDemoForget" style="max-width: 100%;">
                            <ul class="layui-tab-title">
                                <li class="{{ request('type') == '1' ? "layui-this" : '' }}">
                                    <i class="layui-icon layui-icon-ok">1</i>
                                    <span class="layui-steps-title">第一步</span>
                                    <span class="layui-steps-content">选择现场设备</span>
                                </li>
                                <li class="{{ request('type') == '2' ? "layui-this" : '' }}">
                                    <i class="layui-icon layui-icon-ok">2</i>
                                    <span class="layui-steps-title">第二步</span>
                                    <span class="layui-steps-content">选择型号</span>
                                </li>
                                <li class="{{ request('type') == '3' ? "layui-this" : '' }}">
                                    <i class="layui-icon layui-icon-ok">3</i>
                                    <span class="layui-steps-title">第三步</span>
                                    <span class="layui-steps-content">更换设备</span>
                                </li>
                                <li class="{{ request('type') == '4' ? "layui-this" : '' }}">
                                    <i class="layui-icon layui-icon-ok">4</i>
                                    <span class="layui-steps-title">第四步</span>
                                    <span class="layui-steps-content">换型完成</span>
                                </li>
                            </ul>
                            <div class="layui-tab-content">
                                <div class="{{ request('type') == '1' ? 'layui-tab-item layui-show' : 'layui-tab-item' }}">
                                    <div class="box box-solid">
                                        <div class="box-header">
                                            <h3 class="box-title">搜索</h3>
                                            <div class="pull-right btn-group btn-group-sm">
                                                <div class="btn btn-default btn-flat" onclick="fnQuery('{{ $sn }}')"><i class="fa fa-search">&nbsp;</i>搜索</div>
                                            </div>
                                        </div>
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">种类</div>
                                                        <select id="selCategory" name="categoryUniqueCode" class="select2 form-control" style="width:100%;" onchange="fnSelectCategory(this.value)">
                                                            <option value="">全部</option>
                                                            @foreach($categories as $categoryUniqueCode=>$categoryName)
                                                                <option value="{{ $categoryUniqueCode }}" {{ request('categoryUniqueCode') == $categoryUniqueCode ? 'selected' : '' }}>{{ $categoryName }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="input-group-addon">类型</div>
                                                        <select id="selEntireModel" name="entireModelUniqueCode" class="select2 form-control" style="width:100%;" onchange="fnSelectEntireModel(this.value)">
                                                            <option value="">全部</option>
                                                        </select>
                                                        <div class="input-group-addon">型号</div>
                                                        <select id="selSubModel" name="subModelUniqueCode" class="select2 form-control" style="width:100%;">
                                                            <option value="">全部</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">供应商</div>
                                                        <select id="factories" class="form-control select2" name="factories" style="width:100%;">
                                                            <option value="" selected="selected">全部</option>
                                                            @foreach($factories as $factoryName)
                                                                <option {{ request('factoryName') == $factoryName ? 'selected' : '' }}>{{ $factoryName }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">设备编号</div>
                                                        <input type="text" id="entireInstanceUniqueCode" name="entireInstanceUniqueCode" class="form-control" value="{{ request('entireInstanceUniqueCode') ? request('entireInstanceUniqueCode') : '' }}" onkeydown="if(event.keyCode===13) fnQuery('{{ $sn }}')">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="sn" value="{{ request('sn') }}" />
                                                <input type="hidden" name="stationName" value="{{ request('stationName') }}" />
                                                <input type="hidden" name="type" value="1" />
                                                <input type="hidden" name="is_iframe" value="1" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box box-solid">
                                        <div class="box-body">
                                            <div class="table-responsive" style="overflow-x: auto; overflow-y: auto; height: 420px; display: flex; width:100%;margin: 0px">
                                                <table class="table table-hover table-striped table-condensed" id="table">
                                                    <thead>
                                                    <tr>
                                                        <th><input type="checkbox" name="all"></th>
                                                        <th>设备编号</th>
                                                        <th>所编号</th>
                                                        <th>型号</th>
                                                        <th>厂家</th>
                                                        <th>生产日期</th>
                                                        <th>出所日期</th>
                                                        <th>上道位置</th>
                                                        <th>状态</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($workshopEntireInstances as $workshopEntireInstance)
                                                        <tr>
                                                            <td><input type="checkbox" name="check" value="{{ $workshopEntireInstance->identity_code }}"/></td>
                                                            <td>{{ @$workshopEntireInstance->identity_code }}</td>
                                                            <td>{{ @$workshopEntireInstance->serial_number }}</td>
                                                            <td>{{ @$workshopEntireInstance->model_name }}</td>
                                                            <td>{{ @$workshopEntireInstance->factory_name }}</td>
                                                            <td>{{ substr(@$workshopEntireInstance->made_at, 0,10) }}</td>
                                                            <td>{{ substr(@$workshopEntireInstance->last_out_at, 0, 10) }}</td>
                                                            <td>{{ @$workshopEntireInstance->maintain_station_name . ' '. @$workshopEntireInstance->maintain_location_code }}</td>
                                                            <td>{{ \App\Model\EntireInstance::$STATUSES[$workshopEntireInstance->status] ?? '' }}</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if($workshopEntireInstances->hasPages())
                                                <div class="box-footer">
                                                    {{ $workshopEntireInstances->appends(request()->all())->links() }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="layui-form-item text-center">
                                        <div class="layui-btn layui-btn-radius" lay-filter="stepDemoFormSubmit1" lay-submit>下一步</div>
                                    </div>
                                </div>
                                <div class="{{ request('type') == '2' ? 'layui-tab-item layui-show' : 'layui-tab-item' }}">
                                    <div class="layui-form-item">
                                        <div class="box box-solid">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">种类</div>
                                                        <select id="selCategory1" name="categoryUniqueCode" class="select2 form-control" style="width:100%;" onchange="fnSelectCategory(this.value)">
                                                            <option value="">全部</option>
                                                            @foreach($categories as $categoryUniqueCode=>$categoryName)
                                                                <option value="{{ $categoryUniqueCode }}" {{ request('categoryUniqueCode') == $categoryUniqueCode ? 'selected' : '' }}>{{ $categoryName }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">类型</div>
                                                        <select id="selEntireModel1" name="entireModelUniqueCode" class="select2 form-control" style="width:100%;" onchange="fnSelectEntireModel(this.value)">
                                                            <option value="">全部</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">型号</div>
                                                        <select id="selSubModel1" name="subModelUniqueCode" class="select2 form-control" style="width:100%;">
                                                            <option value="">全部</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-form-item text-center">
                                        <a href="javascript:history.back(-1)" class="layui-btn layui-btn-primary layui-btn-radius">上一步</a>
                                        <div class="layui-btn layui-btn-radius" lay-filter="stepDemoFormSubmit2" lay-submit>下一步</div>
                                    </div>
                                </div>
                                <div class="{{ request('type') == '3' ? 'layui-tab-item layui-show' : 'layui-tab-item' }}">
                                    <div class="box box-solid">
                                        <div class="box-body">
                                            <div class="table-responsive" style="overflow-x: auto; overflow-y: auto; height: 420px; display: flex; width:100%;margin: 0px">
                                                <table class="table table-hover table-striped table-condensed" id="table">
                                                    <thead>
                                                    <tr>
                                                        <th><input type="checkbox" name="all1"></th>
                                                        <th>设备编号</th>
                                                        <th>所编号</th>
                                                        <th>型号</th>
                                                        <th>厂家</th>
                                                        <th>生产日期</th>
                                                        <th>仓库位置</th>
                                                        <th>状态</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($workshopEntireInstances1 as $workshopEntireInstance1)
                                                        <tr>
                                                            <td><input type="checkbox" name="check1" value="{{ $workshopEntireInstance1->identity_code }}"/></td>
                                                            <td>{{ @$workshopEntireInstance1->identity_code }}</td>
                                                            <td>{{ @$workshopEntireInstance1->serial_number }}</td>
                                                            <td>{{ @$workshopEntireInstance1->model_name }}</td>
                                                            <td>{{ @$workshopEntireInstance1->factory_name }}</td>
                                                            <td>{{ substr(@$workshopEntireInstance1->made_at, 0,10) }}</td>
                                                            @if(@$workshopEntireInstance1->position_name)
                                                                <td>
                                                                    <a href="javascript:" onclick="fnLocation(`{{ $workshopEntireInstance1->identity_code }}`)"><i class="fa fa-location-arrow"></i>
                                                                        {{ @$workshopEntireInstance1->storehous_name }}
                                                                        {{ @$workshopEntireInstance1->area_name }}
                                                                        {{ @$workshopEntireInstance1->platoon_name }}
                                                                        {{ @$workshopEntireInstance1->shelf_name }}
                                                                        {{ @$workshopEntireInstance1->tier_name }}
                                                                        {{ @$workshopEntireInstance1->position_name }}
                                                                    </a>
                                                                </td>
                                                            @else
                                                                <td></td>
                                                            @endif
                                                            <td>{{ \App\Model\EntireInstance::$STATUSES[$workshopEntireInstance1->status] ?? '' }}</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="layui-form-item text-center">
                                        <a href="javascript:history.back(-1)" class="layui-btn layui-btn-primary layui-btn-radius">上一步</a>
                                        <div class="layui-btn layui-btn-radius" lay-filter="stepDemoFormSubmit3" lay-submit>下一步</div>
                                    </div>
                                </div>
                                <div class="{{ request('type') == '4' ? 'layui-tab-item text-center layui-show' : 'layui-tab-item text-center' }}" style="padding: 40px 150px 10px 60px;">
                                    <i class="layui-icon layui-icon-ok layui-circle" style="background: #52C41A;color: #fff;font-size:30px;font-weight:bold;padding: 20px;line-height: 80px;"></i>
                                    <div style="font-size: 24px;color: #333;margin-top: 30px;">操作成功</div>
                                    <div style="text-align: center;margin: 50px 0 25px 0;">
                                        <a href="{{ url('v250ChangeModel') }}/changeModelList?sn={{ request('sn') }}&stationName={{ request('stationName') }}&type=1&is_iframe=1" class="layui-btn layui-btn-radius" lay-filter="stepDemoFormSubmit4" lay-submit>再次换型</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--仓库位置图-->
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" id="locationShow">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">位置：<span id="title"></span></h4>
                        </div>
                        <div class="modal-body">
                            <img id="location_img" class="model-body-location" alt="" style="width: 100%;">
                            <div class="spot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/EasyWeb/spa/assets/libs/layui/layui.js"></script>
    <script>
        let $select2 = $('.select2');
        let selEntireModel = $('#selEntireModel');
        let selSubModel = $('#selSubModel');
        let selEntireModel1 = $('#selEntireModel1');
        let selSubModel1 = $('#selSubModel1');

        $(function () {
            async function fnInitData() {
                await fnSelectCategory($('#selCategory').val());
            }

            fnInitData();

            if ($select2.length > 0) {
                $select2.select2();
            }
        });

        // 全选or全不选1
        var all = document.getElementsByName("all")[0];
        var checks = document.getElementsByName("check");
        // 实现全选和全不选
        all.onclick = function () {
            for (var i = 0; i < checks.length; i++) {
                checks[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择1
        for (var j = 0; j < checks.length; j++) {
            checks[j].onclick = function () {
                var count = 0; //定义一个计数器
                for (var m = 0; m < checks.length; m++) {
                    //判断是否取消全选
                    if (!checks[m].checked) {
                        all.checked = false;
                    } else { //如果是选中状态，计数器+1
                        count++;
                    }
                    //判断是否都是选中状态/如果是则自动选中全选按钮
                    if (count == checks.length) {
                        all.checked = true;
                    }
                }
            }
        }

        // 全选or全不选3
        var alls = document.getElementsByName("all1")[0];
        var checkss = document.getElementsByName("check1");
        // 实现全选和全不选
        alls.onclick = function () {
            for (var i = 0; i < checkss.length; i++) {
                checkss[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择3
        for (var j = 0; j < checkss.length; j++) {
            checkss[j].onclick = function () {
                var count = 0; //定义一个计数器
                for (var m = 0; m < checkss.length; m++) {
                    //判断是否取消全选
                    if (!checkss[m].checked) {
                        alls.checked = false;
                    } else { //如果是选中状态，计数器+1
                        count++;
                    }
                    //判断是否都是选中状态/如果是则自动选中全选按钮
                    if (count == checkss.length) {
                        alls.checked = true;
                    }
                }
            }
        }

        /**
         * 搜索
         * @param event
         * @returns {boolean}
         */
        function fnQuery(sn) {
            var type = {{ request('type') }};
            var stationName = '{{ request('stationName') }}';
            var categoryUniqueCode = $('#selCategory').val();
            var entireModelUniqueCode = $('#selEntireModel').val();
            var subModelUniqueCode = $('#selSubModel').val();
            var factoryName = $('#factories').val();
            var entireInstanceUniqueCode = $('#entireInstanceUniqueCode').val();
            location.href = `{{ url('v250ChangeModel') }}/changeModelList?sn=${sn}&categoryUniqueCode=${categoryUniqueCode}&entireModelUniqueCode=${entireModelUniqueCode}&subModelUniqueCode=${subModelUniqueCode}&factoryName=${factoryName}&entireInstanceUniqueCode=${entireInstanceUniqueCode}&stationName=${stationName}&type=${type}&is_iframe=1`;
        }

        layui.config({
            base: '/EasyWeb/spa/assets/module/'
        }).use(['layer', 'steps', 'form', 'admin', 'formX'], function () {
            var $ = layui.jquery;
            var layer = layui.layer;
            var steps = layui.steps;
            var form = layui.form;
            var admin = layui.admin;

            // 选择现场设备
            form.on('submit(stepDemoFormSubmit1)', function () {
                let oldIdentityCodes = [];
                $("input[type='checkbox'][name='check']:checked").each((index, item) => {
                    let new_code = $(item).val();
                    if (new_code !== '') oldIdentityCodes.push(new_code);
                });
                if (oldIdentityCodes.length <= 0) {
                    layer.msg('请先选择设备', {icon: 7});
                    return false;
                }
                var sn = '{{ request('sn') }}';
                var stationName = '{{ request('stationName') }}';
                admin.btnLoading('[lay-filter="stepDemoFormSubmit1"]');  // 加载动画
                setTimeout(function () {
                    location.href = `{{ url('v250ChangeModel') }}/changeModelList?sn=${sn}&stationName=${stationName}&oldIdentityCodes=${oldIdentityCodes}&type=2&is_iframe=1`;
                }, 600);
                return false;
            });

            // 选择型号
            form.on('submit(stepDemoFormSubmit2)', function () {
                var selSubModel1 = document.getElementById('selSubModel1').value;
                if (!selSubModel1) {
                    layer.msg('请选择型号', {icon: 7});
                    return false;
                }
                let oldIdentityCodes = '{{ request('oldIdentityCodes') }}';
                var stationName = '{{ request('stationName') }}';
                var sn = '{{ request('sn') }}';
                admin.btnLoading('[lay-filter="stepDemoFormSubmit2"]');
                setTimeout(function () {
                    $.ajax({
                        url: `{{ url('v250ChangeModel/changeModelList') }}`,
                        type: 'get',
                        data: {},
                        async: true,
                        success: function (res) {
                            location.href = `{{ url('v250ChangeModel') }}/changeModelList?sn=${sn}&stationName=${stationName}&subModel1=${selSubModel1}&oldIdentityCodes=${oldIdentityCodes}&type=3&is_iframe=1`;
                        },
                        error: function (err) {
                            if (err.status === 401) location.href = "{{ url('login') }}";
                            alert(err['responseJSON']['details']['message']);
                        }
                    });
                }, 600);
                return false;
            });

            // 更换设备
            form.on('submit(stepDemoFormSubmit3)', function () {
                var oldIdentityCodes= new Array(); //定义一数组
                oldIdentityCodes='{{ request('oldIdentityCodes') }}'.split(","); //字符分割
                let newIdentityCodes = [];
                $("input[type='checkbox'][name='check1']:checked").each((index, item) => {
                    let new_code = $(item).val();
                    if (new_code !== '') newIdentityCodes.push(new_code);
                });
                if (newIdentityCodes.length <= 0) {
                    layer.msg('请先选择设备', {icon: 7});
                    return false;
                }else if (oldIdentityCodes.length < newIdentityCodes.length) {
                    layer.msg('请取消勾选' + (newIdentityCodes.length-oldIdentityCodes.length) + '个设备', {icon: 7});
                    return false;
                }else if (oldIdentityCodes.length > newIdentityCodes.length) {
                    layer.msg('请再勾选' + (oldIdentityCodes.length-newIdentityCodes.length) + '个设备', {icon: 7});
                    return false;
                }
                admin.btnLoading('[lay-filter="stepDemoFormSubmit3"]');
                var stationName = '{{ request('stationName') }}';
                var sn = '{{ request('sn') }}';
                setTimeout(function () {
                    $.ajax({
                        url: `{{ url('v250ChangeModel/changeModel') }}`,
                        type: 'post',
                        data: {
                            'oldIdentityCodes': oldIdentityCodes,
                            'newIdentityCodes': newIdentityCodes,
                            'sn': sn,
                        },
                        async: true,
                        success: function (res) {
                            location.href = `{{ url('v250ChangeModel') }}/changeModelList?sn=${sn}&stationName=${stationName}&type=4&is_iframe=1`;
                        },
                        error: function (err) {
                            if (err.status === 401) location.href = "{{ url('login') }}";
                            alert(err['responseJSON']['details']['message']);
                            admin.btnLoading('[lay-filter="stepDemoFormSubmit3"]', false);
                        }
                    });
                }, 600);
                return false;
            });

        });

        /**
         * 选择种类，获取类型列表
         * @param {string} categoryUniqueCode
         */
        function fnSelectCategory(categoryUniqueCode) {
            let html = '<option value="">全部<option>';
            if (categoryUniqueCode !== '') {
                $.ajax({
                    url: `/query/entireModels/${categoryUniqueCode}`,
                    type: 'get',
                    data: {},
                    async: false,
                    success: res => {
                        $.each(res, (entireModelUniqueCode, entireModelName) => {
                            html += `<option value=${entireModelUniqueCode} ${"{{request('entireModelUniqueCode')}}" === entireModelUniqueCode ? 'selected' : ''}>${entireModelName}</option>`;
                        });
                        selEntireModel.html(html);
                        fnSelectEntireModel(selEntireModel.val());
                        selEntireModel1.html(html);
                        fnSelectEntireModel(selEntireModel1.val());
                    },
                    error: err => {
                        console.log(`query/entireModels/${categoryUniqueCode} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selEntireModel.html(html);
                selEntireModel1.html(html);
                selSubModel.html(html);
                selSubModel1.html(html);
            }
        }

        /**
         * 根据类型，获取型号列表
         * @param {string} entireModelUniqueCode
         */
        function fnSelectEntireModel(entireModelUniqueCode) {
            let html = '<option value="">全部<option>';
            if (entireModelUniqueCode !== '') {
                $.ajax({
                    url: `/query/subModels/${entireModelUniqueCode}`,
                    type: 'get',
                    data: {},
                    async: true,
                    success: res => {
                        $.each(res, (subModelUniqueCode, subModelName) => {
                            html += `<option value=${subModelUniqueCode} ${"{{request('subModelUniqueCode')}}" === subModelUniqueCode ? 'selected' : ''}>${subModelName}</option>`;
                        });
                        selSubModel.html(html);
                        selSubModel1.html(html);
                    },
                    error: err => {
                        console.log(`query/subModels/${entireModelUniqueCode} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selSubModel.html(html);
                selSubModel1.html(html);
            }
        }

        /**
         * 打印设备标签
         */
        function fnPrintQrCode(){
            // 处理数据
            let identityCodes = [];

            $("input[type='checkbox'][name='check']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') identityCodes.push(new_code);
            });
            if (identityCodes.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            // 保存需要打印的数据
            $.ajax({
                url: `{{ url('/warehouse/report/identityCodeWithPrint') }}`,
                type: 'post',
                data: {identityCodes},
                async: false,
                success: function (res) {
                    console.log(`{{ url('/warehouse/report/identityCodeWithPrint') }} success:`, res);
                    window.open(`{{url('qrcode/printQrCode')}}`, '_blank');
                    {{--window.location.href = `{{url('qrcode/printQrCode')}}`, '_blank';--}}
                },
                error: function (err) {
                    console.log(`{{ url('/warehouse/report/identityCodeWithPrint') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 查找位置
         * @param identity_code
         */
        function fnLocation(identity_code) {
            $.ajax({
                url: `{{url('storehouse/location/getImg')}}/${identity_code}`,
                type: 'get',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        console.log(response);
                        $('#title').text(response.data.location_full_name);
                        let location_img = response.data.location_img;
                        if (location_img) {
                            document.getElementById('location_img').src = location_img;
                            $("#locationShow").modal("show");
                        } else {
                            layer.msg('请联系管理员，绑定位置图片', {icon: 7});
                            // location.reload();
                        }
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }
    </script>
@endsection

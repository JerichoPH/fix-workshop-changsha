@extends('Layout.index')
@section('content')
    <section class="content">
        @include('Layout.alert')
        <form>
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
                        <input type="hidden" name="is_iframe" value="1" />
                    </div>
                </div>
            </div>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">待利旧设备列表</h3>
                    <!--右侧最小化按钮-->
                    <div class="pull-right btn-group btn-group-sm">
                        <a href="javascript:" class="btn btn-success btn-flat" onclick="fnStoreUseOld('{{ $sn }}')">利旧</a>
                        <a href="javascript:" class="btn btn-default btn-flat" onclick="fnPrintQrCode()">打印二维码</a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-condensed" id="table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" name="all"></th>
                                    <th>设备编号</th>
                                    <th>所编号</th>
                                    <th>型号</th>
                                    <th>厂家</th>
                                    <th>厂编号</th>
                                    <th>生产日期</th>
                                    <th>仓库位置</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($workshopEntireInstances as $workshopEntireInstance)
                                <tr>
                                    <td><input type="checkbox" name="check" value="{{ $workshopEntireInstance->identity_code }}"/></td>
                                    <td>{{ $workshopEntireInstance->identity_code }}</td>
                                    <td>{{ $workshopEntireInstance->serial_number }}</td>
                                    <td>{{ $workshopEntireInstance->model_name }}</td>
                                    <td>{{ $workshopEntireInstance->factory_name }}</td>
                                    <td>{{ $workshopEntireInstance->factory_device_code }}</td>
                                    <td>{{ $workshopEntireInstance->made_at ? date('Y-m-d',strtotime($workshopEntireInstance->made_at)) :'' }}</td>
                                    @if(@$workshopEntireInstance->position_name)
                                        <td>
                                            <a href="javascript:" onclick="fnLocation(`{{ $workshopEntireInstance->identity_code }}`)"><i class="fa fa-location-arrow"></i>
                                                {{ @$workshopEntireInstance->storehous_name }}
                                                {{ @$workshopEntireInstance->area_name }}
                                                {{ @$workshopEntireInstance->platoon_name }}
                                                {{ @$workshopEntireInstance->shelf_name }}
                                                {{ @$workshopEntireInstance->tier_name }}
                                                {{ @$workshopEntireInstance->position_name }}
                                            </a>
                                        </td>
                                    @else
                                        <td></td>
                                    @endif
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
        </form>
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
</section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let selEntireModel = $('#selEntireModel');
        let selSubModel = $('#selSubModel');

        $(function () {
            async function fnInitData() {
                await fnSelectCategory($('#selCategory').val());
            }

            fnInitData();

            if ($select2.length > 0) {
                $select2.select2();
            }
        });

        // 全选or全不选
        var all = document.getElementsByName("all")[0];
        var checks = document.getElementsByName("check");
        // 实现全选和全不选
        all.onclick = function () {
            for (var i = 0; i < checks.length; i++) {
                checks[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择
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

        /**
         * 搜索
         * @param event
         * @returns {boolean}
         */
        function fnQuery(sn) {
            var categoryUniqueCode = $('#selCategory').val();
            var entireModelUniqueCode = $('#selEntireModel').val();
            var subModelUniqueCode = $('#selSubModel').val();
            var factoryName = $('#factories').val();
            var entireInstanceUniqueCode = $('#entireInstanceUniqueCode').val();
            location.href = `{{ url('v250UseOld') }}/create?sn=${sn}&categoryUniqueCode=${categoryUniqueCode}&entireModelUniqueCode=${entireModelUniqueCode}&subModelUniqueCode=${subModelUniqueCode}&factoryName=${factoryName}&entireInstanceUniqueCode=${entireInstanceUniqueCode}&is_iframe=1`;
        }

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
                    },
                    error: err => {
                        console.log(`query/entireModels/${categoryUniqueCode} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selEntireModel.html(html);
                selSubModel.html(html);
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
                    },
                    error: err => {
                        console.log(`query/subModels/${entireModelUniqueCode} error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseText);
                    }
                });
            } else {
                selSubModel.html(html);
            }
        }

        /**
         * 利旧
         */
        function fnStoreUseOld(sn) {
            let identityCodes = [];
            $("input[type='checkbox'][name='check']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') identityCodes.push(new_code);
            });
            if (identityCodes.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            $.ajax({
                url: `{{ url('v250UseOld') }}`,
                type: 'post',
                data: {
                    'sn': sn,
                    'identityCodes': identityCodes,
                },
                async: true,
                success: function (res) {
                    location.reload();
                },
                error: function (err) {
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['details']['message']);
                }
            });
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
                            alert('请联系管理员，绑定位置图片');
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

@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            @if (empty(request('take_stock_unique_code')))
                盘点
            @else
                {{ $takeStock->name ?? '' }}
                盘点
            @endif
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">--}}
{{--                @if (empty(request('take_stock_unique_code')))--}}
{{--                    盘点--}}
{{--                @else--}}
{{--                    {{ $takeStock->name ?? '' }}--}}
{{--                    盘点--}}
{{--                @endif--}}
{{--            </li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <div class="row">
                            <div class="col-md-9">
                                <h3 class="box-title">扫描结果列表
                                    @if (empty(request('take_stock_unique_code')))
                                        <small>盘点未开始</small>
                                    @else
                                        <small>本次盘点设备预计:{{$takeStockMaterials->count()}}台</small>
                                    @endif
                                </h3>
                            </div>
                            <div class="col-md-3">
                                <div class="box-tools pull-right">
                                    @if (empty(request('take_stock_unique_code')))
                                        <a href="javascript:" onclick="fmReady()" class="btn btn-success btn-flat">开始盘点</a>
                                    @else
                                        <a href="javascript:" onclick="confirmTakeStock()" class="btn btn-success btn-flat">确认盘点</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if(empty(request('take_stock_unique_code')))
                            <br>
                            <form id="frmScreen">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="input-group">
                                            <div class="input-group-addon">盘点区域</div>
                                            <div class="input-group-addon">仓</div>
                                            <select id="selStorehouse" class="form-control select2" name="storehouse_unique_code" style="width:100%;" onchange="fnSelStorehouse(this.value)">
                                                <option value="">全部</option>
                                                @foreach($storehouses as $storehouse)
                                                    <option value="{{$storehouse->unique_code}}" {{request('storehouse_unique_code') == $storehouse->unique_code ? 'selected' : ''}}>{{$storehouse->name}}</option>
                                                @endforeach
                                            </select>
                                            <div class="input-group-addon">区</div>
                                            <select id="selArea" class="form-control select2" name="area_unique_code" style="width:100%;" onchange="fnSelArea(this.value)">

                                            </select>
                                            <div class="input-group-addon">排</div>
                                            <select id="selPlatoon" class="form-control select2" name="platoon_unique_code" style="width:100%;" onchange="fnSelPlatoon(this.value)">

                                            </select>
                                            <div class="input-group-addon">架</div>
                                            <select id="selShelf" class="form-control select2" name="shelf_unique_code" style="width:100%;" onchange="fnSelShelf(this.value)">

                                            </select>
                                            <div class="input-group-addon">层</div>
                                            <select id="selTier" class="form-control select2" name="tier_unique_code" style="width:100%;" onchange="fnSelTier(this.value)">

                                            </select>
                                            <div class="input-group-addon">位</div>
                                            <select id="selPosition" class="form-control select2" name="position_unique_code" style="width:100%;">

                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @endif

                    </div>
                    <div class="box-body">
                        <div class="input-group input-group-lg">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">唯一编码</button>
                            </div>
                            <input type="text" name="unique_code" id="uniqueCode" autofocus class="form-control" onkeydown="if(event.keyCode===15) fnOut(this.value)" onchange="fnTakeStock(this.value)" placeholder="扫码前点击"
                                   @if(empty($takeStock))
                                   disabled
                                @endif
                            >
                        </div>
                        <br>
                        <div class="table-responsive">
                            <table class="table table-hover table-condensed">
                                <tbody>
                                <tr>
                                    <th>库存编码</th>
                                    <th>实盘编码</th>
                                    <th>差异</th>
                                    <th>操作</th>
                                </tr>
                                @foreach($takeStockMaterials as $takeStockMaterial)
                                    <tr>
                                        <td>{{$takeStockMaterial->stock_identity_code}}</td>
                                        <td>{{$takeStockMaterial->real_stock_identity_code}}</td>
                                        <td class="{{$takeStockMaterial->difference['value'] =='='?'text-success':'text-danger'}}">{{$takeStockMaterial->difference['text']}}</td>
                                        <td>
                                            <a href="javascript:" onclick="fnDel('{{$takeStockMaterial->id}}')" class="btn btn-bitbucket btn-flat btn-sm">移除</a>
                                        </td>
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
@endsection
@section('script')
    <script type="text/javascript" src="/layer/layer.js"></script>
    <script>
        let take_stock_unique_code = '';
        let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select2();
            $("#uniqueCode").val('');
            fnSelStorehouse(``);
            fnSelArea(``);
            fnSelPlatoon(``);
            fnSelShelf(``);
            fnSelTier(``);
        });

        /**
         * 盘点准备
         */
        function fmReady() {
            let loading = layer.load(2, {shade: false});
            $.ajax({
                url: `{{url('storehouse/takeStock/takeStockReady')}}`,
                type: 'get',
                data: $('#frmScreen').serialize(),
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        location.href = `{{url('storehouse/takeStock/startTakeStock')}}?take_stock_unique_code=${response.data.take_stock_unique_code}`
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                    layer.close(loading);
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }

        /**
         * 扫码
         * @param unique_code
         */
        function fnTakeStock(unique_code) {
            $.ajax({
                url: `{{url('storehouse/takeStock/takeStockMaterialStore')}}/${unique_code}`,
                type: 'get',
                data: {
                    take_stock_unique_code: `{{request('take_stock_unique_code')}}`
                },
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {

                    } else {
                        alert(response.message)
                    }
                    location.reload()
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }

        /**
         * 移除
         * @param id
         */
        function fnDel(id) {
            $.ajax({
                url: `{{url('storehouse/takeStock/takeStockMaterialDestory')}}/${id}`,
                type: 'delete',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {

                    } else {
                        alert(response.message)
                    }
                    location.reload();
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }

        /**
         * 确认盘点
         */
        function confirmTakeStock() {
            let loading = layer.load(2, {shade: false});
            $.ajax({
                url: `{{url('storehouse/takeStock/takeStock')}}/{{request('take_stock_unique_code')}}`,
                type: 'get',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        location.href = `{{url('storehouse/takeStock')}}/{{request('take_stock_unique_code')}}`;
                    } else {
                        alert(response.message);
                        layer.close(loading);
                        location.reload();
                    }
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }


        /**
         *选择仓
         */
        function fnSelStorehouse(unique_code) {
            let html = `<option value="">全部</option>`;
            if (unique_code && unique_code !== '') {
                $.ajax({
                    url: `{{url('storehouse/location/getAreaJson')}}/${unique_code}`,
                    type: 'get',
                    async: false,
                    success: response => {
                        response = JSON.parse(response);
                        $.each(response.data, function (k, value) {
                            html += `<option value="${value.unique_code}" ${value.unique_code === "{{request('area_unique_code')}}" ? 'selected' : ''}>${value.name}</option>`;
                        });
                    },
                    error: error => {
                        console.log(`error:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.message);
                        location.reload();
                    }
                });
            }
            $("#selArea").html(html);
            $("#selPlatoon").html(`<option value="">全部</option>`);
            $("#selShelf").html(`<option value="">全部</option>`);
            $("#selTier").html(`<option value="">全部</option>`);
            $("#selPosition").html(`<option value="">全部</option>`);
        }

        /**
         * 选择区
         */
        function fnSelArea(unique_code) {
            let html = `<option value="">全部</option>`;
            if (unique_code && unique_code !== '') {
                $.ajax({
                    url: `{{url('storehouse/location/getPlatoonJson')}}/${unique_code}`,
                    type: 'get',
                    async: false,
                    success: response => {
                        response = JSON.parse(response);
                        $.each(response.data, function (k, value) {
                            html += `<option value="${value.unique_code}" ${value.unique_code === "{{request('platoon_unique_code')}}" ? 'selected' : ''}>${value.name}</option>`;
                        });
                    },
                    error: error => {
                        console.log(`error:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.message);
                        location.reload();
                    }
                });
            }
            $("#selPlatoon").html(html);
            $("#selShelf").html(`<option value="">全部</option>`);
            $("#selTier").html(`<option value="">全部</option>`);
            $("#selPosition").html(`<option value="">全部</option>`);
        }

        /**
         * 选择排
         */
        function fnSelPlatoon(unique_code) {
            let html = `<option value="">全部</option>`;
            if (unique_code && unique_code !== '') {
                $.ajax({
                    url: `{{url('storehouse/location/getShelfJson')}}/${unique_code}`,
                    type: 'get',
                    async: false,
                    success: response => {
                        response = JSON.parse(response);
                        $.each(response.data, function (k, value) {
                            html += `<option value="${value.unique_code}" ${value.unique_code === "{{request('shelf_unique_code')}}" ? 'selected' : ''}>${value.name}</option>`;
                        });
                    },
                    error: error => {
                        console.log(`error:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.message);
                        location.reload();
                    }
                });
            }
            $("#selShelf").html(html);
            $("#selTier").html(`<option value="">全部</option>`);
            $("#selPosition").html(`<option value="">全部</option>`);
        }

        /**
         * 选择架
         */
        function fnSelShelf(unique_code) {
            let html = `<option value="">全部</option>`;
            if (unique_code && unique_code !== '') {
                $.ajax({
                    url: `{{url('storehouse/location/getTierJson')}}/${unique_code}`,
                    type: 'get',
                    async: false,
                    success: response => {
                        response = JSON.parse(response);
                        $.each(response.data, function (k, value) {
                            html += `<option value="${value.unique_code}" ${value.unique_code === "{{request('tier_unique_code')}}" ? 'selected' : ''}>${value.name}</option>`;
                        });
                    },
                    error: error => {
                        console.log(`error:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.message);
                        location.reload();
                    }
                });
            }
            $("#selTier").html(html);
            $("#selPosition").html(`<option value="">全部</option>`);
        }

        /**
         * 选择层
         */
        function fnSelTier(unique_code) {
            let html = `<option value="">全部</option>`;
            if (unique_code && unique_code !== '') {
                $.ajax({
                    url: `{{url('storehouse/location/position')}}/${unique_code}`,
                    type: 'get',
                    async: false,
                    success: response => {
                        response = JSON.parse(response);
                        $.each(response.data, function (k, value) {
                            html += `<option value="${value.unique_code}" ${value.unique_code === "{{request('position_unique_code')}}" ? 'selected' : ''}>${value.name}</option>`;
                        });
                    },
                    error: error => {
                        console.log(`error:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.message);
                        location.reload();
                    }
                });
            }
            $("#selPosition").html(html);
        }

    </script>
@endsection

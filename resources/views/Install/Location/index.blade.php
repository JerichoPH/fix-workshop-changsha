@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            上道位置打印标签
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-body form-horizontal">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="input-group">
                                    <div class="input-group-addon">现场车间</div>
                                    <select id="selWorkshop" class="select2 form-control" onchange="fnSelectWorkshop(this.value)" style="width:100%;">
                                        <option value="">全部</option>
                                        @foreach($workshops as $workshopUniqueCode=>$workshopName)
                                            <option value="{{ $workshopUniqueCode }}" {{ request()->get('workshop_unique_code') == $workshopUniqueCode ? 'selected' : '' }}>{{ $workshopName }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-addon">车站</div>
                                    <select id="selStation" class="select2 form-control" style="width:100%;" onchange="fnSelectStation(this.value)">

                                    </select>
                                    <div class="input-group-addon">排</div>
                                    <select id="selInstallPlatoon" class="select2 form-control" style="width:100%;" onchange="fnSelectPlatoon(this.value)">

                                    </select>
                                    <div class="input-group-addon">架柜</div>
                                    <select id="selInstallShelf" class="select2 form-control" style="width:100%;" onchange="fnSelectShelf(this.value)">

                                    </select>
                                    <div class="input-group-addon">层</div>
                                    <select id="selInstallTier" class="select2 form-control" style="width:100%;">

                                    </select>
                                    <div class="input-group-btn">
                                        <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">位置打印</h3>
                <div class="box-tools pull-right">
                    @switch(env('ORGANIZATION_CODE'))
                        @case('B050')
                        <a href="javascript:" onclick="print(3)" class="btn btn-primary btn-flat"><i class="fa fa-print"></i>&nbsp;打印标签(40*25)</a>
                        @break
                        @default
                    @endswitch
                </div>
            </div>

            <div class="box-body material-message">
                <table class="table table-hover table-condensed">
                    <tbody>
                    <tr>
                        <th>
                            <input type="checkbox" class="checkbox-toggle">
                        </th>
                        <th>名称（车间/车站/机房/排/架柜/层）</th>
                        <th>层编码</th>
                    </tr>
                    @foreach($locations as $location)
                        <tr>
                            <td><input type="checkbox" name="locationUniqueCodes" value="{{ $location->unique_code }}"></td>
                            <td>
                                {{ $location->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->Parent->name }}&emsp;
                                {{ $location->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name }}&emsp;
                                {{ $location->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text }}&emsp;
                                {{ $location->WithInstallShelf->WithInstallPlatoon->name }}&emsp;
                                {{ $location->WithInstallShelf->name }}&emsp;
                                {{ $location->name }}
                            </td>
                            <td>{{ $location->unique_code }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select2();
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });

            fnSelectWorkshop(`{{ request()->get('workshop_unique_code') }}`);
            fnSelectStation(`{{ request()->get('station_unique_code') }}`);
            fnSelectPlatoon(`{{ request()->get('install_platoon_unique_code') }}`);
            fnSelectShelf(`{{ request()->get('install_shelf_unique_code') }}`);
        });

        /**
         * 选择现场车间
         * @param workshop_unique_code
         */
        function fnSelectWorkshop(workshop_unique_code) {
            let html = `<option value="">全部</option>`;
            $.ajax({
                url: `{{url('query/stations')}}`,
                type: 'get',
                data: {
                    sceneWorkshopUniqueCode: workshop_unique_code
                },
                async: false,
                success: response => {
                    console.log(`success:`, response);
                    $.each(response, function (station_unique_code, station_name) {
                        html += `<option value="${station_unique_code}" ${station_unique_code === "{{request()->get('station_unique_code')}}" ? 'selected' : ''}>${station_name}</option>`;
                    })
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{url('login')}}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
            $("#selStation").html(html);
        }

        /**
         * 选择车站
         * @param station_unique_code
         */
        function fnSelectStation(station_unique_code) {
            let html = `<option value="">全部</option>`;
            $.ajax({
                url: `{{url('installLocation/platoon/platoonWithStation')}}`,
                type: 'get',
                data: {
                    station_unique_code: station_unique_code
                },
                async: false,
                success: response => {
                    console.log(`success:`, response);
                    $.each(response.data, function (k, platoon) {
                        html += `<option value="${platoon.unique_code}" ${platoon.unique_code === "{{request()->get('install_platoon_unique_code')}}" ? 'selected' : ''}>${platoon.name}</option>`;
                    })
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{url('login')}}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
            $("#selInstallPlatoon").html(html);
            $("#selInstallShelf").html(`<option value="">全部</option>`);
            $("#selInstallTier").html(`<option value="">全部</option>`);
        }

        /**
         * 选择排
         * @param install_platoon_unique_code
         */
        function fnSelectPlatoon(install_platoon_unique_code) {
            let html = `<option value="">全部</option>`;
            $.ajax({
                url: `{{url('installLocation/shelf')}}`,
                type: 'get',
                data: {
                    install_platoon_unique_code: install_platoon_unique_code
                },
                async: false,
                success: response => {
                    console.log(`success:`, response);
                    response = JSON.parse(response);
                    $.each(response.data, function (k, value) {
                        html += `<option value="${value.unique_code}" ${value.unique_code === "{{request()->get('install_shelf_unique_code')}}" ? 'selected' : ''}>${value.name}</option>`;
                    });
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{url('login')}}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
            $("#selInstallShelf").html(html);
            $("#selInstallTier").html(`<option value="">全部</option>`);
        }

        /**
         * 选择架
         * @param install_shelf_unique_code
         */
        function fnSelectShelf(install_shelf_unique_code) {
            let html = `<option value="">全部</option>`;
            $.ajax({
                url: `{{url('installLocation/tier')}}`,
                type: 'get',
                data: {
                    install_shelf_unique_code: install_shelf_unique_code
                },
                async: false,
                success: response => {
                    console.log(`success:`, response);
                    response = JSON.parse(response);
                    $.each(response.data, function (k, value) {
                        html += `<option value="${value.unique_code}" ${value.unique_code === "{{request()->get('install_tier_unique_code')}}" ? 'selected' : ''}>${value.name}</option>`;
                    });
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{url('login')}}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
            $("#selInstallTier").html(html);
        }

        /**
         * 查询
         */
        function fnScreen() {
            location.href = `{{ url('installLocation') }}?workshop_unique_code=${$('#selWorkshop').val()}&station_unique_code=${$('#selStation').val()}&install_platoon_unique_code=${$('#selInstallPlatoon').val()}&install_shelf_unique_code=${$('#selInstallShelf').val()}&install_tier_unique_code=${$('#selInstallTier').val()}`;
        }

        /**
         * 打印标签
         */
        function print(type) {
            let locationUniqueCodes = [];
            $('input[name="locationUniqueCodes"]:checked').each(function (index, element) {
                locationUniqueCodes.push($(this).val());
            });
            if (locationUniqueCodes.length > 0) {
                window.open(`{{url('qrcode/installLocation')}}?type=${type}&locationUniqueCodes=${locationUniqueCodes}`);
            } else {
                alert('请选择');
            }
        }
    </script>
@endsection

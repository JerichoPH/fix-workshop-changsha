@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            打印标签
            <small>更换设备</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">打印标签</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="box-title">打印标签</h3>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group pull-right">
                            <div class="input-group-btn"><a href="{{ url('repairBase/exchangeModelOrder',$in_sn) }}?direction=IN" class="btn btn-flat btn-default"><i class="fa fa-arrow-left">&nbsp;</i>返回</a></div>
                            <div class="input-group-addon">唯一编号/所编号</div>
                            <input type="text" name="serial_content" id="txtSearchContent" class="form-control" value="{{ request('search_content') }}" onchange="fnSearch()">
{{--                            <div class="input-group-btn"><a href="javascript:" onclick="fnSearch()" class="btn btn-default btn-flat">搜索</a></div>--}}
                            <div class="input-group-btn"><a href="javascript:" onclick="downloadLabel()" class="btn btn-default btn-flat">打印标签</a></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="checkbox-toggle">
                        </th>
                        <th>唯一编号/所编号</th>
                        <th>型号</th>
                        <th>组合位置/道岔号</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($entire_instances as $entire_instance)
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" name="labelChecked" value="{{ $entire_instance->OldEntireInstance->identity_code }},{{ $entire_instance->OldEntireInstance->model_name }},{{ $entire_instance->OldEntireInstance->serial_number }}">
                                </label>
                            </td>
                            <td>{{ $entire_instance->OldEntireInstance->identity_code }}/{{ $entire_instance->OldEntireInstance->serial_number }}</td>
                            <td>{{ $entire_instance->OldEntireInstance->model_name }}</td>
                            <td>
                                {{ $entire_instance->OldEntireInstance->maintain_location_code }}
                                {{ $entire_instance->OldEntireInstance->crossroad_number }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($entire_instances->hasPages())
                <div class="box-footer">
                    {{ $entire_instances->appends(['search_content','direction'])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        /**
         *下载标签
         */
        downloadLabel = () => {
            //处理数据
            let selected_for_api = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each(function () {
                let value = $(this).val().split(",");
                selected_for_api.push(value[0]);
            });

            if (selected_for_api.length > 0) {
                window.open(`{{url('qrcode/printQrCode')}}?identityCodes=${JSON.stringify(selected_for_api)}`);
            } else {
                alert('无数据')
            }
        };

        /**
         *下载标签
         */
        downloadLabelbak1 = () => {
            $.ajax({
                url: `{{url('qrcode/generateQrcode')}}`,
                type: 'get',
                data: {
                    contents: selected_for_api
                },
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        let html = ``;
                        $.each(response.data, function (identity_code, img) {
                            console.log(img);
                            html += `<img src=${img} alt="">`;
                        });
                        $("#qrcode").html(html);
                        $("#qrcodeBox").modal("show");
                    } else {
                        alert(response.message);
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
            //处理数据
            let selected_for_print = [];
            let selected_for_api = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each(function () {
                let value = $(this).val().split(",");
                let rfid_code = value[2];
                if (rfid_code.length > 0) {
                    rfid_code = `(${rfid_code})`;
                }
                selected_for_print.push(`,${value[1]},${rfid_code},,${value[0]}`);
                selected_for_api.push(value[0]);
            });

            if (selected_for_api.length > 0) {
                $.ajax({
                    url: `/warehouse/report/printNormalLabel?type={{request('type')}}`,
                    type: 'post',
                    data: {identityCodes: selected_for_api},
                    async: true,
                    success: response => {
                        console.log(response);
                        let content = 'maintain_station_name,entire_model,tid,maintain_location_code,identity_code\r\n' + selected_for_print.join('\r\n');
                        let filename = "label-print.txt";
                        var element = document.createElement('a');
                        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
                        element.setAttribute('download', filename);
                        element.style.display = 'none';
                        document.body.appendChild(element);
                        element.click();
                        document.body.removeChild(element);

                    },
                    fail: error => {
                        console.log(`?type={{request('type')}} fail:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    }
                });
            } else {
                alert('无数据')
            }
        };


        /**
         *下载标签
         */
        downloadLabelbak = () => {
            //处理数据
            let selected_for_print = [];
            let selected_for_api = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each(function () {
                let value = $(this).val().split(",");
                let identity_code = `${value[0]}`;
                let entire_model = `${value[1]}`;
                let serial_number = value[2];
                let tmp = [];
                tmp.push({
                    "content": entire_model,
                    "type": "text",
                    "attribute": "20, 80, 25, 0, 0, 0"
                })
                tmp.push({
                    "content": serial_number,
                    "type": "text",
                    "attribute": "260, 80, 25, 0, 0, 0"
                })
                if (identity_code.substr(0, 1) === 'Q') {
                    tmp.push({
                        "content": identity_code,
                        "type": "text",
                        "attribute": "10,115,128,80,1,0,2,2"
                    })
                }
                if (identity_code.substr(0, 1) === 'S') {
                    tmp.push({
                        "content": identity_code,
                        "type": "text",
                        "attribute": "40,115,128,80,1,0,2,2"
                    })
                }
                selected_for_print.push({'data': tmp})
                selected_for_api.push(value[0]);
            });

            if (selected_for_api.length > 0) {
                $.ajax({
                    url: `/warehouse/report/printNormalLabel?type={{request('type')}}`,
                    type: 'post',
                    data: {identityCodes: selected_for_api},
                    async: true,
                    success: response => {
                        console.log(response);
                        if (selected_for_print.length > 0) {
                            $.each(selected_for_print, function (key, value) {
                                let content = JSON.stringify(value);
                                let timestamp = new Date().getTime();
                                let num = Math.floor(Math.random() * (9999 - 1000)) + 1000;
                                let filename = "device." + timestamp + num + ".rnvp.json";
                                var element = document.createElement('a');
                                element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
                                element.setAttribute('download', filename);
                                element.style.display = 'none';
                                document.body.appendChild(element);
                                element.click();
                                document.body.removeChild(element);
                            })
                        }
                    },
                    fail: error => {
                        console.log(`?type={{request('type')}} fail:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    }
                });
            } else {
                alert('无数据')
            }
        };

        $(function () {
            // iCheck for checkbox and radio inputs
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $("#table input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $("#table input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });

            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');

            $('#reservation').daterangepicker({
                locale: {
                    format: "YYYY-MM-DD",
                    separator: "~",
                    daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
                    monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    applyLabel: "确定",
                    cancelLabel: "取消",
                    fromLabel: "开始时间",
                    toLabel: "结束时间",
                    customRangeLabel: "自定义",
                    weekLabel: "W",
                },
                startDate: "",
                endDate: ""
            });

        });

        /**
         * 搜索
         */
        function fnSearch() {
            location.href = `?search_content=${$('#txtSearchContent').val()}`;
        }
    </script>
@endsection

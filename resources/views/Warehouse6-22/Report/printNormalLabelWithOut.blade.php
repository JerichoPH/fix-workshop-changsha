@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/skins/_all-skins.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            打印标签
            <small>备品或状态修</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">打印标签（备品或状态修）</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')

        <div class="row">
            <div class="col-md-12">
                <div class="box">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-8">
                                <h3 class="box-title">成品列表</h3>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <div class="input-group-addon">旧设备：</div>
                                    <input id="txtSearchCondition" type="text" class="form-control" onkeydown="if(event.keyCode===13) fnSearch(this.value)" onchange="fnSearch(this.value)" value="{{request('identityCode')}}">
                                </div>
                            </div>
                        </div>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right"></div>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-hover table-condensed" id="table">
                            <thead>
                            <tr>
                                <th>唯一编号</th>
                                <th>所编号</th>
                                <th>型号</th>
                                <th>替换</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if(!empty($entireInstances))
                                @foreach($entireInstances as $entireInstance)
                                    <tr>
                                        <td>{{$entireInstance->identity_code}}</td>
                                        <td>{{$entireInstance->serial_number}}</td>
                                        <td>{{$entireInstance->model_name}}</td>
                                        <td>
                                            <a href="javascript:" onclick="fnSelect('{{$entireInstance->identity_code}}')" class="btn btn-sm btn-flat btn-success">
                                                <i class="fa fa-check"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($entireInstances))
                        @if($entireInstances->hasPages())
                            <div class="box-footer">
                                {{ $entireInstances->appends(['type'=>'OUT','identityCode'=>request('identityCode')])->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

    </section>
@endsection
@section('script')
    <script>
        $select2 = $('.select2');

        $(function () {
            if ($select2.length > 0) $select2.select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,
                    lengthChange: true,
                    searching: false,
                    ordering: true,
                    info: true,
                    autoWidth: true,
                    aLengthMenu: [[-1, 100, 200, 500], ["全部", "100条", "200条", "500条"]],
                    language: {
                        sProcessing: "正在加载中...",
                        info: "显示第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "显示 _MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "结果中查询：",
                        paginate: {
                            sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"
                        }
                    }
                });
            }
        });

        /**
         * 搜索设备
         * @param value
         */
        fnSearch = value => {
            location.href = `?type={{request('type')}}&identityCode=${value}`;
        };


        /**
         * 选择替换设备
         * @param identityCode
         */
        fnSelect = identityCode => {
            let oldIdentityCode = `{{request('identityCode')}}`;
            if (oldIdentityCode.length > 0) {
                $.ajax({
                    url: `?type={{request('type')}}`,
                    type: 'post',
                    data: {oldIdentityCode: oldIdentityCode, newIdentityCode: identityCode},
                    async: true,
                    success: response => {
                        console.log(`success:`, response);
                        let rfid_code = response.rfid_code;
                        if (rfid_code != null) {
                            rfid_code = rfid_code.substr(-4);
                        } else {
                            rfid_code = '';
                        }
                        let content = `maintain_station_name,entire_model,tid,maintain_location_code,identity_code\r\n${response.maintain_station_name},${response.model_name},${rfid_code},${response.maintain_location_code},${response.identity_code}`;
                        let filename = "label-print.txt";
                        var element = document.createElement('a');
                        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
                        element.setAttribute('download', filename);
                        element.style.display = 'none';
                        document.body.appendChild(element);
                        element.click();
                        document.body.removeChild(element);

                        location.reload();
                    },
                    fail: error => {
                        console.log(`?type={{request('type')}}&identityCode={{request('identityCode')}} fail:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    }
                });


            } else {
                alert('旧编码不能为空，请扫码')
            }
        };

        /**
         * 选择替换设备
         * @param identityCode
         */
        fnSelectbak = identityCode => {
            let oldIdentityCode = `{{request('identityCode')}}`;
            if (oldIdentityCode.length > 0) {
                $.ajax({
                    url: `?type={{request('type')}}`,
                    type: 'post',
                    data: {oldIdentityCode: oldIdentityCode, newIdentityCode: identityCode},
                    async: true,
                    success: response => {
                        console.log(`success:`, response);
                        // let rfid_code = response.rfid_code;
                        // if (rfid_code != null) {
                        //     rfid_code = rfid_code.substr(-4);
                        // } else {
                        //     rfid_code = '';
                        // }
                        // let content = `maintain_station_name,entire_model,tid,maintain_location_code,identity_code\r\n${response.maintain_station_name},${response.model_name},${rfid_code},${response.maintain_location_code},${response.identity_code}`;
                        let identity_code = `${response.identity_code}`;
                        let tmp = [];
                        tmp.push({
                            "content": `${response.maintain_station_name}`,
                            "type": "text",
                            "attribute": "35, 65, 40, 0, 0, 0"
                        });
                        tmp.push({
                            "content": `${response.maintain_location_code}`,
                            "type": "text",
                            "attribute": "35, 125, 60, 0, 0, 0"
                        });
                        if (identity_code.substr(0, 1) === 'Q') {
                            tmp.push({
                                "content": identity_code,
                                "type": "text",
                                "attribute": "105, 200, 30, 0, 0, 0"
                            })
                        }
                        if (identity_code.substr(0, 1) === 'S') {
                            tmp.push({
                                "content": identity_code,
                                "type": "text",
                                "attribute": "170, 200, 30, 0, 0, 0"
                            })
                        }
                        let selected_for_print = {'data': tmp};
                        let content = JSON.stringify(selected_for_print);
                        let timestamp = new Date().getTime();
                        let num = Math.floor(Math.random() * (9999 - 1000)) + 1000;
                        let filename = "locations." + timestamp + num + ".rnvp.json";
                        var element = document.createElement('a');
                        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
                        element.setAttribute('download', filename);
                        element.style.display = 'none';
                        document.body.appendChild(element);
                        element.click();
                        document.body.removeChild(element);

                        location.reload();
                    },
                    fail: error => {
                        console.log(`?type={{request('type')}}&identityCode={{request('identityCode')}} fail:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    }
                });


            } else {
                alert('旧编码不能为空，请扫码')
            }
        };
    </script>
@endsection

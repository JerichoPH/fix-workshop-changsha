@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css" xmlns="http://www.w3.org/1999/html">
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
            <small>周期修</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">打印标签（周期修）</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header">
                <div class="row">
                    <div class="col-md-4">
                        <h3 class="box-title">打印标签 {{$oldEntireInstances->total()}}</h3>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group">
                            <div class="input-group-addon">车站</div>
                            <select class="form-control select2" id="selStation" style="width: 100%;" onchange="fnReload()">
                                <option value="">全部</option>
                                @foreach($stations as $station_name)
                                    <option value="{{$station_name}}" {{$current_station_name == $station_name ? 'selected' : ''}}>{{$station_name}}</option>
                                @endforeach
                            </select>
                            <div class="input-group-addon">月份</div>
                            <select class="form-control select2" id="selMonth" style="width: 100%;" onchange="fnReload()">
                                <option value="">全部</option>
                                @foreach($dateLists as $dateList)
                                    <option value="{{$dateList}}" {{request('date') == $dateList ? 'selected' : ''}} >
                                        {{$dateList}}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-addon">型号</div>
                            <select class="form-control select2" id="selModelName" style="width: 100%;" onchange="fnReload()">
                                <option value="">全部</option>
                                @foreach($current_model_names as $model_name)
                                    <option value="{{$model_name}}" {{request('model_name') == $model_name ? 'selected' : ''}} >
                                        {{$model_name}}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-btn">
                                <a href="javascript:" onclick="downloadLabel()" class="btn btn-default btn-flat">打印标签</a>
                            </div>
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
                        <th>选择</th>
                        <th>周期修时间</th>
                        <th>唯一编号</th>
                        <th>所编号</th>
                        <th>型号</th>
                        <th>位置</th>
                        <th>替换</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($oldEntireInstances as $oldEntireInstance)
                        <tr>
                            <td><input type="checkbox" class="minimal-red" name="labelChecked" value="{{$oldEntireInstance->identity_code}}"></td>
                            <td>{{explode(' ',$oldEntireInstance->next_fixing_day)[0]}}</td>
                            <td>{{$oldEntireInstance->identity_code}}</td>
                            <td>{{$oldEntireInstance->serial_number}}</td>
                            <td>{{$oldEntireInstance->model_name}}</td>
                            <td>
                                {{$oldEntireInstance->maintain_station_name}}
                                {{$oldEntireInstance->maintain_location_code}}
                            </td>
                            <td style="width: 20%;">
                                <select class="form-control select2 select-for-print" name="{{$oldEntireInstance->model_name}}:{{$oldEntireInstance->maintain_station_name}}:{{$oldEntireInstance->maintain_location_code}}" id="{{$oldEntireInstance->identity_code}}" style="width: 100%;">
                                    @if(array_key_exists($oldEntireInstance->model_name,$newEntireInstances))
                                        <option value="">未选择</option>
                                        @foreach($newEntireInstances[$oldEntireInstance->model_name] as $code)
                                            <option value="{{$code}}" {{explode('_',$code)[0] == $cycleFixWithoutLocation[$oldEntireInstance->identity_code] ? 'selected' : ''}}>
                                                {{explode('_',$code)[0]}}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="">无</option>
                                    @endif
                                </select>
                                {{--                                <input type="text" name="{{$oldEntireInstance->identity_code}}" class="form-control" value="" placeholder="扫描条形码">--}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($oldEntireInstances->hasPages())
                <div class="box-footer">
                    {{ $oldEntireInstances->appends([
                        'type'=>'CYCLE_FIX','date'=>request('date',''),
                        'station_name'=>$current_station_name,
                        'model_name'=>$current_model_name,
                        ])->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $selMonth = $('#selMonth');
        let $selStation = $('#selStation');
        let $selModelName = $('#selModelName');

        $(function () {
            let originAt = moment().startOf('month').format('YYYY-MM-DD');
            let finishAt = moment().endOf('month').format('YYYY-MM-DD');
            //Red color scheme for iCheck
            $('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red').iCheck({
                checkboxClass: 'icheckbox_minimal-red',
                radioClass: 'iradio_minimal-red'
            });
            if ($select2.length > 0) $select2.select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,
                    lengthChange: true,
                    searching: false,
                    ordering: true,
                    info: true,
                    autoWidth: true,
                    language: {
                        sProcessing: "正在加载中...",
                        info: "显示第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "显示 _MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "查询：",
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });
            };
        });

        /**
         *下载标签
         */
        function downloadLabel() {
            //处理数据
            let selected_for_api = {};
            let selected_for_data = {};
            let selected_for_List = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let old_id = $(item).val();
                selected_for_data[old_id] = $(item).closest('tr').find('td').eq(1).text();
                let selected = $(`#${old_id}`);
                let new_tmp = selected.val();
                if (new_tmp) {
                    let tmp = new_tmp.split('_');
                    let new_id = tmp[0];
                    selected_for_api[old_id] = new_id;
                    selected_for_List.push({
                        new_id: new_id,
                    });

                } else {
                    selected_for_api[old_id] = "";
                }
            });

            let selected_for_api_key = Object.keys(selected_for_api);
            if (selected_for_api_key.length > 0) {
                $.ajax({
                    url: `?type=CYCLE_FIX`,
                    type: 'post',
                    data: {
                        identityCode: selected_for_api,
                        selectedForData: selected_for_data
                    },
                    async: true,
                    success: function (response) {
                        console.log(` success:`, response);
                        if (response.successes > 0) {
                            let fail_old = response.no_exists.old;
                            let fail_new = response.no_exists.new;
                            let selected_for_print = [];
                            $.each(selected_for_List, (key, value) => {
                                if ($.inArray(value.new_id, fail_new) === -1 && $.inArray(value.old_id, fail_old) === -1) {
                                    selected_for_print.push(`${value.new_id}`);
                                }
                            });

                            if (selected_for_print.length > 0) {
                                window.open(`{{url('qrcode/printLabel')}}?identityCodes=${JSON.stringify(selected_for_print)}`);
                            }
                        }
                    },
                    error: function (error) {
                        console.log(` error:`, error);
                    }
                });
            } else {
                alert('请选择标签')
            }
        }

        /**
         *下载标签
         */
        function downloadLabelbak1() {
            //处理数据
            let selected_for_api = {};
            let selected_for_data = {};
            let selected_for_List = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let old_id = $(item).val();
                selected_for_data[old_id] = $(item).closest('tr').find('td').eq(1).text();
                let selected = $(`#${old_id}`);
                let new_tmp = selected.val();
                if (new_tmp) {
                    let tmp = new_tmp.split('_');
                    let new_id = tmp[0];
                    let new_tid = tmp[1];
                    tmp = selected.attr('name').split(':');
                    let model_name = tmp[0];
                    let station_name = tmp[1];
                    let location_code = tmp[2];

                    selected_for_api[old_id] = new_id;
                    selected_for_List.push({
                        station_name: station_name,
                        model_name: model_name,
                        new_tid: new_tid.substr(-4),
                        location_code: location_code,
                        new_id: new_id,
                        old_id: old_id,
                    });

                } else {
                    selected_for_api[old_id] = "";
                }
            });

            let selected_for_api_key = Object.keys(selected_for_api);
            if (selected_for_api_key.length > 0) {
                let alertContent = '';
                $.ajax({
                    url: `?type=CYCLE_FIX`,
                    type: 'post',
                    data: {
                        identityCode: selected_for_api,
                        selectedForData: selected_for_data
                    },
                    async: true,
                    success: function (response) {
                        console.log(` success:`, response);
                        if (response.successes > 0) {
                            let fail_old = response.no_exists.old;
                            let fail_new = response.no_exists.new;
                            let selected_for_print = [];
                            $.each(selected_for_List, (key, value) => {
                                if ($.inArray(value.new_id, fail_new) === -1 && $.inArray(value.old_id, fail_old) === -1) {
                                    selected_for_print.push(`${value.station_name},${value.model_name},${value.new_tid},${value.location_code},${value.new_id}`);
                                }
                            });

                            let selected_for_print_length = selected_for_print.length;
                            if (selected_for_print_length > 0) {
                                //拼接txt
                                let content = 'maintain_station_name,entire_model,tid,maintain_location_code,identity_code\r\n' + selected_for_print.join('\r\n');
                                let filename = "label-print.txt";
                                let element = document.createElement('a');
                                element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
                                element.setAttribute('download', filename);
                                element.style.display = 'none';
                                document.body.appendChild(element);
                                element.click();
                                document.body.removeChild(element);
                            }
                            alertContent = "下载成功：" + selected_for_print_length + "条\n\r旧设备失败：" + fail_old.length + "条\r\r编号：" + fail_old.join('\n\r') + "\n\r新设备失败：" + fail_new.length + "条\n\r编号：" + fail_new.join('\n\r');
                        } else {
                            alertContent = "下载失败！！\n\r无下载数据";
                        }
                        alert(alertContent);
                    },
                    fail: function (error) {
                        console.log(` fail:`, error);
                    }
                });
            } else {
                alert('请选择标签')
            }
        }

        /**
         *下载标签
         */
        function downloadLabelbak() {
            //处理数据
            let selected_for_api = {};
            let selected_for_List = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let old_id = $(item).val();

                let selected = $(`#${old_id}`);
                let new_tmp = selected.val();
                if (new_tmp) {
                    let tmp = new_tmp.split('_');
                    let new_id = tmp[0];
                    let new_tid = tmp[1];
                    tmp = selected.attr('name').split(':');
                    let model_name = tmp[0];
                    let station_name = tmp[1];
                    let location_code = tmp[2];

                    selected_for_api[old_id] = new_id;
                    selected_for_List.push({
                        station_name: station_name,
                        model_name: model_name,
                        new_tid: new_tid.substr(-4),
                        location_code: location_code,
                        new_id: new_id,
                        old_id: old_id,
                    });
                }
            });
            let selected_for_api_key = Object.keys(selected_for_api);
            if (selected_for_api_key.length > 0) {
                let selMonth = $("#selMonth").val();
                if (selMonth === '' || selMonth === null) {
                    alert('请选择时间');
                    return false;
                }
                let alertContent = '';
                $.ajax({
                    url: `?type=CYCLE_FIX`,
                    type: 'post',
                    data: {
                        identityCode: selected_for_api,
                        date: selMonth
                    },
                    async: true,
                    success: response => {
                        console.log(` success:`, response);
                        if (response.successes > 0) {
                            let fail_old = response.no_exists.old;
                            let fail_new = response.no_exists.new;
                            let selected_for_print = [];
                            $.each(selected_for_List, (key, value) => {
                                if ($.inArray(value.new_id, fail_new) === -1 && $.inArray(value.old_id, fail_old) === -1) {
                                    // selected_for_print.push(`${value.station_name},${value.model_name},${value.new_tid},${value.location_code},${value.new_id}`);
                                    let identity_code = `${value.new_id}`;
                                    let tmp = [];
                                    tmp.push({
                                        "content": `${value.station_name}`,
                                        "type": "text",
                                        "attribute": "35, 65, 40, 0, 0, 0"
                                    });
                                    tmp.push({
                                        "content": `${value.location_code}`,
                                        "type": "text",
                                        "attribute": "35, 125, 60, 0, 0, 0"
                                    });
                                    if (identity_code.substr(0, 1) === 'Q') {
                                        tmp.push({
                                            "content": identity_code,
                                            "type": "text",
                                            "attribute": "105, 200, 30, 0, 0, 0"
                                        });
                                    }
                                    if (identity_code.substr(0, 1) === 'S') {
                                        tmp.push({
                                            "content": identity_code,
                                            "type": "text",
                                            "attribute": "170, 200, 30, 0, 0, 0"
                                        });
                                    }
                                    selected_for_print.push({'data': tmp})
                                }
                            });

                            let selected_for_print_length = selected_for_print.length;
                            if (selected_for_print_length > 0) {

                                $.each(selected_for_print, function (key, value) {
                                    let content = JSON.stringify(value);
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
                                })
                            }
                            alertContent = "下载成功：" + selected_for_print_length + "条\n\r旧设备失败：" + fail_old.length + "条\r\r编号：" + fail_old.join('\n\r') + "\n\r新设备失败：" + fail_new.length + "条\n\r编号：" + fail_new.join('\n\r');
                        } else {
                            alertContent = "下载失败！！\n\r无下载数据";
                        }
                        alert(alertContent);
                    },
                    fail: error => {
                        console.log(` fail:`, error);
                    }
                });
            } else {
                alert('请选择标签')
            }
        }

        /**
         * 刷新筛选条件
         */
        function fnReload() {
            let root_url = `type=CYCLE_FIX`;
            let month = `date=${$selMonth.val()}`;
            let station = `station_name=${$selStation.val()}`;
            let modelName = `model_name=${$selModelName.val()}`;
            location.href = `?${root_url}&${month}&${station}&${modelName}`;
        }

    </script>
@endsection

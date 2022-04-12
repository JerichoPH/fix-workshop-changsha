@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection
@section('content')
    <section class="content">
        @include('Layout.alert')
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-6"><h3 class="box-title">测试列表</h3></div>
                    <div class="col-md-6"></div>
                </div>
            </div>
            <div class="box-body table-responsive">
                <div class="from">
                    <div class="input-group">
                        <input class="form-control"
                               id="txtCode" name="code" type="text" placeholder="厂编号/所编号"
                               required autofocus onkeydown="if(event.keyCode==13){fnGetDevice(_type);}">
                        <div class="input-group-btn">
                            <button id="selType" type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><span id="spanType">厂编号</span>
                                <span class="fa fa-caret-down"></span></button>
                            <ul class="dropdown-menu">
                                <li><a href="javascript:" onclick="fnSelectType('factory_device_code')">厂编号</a></li>
                                <li><a href="javascript:" onclick="fnSelectType('serial_number')">所编号</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>唯一编号</th>
                        <th>厂编号</th>
                        <th>所编号</th>
                        <th>站场</th>
                        <th>位置</th>
                        <th>型号</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        $('.select2').select2();

        var _accept = 'application/x.fix_workshop.v1+json';
        var _type = 'factory_device_code';
        var _rfidTid = 'ABC';  // 用来接收RFID TID，测试时固定写死

        /**
         * 选择搜索类型
         */
        fnSelectType = type => {
            types = {
                serial_number: '所编号',
                factory_device_code: '厂编号'
            };
            $("#spanType").text(types[type]);
            _type = type;
            $('#txtCode').focus();
        };

        /**
         * 获取设备
         * @param type
         */
        fnGetDevice = type => {
            $.ajax({
                headers: {Accept: _accept},
                url: `{{url('/api/warehouse/entireInstance')}}/${$('#txtCode').val()}?type=${type}`,
                type: 'get',
                success: res => {
                    console.log('成功：', res);
                    html = '';
                    $.each(res, (index, item) => {
                        html += `<tr>
<td>${item.identity_code ? item.identity_code : '无'}</td>
<td>${item.factory_device_code ? item.factory_device_code : '无'}</td>
<td>${item.serial_number ? item.serial_number : '无'}</td>
<td>${item.maintain_station_name ? item.maintain_station_name : '无'}</td>
<td>${item.maintain_location_code ? item.maintain_location_code : '无'}</td>
<td>${item.entire_model_name ? item.entire_model_name : '无'}</td>
<td><a href="javascript:" onclick="fnBinding('${item.identity_code}','${_rfidTid}')" class="btn btn-success btn-flat"><i class="fa fa-check">&nbsp;</i>绑定</a></td>
</tr>`;
                        $('tbody').html(html);
                    });
                },
                error: err => {
                    console.log('错误：', err);
                }
            });
        };

        /**
         * 绑定RFID TID到设备
         * @param identityCode
         * @param rfidCode
         */
        fnBinding = (identityCode, rfidCode) => {
            $.ajax({
                headers: {Accept: _accept,},
                url: "{{url('api/warehouse/entireInstance/batchBindingRFIDWithIdentityCode')}}",
                type: 'post',
                data: JSON.stringify({identityCode, rfidCode}),
                success: (res, status) => {
                    if (status == 'success') {
                        alert('绑定成功');
                        location.reload();
                    } else {
                        alert('绑定失败：' + res);
                    }
                },
                error: err => {
                    console.log('错误：', err);
                },
            })
        };
    </script>
@endsection

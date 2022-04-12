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
                    <div class="col-md-6"><a href="javascript:" class="btn btn-success btn-flat pull-right" onclick="funSave()"><i class="fa fa-check">&nbsp;</i>保存</a></div>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed" id="table">
                    <thead>
                    <tr>
                        <th>厂编号</th>
                        <th>RFID TID</th>
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
        var sourceData = {};  // 用来转换数组结构（我给不了你key=>value的结构）
        var rfidTid = 'ABC';  // 这里用来接收扫描barcode结果，测试页面写死ABC
        var requestData = [];  // 这里是需要准备上传的变量

        $(function () {
            $.ajax({
                headers: {'Accept': 'application/x.fix_workshop.v1+json',},
                url: "{{url('api/warehouse/entireInstance')}}?type=withoutRFID&page={{request('page',1)}}",
                type: 'get',
                success: ret => {
                    console.log('成功：', ret);

                    // 循环内容，改变数组的格式
                    $.each(ret.data, function (id, it) {
                        sourceData[it.factory_device_code] = it.identity_code;
                    });
                    console.log('改变数组结构：', sourceData);

                    // 将改变的数组填充到table
                    html = '';
                    $.each(sourceData, function (factoryDeviceCode, identityCode) {
                        html += `<tr>
<td>${factoryDeviceCode}</td>
<td><span id="span${factoryDeviceCode}"></span></td>
<td><a href="javascript:" id="btn${factoryDeviceCode}" onclick="funBindingRFID2FactoryDeviceCode('${factoryDeviceCode}')" class="btn btn-primary btn-flat">绑定</a></td>
</tr>`;
                    });
                    $("#tbody").html(html);
                    html = null;
                },
                error: err => {
                    console.log('错误：', err);
                }
            });
        });

        /**
         * 绑定rfid tid到厂编号
         * @param factoryDeviceCode 厂编号
         */
        funBindingRFID2FactoryDeviceCode = factoryDeviceCode => {
            console.log(`厂编号：${factoryDeviceCode}`, `RFID TID：${rfidTid}`);  // 检查所需变量是否准备好
            identityCode = sourceData[factoryDeviceCode];
            requestData.push({identityCode, rfidTid});
            // 将数据动态写入到表格
            $(`#span${factoryDeviceCode}`).text(rfidTid);
            $(`#btn${factoryDeviceCode}`).removeClass('btn-primary').addClass('btn-danger').text('已选择');
            console.log(`准备上传的数据：`, requestData);
        };

        /**
         * 上传
         */
        funSave = () => {
            $.ajax({
                headers: {'Accept': 'application/x.fix_workshop.v1+json',},
                url: "{{url('api/warehouse/entireInstance/batchBindingRFIDWithIdentityCode')}}",
                type: 'post',
                data: JSON.stringify(requestData),
                success: ret => {
                    console.log(ret);
                },
                error: err => {
                    console.log('错误：', err);
                },
            })
        };
    </script>
@endsection

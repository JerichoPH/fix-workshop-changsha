@extends('Layout.index')
@section('content')
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">添加检测数据</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right"></div>
            </div>
            <br>
            <div class="box-body">
                <form class="form-horizontal" id="frmCreate" action="" method="post">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">测试数据：</label>
                        <div class="col-sm-10 col-md-9">
                            <textarea name="data" id="txaData" cols="30" rows="10" class="form-control">{"body":{"测试项目":[{"判定结论":0,"单位":"","标准值":"66Ω","测试值":"0.00","项目编号":"B049ME20190704193346663451562240026"},{"判定结论":0,"单位":"","标准值":"345Ω","测试值":"0.00","项目编号":"B049ME20190704193357663451562240037"},{"判定结论":1,"单位":"","标准值":"≤50mΩ","测试值":"0.00","项目编号":"接点电阻/3H"},{"判定结论":1,"单位":"","标准值":"≤50mΩ","测试值":"0.00","项目编号":"接点电阻/4H"},{"判定结论":1,"单位":"","标准值":"≤50mΩ","测试值":"0.00","项目编号":"接点电阻/1Q"},{"判定结论":1,"单位":"","标准值":"≤50mΩ","测试值":"0.00","项目编号":"接点电阻/2Q"},{"判定结论":0,"单位":"","标准值":"110V","测试值":"0.00","项目编号":"B049ME20190704193646663451562240206"},{"判定结论":1,"单位":"","标准值":"≤80mA","测试值":"0.00","项目编号":"B049ME20190704193701663451562240221"},{"判定结论":0,"单位":"","标准值":"160°±8°","测试值":"85.52","项目编号":"B049ME20190704193720663451562240240"},{"判定结论":1,"单位":"","标准值":"≤15V","测试值":"0.91","项目编号":"B049ME20190704193733663451562240253"},{"判定结论":1,"单位":"","标准值":"≤38mA","测试值":"2.33","项目编号":"B049ME20190704193745663451562240265"},{"判定结论":0,"单位":"","标准值":"≥7.5V","测试值":"0.91","项目编号":"B049ME20190704193757663451562240277"},{"判定结论":1,"单位":"","标准值":"≤5V","测试值":"0.91","项目编号":"B049ME20190704193810663451562240290"},{"判定结论":0,"单位":"","标准值":"≥100MΩ","测试值":"0.00","项目编号":"B049ME20190704193827663451562240307"}]},"header":{"message_ID":"testing","platform":"西安安路信铁路技术有限公司","testing_device_ID":"二元二位继电器","time":"2019-07-08 15:08:22","器材型号":"Q011301","条码编号":"123456","测试人":"001","记录类型":"检前"}}</textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        <a href="javascript:" onclick="fnUpload()" class="btn btn-success btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>AJAX上传</a>
                        <a href="javascript:" onclick="fnWsUpload()" class="btn btn-success btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>WS上传</a>
                        <button class="btn btn-primary btn-flat pull-right" style="margin-right: 5px;"><i class="fa fa-upload">&nbsp;</i>上传</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        // ws = new WebSocket("ws://118.190.155.136:2346");
        // ws = new WebSocket("ws://127.0.0.1:2346");

        /**
         * 发送数据
         */
        fnUpload = () => {
            $.ajax({
                url: "{{ url('detecting') }}",
                type: "post",
                data: {data: $("#txaData").val()},
                async: true,
                success: function (response) {
                    console.log('success:', response);
                    // alert(response);
                    // location.reload();
                },
                error: function (error) {
                    console.log('fail:', error);
                    // if (error.status == 401) location.href = "{{ url('login') }}";
                    // alert(error.responseText);
                },
            });
        };

        fnWsUpload = () =>{
            ws.send($("#txaData").val());
        };

        ws.onmessage = (evt) => {
            console.log(evt.data);
        };

        ws.onopen = () => {
            console.log('链接成功');
        };

        ws.onclose = (evt) => {
            alert('链接关闭，请刷新页面');
            console.log(evt);
        };

        ws.onerror = (evt) => {
            alert('链接错误，请刷新页面');
            console.log(evt);
        };
    </script>
@endsection

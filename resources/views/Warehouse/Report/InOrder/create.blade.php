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
    @include('Layout.alert')
    <section class="content">
        <form id="frmCreate" action="{{url('warehouse/report/inOrder')}}" method="post" class="form-horizontal" enctype="multipart/form-data">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">添加入库单</h3>
                    {{--右侧最小化按钮--}}
                    <div class="box-tools pull-right">
                        <a href="{{url('downloadWarehouseReportInOrderTemplateExcel')}}" target="_blank" class="btn btn-box-tool"><i class="fa fa-download"></i></a>
                    </div>
                </div>
                <br>
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">送货人姓名：</label>
                        <div class="col-sm-10 col-md-8">
                            <input class="form-control" type="text" required autofocus onkeydown="if(event.keyCode==13){return false;}"
                                   name="send_processor_name" placeholder="送货人姓名" value="{{old('send_processor_name')}}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">送货人手机号：</label>
                        <div class="col-sm-10 col-md-8">
                            <input class="form-control" type="text" required autofocus onkeydown="if(event.keyCode==13){return false;}"
                                   name="send_processor_phone" placeholder="送货人手机号" value="{{old('send_processor_phone')}}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">入库日期：</label>
                        <div class="col-sm-10 col-md-8">
                            <div class="input-group date">
                                <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                                <input name="processed_at" type="text" class="form-control pull-right" id="datepicker" value="{{old('processed_at',date('Y-m-d'))}}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">上传入库单：</label>
                        <div class="col-sm-9 col-md-8">
                            <div class="input-group">
                                <input type="file" name="inOrderFile" class="form-control">
                                <div class="input-group-addon"><a href="{{url('downloadWarehouseReportInOrderTemplateExcel')}}" target="_blank"><i class="fa fa-download"></i></a></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">入库类型：</label>
                        <div class="col-sm-9 col-md-8">
                            <select name="type" class="form-control select2" style="width:100%;">
                                @foreach(\App\Model\WarehouseReportInOrder::$TYPE as $typeKey=>$typeValue)
                                    <option value="{{$typeKey}}">{{$typeValue}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">入库办理人：</label>
                        <div class="col-sm-9 col-md-8">
                            <select name="processor_id" class="form-control select2" style="width:100%;">
                                @foreach($accounts as $accountId => $accountNickname)
                                    <option value="{{$accountId}}" {{old('processor_id') ? old('processor_id') : session('account.id') == $accountId ? 'selected' : ''}}>{{$accountNickname}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">备注：</label>
                        <div class="col-sm-10 col-md-8">
                            <textarea name="description" cols="30" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{url('warehouse/report/inOrder')}}" class="btn btn-default pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                    <button type="submit" class="btn btn-success pull-right"><i class="fa fa-check">&nbsp;</i>生成入库单</button>
                </div>
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script src="/AdminLTE/bower_components/select2/dist/js/select2.full.min.js"></script>
    <!-- iCheck 1.0.1 -->
    <script src="/AdminLTE/plugins/iCheck/icheck.min.js"></script>
    <!-- bootstrap datepicker -->
    <script src="/AdminLTE/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
            //Date picker
            $('#datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd'
            });
        });
    </script>
@endsection

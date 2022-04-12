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
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            整件管理
            <small>批量导入</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('entire/instance')}}"><i class="fa fa-users">&nbsp;</i>整件管理</a></li>--}}
{{--            <li class="active">批量导入</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @include('Layout.alert')
            </div>
            <form action="{{url('entire/instance/batch')}}" method="post" enctype="multipart/form-data">
                {{csrf_field()}}
                {{--整件录入--}}
                <div class="col-md-12">
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h1 class="box-title">批量导入设备</h1>
                            {{--右侧最小化按钮--}}
                            <div class="box-tools pull-right"></div>
                        </div>
                        <br>
                        <div class="box-body form-horizontal" style="font-size: 18px;">
                            <div class="form-group">
                                <label class="col-sm-3 control-label">入所经办人：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="processor_id" class="form-control select2 input-lg" style="width: 100%;">
                                        @foreach($accounts as $accountId => $accountNickname)
                                            <option value="{{$accountId}}" {{old('processor_id',null) ? $accountId == old('processor_id') ? 'selected' : '' : $accountId == session('account.id') ? 'selected' : ''}}>{{$accountNickname}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">联系人：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input placeholder="联系人" class="form-control input-lg" type="text" required onkeydown="if(event.keyCode==13){return false;}"
                                           name="connection_name" value="{{old('connection_name','张三')}}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">联系电话：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input placeholder="联系电话" class="form-control input-lg" type="text" required onkeydown="if(event.keyCode==13){return false;}"
                                           name="connection_phone" value="{{old('connection_phone','13522178057')}}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">入所日期：</label>
                                <div class="col-sm-10 col-md-8">
                                    <div class="input-group date">
                                        <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                                        <input name="processed_at" type="text" class="form-control pull-right input-lg" id="datepicker" value="{{old('processed_at',date('Y-m-d'))}}">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">选择文件：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input type="file" name="file">
                                </div>
                            </div>
                            <div class="box-footer">
{{--                                <a href="{{url('entire/instance')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left btn-lg"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left btn-lg"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                <a href="http://www.rfid.com" target="_blank" class="btn btn-warning btn-flat pull-left btn-lg">扫码绑定</a>
                                <button type="submit" class="btn btn-success btn-flat pull-right btn-lg" style="margin-left: 5px;"><i class="fa fa-upload">&nbsp;</i>上传</button>
{{--                                <label class="control-label pull-right" style="text-align: left; font-weight: normal;"><input name="auto_insert_fix_workflow" type="checkbox" class="minimal input-lg" value="1">自动生成检修单</label>--}}
                                <label class="control-label pull-right" style="text-align: left; font-weight: normal; margin-right: 15px;"><input name="has_part" type="checkbox" class="minimal input-lg" value="1">是否有部件</label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
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

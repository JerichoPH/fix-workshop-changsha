@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            新站任务
            <small>导入设备</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">导入设备</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">导入设备</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a onclick="fnDownloadTemplateExcel()" class="btn btn-default btn-flat btn-sm pull-right"><i class="fa fa-download">&nbsp;</i>下载{{ session('account.work_area') }}导入表</a>
                        </div>
                    </div>
                    <br>
                    <form class="form-horizontal" id="frmStore" action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="workArea" value="{{ $currentWorkArea == 1 ? '01' : '02' }}">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">设备类型：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select id="selType" name="type" class="form-control select2">
                                        <option value="INSTALLED">上道</option>
                                        <option value="INSTALLING">备品</option>
                                        <option value="FIXED">成品</option>
                                        <option value="FIXING">待修</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">工区：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select id="selType" name="type" class="form-control select2 disabled" disabled>
                                        <option value="{{ $currentWorkArea == 1 ? '01' : '02' }}">{{ session('account.work_area') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">经办人：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="account_id" class="form-control select2">
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->nickname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">文件：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input type="file" name="file" id="txtFile" value="" placeholder="选择批量导入Excel">
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
{{--                            <a href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/edit?page={{ request('page',1) }}" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>
                            <button type="submit" class="btn btn-success btn-flat btn-sm pull-right"><i class="fa fa-upload">&nbsp;</i>上传</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $selType = $('#selType');
        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 下载Excel模板
         */
        function fnDownloadTemplateExcel() {
            let type = '00';
            let currentWorkArea = '00';
            let url = `{{ url('entire/instance/upload') }}?download=`;

            switch ('{{ $currentWorkArea }}') {
                case '1':
                    currentWorkArea = '01';
                    break;
                case '2':
                case '3':
                    currentWorkArea = '02';
                    break;
            }

            switch ($selType.val()) {
                case 'INSTALLED':
                case 'INSTALLING':
                    type = '01';
                    break;
                case 'FIXED':
                case 'FIXING':
                    type = '02';
                    break;
                default:
                    alert('设备类型错误');
                    return;
            }

            open(`${url}${currentWorkArea}${type}`, '_blank');
        }
    </script>
@endsection

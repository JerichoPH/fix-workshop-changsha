@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备赋码
            <small></small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/edit?page={{ request('page',1) }}">任务详情</a></li>--}}
{{--            <li class="active">设备赋码</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">设备赋码</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('v250TaskOrder/downloadUploadCreateDeviceExcelTemplate') }}?type={{ request('type') }}&workAreaType={{ $taskOrder->WorkAreaByUniqueCode->type }}" class="btn btn-default btn-flat btn-sm pull-right" target="_blank"><i class="fa fa-download">&nbsp;</i>下载模板</a>
                        </div>
                    </div>
                    <form class="form-horizontal" id="frmStore" action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="type" value="{{ request('type') }}">
                        <input type="hidden" name="workAreaType" value="{{ $taskOrder->WorkAreaByUniqueCode->type }}">
                        <input type="hidden" name="workAreaUniqueCode" value="{{ $taskOrder->work_area_unique_code }}">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">工区：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select id="selType" name="type" class="form-control select2 disabled" disabled style="width: 100%;">
                                        <option value="{{ session('account.work_area_by_unique_code.unique_code') }}">{{ session('account.work_area_by_unique_code.name') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">经办人：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="account_id" class="form-control select2" style="width: 100%;">
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
                            <a
                                href="{{ url('v250TaskOrder',$taskOrder->serial_number) }}/edit?page={{ request('page',1) }}&type={{ request('type') }}"
                                class="btn btn-default btn-flat btn-sm pull-left"
                            >
                                <i class="fa fa-arrow-left btn-flat">&nbsp;</i>
                                返回任务
                            </a>
                            <button
                                type="submit"
                                class="btn btn-success btn-flat btn-sm pull-right"
                            >
                                <i class="fa fa-upload">&nbsp;</i>
                                确定上传
                            </button>
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
    </script>
@endsection

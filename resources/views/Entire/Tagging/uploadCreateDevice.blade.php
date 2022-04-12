@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            设备/器材赋码
            <small></small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">设备赋码</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">设备/器材赋码</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a
                                href="javascript:"
                                class="btn btn-default btn-flat"
                                onclick="fnDownloadExcelTemplate(selWorkArea.value)"
                            >
                                <i class="fa fa-download">&nbsp;</i>下载模板
                            </a>
                            <a
                                href="{{ url('entire/tagging/report') }}"
                                class="btn btn-default btn-flat"
                            >
                                <i class="fa fa-list">&nbsp;</i>赋码历史记录
                            </a>
                        </div>
                    </div>
                    <form class="form-horizontal" id="frmStore" action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="account_id" value="{{ session('account.id') }}">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">工区：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select
                                        id="selWorkArea"
                                        name="work_area_unique_code"
                                        class="form-control select2"
                                        style="width: 100%;"
                                    >
                                        @foreach($work_areas as $work_area)
                                            <option value="{{ $work_area->unique_code }}" {{ $work_area->unique_code == session('account.work_area_unique_code') ? 'selected' : '' }}>{{ $work_area->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">经办人：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="account_id" class="form-control select2" style="width: 100%;" disabled>
                                        <option value="{{ session('account.id') }}">{{ session('account.nickname') }}
                                        </option>
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
                            <a href="/" class="btn btn-default btn-flat btn-sm pull-left"> <i
                                    class="fa fa-arrow-left btn-flat">&nbsp;</i> 返回主页 </a>
                            <button type="submit" class="btn btn-success btn-flat btn-sm pull-right"><i
                                    class="fa fa-upload">&nbsp;</i> 确定上传
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
        let $selWorkArea = $('#selWorkArea');
        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 下载excel模板
         * @param {string} workAreaUniqueCode
         */
        function fnDownloadExcelTemplate(workAreaUniqueCode) {
            open(`{{ url('entire/tagging/downloadUploadCreateDeviceExcelTemplate') }}?work_area_unique_code=${workAreaUniqueCode}`, '_blank');
        }
    </script>
@endsection

@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            上传现场人员
            <small></small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">上传现场人员</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">上传现场人员</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="{{ url('account/downloadUploadCreateAccountBySceneExcelTemplate') }}" class="btn btn-default btn-flat btn-sm pull-right" target="_blank"><i class="fa fa-download">&nbsp;</i>下载模板</a>
                        </div>
                    </div>
                    <form class="form-horizontal" id="frmStore" action="" method="POST" enctype="multipart/form-data">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">文件：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input type="file" name="file" id="txtFile" value="" placeholder="选择批量导入Excel">
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ url('account') }}?page={{ request('page',1) }}" class="btn btn-default btn-flat btn-sm pull-left"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回列表</a>
                            <button type="submit" class="btn btn-success btn-flat btn-sm pull-right"><i class="fa fa-upload">&nbsp;</i>确定上传</button>
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

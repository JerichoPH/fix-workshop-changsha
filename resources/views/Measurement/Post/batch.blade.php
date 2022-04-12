@extends('Layout.index')
@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">批量导入检测标准值</h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <div class="box-body">
                        @include('Layout.alert')
                        <form class="form-horizontal" action="" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="col-sm-3 control-label">选择文件：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input type="file" name="file">
                                </div>
                            </div>
                            <div class="box-footer">
                                <a href="{{url('measurements')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                <button class="btn btn-success btn-flat pull-right"><i class="fa fa-upload">&nbsp;</i>上传</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

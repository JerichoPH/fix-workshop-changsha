@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
      <h1>
        供应商管理
        <small>批量导入</small>
      </h1>
{{--      <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li><a href="{{url('factory')}}"><i class="fa fa-users">&nbsp;</i>供应商管理</a></li>--}}
{{--        <li class="active">批量导入</li>--}}
{{--      </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">批量导入供应商</h3>
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
{{--                        <a href="{{url('measurements')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                        <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <button class="btn btn-success btn-flat pull-right"><i class="fa fa-upload">&nbsp;</i>上传</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

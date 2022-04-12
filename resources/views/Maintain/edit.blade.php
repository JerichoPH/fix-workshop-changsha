@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
@endsection
@section('content')
    <section class="content-header">
        <h1>
            车间/车站管理
            <small>编辑</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--  <li><a href="/"><i class="fa fa-home"></i> 首页</a></li>--}}
        {{--  <li class="active">基础数据</li>--}}
        {{--  <li class="active">车间/车站管理</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            {{--<div class="col-md-{{ $type == 'STATION' ? '8' : '12' }}">--}}
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        @if($type == 'STATION')
                            <h3 class="box-title">车站基础信息编辑</h3>
                            <div class="pull-right">
                                <a href="{{ url('equipmentCabinet') }}?maintain_station_unique_code={{ $maintain[0]->unique_code }}" class="btn btn-default btn-flat">机柜管理</a>
                            </div>
                        @else
                            <h3 class="box-title">车间基础信息编辑</h3>
                        @endif
                    </div>
                    <br>
                    <div class="box-body">
                        <form class="form-horizontal" id="frmUpdate">
                            <input type="hidden" name="type" value="{{ $type }}">
                            <div class="form-group">
                                <label class="col-sm-2 control-label" style="color: red">名称*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="name" type="text" class="form-control" placeholder="{{ $maintain[0]->name }}" required value="{{ $maintain[0]->name }}">
                                </div>
                            </div>
                            @if($type == 'STATION')
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" style="color: red">车站编码*：</label>
                                    <div class="col-sm-10 col-md-8">
                                        <input name="unique_code" type="text" class="form-control" placeholder="{{ $maintain[0]->unique_code }}" required value="{{ $maintain[0]->unique_code }}" disabled>
                                    </div>
                                </div>
                                {{--<div class="form-group">--}}
                                {{--    <label class="col-sm-2 control-label" style="color: red">线别*：</label>--}}
                                {{--    <div class="col-sm-10 col-md-8">--}}
                                {{--        <select name="line" class="form-control select2" style="width:100%;">--}}
                                {{--            <option value="0" selected disabled>请选择</option>--}}
                                {{--            @foreach(@$lines as $line)--}}
                                {{--                <option value="{{@$line->id}}" {{ @$maintain[0]->line_id == @$line->id ? 'selected' : ''}}>{{$line->name}}</option>--}}
                                {{--            @endforeach--}}
                                {{--        </select>--}}
                                {{--    </div>--}}
                                {{--</div>--}}
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" style="color: red">线别*：</label>
                                    <div class="col-sm-10 col-md-8">
                                        <select class="form-control select2" name="line[]" multiple="multiple" data-placeholder="&nbsp;&nbsp;请选择" style="width: 100%;">
                                            @foreach($lines as $line)
                                                <option value="{{ $line->id }}" {{ in_array($line->id, @$maintainsIds) ? 'selected' : ''}}>{{ $line->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" style="color: red">车间*：</label>
                                    <div class="col-sm-10 col-md-8">
                                        <select id="workshop" name="workshop" class="form-control select2" style="width:100%;">
                                            <option value="0" selected disabled>请选择</option>
                                            @foreach(@$workShops as $workShop)
                                                <option value="{{ @$workShop->unique_code }}" {{ @$maintain[0]->parent_unique_code == @$workShop->unique_code ? 'selected' : ''}}>{{ $workShop->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{--线别车间二级联动--}}
                                {{--<div class="form-group">--}}
                                {{--    <label class="col-sm-2 control-label" style="color: red">线别*：</label>--}}
                                {{--    <div class="col-sm-10 col-md-8">--}}
                                {{--        <select name="line" class="form-control select2" style="width:100%;" onchange="lines(this.value)">--}}
                                {{--            <option value="0" selected disabled>请选择</option>--}}
                                {{--            @foreach($lines as $line)--}}
                                {{--                <option value="{{$line->id}}">{{$line->name}}</option>--}}
                                {{--            @endforeach--}}
                                {{--        </select>--}}
                                {{--    </div>--}}
                                {{--</div>--}}
                                {{--<div class="form-group">--}}
                                {{--    <label class="col-sm-2 control-label" style="color: red">车间*：</label>--}}
                                {{--    <div class="col-sm-10 col-md-8">--}}
                                {{--        <select id="workshop" name="workshop" class="form-control select2" s
                                {{--        </select>--}}
                                {{--    </div>--}}
                                {{--</div>--}}
                            @else
                                <div class="form-group">
                                    <label class="col-sm-2 control-label" style="color: red">车间编码*：</label>
                                    <div class="col-sm-10 col-md-8">
                                        <input name="unique_code" type="text" class="form-control" placeholder="{{ $maintain[0]->unique_code }}" required value="{{ $maintain[0]->unique_code }}" disabled>
                                    </div>
                                </div>
                            @endif
                            <div class="form-group">
                                <label class="col-sm-2 control-label">经度：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="lon" type="text" class="form-control" placeholder="{{ $maintain[0]->lon }}" required value="{{ $maintain[0]->lon }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">纬度：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="lat" type="text" class="form-control" placeholder="{{ $maintain[0]->lat }}" required value="{{ $maintain[0]->lat }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">联系人：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="contact" type="text" class="form-control" placeholder="{{ $maintain[0]->contact }}" required value="{{ $maintain[0]->contact }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">联系电话：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="contact_phone" type="text" class="form-control" placeholder="{{ $maintain[0]->contact_phone }}" required value="{{ $maintain[0]->contact_phone }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">联系地址：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="contact_address" type="text" class="form-control" placeholder="{{ $maintain[0]->contact_address }}" required value="{{ $maintain[0]->contact_address }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">台账：</label>
                                <div class="col-sm-10 col-md-8" style="padding: 7px 15px 0px">
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="is_show" value="1" {{ $maintain[0]->is_show == 1 ? 'checked' : ''}}>显示</label>&nbsp;&nbsp;
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="is_show" value="0" {{ $maintain[0]->is_show == 0 ? 'checked' : ''}}>不显示</label>
                                </div>
                            </div>
                            <div class="box-footer">
                                {{--<a href="{{url('maintain')}}?page={{request('page',1)}}" class="btn btn-default pull-left btn-lg btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                <a href="#" onclick="history.back(-1);" class="btn btn-default pull-left btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                <a href="javascript:" onclick="fnUpdate()" class="btn btn-success pull-right btn-flat"><i class="fa fa-check">&nbsp;</i>保存</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {{--@if( env('ORGANIZATION_CODE') == 'B050' )--}}
            {{--    --}}{{--电子图纸管理--}}
            {{--    @if( $type == 'STATION' )--}}
            {{--        <div class="col-md-4">--}}
            {{--            <div class="box box-primary">--}}
            {{--                <div class="box-header with-border">--}}
            {{--                    <h3 class="box-title">电子图纸管理</h3>--}}
            {{--                </div>--}}
            {{--                <br>--}}
            {{--                <div class="box-body">--}}
            {{--                    <form action="{{ url('stationElectricImage',$current_unique_code) }}" method="POST" enctype="multipart/form-data" class="form-horizontal" id="frmUploadElectricImage">--}}
            {{--                        <input type="file" name="electric_images[]" id="fileElectricImages" multiple>--}}
            {{--                        <button class="btn btn-primary btn-flat pull-right"><i class="fa fa-upload">&nbsp;</i>上传</button>--}}
            {{--                    </form>--}}
            {{--                </div>--}}
            {{--                <div class="box-footer">--}}
            {{--                    <h4 class="box-title">电子图纸列表</h4>--}}
            {{--                    <table class="table table-hover table-condensed">--}}
            {{--                        <thead>--}}
            {{--                        <tr>--}}
            {{--                            <th>文件名</th>--}}
            {{--                            <th>操作</th>--}}
            {{--                        </tr>--}}
            {{--                        </thead>--}}
            {{--                        <tbody>--}}
            {{--                        @foreach($station_electric_images as $station_electric_image)--}}
            {{--                            <tr>--}}
            {{--                                <td>{{ $station_electric_image->original_filename }}</td>--}}
            {{--                                <td><a href="javascript:" onclick="fnDeleteElectricImage({{ $station_electric_image->id }})" class="text-danger"><i class="fa fa-times"></i></a></td>--}}
            {{--                            </tr>--}}
            {{--                        @endforeach--}}
            {{--                        </tbody>--}}
            {{--                    </table>--}}
            {{--                </div>--}}
            {{--            </div>--}}
            {{--        </div>--}}
            {{--    @endif--}}
            {{--@endif--}}
        </div>
    </section>
@endsection
@section('script')
    <script src="/AdminLTE/bower_components/select2/dist/js/select2.full.min.js"></script>
    <!-- iCheck 1.0.1 -->
    <script src="/AdminLTE/plugins/iCheck/icheck.min.js"></script>
    <script>
        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
        });

        /**
         * 保存
         */
        fnUpdate = function () {
            $.ajax({
                url: "{{ url('maintain',$maintain[0]->unique_code) }}",
                type: "put",
                data: $("#frmUpdate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    alert(response);
                    location.href = "{{ url('maintain') }}?page={{ request('page',1) }}";
                },
                error: function (error) {
                    // console.log('fail:', error);
                    if (error.responseText === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        };

        /**
         * 删除电子图纸
         * @param {int} id
         */
        function fnDeleteElectricImage(id) {
            if (confirm('删除不可恢复，是否确认删除？'))
                $.ajax({
                    url: `{{ url('stationElectricImage') }}/${id}`,
                    type: 'DELETE',
                    data: {},
                    success: function (res) {
                        console.log(`{{ url('maintain/electricImage') }}/${id} success:`, res);
                        location.reload();
                    },
                    fail: function (err) {
                        console.log(`{{ url('maintain/electricImage') }}/${id} fail:`, err);
                        if (error.responseText === 401) location.href = "{{ url('login') }}";
                        alert(error.responseText);
                    }
                })
        }
    </script>
@endsection

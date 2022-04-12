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
            <small>新建</small>
        </h1>
        {{--      <ol class="breadcrumb">--}}
        {{--        <li><a href="/"><i class="fa fa-home"></i> 首页</a></li>--}}
        {{--        <li class="active">基础数据</li>--}}
        {{--        <li class="active">车间/车站管理</li>--}}
        {{--      </ol>--}}
    </section>
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                @if(request('type') == 'workshop')
                    <h3 class="box-title">新建车间</h3>
                @else
                    <h3 class="box-title">新建车站</h3>
                @endif
            </div>
            <br>
            <div class="box-body">
                <form class="form-horizontal" id="frmCreate">
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" style="color: red">名称*：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="name" type="text" class="form-control" placeholder="名称" required value="">
                        </div>
                    </div>
                    @if(request('type') == 'workshop')
                        <div class="form-group">
                            <label class="col-sm-2 control-label" style="color: red">车间编码*：</label>
                            <div class="col-sm-10 col-md-8">
                                <input name="unique_code" type="text" class="form-control" placeholder="{{$workshopUniqueCode}}" required value="{{$workshopUniqueCode}}" disabled>
                            </div>
                            <input name="unique_code" type="hidden" value="{{$workshopUniqueCode}}">
                        </div>
                        {{--                    <div class="form-group">--}}
                        {{--                        <label class="col-sm-2 control-label" style="color: red">线别*：</label>--}}
                        {{--                        <div class="col-sm-10 col-md-8">--}}
                        {{--                            <select class="form-control select2" name="line[]" multiple="multiple" data-placeholder="请选择" style="width: 100%;">--}}
                        {{--                                @foreach($lines as $line)--}}
                        {{--                                    <option value="{{$line->id}}">{{$line->name}}</option>--}}
                        {{--                                @endforeach--}}
                        {{--                            </select>--}}
                        {{--                        </div>--}}
                        {{--                    </div>--}}
                    @else
                        <div class="form-group">
                            <label class="col-sm-2 control-label" style="color: red">车站编码*：</label>
                            <div class="col-sm-10 col-md-8">
                                <input name="unique_code" type="text" class="form-control" placeholder="{{$stationUniqueCode}}" required value="{{$stationUniqueCode}}" disabled>
                            </div>
                            <input name="unique_code" type="hidden" value="{{$stationUniqueCode}}">
                        </div>
                        {{--                        <div class="form-group">--}}
                        {{--                            <label class="col-sm-2 control-label" style="color: red">线别*：</label>--}}
                        {{--                            <div class="col-sm-10 col-md-8">--}}
                        {{--                                <select name="line" class="form-control select2" style="width:100%;">--}}
                        {{--                                    <option value="0" selected disabled>请选择</option>--}}
                        {{--                                    @foreach($lines as $line)--}}
                        {{--                                        <option value="{{$line->id}}">{{$line->name}}</option>--}}
                        {{--                                    @endforeach--}}
                        {{--                                </select>--}}
                        {{--                            </div>--}}
                        {{--                        </div>--}}
                        <div class="form-group">
                            <label class="col-sm-2 control-label" style="color: red">线别*：</label>
                            <div class="col-sm-10 col-md-8">
                                <select class="form-control select2" name="line[]" multiple="multiple" data-placeholder="&nbsp;&nbsp;请选择" style="width: 100%;">
                                    @foreach($lines as $line)
                                        <option value="{{$line->id}}">{{$line->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" style="color: red">车间*：</label>
                            <div class="col-sm-10 col-md-8">
                                <select id="workshop" name="workshop" class="form-control select2" style="width:100%;">
                                    <option value="0" selected disabled>请选择</option>
                                    @foreach($workShops as $workShop)
                                        <option value="{{$workShop->id}}">{{$workShop->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{--                    线别车间二级联动--}}
                        {{--                        <div class="form-group">--}}
                        {{--                            <label class="col-sm-2 control-label" style="color: red">线别*：</label>--}}
                        {{--                            <div class="col-sm-10 col-md-8">--}}
                        {{--                                <select name="line" class="form-control select2" style="width:100%;" onchange="lines(this.value)">--}}
                        {{--                                    <option value="0" selected disabled>请选择</option>--}}
                        {{--                                    @foreach($lines as $line)--}}
                        {{--                                        <option value="{{$line->id}}">{{$line->name}}</option>--}}
                        {{--                                    @endforeach--}}
                        {{--                                </select>--}}
                        {{--                            </div>--}}
                        {{--                        </div>--}}
                        {{--                        <div class="form-group">--}}
                        {{--                            <label class="col-sm-2 control-label" style="color: red">车间*：</label>--}}
                        {{--                            <div class="col-sm-10 col-md-8">--}}
                        {{--                                <select id="workshop" name="workshop" class="form-control select2" style="width:100%;">--}}

                        {{--                                </select>--}}
                        {{--                            </div>--}}
                        {{--                        </div>--}}
                    @endif
                    <div class="form-group">
                        <label class="col-sm-2 control-label">经度：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="lon" type="text" class="form-control" placeholder="经度" required value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">纬度：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="lat" type="text" class="form-control" placeholder="纬度" required value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">联系人：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="contact" type="text" class="form-control" placeholder="联系人" required value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">联系电话：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="contact_phone" type="text" class="form-control" placeholder="联系电话" required value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">联系地址：</label>
                        <div class="col-sm-10 col-md-8">
                            <input name="contact_address" type="text" class="form-control" placeholder="联系地址" required value="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">台账：</label>
                        <div class="col-sm-10 col-md-8" style="padding: 7px 15px 0px">
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="is_show" value="1" checked>显示</label>&nbsp;&nbsp;
                            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="is_show" value="0">不显示</label>
                        </div>
                    </div>
                    <div class="box-footer">
                        @if(request('is_iframe',0) == 0)
{{--                            <a href="{{url('maintain')}}?page={{request('page',1)}}" class="btn btn-default pull-left btn-sm btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default pull-left btn-sm btn-flat"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        @endif
                        <a href="javascript:" onclick="fnCreate()" class="btn btn-success pull-right btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>新建</a>
                    </div>
                </form>
            </div>
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
         * 新建
         */
        fnCreate = function () {
            $.ajax({
                url: "{{url('maintain')}}",
                type: "post",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    alert(response);
                    if ('{{ request('is_iframe',0) }}' === '0')
                        location.href = "{{ url('maintain?page=1') }}";

                },
                error: function (error) {
                    console.log('fail:', error);
                    alert(error.responseText);
                }
            });
        };

        {{--/**--}}
        {{-- * 线别->车间 二级联动--}}
        {{-- */--}}
        {{--lines = function ($lineId) {--}}
        {{--    $.ajax({--}}
        {{--        url: `{{url('maintain/getWorkshop')}}/${$lineId}`,--}}
        {{--        type: "get",--}}
        {{--        success: function (response) {--}}
        {{--            if(response.status === 200){--}}
        {{--                console.log(response);--}}
        {{--                var option = '<option value="0" selected disabled>请选择</option>';--}}
        {{--                for(var i=0; i<response.data.length; i++){--}}
        {{--                    option += `<option value="${response.data[i][0].id}">${response.data[i][0].name}</option>`--}}
        {{--                }--}}
        {{--            }else{--}}
        {{--                var option = '<option value="0" selected disabled>请选择</option>';--}}
        {{--            }--}}
        {{--            $("#workshop").html(option);--}}
        {{--        },--}}
        {{--        error: function (error) {--}}
        {{--            if (error.status == 401) location.href = "{{ url('login') }}";--}}
        {{--        }--}}
        {{--    });--}}
        {{--};--}}
    </script>
@endsection

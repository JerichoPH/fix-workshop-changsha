@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            整件型号管理
            <small>编辑</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('entire/model')}}"><i class="fa fa-users">&nbsp;</i>类型管理</a></li>--}}
{{--            <li class="active">编辑</li>--}}
{{--        </ol>--}}
    </section>
    <form class="form-horizontal" id="frmCreate">
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">编辑整件型号</h3>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right"></div>
                        </div>
                        <br>
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-3 control-label">名称：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        id="txtName"
                                        placeholder="名称"
                                        class="form-control"
                                        type="text"
                                        required
                                        autofocus
                                        onkeydown="if(event.keyCode===13){$('#txtUniqueCode').val(this.value);$('#txtUniqueCode').focus();}"
                                        name="name"
                                        value="{{ $subModel->name }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">设备种类：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select
                                        disabled
                                        id="selCategory"
                                        name="category_unique_code"
                                        class="form-control select2"
                                        style="width: 100%;">
                                        <option value="">{{ $subModel->Category->name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">设备类型：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select
                                        disabled
                                        id="selEntireModel"
                                        name="entire_model_unique_code"
                                        class="form-control select2"
                                        style="width: 100%;">
                                        <option value="">{{ $subModel->Parent->name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="unique_code" id="hidUniqueCode" value="">
                                <label class="col-sm-3 control-label">型号统一代码：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        disabled
                                        id="txtUniqueCode"
                                        placeholder="设备类型统一代码"
                                        class="form-control disabled"
                                        type="text"
                                        required
                                        onkeydown="if(event.keyCode===13){fnCreate();}"
                                        name="unique_code"
                                        value="{{ $subModel->unique_code }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">周期修年：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input
                                        placeholder="周期修年"
                                        class="form-control"
                                        type="number"
                                        min="0"
                                        max="99"
                                        step="1"
                                        required
                                        onkeydown="if(event.keyCode===13){return false;}"
                                        name="fix_cycle_value" value="{{ $subModel->fix_cycle_value }}">
                                </div>
                            </div>
                            <div class="box-footer">
                                @if(request('categoryUniqueCode'))
{{--                                    <a href="{{url('category',request('categoryUniqueCode'))}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                @else
{{--                                    <a href="{{url('entire/subModel')}}?page={{request('page',1)}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                                    <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                                @endif

                                <a href="javascript:" onclick="fnUpdate()" class="btn btn-warning btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>编辑</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </form>
@endsection
@section('script')
    <script>
        let $selCategory = $('#selCategory');
        let $selEntireModel = $('#selEntireModel');
        let $txtUniqueCode = $('#txtUniqueCode');
        let $hidUniqueCode = $('#hidUniqueCode');

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

        /**
         * 编辑
         */
        function fnUpdate() {
            $.ajax({
                url: "{{url('entire/subModel',$subModel->id)}}",
                type: "put",
                data: $("#frmCreate").serialize(),
                success: function (response) {
                    console.log('success:', response);
                    // alert(response);
                    // $('#txtName').focus();
                    location.reload();
                },
                error: function (error) {
                    console.log('fail:', error);
                    if (error.responseText === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                }
            });
        }
    </script>
@endsection

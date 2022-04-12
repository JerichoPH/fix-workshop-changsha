@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection
@section('content')
    <section class="content-header">
        <h1>
            消息中心
            <small>新建消息</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('message/'.request('type','input'))}}?page={{request('page',1)}}"> {{request('type','input') == 'input' ? '收件箱' : '发件箱'}}</a></li>--}}
{{--            <li class="active">新建消息</li>--}}
{{--        </ol>--}}
    </section>

    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-3">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">收件箱</h3>

                        <div class="box-tools">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body no-padding">
                        <ul class="nav nav-pills nav-stacked">
                            <li>
                                <a href="{{url('message/input')}}">
                                    <i class="fa fa-inbox"></i> 收件箱
                                    <span class="label label-primary pull-right">{{$unread_count}}/{{$receive_count}}</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{url('message/send')}}">
                                    <i class="fa fa-envelope-o"></i> 发件箱
                                    <span class="label label-primary pull-right">{{$send_count}}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                {{--<div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">创建新消息</h3>

                        <div class="box-tools">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body no-padding">
                        <ul class="nav nav-pills nav-stacked">
                            <li><a href="#"><i class="fa fa-circle-o text-red"></i> Important</a></li>
                            <li><a href="#"><i class="fa fa-circle-o text-yellow"></i> Promotions</a></li>
                            <li><a href="#"><i class="fa fa-circle-o text-light-blue"></i> Social</a></li>
                        </ul>
                    </div>
                </div>--}}
            </div>
            <div class="col-md-9">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">发送新消息</h3>
                    </div>
                    <div class="box-body">
                        <form class="form-horizontal" id="frmCreate">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">标题：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input type="text" name="title" id="txtTitle" class="form-control" onfocus>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">收件人单位：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="receiver_affiliation" id="selReceiverAffiliation" class="form-control select2" onchange="fnSelectReceiverAffiliation(this.value)">
                                        <option value="">未选择</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">收件人：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="receiver_id" class="form-control select2" id="selReceiverId">
                                        <option value="">未选择</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">任务内容：</label>
                                <div class="col-sm-10 col-md-8">
                                    <textarea id="txaContent" name="content" rows="10" cols="80">
                                        <ol>
                                            <li>这里是个演示
                                            <ol>
                                                <li>演示1.1</li>
                                                <li>演示1.2</li>
                                            </ol>
                                            </li>
                                        </ol>

                                        <ul>
                                            <li>A
                                            <ul>
                                                <li>A.A</li>
                                            </ul>
                                            </li>
                                            <li>B
                                            <ul>
                                                <li>B.A</li>
                                            </ul>
                                            </li>
                                        </ul>
                                    </textarea>
                                </div>
                            </div>
                            {{--                        <div class="form-group">--}}
                            {{--                            <div class="btn btn-default btn-file">--}}
                            {{--                                <i class="fa fa-paperclip"></i> Attachment--}}
                            {{--                                <input type="file" name="attachment">--}}
                            {{--                            </div>--}}
                            {{--                            <p class="help-block">Max. 32MB</p>--}}
                            {{--                        </div>--}}
                        </form>
                    </div>
                    <div class="box-footer">
                        <div class="pull-left">
{{--                            <a href="{{url('message/'.request('type','input'))}}?page={{request('page',1)}}" class="btn btn-default btn-sm btn-flat"><i class="fa fa-arrow-left"></i> 返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-sm btn-flat"><i class="fa fa-arrow-left"></i> 返回</a>
                        </div>
                        <div class="pull-right">
                            <a class="btn btn-primary btn-sm btn-flat" onclick="fnCreate()"><i class="fa fa-envelope-o"></i> 发送</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let accounts = JSON.parse('{!! $accounts !!}');
        let $selReceiverId = $('#selReceiverId');
        let $selReceiverAffiliation = $('#selReceiverAffiliation');

        /**
         * 初始化填充收件人单位列表
         */
        function fnInitReceiverAffiliation() {
            let html = '<option value="">未选择</option>';
            $.each(accounts, function (code, item) {
                html += `<option value="${code}">${item['name']}</option>`;
            });
            $selReceiverAffiliation.html(html);
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            // 初始化 ckeditor
            CKEDITOR.replace('txaContent', {
                toolbar: [
                    {name: 'document', items: ['Source', '-', 'Save', 'NewPage', 'DocProps', 'Preview', 'Print', '-', 'Templates']},
                    {name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']},
                    {name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt']},
                    {name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']},
                    '/',
                    {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                    {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']},
                    {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
                    {name: 'insert', items: ['Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']},
                    '/',
                    {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                    {name: 'colors', items: ['TextColor', 'BGColor']},
                    {name: 'tools', items: ['Maximize', 'ShowBlocks', '-', 'About']}
                ]
            });

            // 初始化填充收件人单位列表
            fnInitReceiverAffiliation();
        });

        /**
         * 新建任务
         */
        function fnCreate() {
            let data = {
                title: $('#frmCreate input[name=title]').val(),
                receiver_id: $('#frmCreate select[name=receiver_id]').val(),
                receiver_affiliation: $('#frmCreate select[name=receiver_affiliation]').val(),
                content: CKEDITOR.instances['txaContent'].getData(),
            };

            $.ajax({
                url: "{{url('message')}}",
                type: "post",
                data: data,
                success: function (response) {
                    console.log(`{{url('message')}} success:`, response);
                    alert(response['message']);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{url('message')}} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 选择收件人单位，填充收件人列表
         * @param code
         */
        function fnSelectReceiverAffiliation(code) {
            let html = '<option value="">未选择</option>';
            $.each(accounts[code]['accounts'], function (index, item) {
                html += `<option value="${item['id']}">${item['nickname']}</option>`;
            });
            $selReceiverId.html(html);
        }
    </script>
@endsection

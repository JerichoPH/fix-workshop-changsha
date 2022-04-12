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
                        <h3 class="box-title">消息详情</h3>
                        <div class="box-tools pull-right">
                            <a href="{{$previous_message_id > 0 ? url('message',$previous_message_id) : 'javascript:'}}" class="btn btn-box-tool" data-toggle="tooltip" title="Previous" {{$previous_message_id > 0 ? '' : 'disabled'}}><i class="fa fa-chevron-left"></i></a>
                            <a href="{{$next_message_id > 0 ? url('message',$next_message_id) : 'javascript:'}}" class="btn btn-box-tool" data-toggle="tooltip" title="Next" {{$next_message_id > 0 ? '' : 'disabled'}}><i class="fa fa-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="box-body no-padding">
                        <div class="mailbox-read-info">
                            <h3>{{$message['title']}}</h3>
                            <h5>发件人: {{$message['sender_affiliation_name']}}:{{$message['sender_name']}}
                                <span class="mailbox-read-time pull-right">{{\Carbon\Carbon::parse($message['created_at'])->format('Y-m-d H:i:s')}}</span>
                            </h5>
                        </div>
                        <div class="mailbox-controls with-border text-center"></div>
                        <div class="mailbox-read-message">
                            {!! $message['content'] !!}
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            <div class="btn-group btn-sm">
                                @if($message['receiver_id'] == session('account.id'))
                                    <button type="button" class="btn btn-default" onclick="modalReply()"><i class="fa fa-reply"></i> 回复</button>
                                @endif
                                {{--<button type="button" class="btn btn-default"><i class="fa fa-share"></i> 转发</button>--}}
                            </div>
                        </div>
                        {{--<button type="button" class="btn btn-default"><i class="fa fa-trash-o"></i> 删除</button>--}}
                        {{--<button type="button" class="btn btn-default"><i class="fa fa-print"></i> 打印</button>--}}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div id="divModalReply"></div>
        <div id="divModalShare"></div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $selReceiverId = $('#selReceiverId');
        let $selReceiverAffiliation = $('#selReceiverAffiliation');
        let $divModalReply = $('#divModalReply');

        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 打开回复窗口
         */
        function modalReply() {
            console.log($divModalReply);
            $.ajax({
                url: `{{url('message/reply')}}`,
                type: 'get',
                data: {
                    sender_id: "{{$message['sender_id']}}",
                    sender_affiliation: "{{$message['sender_affiliation']}}",
                    receiver_id: "{{$message['receiver_id']}}",
                    receiver_affiliation: "{{$message['receiver_affiliation']}}",
                    receiver_name: "{{$message['receiver_name']}}",
                    receiver_affiliation_name: "{{$message['receiver_affiliation_name']}}",
                    title: "{{$message['title']}}",
                },
                async: true,
                success: function (res) {
                    console.log(`{{url('message/reply')}} success:`, res);
                    $divModalReply.html(res);
                    $('#modalReply').modal('show');
                },
                error: function (err) {
                    console.log(`{{url('message/reply')}} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    if (err.status === 421) alert(err['responseJSON']['messages'].join("\r\n"));
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 转发窗口
         */
        function modalShare() {

        }
    </script>
@endsection

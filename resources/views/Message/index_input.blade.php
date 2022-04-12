@extends('Layout.index')
@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            消息中心
            <small>收件箱</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">收件箱</li>--}}
{{--        </ol>--}}
    </section>

    <!-- Main content -->
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-3">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">文件夹</h3>

                        <div class="box-tools">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="box-body no-padding">
                        <ul class="nav nav-pills nav-stacked">
                            <li class="active">
                                <a>
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
                        <h3 class="box-title">Labels</h3>

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
                    </div>--}}
            </div>
            <div class="col-md-9">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">收件箱</h3>

                        <div class="box-tools pull-right">
                            <div class="has-feedback">
                                {{--<input type="text" class="form-control input-sm" placeholder="Search Mail">
                                <span class="glyphicon glyphicon-search form-control-feedback"></span>--}}
                            </div>
                        </div>
                    </div>
                    <div class="box-body no-padding">
                        <div class="mailbox-controls">
                            <a class="btn btn-default btn-sm checkbox-toggle"><i class="fa fa-square-o"></i></a>
                            <a href="{{url('message/create')}}?type=input&page={{request('page',1)}}" class="btn btn-default btn-sm"><i class="fa fa-plus"></i></a>
                            <div class="btn-group">
                                {{--<button type="button" class="btn btn-default btn-sm"><i class="fa fa-trash-o"></i></button>--}}
                                <button type="button" class="btn btn-default btn-sm"><i class="fa fa-reply"></i></button>
                                <button type="button" class="btn btn-default btn-sm"><i class="fa fa-share"></i></button>
                            </div>
                            <a class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a>
                            <div class="pull-right">
                                当前页：{{$messages['current_page']}}，最大页{{$messages['max_page']}}，共：{{$messages['count']}}条
                                <div class="btn-group">
                                    <a class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i></a>
                                    <a class="btn btn-default btn-sm"><i class="fa fa-chevron-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive mailbox-messages">
                            <table class="table table-hover table-striped">
                                <tbody>
                                @foreach($messages['data'] as $message)
                                    <tr>
                                        <td><input type="checkbox"></td>
                                        <td class="mailbox-star"><a><i class="fa {{$message['star'] ? 'fa-star' : 'fa-star-o'}} text-yellow" id="{{$message['id']}}"></i></a></td>
                                        <td class="mailbox-name"><a href="{{url('message',$message['id'])}}?type=input">{{$message['sender_affiliation_name']}}:{{$message['sender_name']}}</a></td>
                                        <td class="mailbox-subject">
                                            @if($message['read_at'])
                                                {{$message['title']}}
                                            @else
                                                <b>{{$message['title']}}</b>
                                            @endif
                                            <i>{{strlen($message['intro']) <30 ? $message['intro'] : substr($message['intro'], 30)}}</i>
                                        </td>
                                        <td class="mailbox-attachment">
                                            <i class="fa fa-paperclip"></i>
                                        </td>
                                        <td class="mailbox-date">{{\Carbon\Carbon::parse($message['created_at'])->format('Y-m-d H:i:s')}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="box-footer no-padding">

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        $(function () {
            //Enable iCheck plugin for checkboxes
            //iCheck for checkbox and radio inputs
            $('.mailbox-messages input[type="checkbox"]').iCheck({
                checkboxClass: 'icheckbox_flat-blue',
                radioClass: 'iradio_flat-blue'
            });

            //Enable check and uncheck all functionality
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $(".mailbox-messages input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $(".mailbox-messages input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });

            // 标星
            $(".mailbox-star").click(function (e) {
                e.preventDefault();
                //detect type
                let $this = $(this).find("a > i");
                let fa = $this.hasClass("fa");

                if (fa) {
                    $this.toggleClass("fa-star");
                    $this.toggleClass("fa-star-o");
                    // 标记消息星标
                    $.ajax({
                        url: `{{url('message/markStar')}}/${$this.attr('id')}`,
                        type: 'put',
                        data: {},
                        async: false,
                        success: function (res) {
                            console.log(`{{url('message/markStar')}}/${$this.attr('id')} success:`, res);
                        },
                        error: function (err) {
                            console.log(`{{url('message/markStar')}}/${$this.attr('id')} fail:`, err);
                            if (err.status === 401) location.href = "{{ url('login') }}";
                            if (err.status === 421) alert(err['responseJSON']['messages'].join("\r\n"));
                            alert(err['responseJSON']['message']);
                        }
                    });
                }
            });
        });
    </script>
@endsection

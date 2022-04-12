@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            临时生产任务管理
            <small>编辑</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('tempTask')}}?page={{request('page', 1)}}"><i--}}
{{--                        class="fa fa-users">&nbsp;</i>临时生产任务管理</a></li>--}}
{{--            <li class="active">编辑</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form class="form-horizontal" action="{{ url('tempTask',$tempTask->id) }}" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="nonce" value="{{ \Jericho\TextHelper::rand() }}">
            <input type="hidden" name="mode" value="PARAGRAPH_TO_PARAGRAPH">
            <input type="hidden" name="organization_type_unique_code" value="FIX_WORKSHOP">
            <input type="hidden" name="receive_paragraph_unique_code" value="{{ env('ORGANIZATION_CODE') }}">
            <div class="row">
                <div class="col-md-7">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">编辑临时生产任务</h3>
                            <!--右侧最小化按钮-->
                            <div class="btn-group pull-right">
                                {{--@if(array_flip($tempTaskStatuses)[$tempTask->status] == '101_UN_PUBLISH')--}}
                                {{--<button class="btn btn-warning btn-flat btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>保存</button>--}}
                                {{--<a href="javascript:" class="btn btn-default btn-flat btn-sm pull-right" onclick="fnPublish({{ $tempTask->id }})"><i class="fa fa-share-alt">&nbsp;</i>发布</a>--}}
                                {{--@endif--}}
                            </div>
                        </div>
                        <br>
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">任务编号*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="serial_number" id="txtSerialNumber" type="text" class="form-control disabled" placeholder="任务编号" required value="{{ old('serial_number',$tempTask->serial_number) }}" disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">任务标题*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="title" id="txtTitle" type="text" class="form-control" placeholder="任务标题" required value="{{ old('title',$tempTask->title) }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">状态：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="status" id="txtStatus" type="text" class="form-control disabled" placeholder="状态" required value="{{ $tempTask->status }}" disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">负责人*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="principal_id" class="form-control select2" id="selPrincipalId" required>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('principal_id', $tempTask->principal_paragraph_original_id) == $account->id ? 'selected' : '' }}>{{ $account->nickname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">任务类型*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="type" class="form-control select2" id="selType" required>
                                        @foreach($tempTaskTypes as $typeUniqueCode => $typeName)
                                            <option value="{{ $typeUniqueCode }}" {{ old('type', array_flip($tempTaskTypes)[$tempTask->type]) == $typeUniqueCode ? 'selected' : '' }}>{{ $typeName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">截止日期：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="expire_at" type="text" class="form-control pull-right" id="dpExpireAt" value="{{ \Carbon\Carbon::parse(old('expire_at', $tempTask->expire_at))->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">说明：</label>
                                <div class="col-sm-10 col-md-8">
                                    <textarea id="txaDescription" class="form-control" name="description" rows="10">{!! old('description', $tempTask->description) !!}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ url('tempTask') }}?page={{ request('page',1) }}" class="btn btn-default btn-flat pull-left btn-sm"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>
                            @if(array_flip($tempTaskStatuses)[$tempTask->status] == '101_UN_PUBLISH')
                                <button class="btn btn-warning btn-flat btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>保存</button>
                                <a href="javascript:" class="btn btn-default btn-flat btn-sm pull-right" onclick="fnPublish()"><i class="fa fa-share-alt">&nbsp;</i>发布</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">上传附件</h3>
                            <!--右侧最小化按钮-->
                            <div class="btn-group pull-right">
                                <a href="javascript:" class="btn btn-default btn-sm btn-flat" onclick="fnAddAccessory()"><i class="fa fa-plus"></i></a>
                            </div>
                        </div>
                        <br>
                        <div class="box-body" id="fileBody">
                            <div class="form-group">
                                <label class="col-sm-3 control-label">已上传：</label>
                            </div>
                            @foreach($tempTask->accessories as $accessory)
                                <div class="form-group" id="accessory_{{ $accessory->id }}">
                                    <label class="col-sm-3 control-label"></label>
                                    <div class="col-sm-9 col-md-9">
                                        {{ $accessory->name }}&emsp;
                                        @if(array_flip($tempTaskStatuses)[$tempTask->status] == '101_UN_PUBLISH')
                                            <a href="javascript:" class="text-danger" onclick="fnDeleteAccessory({{ $accessory->id }})"><i class="fa fa-times"></i></a>&emsp;
                                        @endif
                                        <a href="{{ url('/tempTaskAccessory/download',$accessory->id) }}" class="text-primary" target="_blank"><i class="fa fa-download"></i></a>&emsp;
                                    </div>
                                </div>
                            @endforeach
                            <div class="form-group files">
                                <label class="col-sm-3 control-label">上传新附件：</label>
                            </div>
                            <div class="form-group files" id="file_0">
                                <label class="col-sm-3 control-label"></label>
                                <div class="col-sm-9 col-md-9">
                                    <div class="input-group">
                                        <input type="file" name="files[]">
                                        <div class="input-group-btn">
                                            <a href="javascript:" class="btn btn-default btn-flat" onclick="fnCutAccessory(0)"><i class="fa fa-times"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $selReceiverParagraph = $('#selReceiverParagraph');
        let $selPrincipalId = $('#selPrincipalId');
        let $dpExpireAt = $('#dpExpireAt');
        let $txaDescription = $('#txaDescription');
        let $txtSerialNumber = $('#txtSerialNum');
        let $txtTitle = $('#txtTitle');

        let originAt = moment().startOf('month').format('YYYY-MM-DD');
        let finishAt = moment().endOf('month').format('YYYY-MM-DD');
        let tomorrow = moment().add(1, 'days').format('YYYY-MM-DD');

        // 日期选择器
        $dpExpireAt.datepicker({
            format: "yyyy-mm-dd",
            language: "cn",
            clearBtn: true,
            autoclose: true,
            startDate: tomorrow,
            endData: '9999-12-31',
        });

        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 增加附件
         */
        function fnAddAccessory() {
            let maxFileId = $(`.files`).length;
            maxFileId += 1;
            $('#fileBody').append(`
<div class="form-group files" id="file_${maxFileId}">
    <label class="col-sm-2 control-label"></label>
    <div class="col-sm-10 col-md-8 files">
        <div class="input-group">
            <input type="file" name="files[]">
            <div class="input-group-btn">
                <a href="javascript:" class="btn btn-default btn-flat" onclick="fnCutAccessory(${maxFileId})"><i class="fa fa-times"></i></a>
            </div>
        </div>
    </div>
</div>`);
        }

        /**
         * 减少附件
         */
        function fnCutAccessory(id) {
            $(`#file_${id}`).remove();
        }

        /**
         * 删除附件
         * @param id
         */
        function fnDeleteAccessory(id) {
            $.ajax({
                url: `{{ url('tempTaskAccessory') }}/${id}`,
                type: 'delete',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('tempTaskAccessory') }} success:`, res);
                    $(`#accessory_${id}`).remove();
                },
                error: function (err) {
                    console.log(`{{ url('tempTaskAccessory') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 发布
         */
        function fnPublish() {
            if (confirm('发布后不可撤回，是否确认发布'))
                $.ajax({
                    url: `{{ url('tempTask', $tempTask->id) }}/publish`,
                    type: 'PUT',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('tempTask', $tempTask->id) }}/publish success:`, res);
                        alert(res['msg']);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('tempTask', $tempTask->id) }}/publish fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
        }

        /**
         * 下载
         * @param id
         */
        function fnDownload(id) {
            open(`{{ url('tempTaskAccessory/download') }}/${id}`, '_blank');
            {{--$.ajax({--}}
            {{--    url: `{{ url('tempTaskAccessory/download') }}/${id}`,--}}
            {{--    type: 'GET',--}}
            {{--    data: {},--}}
            {{--    async: true,--}}
            {{--    success: function (res) {--}}
            {{--        console.log(`{{ url('tempTaskAccessory/download') }}/${id} success:`,res);--}}
            {{--    },--}}
            {{--    error: function (err) {--}}
            {{--        console.log(`{{ url('tempTaskAccessory/download') }}/${id} fail:`, err);--}}
            {{--        if (err.status === 401) location.href = "{{ url('login') }}";--}}
            {{--        alert(err['responseJSON']['message']);--}}
            {{--    }--}}
            {{--});--}}
        }
    </script>
@endsection

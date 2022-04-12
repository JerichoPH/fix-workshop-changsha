@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            临时生产任务管理
            <small>新建</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li><a href="{{url('tempTask')}}?page={{request('page',1)}}"><i class="fa fa-users">&nbsp;</i>临时生产任务管理</a></li>--}}
{{--            <li class="active">新建</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form class="form-horizontal" action="{{ url('tempTask') }}" method="post" enctype="multipart/form-data">
            <input type="hidden" name="initiator_id" value="{{ session('account.id') }}">
            <input type="hidden" name="nonce" value="{{ \Jericho\TextHelper::rand() }}">
            <input type="hidden" name="mode" value="PARAGRAPH_TO_PARAGRAPH">
            <input type="hidden" name="organization_type_unique_code" value="FIX_WORKSHOP">
            <input type="hidden" name="receive_paragraph_unique_code" value="{{ env('ORGANIZATION_CODE') }}">
            <div class="row">
                <div class="col-md-7">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">新建临时生产任务</h3>
                            <!--右侧最小化按钮-->
                            <div class="btn-group pull-right">
                                {{--<button class="btn btn-success btn-flat btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>确定</button>--}}
                            </div>
                        </div>
                        <br>
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">任务编号*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="serial_number" id="txtSerialNumber" type="text" class="form-control" placeholder="任务编号" required value="{{ old('serial_number') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">任务标题*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="title" id="txtTitle" type="text" class="form-control" placeholder="任务标题" required value="{{ old('title') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">负责人*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="principal_id" class="form-control select2" id="selPrincipalId" required>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('principal_paragraph_original_id') == $account->id ? 'selected' : '' }}>{{ $account->nickname }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">任务类型*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="type" class="form-control select2" id="selType" required>
                                        @foreach($tempTaskTypes as $typeUniqueCode => $typeName)
                                            <option value="{{ $typeUniqueCode }}" {{ old('type') == $typeUniqueCode ? 'selected' : '' }}>{{ $typeName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">截止日期：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="expire_at" type="text" class="form-control pull-right" id="dpExpireAt" value="{{ old('expire_at', \Carbon\Carbon::now()->addMonth()->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">说明：</label>
                                <div class="col-sm-10 col-md-8">
                                    <textarea id="txaDescription" class="form-control" name="description" rows="10">{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
{{--                            <a href="{{ url('tempTask') }}?page={{ request('page',1) }}" class="btn btn-default btn-flat pull-left btn-sm"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>--}}
                            <a href="#" onclick="javascript :history.back(-1);" class="btn btn-default btn-flat pull-left btn-sm"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>
                            <button class="btn btn-success btn-flat btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>确定</button>
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
                            <div class="form-group files" id="file_0">
                                <label class="col-sm-2 control-label"></label>
                                <div class="col-sm-10 col-md-8">
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
    </script>
@endsection

@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            数据采集单
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--    <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--    <li class="active">基础数据</li>--}}
        {{--    <li class="active">数据采集</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">数据采集单</h3>
                <div class="btn-group btn-group-sm pull-right">
                    <a onclick="$('#modalUpload').modal('show')" class="btn btn-default btn-flat"><i class="fa fa-upload">&nbsp;</i>上传采集单修改数据</a>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-hover table-condensed">
                    <tbody>
                    <tr>
                        <th>编号</th>
                        <th>采集时间</th>
                        <th>采集人</th>
                        <th>类型</th>
                        <th>操作</th>
                    </tr>
                    @foreach($collectionOrders as $collectionOrder)
                        <tr>
                            <td><a href="{{ url('collectionOrder/material') }}?unique_code={{ $collectionOrder->unique_code }}">{{ $collectionOrder->unique_code }}</a></td>
                            <td>{{ $collectionOrder->updated_at }}</td>
                            <td>{{ $collectionOrder->WithStationInstallUser->nickname ?? '' }}</td>
                            <td>{{ $collectionOrder->type->text }}</td>
                            <td>
                                @if(empty($collectionOrder->excel_url) || !is_file(storage_path($collectionOrder->excel_url)))
                                    <a href="javascript:" onclick="makeExcel(`{{ $collectionOrder->unique_code }}`)" class="btn btn-primary btn-sm btn-flat"><i class="fa fa-file-excel-o"></i> 生成Excel</a>
                                @else
                                    <a href="{{ url('collectionOrder/downloadExcel',$collectionOrder->unique_code) }}" class="btn btn-primary btn-sm btn-flat"><i class="fa fa-download"></i> 下载Excel</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /.box-body -->
            @if($collectionOrders->hasPages())
                <div class="box-footer">
                    {!! $collectionOrders->links('vendor.pagination.no_jump') !!}
                </div>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="modal fade" id="modalUpload">
            <form action="{{ url('collectionOrder/upload') }}" method="POST" enctype="multipart/form-data">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">上传数据采集单</h4>
                        </div>
                        <div class="modal-body form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">名称：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="file" name="file">
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                            <button type="submit" class="btn btn-success btn-flat btn-sm"><i class="fa fa-check">&nbsp;</i>确定</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/layer/layer.js"></script>
    <script>
        let $select2 = $(".select2");
        let $modalUpload = $('#modalUpload');

        $(function () {
            if ($select2.length > 0) $select2.select2();
        });

        /**
         * 生成Excel
         * @param uniqueCode
         */
        function makeExcel(uniqueCode) {
            let loading = layer.load(2, {shade: false});
            $.ajax({
                url: `{{ url('collectionOrder/makeExcel') }}/${uniqueCode}`,
                type: 'get',
                data: {},
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    layer.msg(response.msg)
                    location.reload();
                    layer.close(loading);
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    layer.close(loading);
                }
            });
        }
    </script>
@endsection

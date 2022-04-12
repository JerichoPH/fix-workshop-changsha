@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            打印标签
            <small>备品或状态修</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">打印标签（备品或状态修）</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-4">
                                <h3 class="box-title">打印标签 {{$out_entire_instance_correspondences->total()}}</h3>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <div class="input-group-addon">旧设备：</div>
                                    <input id="txtSearchCondition" type="text" class="form-control" onchange="fnStore(this.value)" value="">
                                </div>
                            </div>
                        </div>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right">
                            <a href="javascript:" onclick="downloadLabel()" class="btn btn-default btn-flat">打印标签</a>
                            <a href="{{url('warehouse/breakdownOrder/out')}}" class="btn btn-default btn-flat">状态修出所</a>
                            <a href="javascript:" onclick="del()" class="btn btn-danger btn-flat">删除</a>
                        </div>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-hover table-condensed" id="table">
                            <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" class="checkbox-toggle">
                                </th>
                                <th>唯一编号</th>
                                <th>所编号</th>
                                <th>型号</th>
                                <th>位置</th>
                                <th>新唯一编号</th>
                                <th>新设备库房位置</th>
                                <th>替换</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($out_entire_instance_correspondences as $out_entire_instance_correspondence)
                                <tr>
                                    <td><input type="checkbox" name="labelChecked" value="{{$out_entire_instance_correspondence->id}}"></td>
                                    <td>{{$out_entire_instance_correspondence->old}}</td>
                                    <td>{{@$out_entire_instance_correspondence->WithEntireInstanceOld->serial_number}}</td>
                                    <td>{{@$out_entire_instance_correspondence->WithEntireInstanceOld->model_name}}</td>
                                    <td>
                                        @if(empty($out_entire_instance_correspondence->WithEntireInstanceOld))
                                            ''
                                        @else
                                            {{$out_entire_instance_correspondence->WithEntireInstanceOld->maintain_station_name}}
                                            {{$out_entire_instance_correspondence->WithEntireInstanceOld->maintain_location_code}}
                                        @endif
                                    </td>
                                    <td>{{$out_entire_instance_correspondence->new}}</td>
                                    <td>{{$out_entire_instance_correspondence->WithEntireInstanceNew ? $out_entire_instance_correspondence->WithEntireInstanceNew->WithTier ? $out_entire_instance_correspondence->WithEntireInstanceNew->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $out_entire_instance_correspondence->WithEntireInstanceNew->WithTier->WithShelf->WithPlatoon->WithArea->name . $out_entire_instance_correspondence->WithEntireInstanceNew->WithTier->WithShelf->WithPlatoon->name . $out_entire_instance_correspondence->WithEntireInstanceNew->WithTier->WithShelf->name . $out_entire_instance_correspondence->WithEntireInstanceNew->WithTier->name : '' :''}}</td>
                                    <td style="width: 20%;">
                                        @if($out_entire_instance_correspondence->WithEntireInstanceOld)
                                            @if(array_key_exists($out_entire_instance_correspondence->WithEntireInstanceOld->model_name,$new_entire_instances))
                                                <select class="form-control select2 select-for-print" id="{{$out_entire_instance_correspondence->old}}" style="width: 100%;" onchange="replaceEntireInstance(this.value,`{{$out_entire_instance_correspondence->old}}`)" {{empty($out_entire_instance_correspondence->out_warehouse_sn) ? '' : 'disabled'}}>
                                                    <option value="">未选择</option>
                                                    @foreach($new_entire_instances[$out_entire_instance_correspondence->WithEntireInstanceOld->model_name] as $new_code)
                                                        <option value="{{$new_code}}">{{$new_code}}</option>
                                                    @endforeach
                                                    @if(!empty($out_entire_instance_correspondence->new))
                                                        <option value="{{$out_entire_instance_correspondence->new}}" selected>{{$out_entire_instance_correspondence->new}}</option>
                                                    @endif
                                                </select>
                                            @else
                                                暂无可替换设备
                                            @endif
                                        @else
                                            设备数据丢失
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($entireInstances))
                        @if($entireInstances->hasPages())
                            <div class="box-footer">
                                {{ $entireInstances->appends(['type'=>'OUT'])->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/layer/layer.js"></script>
    <script>
        $select2 = $('.select2');

        $(function () {
            if ($select2.length > 0) $select2.select2();
            // iCheck for checkbox and radio inputs
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $("#table input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $("#table input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    paging: false,
                    lengthChange: true,
                    searching: false,
                    ordering: true,
                    info: true,
                    autoWidth: true,
                    aLengthMenu: [[-1, 100, 200, 500], ["全部", "100条", "200条", "500条"]],
                    language: {
                        sProcessing: "正在加载中...",
                        info: "显示第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "显示 _MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "结果中查询：",
                        paginate: {
                            sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"
                        }
                    }
                });
            }
        });

        /**
         * 添加设备
         * @param value
         */
        function fnStore(value) {
            $.ajax({
                url: `{{url('warehouse/breakdownOrder/outWithEntireInstance')}}/${value}`,
                type: 'post',
                data: {},
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {

                    } else {
                        alert(response.message);
                    }
                    location.reload();
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }


        /**
         *下载标签
         */
        function downloadLabel() {
            //处理数据
            let selected_for_print = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let new_code = $(item).closest('tr').find('td').eq(5).text();
                if (new_code !== '') selected_for_print.push(new_code);
            });
            if (selected_for_print.length > 0) {
                window.open(`{{url('qrcode/printLabel')}}?identityCodes=${JSON.stringify(selected_for_print)}`);
            } else {
                alert('请选择下载标签');
            }
        }

        /**
         * 替换设备/取消替换
         * @param new_code
         * @param old_code
         */
        function replaceEntireInstance(new_code, old_code) {
            let loading = layer.msg('设备替换中');
            $.ajax({
                url: `{{url('warehouse/breakdownOrder/outWithEntireInstance')}}`,
                type: 'put',
                data: {oldIdentityCode: old_code, newIdentityCode: new_code},
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        location.reload();
                        layer.close(loading);
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            });
        }

        /**
         * 删除
         */
        function del() {
            //处理数据
            let ids = [];
            $("input[type='checkbox'][name='labelChecked']:checked").each((index, item) => {
                let id = $(item).val();
                if (id && id !== '') ids.push(id);
            });
            if (ids.length > 0) {
                $.ajax({
                    url: `{{url('warehouse/breakdownOrder/outWithEntireInstance')}}`,
                    type: 'delete',
                    data: {ids: ids},
                    async: true,
                    success: response => {
                        console.log(`success:`, response);
                        if (response.status === 200) {
                            location.reload();
                        } else {
                            alert(response.message);
                            location.reload();
                        }
                    },
                    error: error => {
                        console.log(`error:`, error);
                        if (error.status === 401) location.href = "{{ url('login') }}";
                        alert(error.message);
                        location.reload();
                    }
                });

            } else {
                alert('请选择删除设备');
            }
        }

    </script>
@endsection

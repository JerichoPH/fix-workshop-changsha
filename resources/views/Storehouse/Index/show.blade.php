@extends('Layout.index')
@section('content')
    @include('Layout.alert')
    <section class="invoice">
        <div class="row">
            <div class="col-xs-12">
                <h2 class="page-header">
                    <i class="fa fa-globe"></i> 检修车间设备器材全生命周期管理系统
                    <small class="pull-right">
                        日期：{{\Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$warehouses->created_at)->format('Y-m-d')}}
                    </small>
                </h2>
            </div>
        </div>
        <div class="row invoice-info">
            <div class="col-sm-6 invoice-col">
                <strong>基本信息</strong>
                <address>
                    {{$warehouses->direction['text']}}编码：{{$warehouses->unique_code}}<br>
                    操作人：{{$warehouses->WithAccount ? $warehouses->WithAccount->nickname : ''}}<br>
                    联系电话：{{$warehouses->WithAccount ? $warehouses->WithAccount->phone : ''}}<br>
                    {{$warehouses->direction['text']}}时间：{{explode(' ',$warehouses->created_at)[0]}}<br>
                    方向：{{$warehouses->direction['text']}}<br>
                </address>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>物资编码</th>
                        <th>状态</th>
                        <th>种类</th>
                        <th>型号</th>
                        <th>位置</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($warehouses->WithWarehouseMaterials as $WithWarehouseMaterials)
                        @if($WithWarehouseMaterials->material_type == '整件')
                            <tr>
                                <td>{{ $WithWarehouseMaterials->WithEntireInstance->identity_code }}</td>
                                <td>{{ $WithWarehouseMaterials->WithEntireInstance->status }}</td>
                                <td>{{ $WithWarehouseMaterials->WithEntireInstance->category_name }}</td>
                                <td>{{ $WithWarehouseMaterials->WithEntireInstance->model_name }}</td>
                                <td>
                                    @if(!empty($WithWarehouseMaterials->WithEntireInstance->WithPosition))
                                        <a href="javascript:" onclick="fnLocation(`{{ $WithWarehouseMaterials->WithEntireInstance->identity_code }}`)">
                                            {{ $WithWarehouseMaterials->WithEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $WithWarehouseMaterials->WithEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $WithWarehouseMaterials->WithEntireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $WithWarehouseMaterials->WithEntireInstance->WithPosition->WithTier->WithShelf->name . $WithWarehouseMaterials->WithEntireInstance->WithPosition->WithTier->name . $WithWarehouseMaterials->WithEntireInstance->WithPosition->name }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $WithWarehouseMaterials->WithPartInstance->identity_code }}</td>
                                <td>{{ $WithWarehouseMaterials->WithPartInstance->status }}</td>
                                <td>
                                    {{ $WithWarehouseMaterials->WithPartInstance->Category->name }}
                                    {{ $WithWarehouseMaterials->WithPartInstance->PartCategory->name }}
                                </td>
                                <td>{{ $WithWarehouseMaterials->WithPartInstance->part_model_name }}</td>
                                <td>
                                    @if(!empty($WithWarehouseMaterials->WithPartInstance->WithPosition))
                                        <a href="javascript:" onclick="fnLocation(`{{ $WithWarehouseMaterials->WithPartInstance->identity_code }}`)">
                                            {{ $WithWarehouseMaterials->WithPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $WithWarehouseMaterials->WithPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $WithWarehouseMaterials->WithPartInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $WithWarehouseMaterials->WithPartInstance->WithPosition->WithTier->WithShelf->name . $WithWarehouseMaterials->WithPartInstance->WithPosition->WithTier->name . $WithWarehouseMaterials->WithPartInstance->WithPosition->name }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-6">
            </div>
            <div class="col-xs-6">
                <p class="lead"></p>
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>总计</th>
                            <td>{{$warehouses->WithWarehouseMaterials->count()}}&nbsp;件</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row no-print">
            <div class="col-xs-12">
                <a href="javascript:" onclick="window.history.back(-1)" class="btn btn-default pull-left btn-flat">
                    <i class="fa fa-arrow-left">&nbsp;</i>返回
                </a>
                <a href="javascript:" onclick="window.print()" class="btn btn-primary pull-right btn-flat">
                    <i class="fa fa-print"></i> 打印
                </a>

            </div>
        </div>
    </section>
    <div class="clearfix"></div>

    <!--仓库图片弹窗-->
    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" id="locationShow">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">位置：<span id="title"></span></h4>
                </div>
                <div class="modal-body">
                    <img id="location_img" class="model-body-location" alt="" style="width: 100%;">
                    <div class="spot"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        /**
         * 查找位置
         * @param identity_code
         */
        function fnLocation(identity_code) {
            $.ajax({
                url: `{{url('storehouse/location/getImg')}}/${identity_code}`,
                type: 'get',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        console.log(response);
                        $('#title').text(response.data.location_full_name);
                        let location_img = response.data.location_img;
                        if (location_img) {
                            document.getElementById('location_img').src = location_img;
                            $("#locationShow").modal("show");
                        } else {
                            alert('请联系管理员，绑定位置图片');
                            // location.reload();
                        }
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: error => {
                    console.log(`fail:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    // location.reload();
                }
            });
        }
    </script>
@endsection

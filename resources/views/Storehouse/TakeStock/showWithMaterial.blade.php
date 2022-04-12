@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            {{ $takeStock->name }}
            盘点列表
            <small>{{$takeStockInstances->first()->category_name ?? '' }} {{$takeStockInstances->first()->sub_model_name ?? ''}}</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">盘点{{$takeStockInstances->first()->category_name ?? ''}} {{$takeStockInstances->first()->sub_model_name ?? ''}}</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        {{--查询--}}
        <div class="row">
            <form id="frmScreen">
                <div class="col-md-12">
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h1 class="box-title">查询</h1>
                            {{--右侧最小化按钮--}}
                            <div class="box-tools pull-right">
                                <a href="javascript:" class="btn btn-primary btn-flat" onclick="fnScreen()">查询</a>
                            </div>
                        </div>
                        <div class="box-body form-horizontal">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="input-group">
                                        <div class="input-group-addon">差异</div>
                                        <select id="difference" name="difference" class="form-control select2" style="width:100%;">
                                            <option value="">全部</option>
                                            @foreach($differences as $difference=>$differenceName)
                                                <option value="{{$difference}}" {{request('difference') == $difference ? 'selected' : ''}}>{{$differenceName}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">盘点列表 <small>数量：{{$takeStockInstances->count()}}</small></h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">

                </div>
            </div>

            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <tbody>
                    <tr>
                        <th>盘点编码</th>
                        <th>唯一编码</th>
                        <th>实盘编码</th>
                        <th>仓库位置</th>
                        <th>差异</th>
                    </tr>
                    @foreach($takeStockInstances as $takeStockInstance)
                        <tr>
                            <td>{{$takeStockInstance->take_stock_unique_code}}</td>
                            <td>{{$takeStockInstance->stock_identity_code}}</td>
                            <td>{{$takeStockInstance->real_stock_identity_code}}</td>
                            <td>{{$takeStockInstance->location_name}}</td>
                            <td>{{$takeStockInstance->difference['text']}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>

        let select2 = $('.select2');

        $(function () {
            if (select2.length > 0) select2.select2();
        });


        /**
         * 查询
         */
        function fnScreen() {
            let difference = $("#difference").val();
            location.href = `{{url('storehouse/takeStock/showWithMaterial',$currentTakeStockUniqueCode)}}/{{$currentSubModelUniqueCode}}?difference=` + encodeURIComponent(difference);
        }
    </script>
@endsection

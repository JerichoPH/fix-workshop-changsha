@extends('Layout.index')
@section('content')
<!-- 面包屑 -->
<section class="content-header">
    <h1>
        数据采集设备
    </h1>
    <ol class="breadcrumb">
        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
        <li class="active">基础数据</li>
        <li class="active">数据采集设备</li>
    </ol>
</section>
<section class="content">
    <form action="" method="GET">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">数据采集设备</h3>
            </div>
            <div class="box-body">
                <table class="table table-hover table-condensed">
                    <tbody>
                        <tr>
                            <th>种类</th>
                            <th>类型</th>
                            <th>型号</th>
                            <th>车间</th>
                            <th>车站</th>
                            <th>供应商</th>
                            <th>出厂日期</th>
                            <th>厂编号</th>
                            <th>使用年限</th>
                            <th>周期修时间</th>
                            <th>周期修年限</th>
                            <th>上道时间</th>
                            <th>版本号</th>
                        </tr>
                        @foreach($collectionOrderMaterials as $collectionOrderMaterial)
                        <tr>
                            <td>{{ $collectionOrderMaterial->category_name ?? '' }}</td>
                            <td>{{ $collectionOrderMaterial->entire_model_name ?? '' }}</td>
                            <td>{{ $collectionOrderMaterial->sub_model_name ?? '' }}</td>
                            <td>{{ $collectionOrderMaterial->WithWorkshop->name ?? '' }}</td>
                            <td>{{ $collectionOrderMaterial->WithStation->name ?? '' }}</td>
                            <td>{{ $collectionOrderMaterial->factory_name ?? '' }}</td>
                            <td>{{ $collectionOrderMaterial->ex_factory_at }}</td>
                            <td>{{ $collectionOrderMaterial->factory_number }}</td>
                            <td>{{ $collectionOrderMaterial->service_life }}</td>
                            <td>{{ $collectionOrderMaterial->cycle_fix_at }}</td>
                            <td>{{ $collectionOrderMaterial->cycle_fix_year }}</td>
                            <td>{{ $collectionOrderMaterial->last_installed_at }}</td>
                            <td>{{ $collectionOrderMaterial->version_number }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /.box-body -->
            @if($collectionOrderMaterials->hasPages())
            <div class="box-footer">
                {!! $collectionOrderMaterials->appends(['unique_code'=>request('unique_code')])->links('vendor.pagination.no_jump') !!}
            </div>
            @endif
        </div>
    </form>
</section>
@endsection
@section('script')
<script>
    let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select2();
        });

</script>
@endsection

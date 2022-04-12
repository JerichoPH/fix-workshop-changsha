@extends('Layout.index')
@section('content')
<!-- 面包屑 -->
<section class="content-header">
    <h1>
        器材定位详情
    </h1>
    <ol class="breadcrumb">
        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
        <li class="active">基础数据</li>
        <li class="active">器材定位详情</li>
    </ol>
</section>
<section class="content">
    <form action="" method="GET">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">器材定位详情</h3>
            </div>
            <div class="box-body">
                <table class="table table-hover table-condensed">
                    <tbody>
                        <tr>
                            <th>器材编码</th>
                            <th>上道位置编码</th>
                        </tr>
                        @foreach($collectionOrderMaterials as $collectionOrderMaterial)
                        <tr>
                            <td>{{ $collectionOrderMaterial->entire_instance_identity_code ?? '' }}</td>
                            <td>{{ $collectionOrderMaterial->install_location_unique_code ?? '' }}</td>
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

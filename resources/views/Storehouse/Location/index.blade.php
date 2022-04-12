@extends('Layout.index')
@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">位置打印</h3>
            <div class="box-tools pull-right">
                <a href="javascript:" onclick="print()" class="btn btn-primary btn-flat"><i class="fa fa-print"></i>&nbsp;打印标签</a>
                <a href="{{url('storehouse/location')}}?download=1" class="btn btn-primary pull-right btn-flat">
                    <i class="fa fa-print"></i> 打印标签Excel
                </a>
                <a href="{{url('storehouse/location')}}?download=2" class="btn btn-primary pull-right btn-flat">
                    <i class="fa fa-print"></i> 下载全部Excel
                </a>
            </div>
        </div>

        <div class="box-body material-message">
            <table class="table table-hover table-condensed">
                <tbody>
                <tr>
                    <th>
                        <input type="checkbox" class="checkbox-toggle">
                    </th>
                    <th>仓名称</th>
                    <th>仓编码</th>
                    <th>区名称</th>
                    <th>区编码</th>
                    <th>排名称</th>
                    <th>排编码</th>
                    <th>架名称</th>
                    <th>架编码</th>
                    <th>层名称</th>
                    <th>层编码</th>
                    <th>位名称</th>
                    <th>位编码</th>
                </tr>
                @foreach($locations as $location)
                    <tr>
                        <td><input type="checkbox" name="locationUniqueCodes" value="{{ $location->unique_code }}"></td>
                        <td>{{ $location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name ?? ''}}</td>
                        <td>{{ $location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->unique_code ?? ''}}</td>
                        <td>{{ $location->WithTier->WithShelf->WithPlatoon->WithArea->name ?? ''}}</td>
                        <td>{{ $location->WithTier->WithShelf->WithPlatoon->WithArea->unique_code ?? ''}}</td>
                        <td>{{ $location->WithTier->WithShelf->WithPlatoon->name ?? ''}}</td>
                        <td>{{ $location->WithTier->WithShelf->WithPlatoon->unique_code ?? ''}}</td>
                        <td>{{ $location->WithTier->WithShelf->name ?? ''}}</td>
                        <td>{{ $location->WithTier->WithShelf->unique_code ?? ''}}</td>
                        <td>{{ $location->WithTier->name ?? ''}}</td>
                        <td>{{ $location->WithTier->unique_code ?? ''}}</td>
                        <td>{{ $location->name ?? ''}}</td>
                        <td>{{ $location->unique_code ?? ''}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <!-- /.box-body -->

    </div>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select2();
            $(".checkbox-toggle").click(function () {
                let clicks = $(this).data('clicks');
                if (clicks) {
                    //Uncheck all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("uncheck");
                    $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
                } else {
                    //Check all checkboxes
                    $(".material-message input[type='checkbox']").iCheck("check");
                    $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
                }
                $(this).data("clicks", !clicks);
            });


        });
        /**
         * 打印标签
         */
        function print() {
            let locationUniqueCodes = [];
            $('input[name="locationUniqueCodes"]:checked').each(function (index, element) {
                locationUniqueCodes.push($(this).val());
            });
            if (locationUniqueCodes.length > 0) {
                window.open(`{{url('qrcode/orCodeLocation')}}?locationUniqueCodes=${locationUniqueCodes}`);
            } else {
                alert('请选择');
            }
        }
    </script>
@endsection

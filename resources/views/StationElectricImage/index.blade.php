@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            电子图纸
            <small>列表</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--  <li><a href="/"><i class="fa fa-home"></i> 首页</a></li>--}}
        {{--  <li class="active">基础数据</li>--}}
        {{--  <li class="active">车间/车站管理</li>--}}
        {{--</ol>--}}
    </section>
    <style>
        .change {
            position: absolute;
            overflow: hidden;
            top: 0;
            opacity: 0;
        }
    </style>
    <section class="content">
        @include('Layout.alert')
        <form>
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">车站列表</h3>
                    <!--右侧最小化按钮-->
                    <div class="pull-right btn-group btn-group-sm"></div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>车站名称</th>
                            <th>车站代码</th>
                            <th>电子图纸</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($stations as $station)
                            <tr>
                                <td>{{ $station->name }}</td>
                                <td>{{ $station->unique_code }}</td>
                                <td>
                                    @if($station->ElectricImages->count() > 0)
                                        <a href="javascript:" class="btn btn-default btn-flat btn-sm" onclick="modalElectricImages('{{ $station->unique_code }}')">
                                            {{ $station->ElectricImages->count() }}
                                        </a>
                                    @else
                                        {{ $station->ElectricImages->count() }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </section>

    <section class="section">
        <!--下载电子图纸模态框-->
        <div class="modal fade" id="modalStationElectricImages">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">电子图纸列表</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <table class="table table-condensed table-hover">
                            <thead>
                            <tr>
                                <th>名称</th>
                                <th>下载</th>
                            </tr>
                            </thead>
                            <tbody id="tbody_modalStationElectricImages"></tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $modalStationElectricImages = $('#modalStationElectricImages');
        let $tbody_modalStationElectricImages = $('#tbody_modalStationElectricImages');

        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });

            $('#table').DataTable({
                paging: true,  // 分页器
                lengthChange: false,
                searching: true,  // 搜索框
                ordering: true,  // 列排序
                order: [[2, 'desc']],
                info: true,
                autoWidth: true,  // 自动宽度
                iDisplayLength: 30,  // 默认分页数
                aLengthMenu: [15, 30, 50, 100],  // 分页下拉框选项
                language: {
                    sInfoFiltered: "从_MAX_中过滤",
                    sProcessing: "正在加载中...",
                    info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                    sLengthMenu: "每页显示_MENU_条记录",
                    zeroRecords: "没有符合条件的记录",
                    infoEmpty: " ",
                    emptyTable: "没有符合条件的记录",
                    search: "筛选：",
                    paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                }
            });
        });

        /**
         * 打开电子图纸模态框
         * @param {string} maintainStationUniqueCode
         */
        function modalElectricImages(maintainStationUniqueCode) {
            $.ajax({
                url: `{{ url('stationElectricImage') }}/${maintainStationUniqueCode}`,
                type: 'GET',
                async: false,
                success: function (res) {
                    console.log(`{{ url('stationElectricImage') }}/${maintainStationUniqueCode} success:`, res);
                    let {station_electric_images} = res.data;

                    let html = '';
                    station_electric_images.map(function (item) {
                        html += `<tr>`;
                        html += `<td>${item['original_filename']}</td>`;
                        html += `<td><a class="btn btn-default btn-flat btn-sm" onclick="fnDownloadElectricImage('${item['filename']}')"><i class="fa fa-download"></i></a></td>`;
                        html += `</tr>`;
                    });

                    $tbody_modalStationElectricImages.html(html);
                    $modalStationElectricImages.modal('show');
                },
                fail: function (err) {
                    console.log(`{{ url('stationElectricImage') }}/${maintainStationUniqueCode} fail:`, err);
                    if (error.responseText === 401) location.href = "{{ url('login') }}";
                    alert(error.responseText);
                },
            });
        }

        /**
         * 下载电子图纸
         * @param {string} filename
         */
        function fnDownloadElectricImage(filename) {
            open(filename, '_blank');
        }
    </script>
@endsection

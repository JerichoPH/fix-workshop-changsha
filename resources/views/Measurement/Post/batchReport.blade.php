@extends('Layout.index')
@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection
@section('content')
    <section class="content">
        @include('Layout.alert')
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">批量导入结果</h3>
                {{--右侧最小化按钮--}}
                <div class="box-tools pull-right">
                    <a href="{{url('measurements/batch')}}?page={{request('page',1)}}" class="btn btn-box-tool"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h3>成功列表</h3>
                    <div class="box-body table-responsive">
                        <table class="table table-hover table-condensed" id="table">
                            <thead>
                            <tr>
                                <td>#</td>
                                <th>整件类型名称</th>
                                <th>部件类型名称</th>
                                <th>测试项</th>
                                <th>最小值</th>
                                <th>最大值</th>
                                <th>单位</th>
                                <th>特性</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $a = 0?>
                            @foreach($success as $item)
                                <?php $a += 1?>
                                <tr>
                                    <td>{{$a}}</td>
                                    <td>{{$item['entire_model_unique_code']}}</td>
                                    <td>{{$item['part_model_unique_code']}}</td>
                                    <td>{{$item['key']}}</td>
                                    <td>{{$item['allow_min']}}</td>
                                    <td>{{$item['allow_max']}}</td>
                                    <td>{{$item['unit']}}</td>
{{--                                    <td>{{$item['character']}}</td>--}}
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h3>失败列表</h3>
                    <div class="box-body table-responsive">
                        <table class="table table-hover table-condensed" id="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>整件类型名称</th>
                                <th>部件类型名称</th>
                                <th>测试项</th>
                                <th>最小值</th>
                                <th>最大值</th>
                                <th>单位</th>
                                <th>特性</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $b = 0?>
                            @foreach($fail as $item)
                                <?php $b += 1?>
                                @if($item[0] != '----')
                                    <tr>
                                        <td>{{$b}}</td>
                                        @foreach($item as $val)
                                            <td>{{$val}}</td>
                                        @endforeach

                                    </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script src="/AdminLTE/bower_components/select2/dist/js/select2.full.min.js"></script>
    <!-- iCheck 1.0.1 -->
    <script src="/AdminLTE/plugins/iCheck/icheck.min.js"></script>
    <!-- bootstrap datepicker -->
    <script src="/AdminLTE/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(function () {
            $('.select2').select2();
            // iCheck for checkbox and radio inputs
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
            //Date picker
            $('#datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd'
            });
        });

        /**
         * 删除
         * @param {int} id 编号
         */
        fnDelete = function (id) {
            $.ajax({
                url: "{{url('measurements')}}/" + id,
                type: "delete",
                data: {},
                success: function (response) {
                    // console.log('success:', response);
                    alert(response);
                    location.reload();
                },
                error: function (error) {
                    console.log('fail:', error);
                }
            });
        };
    </script>
@endsection

@extends('Layout.index')
@section('content')
<!-- 面包屑 -->
<section class="content-header">
    <h1>
        车间/车站管理
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
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="box-title">车间/车站列表</h3>
                    </div>
                    <div class="col-md-6">
                        <div class="pull-right btn-group btn-group-sm">
                            {{--<a href="javascript:" class="btn btn-default btn-flat" onclick="fnDistance()"><i>&nbsp;</i>距离计算</a>--}}
                            <di class="input-group">
                                <div class="input-group-addon">搜索</div>
                                <input type="text" name="name" id="txtName" class="form-control"
                                    onkeydown="fnSearchByName(event)" />
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-flat btn-primary" onclick="fnSynchronization()"><i class="fa fa-exclamation">&nbsp;</i>覆盖同步</a>
                                    <a href="{{url('maintain/create')}}?page={{request('page',1)}}&type=workshop" class="btn btn-flat btn-success">新建车间</a>
                                    <a href="{{url('maintain/create')}}?page={{request('page',1)}}&type=station" class="btn btn-flat btn-success">新建车站</a>
                                </div>
                            </di>
                        </div>
                    </div>
                </div>

                <!--右侧最小化按钮-->

                {{--<form class="form-horizontal" id="frmStore" action="" method="POST" enctype="multipart/form-data">--}}
                {{-- <a href="javascript:;" class="btn btn-default btn-flat" onclick="fnDownloadExcel()"><i class="fa fa-cloud-download">&nbsp;</i>下载Excel</a> --}}
                {{--    <a href="javascript:;" class="btn btn-info btn-flat"><i class="fa fa-cloud-upload">&nbsp;</i>上传Excel--}}
                {{--        <input type="file" id="upLoadExcel" name="upLoadExcel" class="change" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" />--}}
                {{--    </a>--}}
                {{--    <a href="javascript:;" class="btn btn-primary btn-flat" onclick="fnUploadExcel()">确定上传</a>--}}
                {{--</form>--}}
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover table-condensed" id="table">
                <thead>
                    <tr>
                        <th>车间</th>
                        <th>车站</th>
                        <th>统一代码</th>
                        <th>类型</th>
                        <th>经度</th>
                        <th>纬度</th>
                        <th>联系人</th>
                        <th>联系电话</th>
                        <th>联系地址</th>
                        <th>是否在台账统计中显示</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($maintains as $maintain)
                    <tr>
                        @if($maintain->type == 'SCENE_WORKSHOP' || $maintain->type == 'WORKSHOP')
                        <td>{{ $maintain->name }}</td>
                        <td></td>
                        <td>{{$maintain->unique_code}}</td>
                        <td>车间</td>
                        @else
                        <td>{{ \Illuminate\Support\Facades\DB::table('maintains')->where('unique_code', $maintain->parent_unique_code)->value('name') }}
                        </td>
                        <td>{{$maintain->name}}</td>
                        <td>{{$maintain->unique_code}}</td>
                        <td>车站</td>
                        @endif
                        <td>{{ $maintain->lon }}</td>
                        <td>{{ $maintain->lat }}</td>
                        <td>{{ $maintain->contact }}</td>
                        <td>{{ $maintain->contact_phone }}</td>
                        <td>{{ $maintain->contact_address }}</td>
                        @if($maintain->is_show)
                        <td>显示</td>
                        @else
                        <td>不显示</td>
                        @endif
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{url('maintain',$maintain->unique_code)}}/edit?type={{ array_flip(\App\Model\Maintain::$TYPES)[$maintain->type] }}&page={{request('page',1)}}"
                                    class="btn btn-default btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                {{--<a href="javascript:" onclick="fnDelete('{{$maintain->id}}')" class="btn btn-danger
                                btn-flat">删除</a>--}}
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($maintains->hasPages())
        <div class="box-footer">
            {{ $maintains->links() }}
        </div>
        @endif
        </div>
    </form>
</section>
@endsection
@section('script')
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
         * 上传Excel
         */
        function fnUploadExcel() {
            var formData = new FormData();
            var name = $("#upLoadExcel").val();
            formData.append('upLoadExcel',$('#upLoadExcel')[0].files[0]);
            formData.append('name',name);
            if (name == null || name == '') {
                alert('请先上传文件')
            }else {
                $.ajax({
                    url: "{{ url('maintain/UploadExcel') }}",
                    type: 'post',
                    data: formData,
                    processData : false,
                    contentType : false,
                    async: true,
                    success: res => {
                        // console.log(res);
                        alert(res.msg);
                        location.reload();
                    },
                    error: err => {
                        console.log(`/maintain/UploadExcel error:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err.responseJSON.msg);
                    }
                });
            }

        }

        /**
         * 计算全部车间车站距离
         */
        function fnDistance() {
            $.ajax({
                url: "{{ url('maintain/distance') }}",
                type: 'post',
                async: true,
                success: res => {
                    // console.log(res);
                    alert(res);
                    {{--window.open(`/fixMissionOrder/DownloadExcel?workAreaId={{ request('workAreaId') }}&dates={{ request('dates') }}&download=1`, '_blank');--}}
                },
                error: err => {
                    console.log(`/fixMissionOrder/UploadExcel error:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err.responseText);
                }
            });
        }

        /**
         * 删除
         * @param {string} id
         */
        fnDelete = id => {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: "{{url('maintain')}}/" + id,
                    type: "delete",
                    data: {},
                    success: function (response) {
                        console.log('success:', response);
                        alert(response);
                        location.reload();
                    },
                    error: function (error) {
                        console.log('fail:', error);
                    }
                });
        };

        /**
         * 从数据中台同步到本地
         */
        function fnSynchronization() {
            $.ajax({
                url: `{{ url('maintain/backupFromSPAS') }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('maintain/backupFromSPAS') }} success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('maintain/backupFromSPAS') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }

        /**
         * 按照名称搜索
         */
        function fnSearchByName(event){
            let $txtName = $('#txtName');
            if(event.keyCode === 13){
                if ($txtName.val()!==''){
                    let params = `?name[operator]=likeb&name[value]=${$txtName.val()}&page={{ request('page',1) }}`;
                    console.log(params);
                    // location.href = `?name[operator]=likeb&name[value]=${$txtName.val()}&page={{ request('page',1) }}`;
                }
            }
        }
</script>
@endsection

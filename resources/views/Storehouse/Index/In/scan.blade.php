@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            入库
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">入库</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">入库结果列表 <small></small></h3>
                        <div class="box-tools pull-right">
                            <a href="javascript:" onclick="fnBind()" class="btn btn-success btn-flat">确认入库</a>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="input-group input-group-lg">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">扫描编码</button>
                            </div>
                            <input type="text" name="material_unique_code" id="materialUniqueCode" autofocus class="form-control" onkeydown="if(event.keyCode===20) fnIn(this.value)" onchange="fnIn(this.value)" placeholder="扫码前点击">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-condensed">
                                <tbody>
                                <tr>
                                    <th>唯一编码</th>
                                    <th>位置</th>
                                    <th>种类</th>
                                    <th>型号名称</th>
                                    <th>类型</th>
                                    <th>操作</th>
                                </tr>
                                @foreach($tmpMaterials as $tmpMaterial)
                                    <tr class="{{$tmpMaterial->location_unique_code == ''? 'bg-success' : ''}}" id="{{$tmpMaterial->material_unique_code}}">
                                        <td>{{$tmpMaterial->material_unique_code}}</td>
                                        <td>
                                            @if ($tmpMaterial->location_unique_code == '')
                                                <i class="fa fa-close text-danger"></i>
                                            @else
                                                <i class="fa fa-check text-success"></i>
                                                {{$tmpMaterial->WithPosition ? $tmpMaterial->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $tmpMaterial->WithPosition->WithTier->WithShelf->WithPlatoon->name . $tmpMaterial->WithPosition->WithTier->WithShelf->name . $tmpMaterial->WithPosition->WithTier->name . $tmpMaterial->WithPosition->name : ''}}
                                            @endif
                                        </td>
                                        @if($tmpMaterial->material_type['value'] == 'ENTIRE')
                                            <td>{{@$tmpMaterial->WithEntireInstance->Category->name}} </td>
                                            <td>{{@$tmpMaterial->WithEntireInstance->model_name}} </td>
                                        @else
                                            <td>{{@$tmpMaterial->WithPartInstance->Category->name}} </td>
                                            <td>{{@$tmpMaterial->WithPartInstance->part_model_name}} </td>
                                        @endif

                                        <td>{{$tmpMaterial->material_type['text']}}</td>
                                        <td>
                                            <a href="javascript:" onclick="fnDel('{{$tmpMaterial->id}}')" class="btn btn-danger btn-flat btn-sm">删除</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        $(function () {
            $("#materialUniqueCode").val('');
        });

        /**
         * 物资入库临时库
         * @param uniqueCode
         */
        function fnIn(uniqueCode) {
            $.ajax({
                url: `{{url('storehouse/index/in')}}`,
                type: 'post',
                data: {
                    unique_code: uniqueCode
                },
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
        }

        /**
         * 删除
         * @param id
         */
        function fnDel(id) {
            $.ajax({
                url: `{{url('storehouse/index/in')}}/${id}`,
                type: 'delete',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status !== 200) alert(response.message);
                    location.reload();
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                }
            });
        }

        /**
         * 确认入库
         */
        function fnBind() {
            $.ajax({
                url: `{{url('storehouse/index/in/confirm')}}`,
                type: 'get',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    if (response.status === 200) {
                        window.location.href = response.data.return_url;
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    // alert(error.message);
                }
            });
        }
    </script>
@endsection

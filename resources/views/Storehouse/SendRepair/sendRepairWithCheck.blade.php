@extends('Layout.index')
@section('style')
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            验收送修设备
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">验收送修设备结果列表</h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right">
                            <a href="{{ url('storehouse/index/in') }}" class="btn btn-success"><i class="fa fa-sign-in"></i>&nbsp;入库</a>
                            <a href="{{ url('storehouse/sendRepair/instance') }}" class="btn btn-warning"><i class="fa fa-paper-plane"></i>&nbsp;送修</a>
                        </div>
                    </div>
                    <br>
                    <div class="box-body">
                        <div class="input-group input-group-lg">
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">唯一编码</button>
                            </div>
                            <input type="text" id="materialUniqueCode" autofocus class="form-control" onkeydown="if(event.keyCode===20) fnIn(this.value)" onchange="fnIn(this.value)" placeholder="扫码前点击">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-condensed">
                                <tbody>
                                <tr>
                                    <th>送修时间</th>
                                    <th>操作人</th>
                                    <th>操作人电话</th>
                                    <th>设备全部数量</th>
                                    <th>设备验收数量</th>
                                    <th>状态</th>
                                    <th>超期</th>
                                </tr>
                                @foreach($sendRepairs as $sendRepair)
                                    <tr>
                                        <td>
                                            <a href="{{url('storehouse/sendRepair',$sendRepair->unique_code)}}">{{$sendRepair->updated_at}}</a>
                                        </td>
                                        <td>{{ $sendRepair->WithAccount->nickname ?? '' }}</td>
                                        <td>{{ $sendRepair->WithAccount->phone ?? '' }}</td>
                                        <td>{{array_key_exists($sendRepair->unique_code,$statistics) ? $statistics[$sendRepair->unique_code]['all'] : 0}}</td>
                                        <td>{{array_key_exists($sendRepair->unique_code,$statistics) ? $statistics[$sendRepair->unique_code]['check'] : 0}}</td>
                                        <td>{{$sendRepair->state['text']}}</td>
                                        <td>
                                            @if(!empty($sendRepair->repair_due_at))
                                                @if($sendRepair->repair_due_at < \Carbon\Carbon::now())
                                                    <span style="color:red;">超期未返回</span>
                                                @endif
                                            @endif
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
         * 设备验收送修设备
         * @param uniqueCode
         */
        function fnIn(uniqueCode) {
            $.ajax({
                url: `{{url('storehouse/sendRepair/sendRepairWithCheck')}}/${uniqueCode}`,
                type: 'put',
                async: true,
                success: response => {
                    console.log(`success:`, response);
                    location.reload();
                },
                error: error => {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.responseJSON.message);
                    location.reload();
                }
            });
        }

    </script>
@endsection

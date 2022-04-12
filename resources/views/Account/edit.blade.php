@extends('Layout.index')
@section('style')
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            用户管理
            <small>编辑</small>
        </h1>
        {{--<ol class="breadcrumb">--}}
        {{--<li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
        {{--<li><a href="{{ url('account') }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>用户管理</a></li>--}}
        {{--<li class="active">编辑</li>--}}
        {{--</ol>--}}
    </section>
    <section class="content">
        <div class="row">
            @include('Layout.alert')
            {{--编辑用户基本信息--}}
            <div class="col-md-8 col-sm-8">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">编辑用户基本信息</h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-3 control-label text-sm">账号：</label>
                                <div class="col-sm-8 col-md-8">
                                    <input name="account" type="text" class="form-control" placeholder="账号" required value="{{ $account->account }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">名称：</label>
                                <div class="col-sm-8 col-md-8">
                                    <input name="nickname" type="text" class="form-control" placeholder="名称" required value="{{ $account->nickname }}">
                                </div>
                            </div>
                            {{--<div class="form-group">--}}
                            {{--    <label class="col-sm-3 control-label">邮箱：</label>--}}
                            {{--    <div class="col-sm-8 col-md-8">--}}
                            {{--        <input name="email" type="email" class="form-control" placeholder="邮箱" required value="{{ $account->email }}">--}}
                            {{--    </div>--}}
                            {{--</div>--}}
                            <div class="form-group">
                                <label class="col-sm-3 control-label">电话：</label>
                                <div class="col-sm-8 col-md-8">
                                    <input name="phone" type="text" class="form-control" placeholder="电话" required value="{{ $account->phone }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">车间：</label>
                                <div class="col-sm-8 col-md-8">
                                    <select name="workshop_unique_code" id="selWorkshop" class="select2 form-control" style="width: 100%;" onchange="fnFillWorkshops(this.value)"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">车站：</label>
                                <div class="col-sm-8 col-md-8">
                                    <select name="station_unique_code" id="selStation" class="select2 form-control" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">工区：</label>
                                <div class="col-sm-8 col-md-8">
                                    <select name="work_area_unique_code" id="selWorkArea" class="select2 form-control" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">验收权限：</label>
                                <div class="col-sm-8 col-md-8">
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="supervision" value="1" {{ $account->supervision == 1 ? 'checked' : '' }}>是</label>
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="supervision" value="0" {{ $account->supervision == 0 ? 'checked' : '' }}>否</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">数据查询范围：</label>
                                <div class="col-sm-8 col-md-8">
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="read_scope" value="1" {{ $account->read_scope == 1 ? 'checked' : '' }}>个人</label>
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="read_scope" value="2" {{ $account->read_scope == 2 ? 'checked' : '' }}>工区</label>
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="read_scope" value="3" {{ $account->read_scope == 3 ? 'checked' : '' }}>车间</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">数据操作范围：</label>
                                <div class="col-sm-8 col-md-8">
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="write_scope" value="1" {{ $account->write_scope == 1 ? 'checked' : '' }}>个人</label>
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="write_scope" value="2" {{ $account->write_scope == 2 ? 'checked' : '' }}>工区</label>
                                    <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="write_scope" value="3" {{ $account->write_scope == 3 ? 'checked' : '' }}>车间</label>
                                </div>
                            </div>
                            {{--<div class="form-group">--}}
                            {{--    <label class="col-sm-3 control-label">临时生产任务角色：</label>--}}
                            {{--    <div class="col-sm-8 col-md-8">--}}
                            {{--        @foreach ($tempTaskPositions as $uniqueCode => $name)--}}
                            {{--            <label style="font-weight: normal; text-align: left;"><input type="radio" class="minimal" name="temp_task_position" value="{{ $uniqueCode }}" {{ $account->temp_task_position == $name ? 'checked' : '' }}>{{ $name }}</label>--}}
                            {{--        @endforeach--}}
                            {{--    </div>--}}
                            {{--</div>--}}

                            <div class="form-group">
                                <label class="col-sm-3 control-label">职级</label>
                                <div class="col-sm-8 col-md-8">
                                    <select name="rank" id="selRank" class="form-control select2" style="width: 100%;">
                                        @foreach($ranks as $rank_code => $rank_name)
                                            <option value="{{ $rank_code }}" {{ $account->rank->code == $rank_code ? 'selected' : '' }}>{{ $rank_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            {{--                            <a href="{{ url('account') }}?page={{ request('page',1) }}" class="btn btn-default pull-left btn-flat btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>--}}
                            <a href="#" onclick="history.back(-1);" class="btn btn-default pull-left btn-flat btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnUpdate()" class="btn btn-warning pull-right btn-flat btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
                        </div>
                    </form>
                </div>
            </div>

            <!--角色绑定-->
            <div class="col-md-4 col-sm-4">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">角色绑定</h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right">
                            <a href="{{url('rbac/role/create')}}" class="btn btn-box-tool"><i class="fa fa-plus-square">&nbsp;</i>新建角色</a>
                        </div>
                    </div>
                    <form class="form-horizontal" id="frmBindRoles">
                        <div class="box-body">
                            @foreach($roles as $role)
                                @if(in_array($role->id,$roleIds))
                                    <label><input type="checkbox" name="role_ids[]" value="{{ $role->id }}" checked>{{ $role->name }}</label>&nbsp;&nbsp;
                                @else
                                    <label><input type="checkbox" name="role_ids[]" value="{{ $role->id }}">{{ $role->name }}</label>&nbsp;&nbsp;
                                @endif
                            @endforeach
                        </div>
                        <div class="box-footer">
                            <a onclick="fnBindRoles('{{ $account->id }}')" class="btn btn-primary pull-right btn-flat btn-sm"><i class="fa fa-check">&nbsp;</i>确定</a>
                        </div>
                    </form>
                </div>
            </div>

            <!--修改密码-->
            @if(session('account.id') == 1)
                <div class="col-md-4 col-sm-4">
                    <div class="box box-danger">
                        <div class="box-header with-border">
                            <h3 class="box-title">修改密码</h3>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right"></div>
                        </div>
                        <form class="form-horizontal" id="frmEditPassword">
                            <div class="box-body">
                                <form class="form-horizontal" id="frmUpdate">
                                    <div class="box-body">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">新密码：</label>
                                            <div class="col-sm-8 col-md-8">
                                                <input name="password" type="password" id="txtPassword" class="form-control" placeholder="新密码" value="">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">重复密码：</label>
                                            <div class="col-sm-8 col-md-8">
                                                <input name="password_confirm" type="password" id="txtPasswordConfirm" class="form-control" placeholder="重复密码" value="">
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="box-footer">
                                <a onclick="fnEditPassword({{ $account->id }})" class="btn btn-danger pull-right btn-flat btn-sm"><i class="fa fa-check">&nbsp;</i>修改密码</a>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $selWorkshop = $('#selWorkshop');
        let $selStation = $('#selStation');
        let $selWorkArea = $('#selWorkArea');
        let $txtPassword = $('#txtPassword');
        let $txtPasswordConfirm = $('#txtPasswordConfirm');

        let workshops = {!! $workshops_as_json !!};
        let stations = {!! $stations_as_json !!};
        let workAreas = {!! $work_areas_as_json !!};

        /**
         * 填充车间下拉列表
         */
        function fnFillWorkshops(workshopUniqueCode = null) {
            if (!workshopUniqueCode) {
                let html = '<option value="">无</option>';
                $.each(workshops, function (k, workshop) {
                    html += `<option value="${workshop['unique_code']}" ${"{{ $account->workshop_unique_code }}" === workshop['unique_code'] ? 'selected' : ''}>${workshop['name']}</option>`;
                });
                $selWorkshop.html(html);
            }

            fnFillStations($selWorkshop.val());
            fnFillWorkAreas($selWorkshop.val());
        }

        /**
         * 填充车站下拉列表
         */
        function fnFillStations(workshopUniqueCode = null) {
            let html = '<option value="">无</option>';
            let tmp = {};

            if (workshopUniqueCode) {
                if (stations.hasOwnProperty(workshopUniqueCode)) {
                    tmp[workshopUniqueCode] = stations[workshopUniqueCode];
                }
            } else {
                tmp = stations;
            }

            if (tmp) {
                $.each(tmp, function (wun, station) {
                    $.each(station, function (k, v) {
                        html += `<option value="${v['unique_code']}" ${"{{ $account->station_unique_code }}" === v['unique_code'] ? 'selected' : ''}>${v['name']}</option>`;
                    });
                });
            }

            $selStation.html(html);
        }

        /**
         * 填充工区下拉列表
         */
        function fnFillWorkAreas(workshopUniqueCode = null) {
            let html = '<option value="">无</option>';
            let tmp = {};

            if (workshopUniqueCode) {
                if (workAreas.hasOwnProperty(workshopUniqueCode)) {
                    tmp[workshopUniqueCode] = workAreas[workshopUniqueCode];
                }
            } else {
                tmp = workAreas;
            }

            if (tmp) {
                $.each(tmp, function (wun, workArea) {
                    $.each(workArea, function (k, v) {
                        html += `<option value="${v['unique_code']}" ${"{{ $account->work_area_unique_code }}" === v['unique_code'] ? 'selected' : ''}>${v['name']}</option>`;
                    });
                });
            }

            $selWorkArea.html(html);
        }

        $(function () {
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });

            $('.select2').select2();

            fnFillWorkshops();
        });

        /**
         * 退回前一页
         */
        function fnBack() {
            location.history(-1);
        }

        /**
         * 编辑
         */
        function fnUpdate() {
            let data = $("#frmUpdate").serializeArray();

            $.ajax({
                url: `{{ url('account',$account->id) }}`,
                type: "put",
                data,
                success: function (res) {
                    console.log('success:', res);
                    location.href = `{{ url('account') }}?page={{ request('page',1) }}`;
                },
                error: function (err) {
                    // console.log('fail:', error);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 绑定用户到角色
         * @param {string} id 用户开放编号
         */
        function fnBindRoles(id) {
            $.ajax({
                url: `{{ url('account/bindRoles') }}/${id}`,
                type: 'post',
                data: $('#frmBindRoles').serializeArray(),
                async: true,
                success: function (res) {
                    console.log(`{{ url('account/bindRoles') }} success:`, res);
                    alert(res.msg);
                },
                error: function (err) {
                    console.log(`{{ url('account/bindRoles') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 修改密码
         * @param {int} accountId 用户编号
         */
        function fnEditPassword(accountId = 0) {
            if (accountId) {
                $.ajax({
                    url: `{{ url('account') }}/${accountId}/editPassword`,
                    type: 'put',
                    data: $('#frmEditPassword').serializeArray(),
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('account') }}/${accountId}/editPassword success:`, res);
                        alert(res.msg);
                        $txtPassword.val('');
                        $txtPasswordConfirm.val('');
                    },
                    error: function (err) {
                        console.log(`{{ url('account') }}/${accountId}/editPassword fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
            }
        }
    </script>
@endsection

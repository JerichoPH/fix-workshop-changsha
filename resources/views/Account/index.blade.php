@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            用户管理
            <small>列表</small>
        </h1>
{{--        <ol class="breadcrumb">--}}
{{--            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--            <li class="active">列表</li>--}}
{{--        </ol>--}}
    </section>
    <section class="content">
        @include('Layout.alert')
        <form>
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">人员列表</h3>
                {{--右侧最小化按钮--}}
                <div class="pull-right btn-group btn-group-sm">
                    {{--<a href="javascript:" id="btnBackupEmployee" class="btn btn-default btn-flat" onclick="fnBackupEmployee()">备份到电务部</a>--}}
                    <a href="{{ url('account/uploadCreateAccountByScene') }}" class="btn btn-default btn-flat">批量上传现场人员</a>
                    <a href="{{ url('account/uploadCreateAccountByParagraph') }}" class="btn btn-default btn-flat">批量上传电务段人员</a>
                    <a href="{{ url('/account/create') }}?page={{ request('page',1) }}" class="btn btn-success btn-flat">新建</a>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="table">
                        <thead>
                        <tr>
                            <th>工区</th>
                            <th>账号</th>
                            <th>姓名</th>
                            <th>验收权限</th>
                            <th>职级</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($accounts as $account)
                            <tr>
                                <td>{{ @$account->WorkAreaByUniqueCode ? $account->WorkAreaByUniqueCode->name : '无' }}</td>
                                <td>{{ $account->account }}</td>
                                <td>{{ $account->nickname }}</td>
                                <td>{{ $account->supervision ? '是' : '否' }}</td>
                                <td>{{ $account->rank->name }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ url('account',$account->id) }}/edit?page={{ request('page',1) }}" class="btn btn-warning btn-sm btn-flat"><i class="fa fa-wrench">&nbsp;</i></a>
                                        <a onclick="fnDelete(`{{ $account->id }}`)" class="btn btn-danger btn-sm btn-flat"><i class="fa fa-trash">&nbsp;</i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($accounts->hasPages())
                <div class="box-footer">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $btnBackupEmployee = $('#btnBackupEmployee');

        /**
         * 删除
         * @param {int} id 开放编号
         */
        let fnDelete = function (id) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: "{{ url('account') }}/" + id,
                    type: "delete",
                    data: {},
                    success: function (res) {
                        console.log('success:', res);
                        alert(res.msg);
                        location.reload();
                    },
                    error: function (err) {
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        console.log('fail:', err);
                        alert(err['responseJSON']['msg']);
                        // location.reload();
                    }
                });
        };

        /**
         * 备份到电务部
         */
        function fnBackupEmployee() {
            $btnBackupEmployee.addClass('disabled').prop('disabled', true).text('备份中，请稍等……');
            $.ajax({
                url: `{{ url('account/backupToGroup') }}`,
                type: 'post',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ url('account/backupToGroup') }} success:`, res);
                    alert(res['message']);
                    $btnBackupEmployee.removeClass('disabled').prop('disabled', false).text('备份到电务部');
                },
                error: function (err) {
                    console.log(`{{ url('account/backupToGroup') }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['message']);
                }
            });
        }
    </script>
@endsection

@extends('Layout.index')
@section('content')
<section class="content">
    @include('Layout.alert')
    <form>
        <div class="box box-solid">
            <div class="box-header">
                <ul class="nav nav-tabs">
                    <li {{ request('type') == 'tab_1' ? "class=active" : ''}}><a href="#tab_1" data-toggle="tab" onclick="fnTabs('tab_1')">任务</a></li>
                    <li {{ request('type') == 'tab_2' ? "class=active" : ''}}><a href="#tab_2" data-toggle="tab" onclick="fnTabs('tab_2')">已完成</a></li>
                    <li {{ request('type') == 'tab_3' ? "class=active" : ''}}><a href="#tab_3" data-toggle="tab" onclick="fnTabs('tab_3')">超期完成</a></li>
                    <li {{ request('type') == 'tab_4' ? "class=active" : ''}}><a href="#tab_4" data-toggle="tab" onclick="fnTabs('tab_4')">未完成</a></li>
                </ul>
                <div class="tab-content">
                    <!--任务设备列表-->
                    <div id="tab_1" class="{{ request('type') === 'tab_1' ? 'tab-pane active' : 'tab-pane' }}">
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" name="all1"></th>
                                            <th>设备编号</th>
                                            <th>所编号</th>
                                            <th>型号</th>
                                            <th>厂家</th>
                                            <th>厂编号</th>
                                            <th>生产日期</th>
                                            <th>检修人</th>
                                            <th>检修时间</th>
                                            <th>验收人</th>
                                            <th>验收时间</th>
                                            <th>抽烟人</th>
                                            <th>抽验时间</th>
                                            <th>状态</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($taskEntireInstances as $taskEntireInstance)
                                        <tr>
                                            <td><input type="checkbox" name="check1" value="{{ $taskEntireInstance->entire_instance_identity_code }}"/></td>
                                            <td><a href="{{ url('search',$taskEntireInstance->entire_instance_identity_code) }}?is_iframe=1">{{ $taskEntireInstance->entire_instance_identity_code }}</a></td>
                                            <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->serial_number : ''}}</td>
                                            <td>
                                                {{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->SubModel ? $taskEntireInstance->EntireInstance->SubModel->name : '') : '' }}
                                                {{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->PartModel ? $taskEntireInstance->EntireInstance->PartModel->name : '') : '' }}
                                            </td>
                                            <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->factory_name : '' }}</td>
                                            <td>{{ $taskEntireInstance->EntireInstance ? $taskEntireInstance->EntireInstance->factory_device_code : '' }}</td>
                                            <td>{{ $taskEntireInstance->EntireInstance ? ($taskEntireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($taskEntireInstance->EntireInstance->made_at)) : '') : '' }}</td>
                                            <td>{{ $taskEntireInstance->Fixer ? $taskEntireInstance->Fixer->nickname : '' }}</td>
                                            <td>{{ $taskEntireInstance->fixed_at ? date('Y-m-d',strtotime($taskEntireInstance->fixed_at)) : '' }}</td>
                                            <td>{{ $taskEntireInstance->Checker ? $taskEntireInstance->Checker->nickname : '' }}</td>
                                            <td>{{ $taskEntireInstance->checked_at ? date('Y-m-d',strtotime($taskEntireInstance->checked_at)) : '' }}</td>
                                            <td>{{ $taskEntireInstance->SpotChecker ? $taskEntireInstance->SpotChecker->nickname : '' }}</td>
                                            <td>{{ $taskEntireInstance->spot_checked_at ? date('Y-m-d',strtotime($taskEntireInstance->spot_checked_at)) : '' }}</td>
                                            <td>{{ $taskEntireInstance->status['name'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($taskEntireInstances->hasPages())
                                <div class="box-footer">
                                    {{ $taskEntireInstances->appends(request()->all())->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <!--已完成设备列表-->
                    <div id="tab_2" class="{{ request('type') === 'tab_2' ? 'tab-pane active' : 'tab-pane' }}">
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" name="all2"></th>
                                            <th>设备编号</th>
                                            <th>所编号</th>
                                            <th>型号</th>
                                            <th>厂家</th>
                                            <th>厂编号</th>
                                            <th>生产日期</th>
                                            <th>检修人</th>
                                            <th>检修时间</th>
                                            <th>验收人</th>
                                            <th>验收时间</th>
                                            <th>抽烟人</th>
                                            <th>抽验时间</th>
                                            <th>状态</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($completedEntireInstances as $completedEntireInstance)
                                        <tr>
                                            <td><input type="checkbox" name="check2" value="{{ $completedEntireInstance->entire_instance_identity_code }}"/></td>
                                            <td><a href="{{ url('search',$completedEntireInstance->entire_instance_identity_code) }}?is_iframe=1">{{ $completedEntireInstance->entire_instance_identity_code }}</a></td>
                                            <td>{{ $completedEntireInstance->EntireInstance ? $completedEntireInstance->EntireInstance->serial_number : ''}}</td>
                                            <td>
                                                {{ $completedEntireInstance->EntireInstance ? ($completedEntireInstance->EntireInstance->SubModel ? $completedEntireInstance->EntireInstance->SubModel->name : '') : '' }}
                                                {{ $completedEntireInstance->EntireInstance ? ($completedEntireInstance->EntireInstance->PartModel ? $completedEntireInstance->EntireInstance->PartModel->name : '') : '' }}
                                            </td>
                                            <td>{{ $completedEntireInstance->EntireInstance ? $completedEntireInstance->EntireInstance->factory_name : '' }}</td>
                                            <td>{{ $completedEntireInstance->EntireInstance ? $completedEntireInstance->EntireInstance->factory_device_code : '' }}</td>
                                            <td>{{ $completedEntireInstance->EntireInstance ? ($completedEntireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($completedEntireInstance->EntireInstance->made_at)) : '') : '' }}</td>
                                            <td>{{ $completedEntireInstance->Fixer ? $completedEntireInstance->Fixer->nickname : '' }}</td>
                                            <td>{{ $completedEntireInstance->fixed_at ? date('Y-m-d',strtotime($completedEntireInstance->fixed_at)) : '' }}</td>
                                            <td>{{ $completedEntireInstance->Checker ? $completedEntireInstance->Checker->nickname : '' }}</td>
                                            <td>{{ $completedEntireInstance->checked_at ? date('Y-m-d',strtotime($completedEntireInstance->checked_at)) : '' }}</td>
                                            <td>{{ $completedEntireInstance->SpotChecker ? $completedEntireInstance->SpotChecker->nickname : '' }}</td>
                                            <td>{{ $completedEntireInstance->spot_checked_at ? date('Y-m-d',strtotime($completedEntireInstance->spot_checked_at)) : '' }}</td>
                                            <td>{{ $completedEntireInstance->status['name'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($completedEntireInstances->hasPages())
                                <div class="box-footer">
                                    {{ $completedEntireInstances->appends(request()->all())->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <!--超期完成设备列表-->
                    <div id="tab_3" class="{{ request('type') === 'tab_3' ? 'tab-pane active' : 'tab-pane' }}">
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed">
                                    <thead>
                                    <tr>
                                        <th><input type="checkbox" name="all3"></th>
                                        <th>设备编号</th>
                                        <th>所编号</th>
                                        <th>型号</th>
                                        <th>厂家</th>
                                        <th>厂编号</th>
                                        <th>生产日期</th>
                                        <th>检修人</th>
                                        <th>检修时间</th>
                                        <th>验收人</th>
                                        <th>验收时间</th>
                                        <th>抽烟人</th>
                                        <th>抽验时间</th>
                                        <th>状态</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($overdueCompletionEntireInstances as $overdueCompletionEntireInstance)
                                        <tr>
                                            <td><input type="checkbox" name="check3" value="{{ $overdueCompletionEntireInstance->entire_instance_identity_code }}"/></td>
                                            <td><a href="{{ url('search',$overdueCompletionEntireInstance->entire_instance_identity_code) }}?is_iframe=1">{{ $overdueCompletionEntireInstance->entire_instance_identity_code }}</a></td>
                                            <td>{{ $overdueCompletionEntireInstance->EntireInstance ? $overdueCompletionEntireInstance->EntireInstance->serial_number : ''}}</td>
                                            <td>
                                                {{ $overdueCompletionEntireInstance->EntireInstance ? ($overdueCompletionEntireInstance->EntireInstance->SubModel ? $overdueCompletionEntireInstance->EntireInstance->SubModel->name : '') : '' }}
                                                {{ $overdueCompletionEntireInstance->EntireInstance ? ($overdueCompletionEntireInstance->EntireInstance->PartModel ? $overdueCompletionEntireInstance->EntireInstance->PartModel->name : '') : '' }}
                                            </td>
                                            <td>{{ $overdueCompletionEntireInstance->EntireInstance ? $overdueCompletionEntireInstance->EntireInstance->factory_name : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->EntireInstance ? $overdueCompletionEntireInstance->EntireInstance->factory_device_code : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->EntireInstance ? ($overdueCompletionEntireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($overdueCompletionEntireInstance->EntireInstance->made_at)) : '') : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->Fixer ? $overdueCompletionEntireInstance->Fixer->nickname : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->fixed_at ? date('Y-m-d',strtotime($overdueCompletionEntireInstance->fixed_at)) : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->Checker ? $overdueCompletionEntireInstance->Checker->nickname : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->checked_at ? date('Y-m-d',strtotime($overdueCompletionEntireInstance->checked_at)) : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->SpotChecker ? $overdueCompletionEntireInstance->SpotChecker->nickname : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->spot_checked_at ? date('Y-m-d',strtotime($overdueCompletionEntireInstance->spot_checked_at)) : '' }}</td>
                                            <td>{{ $overdueCompletionEntireInstance->status['name'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($overdueCompletionEntireInstances->hasPages())
                                <div class="box-footer">
                                    {{ $overdueCompletionEntireInstances->appends(request()->all())->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <!--未完成设备列表-->
                    <div id="tab_4" class="{{ request('type') === 'tab_4' ? 'tab-pane active' : 'tab-pane' }}">
                        <div class="box-header">
                            <h3 class="box-title"></h3>
                            <!--右侧最小化按钮-->
                            <div class="pull-right btn-group btn-group-sm">
                                <a href="javascript:" onclick="fnOpenOverhaul()" class="btn btn-success btn-flat"><i class="fa fa-wrench">&nbsp;</i>检修完成</a>&nbsp;&nbsp;
                                <a href="javascript:" onclick="fnCancelOverhaul()" class="btn btn-default btn-flat"><i class="fa fa-remove">&nbsp;</i>取消分配</a>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed">
                                    <thead>
                                    <tr>
                                        <th><input type="checkbox" name="all4"></th>
                                        <th>设备编号</th>
                                        <th>所编号</th>
                                        <th>型号</th>
                                        <th>厂家</th>
                                        <th>厂编号</th>
                                        <th>生产日期</th>
                                        <th>检修人</th>
                                        <th>检修时间</th>
                                        <th>验收人</th>
                                        <th>验收时间</th>
                                        <th>抽烟人</th>
                                        <th>抽验时间</th>
                                        <th>状态</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($incompleteEntireInstances as $incompleteEntireInstance)
                                        <tr>
                                            <td><input type="checkbox" name="check4" value="{{ $incompleteEntireInstance->entire_instance_identity_code }}"/></td>
                                            <td><a href="{{ url('search',$incompleteEntireInstance->entire_instance_identity_code) }}?is_iframe=1">{{ $incompleteEntireInstance->entire_instance_identity_code }}</a></td>
                                            <td>{{ $incompleteEntireInstance->EntireInstance ? $incompleteEntireInstance->EntireInstance->serial_number : ''}}</td>
                                            <td>
                                                {{ $incompleteEntireInstance->EntireInstance ? ($incompleteEntireInstance->EntireInstance->SubModel ? $incompleteEntireInstance->EntireInstance->SubModel->name : '') : '' }}
                                                {{ $incompleteEntireInstance->EntireInstance ? ($incompleteEntireInstance->EntireInstance->PartModel ? $incompleteEntireInstance->EntireInstance->PartModel->name : '') : '' }}
                                            </td>
                                            <td>{{ $incompleteEntireInstance->EntireInstance ? $incompleteEntireInstance->EntireInstance->factory_name : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->EntireInstance ? $incompleteEntireInstance->EntireInstance->factory_device_code : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->EntireInstance ? ($incompleteEntireInstance->EntireInstance->made_at ? date('Y-m-d',strtotime($incompleteEntireInstance->EntireInstance->made_at)) : '') : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->Fixer ? $incompleteEntireInstance->Fixer->nickname : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->fixed_at ? date('Y-m-d',strtotime($incompleteEntireInstance->fixed_at)) : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->Checker ? $incompleteEntireInstance->Checker->nickname : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->checked_at ? date('Y-m-d',strtotime($incompleteEntireInstance->checked_at)) : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->SpotChecker ? $incompleteEntireInstance->SpotChecker->nickname : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->spot_checked_at ? date('Y-m-d',strtotime($incompleteEntireInstance->spot_checked_at)) : '' }}</td>
                                            <td>{{ $incompleteEntireInstance->status['name'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($incompleteEntireInstances->hasPages())
                                <div class="box-footer">
                                    {{ $incompleteEntireInstances->appends(request()->all())->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<section class="content">
    <!--检修完成-->
    <div class="modal fade" id="modalCompleteOverhaul">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">检修完成</h4>
                </div>
                <div class="modal-body form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-3 col-md-3 control-label">完成日期：</label>
                        <div class="col-sm-9 col-md-8">
                            <input id="deadLine" name="fixed_at" type="text" class="form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                    <a href="javascript:" onclick="fnCompleteOverhaul()" class="btn btn-success btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>确定</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let selEntireModel = $('#selEntireModel');
        let selSubModel = $('#selSubModel');

        $(function () {
            if ($select2.length > 0) {
                $select2.select2();
            }

            let datepickerOption = {
                autoclose: true,
                todayHighlight: true,
                language: "cn",
                format: "yyyy-mm-dd",
                beforeShowDay: $.noop,
                calendarWeeks: false,
                clearBtn: true,
                daysOfWeekDisabled: [],
                endDate: Infinity,
                forceParse: true,
                keyboardNavigation: true,
                minViewMode: 0,
                orientation: "auto",
                rtl: false,
                startDate: -Infinity,
                startView: 0,
                todayBtn: false,
                weekStart: 0
            };
            $('#deadLine').datepicker(datepickerOption);
        });

        // tab_1 全选or全不选
        var all1 = document.getElementsByName("all1")[0];
        var checks1 = document.getElementsByName("check1");
        // 实现全选和全不选
        all1.onclick = function () {
            for (var i = 0; i < checks1.length; i++) {
                checks1[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择
        for (var j = 0; j < checks1.length; j++) {
            checks1[j].onclick = function () {
                var count = 0; //定义一个计数器
                for (var m = 0; m < checks1.length; m++) {
                    //判断是否取消全选
                    if (!checks1[m].checked) {
                        all1.checked = false;
                    } else { //如果是选中状态，计数器+1
                        count++;
                    }
                    //判断是否都是选中状态/如果是则自动选中全选按钮
                    if (count == checks1.length) {
                        all1.checked = true;
                    }
                }
            }
        }

        // tab_2 全选or全不选
        var all2 = document.getElementsByName("all2")[0];
        var checks2 = document.getElementsByName("check2");
        // 实现全选和全不选
        all2.onclick = function () {
            for (var i = 0; i < checks2.length; i++) {
                checks2[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择
        for (var j = 0; j < checks2.length; j++) {
            checks2[j].onclick = function () {
                var count = 0; //定义一个计数器
                for (var m = 0; m < checks2.length; m++) {
                    //判断是否取消全选
                    if (!checks2[m].checked) {
                        all2.checked = false;
                    } else { //如果是选中状态，计数器+1
                        count++;
                    }
                    //判断是否都是选中状态/如果是则自动选中全选按钮
                    if (count == checks2.length) {
                        all2.checked = true;
                    }
                }
            }
        }

        // tab_3 全选or全不选
        var all3 = document.getElementsByName("all3")[0];
        var checks3 = document.getElementsByName("check3");
        // 实现全选和全不选
        all3.onclick = function () {
            for (var i = 0; i < checks3.length; i++) {
                checks3[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择
        for (var j = 0; j < checks3.length; j++) {
            checks3[j].onclick = function () {
                var count = 0; //定义一个计数器
                for (var m = 0; m < checks3.length; m++) {
                    //判断是否取消全选
                    if (!checks3[m].checked) {
                        all3.checked = false;
                    } else { //如果是选中状态，计数器+1
                        count++;
                    }
                    //判断是否都是选中状态/如果是则自动选中全选按钮
                    if (count == checks3.length) {
                        all3.checked = true;
                    }
                }
            }
        }

        // tab_4 全选or全不选
        var all4 = document.getElementsByName("all4")[0];
        var checks4 = document.getElementsByName("check4");
        // 实现全选和全不选
        all4.onclick = function () {
            for (var i = 0; i < checks4.length; i++) {
                checks4[i].checked = this.checked;
            }
        };
        //点击单选时，全选是否被选择
        for (var j = 0; j < checks4.length; j++) {
            checks4[j].onclick = function () {
                var count = 0; //定义一个计数器
                for (var m = 0; m < checks4.length; m++) {
                    //判断是否取消全选
                    if (!checks4[m].checked) {
                        all4.checked = false;
                    } else { //如果是选中状态，计数器+1
                        count++;
                    }
                    //判断是否都是选中状态/如果是则自动选中全选按钮
                    if (count == checks4.length) {
                        all4.checked = true;
                    }
                }
            }
        }

        /**
         * 打开检修完成模态框
         */
        function fnOpenOverhaul() {
            //处理数据
            let selected_for_fix_misson = [];
            $("input[type='checkbox'][name='check4']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_fix_misson.push(new_code);
            });
            if (selected_for_fix_misson.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            $('#modalCompleteOverhaul').modal('show');
        }

        /**
         * 检修完成
         */
        function fnCompleteOverhaul() {
            //处理数据
            let selected_for_fix_misson = [];
            $("input[type='checkbox'][name='check4']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_fix_misson.push(new_code);
            });
            if (selected_for_fix_misson.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            let deadLine = document.getElementById('deadLine').value;
            if (!deadLine) {
                alert('请选择完成日期');
                return false;
            }
            $.ajax({
                url: `{{ url('v250OverhaulEntireInstance') }}/completeOverhaul`,
                type: 'post',
                data: {
                    'selected_for_fix_misson': selected_for_fix_misson,
                    'deadLine': deadLine
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250OverhaulEntireInstance') }}/completeOverhaul success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('v250OverhaulEntireInstance') }}/completeOverhaul fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 取消检修分配
         */
        function fnCancelOverhaul() {
            //处理数据
            let selected_for_fix_misson = [];
            $("input[type='checkbox'][name='check4']:checked").each((index, item) => {
                let new_code = $(item).val();
                if (new_code !== '') selected_for_fix_misson.push(new_code);
            });
            if (selected_for_fix_misson.length <= 0) {
                alert('请先选择设备');
                return false;
            }
            $.ajax({
                url: `{{ url('v250OverhaulEntireInstance') }}/cancelOverhaul`,
                type: 'post',
                data: {
                    'selected_for_fix_misson': selected_for_fix_misson,
                },
                async: true,
                success: function (res) {
                    console.log(`{{ url('v250OverhaulEntireInstance') }}/cancelOverhaul success:`, res);
                    location.reload();
                },
                error: function (err) {
                    console.log(`{{ url('v250OverhaulEntireInstance') }}/cancelOverhaul fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection

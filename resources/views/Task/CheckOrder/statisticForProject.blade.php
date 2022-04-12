@extends('Layout.index')
@section('style')
    <link rel="stylesheet" href="/EasyWeb/spa/assets/libs/layui/css/layui.css"/>
    <link rel="stylesheet" href="/EasyWeb/spa/assets/css/lite.css"/>
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <style>
        #divTb {
            width: 100%;
            overflow-x: scroll;
            white-space: nowrap;
        }

        #divTb::-webkit-scrollbar {
            display: none;
        }
    </style>
@endsection
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修任务完成统计
            <small>{{ $maintain->Parent->name ?? '' }} {{ $maintain->name ?? '' }}</small>
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <div class="row">
                    <div class="col-md-8">
                        <h3 class="box-title">检修任务完成统计</h3>
                    </div>
                    <div class="col-md-4">
                        <div class="pull-right btn-group">
                            <div class="input-group">
                                <div class="input-group-addon">完成时间</div>
                                <input class="form-control" id="expiring_at" name="expiring_at" type="text" placeholder="任务时间" value="" autofocus="">
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-success btn-flat" onclick="fnSearch()">查询</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-body table-responsive table-responsive-xl table-responsive-sm table-responsive-md table-responsive-lg" id="divTb">
                <table class="table table-bordered table-hover table-condensed text-sm" id="table">

                </table>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script type="text/javascript" src="/EasyWeb/spa/assets/libs/layui/layui.js"></script>
    <script>
        let $select2 = $(".select2");
        layui.config({
            base: '/EasyWeb/spa/assets/module/'
        }).use(['layer', 'laydate'], function () {
            let laydate = layui.laydate;
            laydate.render({
                elem: '#expiring_at',
                trigger: 'click',
                range: false,
                type: 'month',
                value: `{{ $expiring_at }}`,
            });
        });

        $(function () {
            if ($select2.length > 0) $select2.select2();
            fnTable();
        });

        /**
         * 渲染页面
         */
        function fnTable() {
            let statistics = JSON.parse(`{!! $statistics !!}`);
            let current_maintain_unique_code = `{!! $current_maintain_unique_code !!}`;
            let html = ``;
            let title = `{!! $title !!}`;
            if (statistics.length > 0) html = `<thead><tr><th>${title} / 项目（完成 / 任务）</th>`;
            let names = {};
            let projects = {};
            $.each(statistics, function (k, s) {
                if (!names.hasOwnProperty(s['unique_code'])) names[s['unique_code']] = s['name'];
                if (!projects.hasOwnProperty(s['project_id'])) projects[s['project_id']] = s['project_name'];
            });
            $.each(projects, function (id, name) {
                html += `<th>${name}</th>`;
            });
            html += `</tr></thead>`;
            let matrixs = {};
            $.each(names, function (unique_code, name) {
                matrixs[unique_code] = {name: name, projects: {}};
                $.each(projects, function (project_id, project_name) {
                    matrixs[unique_code]['projects'][project_id] = {project_name: project_name, mission: 0, finish: 0};
                });
            });
            // 填充数据
            $.each(statistics, function (key, statistic) {
                matrixs[statistic['unique_code']]['projects'][statistic['project_id']][statistic['type']] = statistic['aggregate'];
            });
            $.each(matrixs, function (unique_code, matrix) {
                html += `<tr><td>`;
                if (title === '人员') {
                    html += `${matrix['name']}`
                } else {
                    html += `<a href="javascript:" onclick="fnSearch('${unique_code}')">${matrix['name']}</a>`
                }
                html += `</td>`;
                $.each(matrix['projects'], function (project_id, project) {
                    let color = '';
                    if (project['finish'] === 0 && project['mission'] === 0) {
                        color = '#e6e6e6';
                    } else {
                        if (project['finish'] >= project['mission']) {
                            color = '#09d240';
                        } else {
                            if (project['finish'] > 0) {
                                color = '#f1a01b';
                            } else {
                                color = '#fc7065';
                            }
                        }
                    }
                    if (title === '人员') {
                        html += `<td style="color: ${color}" onclick="fnInstance('${current_maintain_unique_code}','${unique_code}','${project_id}')">${project['finish']} / ${project['mission']}</td>`;
                    } else {
                        html += `<td style="color: ${color}">${project['finish']} / ${project['mission']}</td>`;
                    }
                });
                html += `</tr>`;
            });
            $('#table').html(html);
        }

        /**
         * 搜索
         */
        function fnSearch(unique_code = '') {
            let expiring_at = $('#expiring_at').val();
            location.href = `{{ url('task/checkOrder/statisticForProject') }}?expiring_at=${expiring_at}&maintain_unique_code=${unique_code}`;
        }

        function fnInstance(current_maintain_unique_code, unique_code, project_id) {
            location.href = `{{ url('task/checkOrder/statisticForInstance') }}?station_unique_code=${current_maintain_unique_code}&principal_id_level_5=${unique_code}&project_id=${project_id}`;
        }

    </script>
@endsection


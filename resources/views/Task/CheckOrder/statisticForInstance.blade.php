@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            检修任务统计
            <small>设备列表</small>
        </h1>
    </section>
    <section class="content">
        @include('Layout.alert')
        <form id="frmScreen">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">设备列表
                        <small>
                            {{ $checkProject->type['text'] }}&emsp;{{ $checkProject->name }}&emsp;{{ $maintain->Parent->name ?? '' }}&emsp; {{ $maintain->name ?? '' }}&emsp;{{ $account->nickname }}
                        </small>
                    </h3>
                    <!--右侧最小化按钮-->
                    <div class="box-tools pull-right">
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>唯一编号</th>
                            <th>道岔号</th>
                            <th>种类型</th>
                            <th>完成情况</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entireInstances as $entireInstance)
                            <tr>
                                <td><a href="{{ url('search',$entireInstance->identity_code) }}">{{ $entireInstance->identity_code }}</a></td>
                                <td>{{ $entireInstance->crossroad_number }}</td>
                                <td>{{ $entireInstance->category_name ?? '' }}{{ $entireInstance->entire_model_name ?? '' }}{{ $entireInstance->sub_model_name ?? '' }}</td>
                                <td>
                                    @if($entireInstance->processor_id != 0 && $entireInstance->processed_at != null)
                                        <span style="color: #09d240">完成</span>&emsp;{{ $entireInstance->processed_at }}
                                    @else
                                        <span style="color:#fc7065;">未完成</span>
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
@endsection

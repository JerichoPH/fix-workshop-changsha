@extends('Layout.index')
@section('style')
<!-- Select2 -->
<link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
<!-- AdminLTE Skins. Choose a skin from the css/skins
    folder instead of downloading all of them to reduce the load. -->
<link rel="stylesheet" href="/AdminLTE/dist/css/skins/_all-skins.min.css">
@endsection
@section('content')
<!-- 面包屑 -->
<section class="content-header">
    <h1>
        公文
        <small>列表</small>
    </h1>
{{--    <ol class="breadcrumb">--}}
{{--        <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>--}}
{{--        <li class="active">公文列表</li>--}}
{{--    </ol>--}}
</section>
<section class="content">
    <div class="row">
        <div class="col-md-2 col-md-offset-10">
            @include('Layout.alert')
        </div>
    </div>
    <div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">公文列表</h3>
            {{--右侧最小化按钮--}}
            <div class="tools pull-right btn-group btn-group-sm"></div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover table-condensed" id="table">
                <thead>
                    <tr>
                        <th>标题</th>
                        <th>简介</th>
                        <th>标签</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach( $official_documents as $official_document )
                    <tr>
                        <td>{{ $official_document['title'] }}</td>
                        <td>{{ $official_document['intro'] }}</td>
                        <td>
                            @foreach ($official_document['tags'] as $tag)
                            <span class="label label-default">{{ $tag['name'] }}</span>
                            @endforeach
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ $spas_url . $official_document['file'] }}" target="_blank"
                                    class="btn btn-info btn-flat"><i class="fa fa-download">&nbsp;</i></a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($official_documents->hasPages())
        <div class="box-footer">
            {{ $official_documents->links() }}
        </div>
        @endif
    </div>
</section>
@endsection
@section('script')
<script>
    let $select2 = $('.select2');
    $(function () {
        if ($select2.length > 0) $('.select2').select2();
    });
</script>
@endsection

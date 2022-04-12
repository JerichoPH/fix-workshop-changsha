@extends('Layout.index')
@section('content')
    <section class="content">
        @include('Layout.alert')
        <div class="box">
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-2">
                        <h3 class="box-title">菜单列表</h3>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <select name="parent_id" class="form-control select2" onchange="fnSelectParentMenu(this.value)">
                                <option value="" selected>顶级</option>
                                @foreach($parentMenus as $parentMenuId=>$parentMenuTitle)
                                    <option value="{{$parentMenuId}}" {{$parentMenuId == request('parentId') ? 'selected' : ''}}>{{$parentMenuTitle}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    {{--右侧最小化按钮--}}
                    <div class="col-md-8">
                        <div class="pull-right">
                            <a href="{{url('rbac/menu/create')}}" class="btn btn-success btn-flat btn-sm"><i class="fa fa-plus">&nbsp;</i>新建</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>标题</th>
                        <th>父级</th>
                        <th>排序</th>
                        <th>图标&nbsp;<a href="http://www.fontawesome.com.cn/faicons/#icons" target="_blank">实例</a></th>
                        <th>uri</th>
                        <th>路由别名</th>
                        <th>子标题</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($menus as $menu)
                        <tr>
                            <td>{{$menu->title}}</td>
                            <td>@if($menu->parent){{$menu->parent->title}}@endif</td>
                            <td>{{$menu->sort}}</td>
                            <td><i class="fa fa-{{$menu->icon}}"></i></td>
                            <td>{{$menu->uri}}</td>
                            <td>{{$menu->action_as}}</td>
                            <td>{{$menu->sub_title}}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{url('rbac/menu',$menu->id)}}/edit?page={{request('page',1)}}" class="btn btn-default btn-sm btn-flat"><i class="fa fa-pencil"></i> 编辑</a>
                                    <a href="javascript:" onclick="fnDelete({{$menu->id}})" class="btn btn-danger btn-sm btn-flat">删除</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($menus->hasPages())
                <div class="box-footer">
                    {{ $menus->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
@section('script')
    <script>
        /**
         * 删除
         * @param {int} id 编号
         */
        fnDelete = function (id) {
            if (confirm('删除不可恢复，是否确认？'))
                $.ajax({
                    url: "{{url('rbac/menu')}}/" + id,
                    type: "delete",
                    data: {id: id},
                    success: function (response) {
                        console.log('success:', response);
                        location.reload();
                    },
                    error: function (error) {
                        // console.log('fail:', error);
                        alert(error.responseText);
                        if (error.status == 401) location.href = "{{ url('login') }}";
                    }
                });
        };

        /**
         * 选择父级菜单
         */
        fnSelectParentMenu = parentId => {
            location.href = `{{url('rbac/menu')}}?parentId=${parentId}`;
        };
    </script>
@endsection

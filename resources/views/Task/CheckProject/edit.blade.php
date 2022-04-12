@extends('Layout.index')
@section('content')
    <section class="content">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">编辑现场检修项目</h3>
            </div>
            <div class="box-body">
                <form class="form-horizontal" id="frmUpdate">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" style="color: red">名称*：</label>
                        <div class="col-sm-10 col-md-8">
                            <input class="form-control" name="name" type="text" placeholder="名称" value="{{ $checkProject->name }}" required="required" autofocus="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" style="color: red">类型：</label>
                        <div class="col-sm-10 col-md-8">
                            <select name="type" class="form-control select2" style="width:100%;">
                                @foreach($type as $key=>$typeName)
                                    <option value="{{$key}}" {{ $checkProject->type['value'] == $key ? 'selected' : '' }}>{{$typeName}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="box-footer">
                        <a href="{{url('task/checkProject')}}" class="btn btn-default btn-flat pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                        <a href="javascript:" onclick="fnUpdate(`{{ $checkProject->id }}`)" class="btn btn-warning btn-flat pull-right"><i class="fa fa-check">&nbsp;</i>保存</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $(".select2");
        $(function () {
            if ($select2.length > 0) $select2.select2();
            $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue',
                radioClass: 'iradio_minimal-blue'
            });
        });

        function fnUpdate(id) {
            $.ajax({
                url: `{{url('task/checkProject')}}/${id}`,
                type: 'put',
                data: $("#frmUpdate").serialize(),
                success: function (response) {
                    console.log(`success：`, response)
                    location.href = "{{url('task/checkProject')}}";
                },
                error: function (error) {
                    console.log(`error:`, error);
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error['responseJSON']['msg']);
                    location.reload();
                }
            });
        }
    </script>
@endsection

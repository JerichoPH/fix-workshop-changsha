@section('style')
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/AdminLTE/plugins/iCheck/all.css">
    <!-- bootstrap datepicker -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
@endsection
<div class="modal fade" id="modalAddPartInstance">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">新增部件管理</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>部件种类：</label>
                    <select name="part_category_index" id="selPartCategory" class="form-control" style="width: 100%;" onchange="fnChangePartCategory()">
                        @foreach($part_categories as $part_category)
                            <option value="{{ $part_category->id }}">{{ $part_category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="table-condensed table-responsive table-responsive-sm table-responsive-md table-responsive-lg table-responesive-xl">
                    <table class="table table-condensed table-hover">
                        <thead>
                        <tr>
                            <th>编号</th>
                            <th>仓库位置</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody id="tbodyPartInstances"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-right btn-sm btn-flat" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('.select2').select2();
        // iCheck for checkbox and radio inputs
        $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
        });
        //Date picker
        $('#datepicker').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });

        fnChangePartCategory();  // 填充设备列表
    });

    /**
     * 选择部件种类 刷新表格
     */
    function fnChangePartCategory() {
        let partInstances = JSON.parse('{!! $part_instances_as_json !!}')[$('#selPartCategory').val()];
        $('#tbodyPartInstances').html('');
        let html = '';
        $.each(partInstances, function (index, item) {
            html += `
<tr>
    <td>${item['identity_code']}</td>
    <td>${item['location_unique_code']}</td>
    <td><a href="javascript:" onclick="fnAddPartInstance('${item['identity_code']}')" class="btn btn-success btn-sm btn-flat"><i class="fa fa-check">&nbsp;</i>添加</a></td>
</tr>
`;
            $('#tbodyPartInstances').html(html);
        });
    }

    /**
     * 添加部件
     */
    function fnAddPartInstance(partInstanceIdentityCode) {
        $.ajax({
            url: `{{ route('addPartInstance.post') }}`,
            type: 'post',
            data: {
                entireInstanceIdentityCode: '{{ $entire_instance->identity_code }}',
                partInstanceIdentityCode,
                fixWorkflowSerialNumber:'{{ $fixWorkflowSerialNumber }}'
            },
            async: true,
            success: function (res) {
                console.log(`{{ route('addPartInstance.post') }} success:`, res);
                location.reload();
            },
            error: function (err) {
                console.log(`{{ route('addPartInstance.post') }} fail:`, err);
                if (err.status === 401) location.href = "{{ url('login') }}";
                alert(err['responseJSON']['message']);
            }
        });
    }

</script>

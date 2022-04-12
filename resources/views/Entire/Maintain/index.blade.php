@extends('Layout.index')
@section('content')
    <section class="content">
        @include('Layout.alert')
        <div class="row" style="display: block;">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-sm-8 col-md-8">
                                <h3>台账</h3>
                            </div>
                        </div>
                    </div>
                    <div class="box-body chart-responsive">
                        @foreach($sceneWorkshops as $scu => $scn)
                            <div class="col-md-4">
                                <a href="javascript:" style="color: black;">
                                    <div class="box box-success">
                                        <div class="box-header">
                                            <i class="fa fa-text-width"></i>
                                            <h3 class="box-title">{{ $scn }} <small id="spanSceneWorkshopTitle_{{ $scu }}"></small></h3>
                                        </div>
                                        <div class="box-body">
                                            <div class="chart" id="echartsMaintain_{{ $scu }}" style="height: 300px;"></div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let sceneWorkshops = JSON.parse('{!! $sceneWorkshopsAsJson !!}');

        /**
         * 台账 ✅
         */
        function fnMakeMaintainChart2() {
            $.each(sceneWorkshops, function (scu, scn) {
                let echartsMaintain = echarts.init(document.getElementById(`echartsMaintain_${scu}`));

                echartsMaintain.showLoading();
                $.ajax({
                    url: `{{ url('reportData') }}`,
                    type: 'get',
                    data: {type: 'maintain', sceneWorkshopUniqueCode: scu},
                    async: true,
                    success: function (res) {
                            {{--console.log(`{{ url('reportData') }} success:`, res);--}}
                        let {maintain} = res['data'];

                        let makeOption = function (name, installed, installing, fixing, fixed, returnFactory) {
                            return {
                                color: ['#37A2DA', '#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA'],
                                title: {
                                    text: '',
                                    subtext: '',
                                    x: 'center'
                                },
                                tooltip: {
                                    trigger: 'item',
                                    formatter: "{a} <br/>{b} : {c} ({d}%)"
                                },
                                legend: {
                                    orient: 'vertical',
                                    x: 'left',
                                    data: ['上道', '备品', '在修', '成品', '送修']
                                },
                                toolbox: {
                                    show: true,
                                    feature: {
                                        magicType: {
                                            show: true,
                                            type: ['pie', 'funnel'],
                                            option: {
                                                funnel: {
                                                    x: '25%',
                                                    width: '50%',
                                                    funnelAlign: 'left',
                                                    max: 1548
                                                }
                                            }
                                        },
                                    }
                                },
                                calculable: true,
                                series: [{
                                    name: name,
                                    type: 'pie',
                                    radius: '55%',
                                    center: ['50%', '60%'],
                                    data: [{
                                        value: installed,
                                        name: '上道'
                                    }, {
                                        value: installing,
                                        name: '备品'
                                    }, {
                                        value: fixing,
                                        name: '在修'
                                    }, {
                                        value: fixed,
                                        name: '成品'
                                    }, {
                                        value: returnFactory,
                                        name: '送修'
                                    }]
                                }]
                            };
                        };
                        $(`#spanSceneWorkshopTitle_${scu}`).text(`总数：${maintain['statistics']['device_total']}`);
                        echartsMaintain.setOption(makeOption(
                            scn,
                            maintain['statistics']['INSTALLED'],
                            maintain['statistics']['INSTALLING'],
                            maintain['statistics']['FIXING'],
                            maintain['statistics']['FIXED'],
                            maintain['statistics']['RETURN_FACTORY']
                        ));
                        echartsMaintain.on('click', function (params) {
                            // location.href = `/report/sceneWorkshopWithAllCategory2/${scu}?status=${params.name}`;
                            location.href = `/report/stationsWithSceneWorkshop/${scu}`;
                        });
                        echartsMaintain.hideLoading();
                    },
                    error: function (err) {
                        {{--console.log(`{{ url('reportData') }} fail:`, err);--}}
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['message']);
                    }
                });
            });
        }

        $(function () {
            fnMakeMaintainChart2(); // 生成台账图
            $('.select2').select2();

            if (document.getElementById('table')) {
                $('#table').DataTable({
                    'paging': false,
                    'lengthChange': false,
                    'searching': false,
                    'ordering': true,
                    'info': false,
                    'autoWidth': false
                });
            }

            $('#reservation').daterangepicker();
        });
    </script>
@endsection

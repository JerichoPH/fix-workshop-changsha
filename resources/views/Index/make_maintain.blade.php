<div class="modal fade" id="maintain">
    <div class="modal-dialog">
        <div class="modal-content" style="width: 350px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">台账 | {{$currentWorkshopName}} | {{$currentStationName}}</h4>
            </div>
            <div class="modal-body">
                <div id="echartsMaintain" style="width: 300px;height: 300px"></div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        fnMakeMaintainChart();
    });
    fnMakeMaintainChart = () => {
        let currentMaintain = JSON.parse('{!! $currentMaintain !!}');
        console.log(currentMaintain)
        if (currentMaintain.length <= 0) {
            $("#echartsMaintain").html(`<h1 style="line-height: 300px;text-align: center;">暂无数据</h1>`)
            return false;
        }
        let echartsMaintainObj = echarts.init(document.getElementById('echartsMaintain'));
        let option = {
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
                    value: currentMaintain.installed,
                    name: '上道'
                }, {
                    value: currentMaintain.installing,
                    name: '备品'
                }, {
                    value: currentMaintain.fixing,
                    name: '检修'
                }, {
                    value: currentMaintain.fixed,
                    name: '成品'
                }, {
                    value: currentMaintain.return_factory,
                    name: '送修'
                }]
            }]
        };


        echartsMaintainObj.setOption(option);
        echartsMaintainObj.on('click', function (params) {
            window.open(`/report/sceneWorkshopWithAllCategory2/{{$currentWorkshopCode}}?status=${params.name}`)
        });
    }


</script>

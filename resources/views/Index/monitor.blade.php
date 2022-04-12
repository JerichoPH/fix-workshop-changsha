<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>监控大屏</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
         folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="/AdminLTE/dist/css/skins/_all-skins.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/select2/dist/css/select2.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/AdminLTE/bower_components/font-awesome/css/font-awesome.min.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    {{--css--}}
    <link rel="stylesheet" href="/css/monitor/monitor.css">
</head>

<body class="hold-transition skin-blue layout-top-nav">
<div class="wrapper">
    <div class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="navbar-header">
                <span class="navbar-brand">检修车间设备器材全生命周期管理系统({{ env('ORGANIZATION_NAME') }})</span>
            </div>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li class="dropdown messages-menu">
                        <div id="header-time"></div>
                    </li>
                    <li class="dropdown messages-menu">
                        <a href="{{url('/')}}" style="padding: 15px 15px 0px 0px" class="go-home"><img src="/images/monitor-go-admin.png" title="进入首页" alt="进入首页"></a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <div class="monitor-box">
        <div id="map"></div>
        <div class="monitor-box-left">
            <!--左上 设备状态统计-->
            <div class="left-top">
                <div class="title">
                    <div class="left-top-title">设备状态统计 总数：<span id="materialStateTotal"></span></div>
                    <div class="left-top-sel">
                        <select id="selCategory" class="form-control select2" style="width:100%;"
                                onchange="fnSelCategory(this.value)">
                            <option value="">全部</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->unique_code }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="info">
                    <div id="echartLeftTop"></div>
                </div>
            </div>

            <!--左中 - 资产统计-->
            <div class="left-middle">
                <div class="title">
                    <div class="left-middle-title">资产统计</div>
                </div>
                <div class="info">
                    <div id="echartLeftMiddle"></div>
                </div>
            </div>

            <!--左下- 出入所-->
            <div class="left-bottom">
                <div class="title">
                    <div class="left-bottom-title">出入所统计</div>
                </div>
                <div class="info">
                    <div id="echartLeftBottom"></div>
                </div>
            </div>
        </div>
        <div class="monitor-box-right">
            <!--右上 - 盘点差异分析/故障统计-->
            <div class="right-top">
                <div class="title">
                    <div class="right-top-title">盘点差异分析</div>
                </div>
                <div class="info">
                    <div id="echartRightTop"></div>
                </div>
            </div>
            <!--右中 - 超期统计-->
            <div class="right-middle">
                <div class="title">
                    <div class="right-middle-title">超期统计</div>
                </div>
                <div class="info">
                    <div id="echartRightMiddle"></div>
                </div>
            </div>
            <!--右下 - 备品统计-->
            <div class="right-bottom">
                <div class="title">
                    <div class="right-bottom-title"></div>
                </div>
                <div class="info">
                    <table class="table table-condensed" id="right-bottom-table">

                    </table>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="monitorModal">
        <div class="modal-dialog modal-dialog-centered" style="width:80vw;height:90vh">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">设备列表</h3>
                </div>
                <div class="modal-body">
                    <iframe id="monitorModalFrame" src=""
                            style="width:calc(80vw - 30px);height: calc(90vh - 95px);border:none;margin:auto;display: none">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
    <div id="divMaterial"></div>
</div>
{{--js--}}
<script src="/AdminLTE/bower_components/jquery/dist/jquery.min.js"></script>
<script src="/AdminLTE/bower_components/jquery-ui/jquery-ui.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="/AdminLTE/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<!-- Select2 -->
<script src="/AdminLTE/bower_components/select2/dist/js/select2.full.min.js"></script>
<!-- DataTables -->
<script src="/AdminLTE/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/AdminLTE/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<!-- layer -->
<script type="text/javascript" src="/layer/layer.js"></script>
{{--echarts--}}
<script src="/js/echarts/echarts.min.js"></script>
{{--baidu api js--}}
{{--    <script type="text/javascript"--}}
{{--            src="http://api.map.baidu.com/api?v=3.0&ak=4lEoc9AGdSPWSWbggh6KDny9W0IFpPBU"></script>--}}
{{--    <script type="text/javascript" src="/js/baidu/InfoBox_min.js"></script>--}}
{{--    <script type="text/javascript" src="/js/baidu/Heatmap_min.js"></script>--}}
<script type="text/javascript" src="/baiduOffline/js/mp_load.js"></script>
<script type="text/javascript" src="/baiduOffline/js/bmap_offline_api_v3.0_min.js"></script>
<script type="text/javascript" src="/baiduOffline/js/InfoBox_min.js"></script>
<script type="text/javascript" src="/baiduOffline/js/Heatmap_min.js"></script>

<script>
    let $select2 = $('.select2');
    let maintainStatistics = JSON.parse(`{!! $maintainStatistics !!}`);
    let currentCategoryId = '';
    let currentSceneWorkshopUniqueCode = '';
    let currentStationUniqueCode = '';

    let categories_iframe_raw = JSON.parse(`{!! $categories_iframe !!}`)
    let categories_iframe = []
    categories_iframe_raw.map(a => {
        categories_iframe[a.name] = a.unique_code
    })

    let sceneWorkshopPoints = JSON.parse(`{!! $sceneWorkshopPoints !!}`);
    let stationPolylines = JSON.parse(`{!! $linePoints !!}`);
    let centerPoint = JSON.parse(`{!! $centerPoint !!}`)
    let stationPoints = JSON.parse(`{!! $stationPoints !!}`);

    let monitorShowModal = (url) => {
        let frame = $("#monitorModalFrame")
        let modal = $('#monitorModal')
        let tmp = $('#monitorModal .modal-body').html()
        frame.attr("src", url + '&is_iframe=1');
        frame.on('load', () => {
            if (frame.contents().find('#iframe').length !== 0) {
                $('#monitorModal .modal-title').html('设备详情')
            } else {
                $('#monitorModal .modal-title').html('设备列表')
            }
            frame.show()
            modal.modal('show')
        })
        modal.on('hidden.bs.modal', () => {
            frame.attr("src", '')
            frame.hide()
            frame.remove()
            $('#monitorModal .modal-body').html(tmp)
        })
    }
    $(function () {
        setInterval("getHeaderTime()", 1000);
        fnMap();
        fnInit();

        if ($select2.length > 0) $('.select2').select2();
    });

    /**
     * 定时时间
     */
    function getHeaderTime() {
        let date = new Date();
        this.year = date.getFullYear();
        this.month = "" + (date.getMonth() + 1);
        this.date = date.getDate() < 10 ? "0" + date.getDate() : date.getDate();
        this.hour = date.getHours() < 10 ? "0" + date.getHours() : date.getHours();
        this.minute = date.getMinutes() < 10 ? "0" + date.getMinutes() : date.getMinutes();
        this.second = date.getSeconds() < 10 ? "0" + date.getSeconds() : date.getSeconds();

        let currentTime = this.year + "年" + this.month + "月" + this.date + "日 " + this.hour + "时" + this.minute + "分" + this.second + "秒 ";

        document.getElementById("header-time").innerHTML = currentTime;
    }


    let map = new BMap.Map("map", {
        minZoom: 2,
        maxZoom: 12,
        enableMapClick: false
    });

    function fnMap() {
        let point = new BMap.Point(centerPoint[0],centerPoint[1]);
        map.centerAndZoom(point, 9);
        map.enableScrollWheelZoom(true);   //启用滚轮放大缩小，默认禁用
        map.enableContinuousZoom(true);    //启用地图惯性拖拽，默认禁用
        // let marker = new BMap.Marker(point);
        // map.addOverlay(marker);
        stationPolylines.map(line=>{ //铁路线
            let tmp_line = line.map(([lon,lat])=>{
                return new BMap.Point(lon, lat)
            })
            map.addOverlay(new BMap.Polyline(tmp_line, {
                strokeColor: '#330099',
                strokeWeight: 3,
                strokeOpacity: 1
            }))
        })
        // 车站标点
        $.each(stationPoints, function (stationUniqueCode, stationPoint) {
            addOverlay(map, stationPoint['lon'], stationPoint['lat'],  "/images/tuding-blue.png", 20, 20, stationPoint['name'], stationUniqueCode, stationPoint['scene_workshop_unique_code']);
        });
        // 车间标点
        $.each(sceneWorkshopPoints, function (sceneWorkshopUniqueCode, sceneWorkshopPoint) {
            addOverlay(map, sceneWorkshopPoint['lon'], sceneWorkshopPoint['lat'], "/images/tuding-green.png", 25, 25, sceneWorkshopPoint['name'], sceneWorkshopUniqueCode, '');
        });

        //连接铁路线
        // stations=stationPolylines.map(line=>line.map(point=>{return point['unique_code'] === null??'point'}))
        // stationPolylines.map(line => {
        //     let points = [];
        //     line.map(point => {
        //         if (point['unique_code'] !== null) {
        //             addOverlay(map, point['lon'], point['lat'], "/images/tuding-blue.png", 20, 20, point['name'], point['unique_code'], point['scene_workshop_unique_code']);
        //         }
        //         points.push(new BMap.Point(point['lon'], point['lat']));
        //     })
        //     let polyline = new BMap.Polyline(points, {
        //         strokeColor: '#330099',
        //         strokeWeight: 3,
        //         strokeOpacity: 1
        //     });   //创建折线
        //     map.addOverlay(polyline);
        // })

        //鼠标点击关闭弹框
        let tmpPoint
        map.addEventListener("mousedown", e => {
            tmpPoint = e.pixel
        })
        map.addEventListener("mouseup", e => {
            if (tmpPoint) {
                if (tmpPoint.equals(e.pixel)) {
                    currentSceneWorkshopUniqueCode = '';
                    currentStationUniqueCode = '';
                    fnInit();
                }
            }
            // fnMapCenter();
        });

    }

    function addOverlay(map, lon, lat, icon, iconLength, iconWidth, name, unique_code, scene_workshop_unique_code) {
        let point = new BMap.Point(lon, lat);
        let marker = null;
        if (icon != null) {
            let mapIcon = new BMap.Icon(icon, new BMap.Size(iconLength, iconWidth));
            marker = new BMap.Marker(point, {icon: mapIcon});
        } else {
            marker = new BMap.Marker(point);
        }
        map.addOverlay(marker);

        // 点击事件
        marker.addEventListener("click", function (e) {
                if (scene_workshop_unique_code === '') {
                    currentSceneWorkshopUniqueCode = unique_code;
                    currentStationUniqueCode = '';
                } else {
                    currentSceneWorkshopUniqueCode = scene_workshop_unique_code;
                    currentStationUniqueCode = unique_code;
                }
                fnInit();
                // fnMapCenter(lon, lat);
            }
        );
    }

    function fnMapCenter(lon = '113.019455', lat = '28.200103') {
        let point = new BMap.Point(lon, lat);
        map.centerAndZoom(point, 9);
    }

    function fnInit() {
        if (currentSceneWorkshopUniqueCode === '' && currentStationUniqueCode === '') {
            $('.left-middle .title .left-middle-title').text('资产统计');
            $('.left-bottom .title .left-bottom-title').text('仓库统计');
            $('.right-top .title .right-top-title').text('盘点差异分析');
            $('.right-middle .title .right-middle-title').text('超期统计');
            $('.right-bottom .title .right-bottom-title').text('车间列表');
        }
        if (currentSceneWorkshopUniqueCode !== '' && currentStationUniqueCode === '') {
            let sceneWorkshopName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['name'] : '';
            $('.left-middle .title .left-middle-title').text('资产统计（' + `${sceneWorkshopName}` + '）');
            $('.left-bottom .title .left-bottom-title').text('现场备品统计（' + `${sceneWorkshopName}` + '）');
            $('.right-top .title .right-top-title').text('故障统计（' + `${sceneWorkshopName}` + '）');
            $('.right-middle .title .right-middle-title').text('超期统计（' + `${sceneWorkshopName}` + '）');
            $('.right-bottom .title .right-bottom-title').text('车站列表（' + `${sceneWorkshopName}` + '）');
        }
        if (currentSceneWorkshopUniqueCode !== '' && currentStationUniqueCode !== '') {
            let sceneWorkshopName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['name'] : '';
            let stationName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'].hasOwnProperty(currentStationUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'][currentStationUniqueCode]['name'] : '' : '';
            $('.left-middle .title .left-middle-title').text('资产统计（' + `${sceneWorkshopName}/${stationName}` + '）');
            $('.left-bottom .title .left-bottom-title').text('现场备品统计（' + `${sceneWorkshopName}/${stationName}` + '）');
            $('.right-middle .title .right-middle-title').text('超期统计（' + `${sceneWorkshopName}/${stationName}` + '）');
            $('.right-top .title .right-top-title').text('故障统计（' + `${sceneWorkshopName}/${stationName}` + '）');
            $('.right-bottom .title .right-bottom-title').text('车站列表（' + `${sceneWorkshopName}/${stationName}` + '）');
        }

        fnMakeLeftTop(); //左上 - 设备状态统计
        fnMakeLefiMiddle(); //左中 - 资产统计
        fnMakeLeftBottom();   //左下 - 出入所统计
        fnMakeRightTop(); //右上 - 周期修
        fnMakeRightMiddle();  // 右中 - 超期使用
        fnMakeRightBottom(); //右下 - 备品统计
    }

    /**
     * 百度地图
     */
    // function fnMap() {
    //     //配置
    //     let map = new BMap.Map("map", {
    //         minZoom: 2,
    //         maxZoom: 12,
    //         enableMapClick: false
    //     });
    //     let point = new BMap.Point(113.019455, 28.200103);
    //     map.centerAndZoom(point, 9);
    //     map.enableScrollWheelZoom(true);   //启用滚轮放大缩小，默认禁用
    //     map.enableContinuousZoom(true);    //启用地图惯性拖拽，默认禁用
    //     // let marker = new BMap.Marker(point);
    //     // map.addOverlay(marker);

    {{--//     let sceneWorkshopPoints = JSON.parse(`{!! $sceneWorkshopPoints !!}`);--}}
    {{--//     let stationPolylines = JSON.parse(`{!! $stationPolylines !!}`);--}}

    //     // 车间标点
    //     $.each(sceneWorkshopPoints, function (k, sceneWorkshopPoint) {
    //         addOverlay(map, sceneWorkshopPoint['lon'], sceneWorkshopPoint['lat'], "/images/dian-green.png", 32, 32, sceneWorkshopPoint['name'], sceneWorkshopPoint['unique_code'], '');
    //     });
    //     //连接铁路线
    //     $.each(stationPolylines, function (k, value) {
    //         let points = [];
    //         $.each(value, function (key, station) {
    //             if (station['unique_code'] !== null) {
    //                 addOverlay(map, station['lon'], station['lat'], "/images/dian-blue.png", 16, 16, station['name'], station['unique_code'], station['scene_workshop_unique_code']);
    //             }
    //             points.push(new BMap.Point(station['lon'], station['lat']));
    //         })
    //         let polyline = new BMap.Polyline(points, {
    //             strokeColor: '#848282',
    //             strokeWeight: 3,
    //             strokeOpacity: 1
    //         });   //创建折线
    //         map.addOverlay(polyline);   //增加折线
    //     });

    //     //鼠标点击关闭弹框
    //     map.addEventListener("mousedown", function (e) {
    //         currentSceneWorkshopUniqueCode = '';
    //         currentStationUniqueCode = '';
    //         fnInit();
    //     });
    // }

    /**
     * 加载点
     */
    // function addOverlay(map, lon, lat, icon, iconLength, iconWidth, name, unique_code, scene_workshop_unique_code) {
    //     let point = new BMap.Point(lon, lat);
    //     let marker = null;
    //     if (icon != null) {
    //         let mapIcon = new BMap.Icon(icon, new BMap.Size(iconLength, iconWidth));
    //         marker = new BMap.Marker(point, {icon: mapIcon});
    //     } else {
    //         marker = new BMap.Marker(point);
    //     }
    //     map.addOverlay(marker);

    //     // 点击事件
    //     marker.addEventListener("click", function (e) {
    //             if (scene_workshop_unique_code === '') {
    //                 currentSceneWorkshopUniqueCode = unique_code;
    //                 currentStationUniqueCode = '';
    //             } else {
    //                 currentSceneWorkshopUniqueCode = scene_workshop_unique_code;
    //                 currentStationUniqueCode = unique_code;
    //             }
    //             fnInit();
    //         }
    //     );
    // }

    /**
     * 选择种类
     */
    function fnSelCategory(categoryId) {
        currentCategoryId = categoryId;
        fnMakeLeftTop();
    }

    /**
     * 左上 - 设备状态统计
     */
    function fnMakeLeftTop() {
        $.ajax({
            url: `{{url('monitor/leftTop')}}`,
            type: 'get',
            data: {
                categoryId: currentCategoryId,
                sceneWorkshopUniqueCode: currentSceneWorkshopUniqueCode,
                stationUniqueCode: currentStationUniqueCode
            },
            async: true,
            success: response => {
                if (response.status === 200) {
                    let materialStates = response.data.materialStates;
                    let materialStateNames = response.data.materialStateNames;
                    let materialStatistics = response.data.materialStatistics;
                    let legend = [];
                    let data = [];
                    let materialStateTotal = 0;
                    $.each(materialStates, function (code, name) {
                        if (materialStatistics.hasOwnProperty(code)) {
                            data.push({value: materialStatistics[code], name: name + ':' + materialStatistics[code]})
                            legend.push(name + ':' + materialStatistics[code]);
                            materialStateTotal += materialStatistics[code];
                        } else {
                            data.push({value: 0, name: name + ':' + '0'})
                            legend.push(name + ':' + '0');
                        }
                    });
                    $('#materialStateTotal').text(materialStateTotal);
                    let option = {
                        color: ['#9FE6B8', '#FFDB5C', '#FF9F7F', '#FB7293', '#8378EA', '#37A2DA'],
                        tooltip: {
                            trigger: 'item',
                            formatter: params => {
                                return `${params.seriesName}<br>${params.name.split(':')[0]}：${params.value}<br>`
                            },
                        },
                        legend: {
                            orient: 'vertical',
                            x: 'left',
                            data: legend,
                            top: '20%',
                            left: '5%',
                            textStyle: {
                                fontSize: '14',
                                color: '#FFFFFF'
                            }
                        },
                        series: [{
                            name: '设备动态统计',
                            type: 'pie',
                            radius: ['50%', '70%'],
                            center: ['60%', '50%'],
                            avoidLabelOverlap: false,
                            label: {
                                normal: {
                                    show: false,
                                    position: 'center',
                                },
                                emphasis: {
                                    show: true,
                                    textStyle: {
                                        fontSize: '18',
                                        fontWeight: 'bold',
                                    }
                                }
                            },
                            labelLine: {normal: {show: true}},
                            data: data,
                        }]
                    };
                    let echartLeftTop = echarts.init(document.getElementById('echartLeftTop'));
                    echartLeftTop.off('click');
                    echartLeftTop.setOption(option);
                    echartLeftTop.on('click', function (params) {
                        let statuses = {
                            "成品": "FIXED",
                            "上道": "INSTALLED",
                            "送检": "RETURN_FACTORY",
                            "维修": "FIXING",
                            "备品": "INSTALLING",
                            "报废": "UNINSTALLED",
                            "下道": "SCRAP"
                        };
                        let stationName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'].hasOwnProperty(currentStationUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'][currentStationUniqueCode]['name'] : '' : '';
                        let url = `{{url('/entire/instance')}}?category_unique_code=${currentCategoryId}&status_unique_code=${statuses[params.data.name.split(':')[0]]}&scene_workshop_unique_code=${currentSceneWorkshopUniqueCode}&station_name=${stationName}`
                        monitorShowModal(url)
                    });
                } else {
                    alert(response.message);
                    // location.reload();
                }
            },
            error: error => {
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.message);
                // location.reload();
            }
        });
    }

    /**
     * 左中 - 资产统计
     */
    function fnMakeLefiMiddle() {
        $.ajax({
            url: `{{url('monitor/leftMiddle')}}`,
            type: 'get',
            data: {
                sceneWorkshopUniqueCode: currentSceneWorkshopUniqueCode,
                stationUniqueCode: currentStationUniqueCode
            },
            async: true,
            success: response => {
                if (response.status === 200) {
                    let properties = response.data.properties;
                    let categories = {};
                    let serieData = [];
                    let xAxis = [];
                    if (properties.length > 0) {
                        $.each(properties, function (k, value) {
                            xAxis.push(value.category_name);
                            serieData.push(value.count);
                            categories[value.category_name] = value.category_id;
                        })
                    }
                    let option = {
                        textStyle: {
                            color: '#FFFFFF'
                        },
                        color: ['#9FE6B8'],
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {
                                type: 'shadow',
                                label: {
                                    show: true
                                }
                            },
                            formatter: params => {
                                return `${params[0].name}<br>
${params[0].seriesName}：${params[0].value}<br>`
                            },
                        },
                        calculable: true,
                        legend: {
                            data: ['总数'],
                            itemGap: 5,
                            textStyle: {
                                color: '#FFFFFF'
                            }
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '17%',
                            containLabel: true
                        },
                        xAxis: [
                            {
                                type: 'category',
                                data: xAxis
                            }
                        ],
                        yAxis: [
                            {
                                type: 'value',
                                name: '',
                                axisLabel: {}
                            }
                        ],
                        dataZoom: [
                            {
                                show: true,
                                start: 0,
                                end: 100
                            },
                            {
                                type: 'inside',
                                start: 94,
                                end: 100
                            },
                            {
                                show: false,
                                yAxisIndex: 0,
                                filterMode: 'empty',
                                width: 30,
                                height: '65%',
                                showDataShadow: false,
                                left: '0%'
                            }
                        ],
                        series: [
                            {
                                name: '总数',
                                type: 'bar',
                                label: {
                                    show: true,
                                    position: 'top'
                                },
                                data: serieData
                            }
                        ]
                    };

                    let echartLeftMiddle = echarts.init(document.getElementById('echartLeftMiddle'));
                    echartLeftMiddle.off('click');
                    echartLeftMiddle.setOption(option);
                    echartLeftMiddle.on('click', function (params) {
                        let categories = categories_iframe
                        let stationName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'].hasOwnProperty(currentStationUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'][currentStationUniqueCode]['name'] : '' : '';
                        let url = `{{url('/entire/instance')}}?category_unique_code=${categories[params.name]}&scene_workshop_unique_code=${currentSceneWorkshopUniqueCode}&station_name=${stationName}`
                        monitorShowModal(url)
                    });
                } else {
                    alert(response.message);
                    location.reload();
                }
            },
            error: error => {
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.message);
                location.reload();
            }
        });
    }

    /**
     * 左下 - 仓库统计/现场备品统计
     */
    function fnMakeLeftBottom() {
        $.ajax({
            url: `{{url('monitor/leftBottom')}}`,
            type: 'get',
            data: {
                sceneWorkshopUniqueCode: currentSceneWorkshopUniqueCode,
                stationUniqueCode: currentStationUniqueCode
            },
            async: true,
            success: response => {
                if (response.status === 200) {
                    let data = ['备品数量'];
                    if (currentSceneWorkshopUniqueCode === '' && currentStationUniqueCode === '') {
                        data = ['总数'];
                    }

                    let warehouses = response.data.warehouses;
                    let categories = {};
                    let serieData = [];
                    let xAxis = [];
                    if (warehouses.length > 0) {
                        $.each(warehouses, function (k, value) {
                            if (value['type'] === 'category' || !value.hasOwnProperty('type')) {
                                xAxis.push(value.category_name);
                                serieData.push(value.count);
                            }
                            if (value['type'] === 'entire_model') {
                                xAxis.push(value.entire_model_name);
                                serieData.push(value.count);
                            }

                            categories[value.category_name] = value.category_id;
                        })
                    }
                    let option = {
                        textStyle: {
                            color: '#FFFFFF'
                        },
                        color: ['#9FE6B8'],
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {
                                type: 'shadow',
                                label: {
                                    show: true
                                }
                            },
                            formatter: params => {
                                return `${params[0].name}<br>
${params[0].seriesName}：${params[0].value}<br>`
                            },
                        },
                        calculable: true,
                        legend: {
                            data: data,
                            itemGap: 5,
                            textStyle: {
                                color: '#FFFFFF'
                            },
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '17%',
                            containLabel: true
                        },
                        xAxis: [
                            {
                                type: 'category',
                                data: xAxis
                            }
                        ],
                        yAxis: [
                            {
                                type: 'value',
                                name: '',
                                axisLabel: {}
                            }
                        ],
                        dataZoom: [
                            {
                                show: true,
                                start: 0,
                                end: 100
                            },
                            {
                                type: 'inside',
                                start: 94,
                                end: 100
                            },
                            {
                                show: false,
                                yAxisIndex: 0,
                                filterMode: 'empty',
                                width: 30,
                                height: '65%',
                                showDataShadow: false,
                                left: '0%'
                            }
                        ],
                        series: [
                            {
                                name: data[0],
                                type: 'bar',
                                label: {
                                    show: true,
                                    position: 'top'
                                },
                                data: serieData
                            }
                        ]
                    };
                    let echartLeftBottom = echarts.init(document.getElementById('echartLeftBottom'));
                    echartLeftBottom.off('click');
                    echartLeftBottom.setOption(option);
                    echartLeftBottom.on('click', function (params) {
                        let icategories = categories_iframe
                        let data = {};
                        if (currentSceneWorkshopUniqueCode === '' && currentStationUniqueCode === '') {
                            data = {
                                state: `STANDBY`,
                                category_id: `${categories[params.name]}`,
                                is_bind_location: 1
                            };
                            let url = `{{url('/entire/instance')}}?category_unique_code=${icategories[params.name]}&is_bind_location=1`
                            monitorShowModal(url)
                        } else {
                            data = {
                                state: `SCENE_STANDBY`,
                                category_id: `${categories[params.name]}`,
                                maintain_station_code: currentStationUniqueCode,
                                scene_workshop_code: currentSceneWorkshopUniqueCode,
                            };
                            let stationName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'].hasOwnProperty(currentStationUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'][currentStationUniqueCode]['name'] : '' : '';
                            let url = `{{url('/entire/instance')}}?category_unique_code=${icategories[params.name]}&status_unique_code=INSTALLING&scene_workshop_unique_code=${currentSceneWorkshopUniqueCode}&station_name=${stationName}`
                            monitorShowModal(url)
                        }

                    });
                } else {
                    alert(response.message);
                    location.reload();
                }
            },
            error: error => {
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.message);
                location.reload();
            }
        });
    }


    /**
     * 右上 - 周期修
     */
    {{--function fnMakeRightTop() {--}}
    {{--    $.ajax({--}}
    {{--        url: `{{url('monitor/rightTop')}}`,--}}
    {{--        type: 'get',--}}
    {{--        data: {--}}
    {{--            sceneWorkshopUniqueCode: currentSceneWorkshopUniqueCode,--}}
    {{--            stationUniqueCode: currentStationUniqueCode--}}
    {{--        },--}}
    {{--        async: true,--}}
    {{--        success: response => {--}}
    // if (response.status === 200) {
    //     let legendData = ['任务', '计划', '检修总计'];
    //     let option = {
    //         textStyle: {
    //             color: '#FFFFFF'
    //         },
    //         color: ['#9FE6B8', '#FFDB5C', '#FF9F7F'],
    //         tooltip: {
    //             trigger: 'axis',
    //             axisPointer: {
    //                 type: 'shadow',
    //                 label: {show: true,},
    //             }
    //         },
    //         calculable: true,
    //         legend: {
    //             textStyle: {
    //                 fontSize: '14',
    //                 color: '#FFFFFF'
    //             },
    //             data: legendData,
    //             itemGap: 5,
    //         },
    //         grid: {
    //             left: '3%',
    //             right: '4%',
    //             bottom: '17%',
    //             containLabel: true,
    //         },
    //         xAxis: [{
    //             type: 'category',
    //             data: Object.keys(response.data.cycleFixCategories),
    //         }],
    //         yAxis: [{type: 'value'}],
    //         dataZoom: [{
    //             show: true,
    //             start: 0,
    //             end: 100,
    //         }, {
    //             type: 'inside',
    //             start: 94,
    //             end: 100,
    //         }, {
    //             show: false,
    //             yAxisIndex: 0,
    //             filterMode: 'empty',
    //             width: 30,
    //             height: '80%',
    //             showDataShadow: false,
    //             left: '93%',
    //         }],
    //         series: [{
    //             name: '任务',
    //             type: 'bar',
    //             data: Object.values(response.data.cycleFixMissions),
    //             label: {
    //                 show: true,
    //                 position: 'top'
    //             },
    //         }, {
    //             name: '计划',
    //             type: 'bar',
    //             data: Object.values(response.data.cycleFixPlans),
    //             label: {
    //                 show: true,
    //                 position: 'top'
    //             },
    //         }, {
    //             name: '检修总计',
    //             type: 'bar',
    //             data: Object.values(response.data.cycleFixReals),
    //             label: {
    //                 show: true,
    //                 position: 'top'
    //             },
    //         }]
    //     };
    //     let echartRightTop = echarts.init(document.getElementById('echartRightTop'));
    //     echartRightTop.off('click');
    //     echartRightTop.setOption(option);
    //     echartRightTop.on('click', function (params) {
    //         let url = '/report/cycleFix'
    //         monitorShowModal(url)
    //     });
    // } else {
    //     alert(response.message);
    //     // location.reload();
    // }
    {{--        },--}}
    {{--        error: error => {--}}
    {{--            if (error.status === 401) location.href = "{{ url('login') }}";--}}
    {{--            // location.reload();--}}
    {{--        }--}}
    {{--    });--}}
    {{--}--}}
    function fnMakeRightTop() {
        $.ajax({
                url: `{{url('monitor/rightTop')}}`,
                type: 'get',
                data: {
                    sceneWorkshopUniqueCode: currentSceneWorkshopUniqueCode,
                    stationUniqueCode: currentStationUniqueCode
                },
                async: true,
                success: response => {
                    if (response.status === 200) {
                        let echartRightTop = echarts.init(document.getElementById('echartRightTop'));
                        echartRightTop.off('click');
                        if (currentSceneWorkshopUniqueCode === '' && currentStationUniqueCode === '') {
                            let takeStockStatistic = response.data.takeStocks.takeStockStatistic;
                            let option = {
                                color: ['#9FE6B8', '#FFDB5C', '#FF9F7F'],
                                aria: {
                                    show: true
                                },
                                title: {
                                    text: takeStockStatistic['takeStockTitle'] + '分析',
                                    subtext: takeStockStatistic['time'],
                                    x: 'left',
                                    left: '1%',
                                    subtextStyle: {
                                        color: '#FFFFFF',
                                    },
                                    textStyle: {
                                        color: '#FFFFFF',
                                        fontWeight: 'normal'
                                    }
                                },
                                tooltip: {
                                    trigger: 'item',
                                    formatter: "{a} <br/>{b} : {c} ({d}%)"
                                },
                                legend: {
                                    orient: 'vertical',
                                    left: 'left',
                                    top: '30%',
                                    data: ['盘亏', '盘盈', '盘点正常'],
                                    textStyle: {
                                        color: '#FFFFFF',
                                    }
                                },
                                series: [
                                    {
                                        name: '差异分析',
                                        type: 'pie',
                                        radius: '65%',
                                        center: ['60%', '50%'],
                                        data: [
                                            {value: takeStockStatistic['-'], name: '盘亏'},
                                            {value: takeStockStatistic['+'], name: '盘盈'},
                                            {value: takeStockStatistic['='], name: '盘点正常'}
                                        ],
                                        itemStyle: {
                                            emphasis: {
                                                shadowBlur: 10,
                                                shadowOffsetX: 0,
                                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                                            }
                                        }
                                    }
                                ]
                            };
                            echartRightTop.setOption(option, true);
                        } else {
                            let serieData = [];
                            let xAxis = [];
                            if (currentSceneWorkshopUniqueCode !== '' && currentStationUniqueCode === '') {
                                let breakdownWithStations = response.data.breakdownWithStations;
                                $.each(breakdownWithStations, function (k, value) {
                                    xAxis.push(value.station_name);
                                    serieData.push(value.count);
                                })
                            }
                            if (currentSceneWorkshopUniqueCode !== '' && currentStationUniqueCode !== '') {
                                let breakdownWithCategories = response.data.breakdownWithCategories;
                                $.each(breakdownWithCategories, function (k, value) {
                                    xAxis.push(value.category_name);
                                    serieData.push(value.count);
                                })
                            }
                            let option = {
                                color: ['#9FE6B8'],
                                textStyle: {
                                    color: '#FFFFFF'
                                },
                                tooltip: {
                                    trigger: 'axis',
                                    axisPointer: {
                                        type: 'shadow',
                                        label: {
                                            show: true
                                        }
                                    },
                                    formatter: params => {
                                        return `${params[0].name}<br>
${params[0].seriesName}：${params[0].value}<br>`
                                    },
                                },
                                title: {},
                                calculable: true,
                                legend: {
                                    data: ['总数'],
                                    itemGap: 5,
                                    textStyle: {
                                        color: '#FFFFFF'
                                    },
                                },
                                grid: {
                                    left: '3%',
                                    right: '4%',
                                    bottom: '15%',
                                    containLabel: true
                                },
                                xAxis: [
                                    {
                                        type: 'category',
                                        data: xAxis
                                    }
                                ],
                                yAxis: [
                                    {
                                        type: 'value',
                                        name: '',
                                        axisLabel: {}
                                    }
                                ],
                                series: [
                                    {
                                        name: '总数',
                                        type: 'bar',
                                        label: {
                                            show: true,
                                            position: 'top'
                                        },
                                        data: serieData
                                    }
                                ]
                            };
                            echartRightTop.setOption(option, true);
                        }

                        echartRightTop.on('click', function (params) {

                        });
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: error => {
                    if (error.status === 401) location.href = "{{ url('login') }}";
                    alert(error.message);
                    location.reload();
                }
            }
        );
    }

    /**
     * 右中 - 超期使用
     */
    function fnMakeRightMiddle() {
        $.ajax({
            url: `{{url('monitor/rightMiddle')}}`,
            type: 'get',
            data: {
                sceneWorkshopUniqueCode: currentSceneWorkshopUniqueCode,
                stationUniqueCode: currentStationUniqueCode
            },
            async: true,
            success: response => {
                if (response.status === 200) {
                    let materialStatistics = response.data.materialStatistics;
                    let overdueStatistics = response.data.overdueStatistics;

                    let series = [{
                        name: '总数',
                        type: 'bar',
                        data: [],
                        label: {
                            show: true,
                            position: 'top',
                        },
                    }, {
                        name: '超期使用',
                        type: 'bar',
                        data: [],
                        label: {
                            show: true,
                            position: 'top',
                        },
                    }];
                    let categoryNames = [];
                    let categories = {};

                    $.each(materialStatistics, function (k, material) {
                        categoryNames.push(material.category_name);
                        categories[material.category_name] = material.category_id;
                        series[0]['data'].push(material.count);
                        series[1]['data'].push(overdueStatistics.hasOwnProperty(material.category_name) ? overdueStatistics[material.category_name] : 0);
                    });
                    let legendData = ['总数', '超期使用'];
                    let option = {
                        textStyle: {
                            color: '#FFFFFF'
                        },
                        color: ['#9FE6B8', '#FFDB5C'],
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {
                                type: 'shadow',
                                label: {show: true,},
                            },
                            formatter: function (params) {
                                let html = `${params[0].name}<br>`;
                                if (legendData.length === params.length) {
                                    html += `${params[0]['seriesName']}:${params[0]['value']}<br>
                                ${params[1]['seriesName']}:${params[1]['value']}<br>
                                超期使用率：${params[0].value > 0 ? ((params[1].value / params[0].value) * 100).toFixed(4) : 0}%`;
                                } else {
                                    $.each(params.reverse(), function (idx, item) {
                                        if (item.value > 0) html += `${item.seriesName}:${item.value}<br>`;
                                    });
                                }
                                return html;
                            },
                        },
                        calculable: true,
                        legend: {
                            data: legendData,
                            itemGap: 5,
                            textStyle: {
                                color: '#FFFFFF'
                            }
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '17%',
                            containLabel: true,
                        },
                        xAxis: [{
                            type: 'category',
                            data: categoryNames
                        }],
                        yAxis: [
                            {
                                type: 'value',
                            },
                        ],
                        dataZoom: [{
                            show: true,
                            start: 0,
                            end: 100,
                        }, {
                            type: 'inside',
                            start: 94,
                            end: 100
                        }, {
                            show: false,
                            yAxisIndex: 0,
                            filterMode: 'empty',
                            width: 30,
                            height: '80%',
                            showDataShadow: false,
                            left: '93%'
                        }],
                        series: series
                    };
                    let echartRightMiddle = echarts.init(document.getElementById('echartRightMiddle'));
                    echartRightMiddle.off('click');
                    echartRightMiddle.setOption(option);
                    echartRightMiddle.on('click', function (params) {

                        let categories = categories_iframe
                        let stationName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'].hasOwnProperty(currentStationUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'][currentStationUniqueCode]['name'] : '' : '';
                        let url = `{{url('/entire/instance')}}?category_unique_code=${categories[params.name]}&scene_workshop_unique_code=${currentSceneWorkshopUniqueCode}&station_name=${stationName}`
                        monitorShowModal(url)
                    });
                } else {
                    alert(response.message);
                    location.reload();
                }
            },
            error: error => {
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.message);
                location.reload();
            }
        });
    }

    /**
     * 右下 - 车间、车站列表
     */
    function fnMakeRightBottom() {
        $.ajax({
            url: `{{url('monitor/rightBottom')}}`,
            type: 'get',
            data: {
                sceneWorkshopUniqueCode: currentSceneWorkshopUniqueCode,
                stationUniqueCode: ''
            },
            async: true,
            success: response => {
                if (response.status === 200) {
                    if (currentSceneWorkshopUniqueCode === '' && currentStationUniqueCode === '') {
                        let sceneWorkshops = response.data.sceneWorkshops;
                        let html = ``;
                        $.each(sceneWorkshops, function (k, item) {
                            html += `<tr>`;
                            $.each(item, function (key, sceneWorkshop) {
                                html += `<td onclick="selSceneWorkshop('` + `${sceneWorkshop['unique_code']}` + `')">${sceneWorkshop['name']}  ${sceneWorkshop['count']}</td>`;
                            });
                            html += `</tr>`;
                        });
                        $('#right-bottom-table').html(html);
                    }
                    if (currentSceneWorkshopUniqueCode !== '') {
                        let stations = response.data.stations;
                        let html = ``;
                        $.each(stations, function (k, sitem) {
                            html += `<tr>`;
                            $.each(sitem, function (key, station) {
                                let tmpname = '';
                                if (station['name'].indexOf("站") === -1) {
                                    tmpname = station['name'] + '站';
                                } else {
                                    tmpname = station['name'];
                                }
                                if (currentStationUniqueCode === station['unique_code']) {
                                    html += `<td style='color:red' onclick="selStation('` + `${currentSceneWorkshopUniqueCode}` + `','` + `${station['unique_code']}` + `')">${tmpname}  ${station['count']}</td>`
                                } else {
                                    html += `<td onclick="selStation('` + `${currentSceneWorkshopUniqueCode}` + `','` + `${station['unique_code']}` + `')">${tmpname}  ${station['count']}</td>`
                                }
                            });
                            html += `</tr>`;
                        });
                        $('#right-bottom-table').html(html);
                    }
                } else {
                    alert(response.message);
                    // location.reload();
                }

            },
            error: error => {
                if (error.status === 401) location.href = "{{ url('login') }}";
                alert(error.message);
                // location.reload();
            }
        });
    }

    /**
     * 选择车间
     * @param sceneWorkshopUniqueCode
     */
    function selSceneWorkshop(sceneWorkshopUniqueCode = '') {
        currentSceneWorkshopUniqueCode = sceneWorkshopUniqueCode;
        currentStationUniqueCode = '';
        if (sceneWorkshopPoints.hasOwnProperty(sceneWorkshopUniqueCode)) {
            // fnMapCenter(sceneWorkshopPoints[sceneWorkshopUniqueCode]['lon'], sceneWorkshopPoints[sceneWorkshopUniqueCode]['lat'])
        } else {
            // fnMapCenter();
        }
        fnInit();
    }

    /**
     * 选择车站
     * @param sceneWorkshopUniqueCode
     * @param stationUniqueCode
     */
    function selStation(sceneWorkshopUniqueCode = '', stationUniqueCode = '') {
        currentSceneWorkshopUniqueCode = sceneWorkshopUniqueCode;
        currentStationUniqueCode = stationUniqueCode;
        // if (stationPoints.hasOwnProperty(stationUniqueCode)) {
        // fnMapCenter(stationPoints[stationUniqueCode]['lon'], stationPoints[stationUniqueCode]['lat'])
        let stationName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'].hasOwnProperty(currentStationUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'][currentStationUniqueCode]['name'] : '' : '';
        let url = `{{url('/entire/instance')}}?scene_workshop_unique_code=${currentSceneWorkshopUniqueCode}&station_name=${stationName}&status[]=INSTALLED&status[]=INSTALLING`;
        monitorShowModal(url);
        {{--} else {--}}
        {{--    let stationName = maintainStatistics.hasOwnProperty(currentSceneWorkshopUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'].hasOwnProperty(currentStationUniqueCode) ? maintainStatistics[currentSceneWorkshopUniqueCode]['stations'][currentStationUniqueCode]['name'] : '' : '';--}}
        {{--    let url = `{{url('/entire/instance')}}?status_unique_code=INSTALLING&scene_workshop_unique_code=${currentSceneWorkshopUniqueCode}&station_name=${stationName}`--}}
        {{--    let frame = $("#installingStatusFrame")--}}
        {{--    let modal = $('#installingStatus')--}}
        {{--    let tmp = $('#installingStatus .modal-body').html()--}}
        {{--    frame.attr("src", url);--}}
        {{--    frame.on('load' , ()=>frame.show())--}}
        {{--    modal.modal('show')--}}
        {{--    modal.on('hidden.bs.modal',()=>{--}}
        {{--        frame.attr("src", '')--}}
        {{--        frame.hide()--}}
        {{--        frame.remove()--}}
        {{--        $('#installingStatus .modal-body').html(tmp)--}}
        {{--    })--}}
        {{--    // fnMapCenter();--}}
        {{--}--}}
        fnInit();
    }

</script>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>打印标签</title>
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body onload="window.print();">
{{--<body>--}}
<div>
    @foreach($contents as $content)
        <div class="qr-code" style="
            width: 350px;
            height: 180px;
            margin-left: 25px;
            padding-top:18px;
            font-size: 24px;
            padding-bottom: 0px;
        ">
            <span style='margin-top: 30px'>型号：{{$content['model_name']}}<br></span>
            <span style='padding-top: 7px'> 站名：{{$content['maintain_station_name']}} <br></span>
            <span style='padding-top: 7px'> 位置：{{ @$content['maintain_location_code'] }}<br></span>
            <span style='padding-top: 7px'> 出所：{{$content['out_time']}}</span>
            <span style='margin-top: 7px'> {{$content['identity_code']}}<br></span>
        </div>
    @endforeach
</div>
</body>
</html>

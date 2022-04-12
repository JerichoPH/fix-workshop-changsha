<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>打印二维码</title>
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
            width: 360px;
            height: 82px;
            margin-left: 8px;
            padding-top:0px;
            font-size: 22px;
            padding-bottom: 0px;
        ">

            <div class="qr-code-left" style="width: 60px;margin-top:10px;float: left;">
                <img src="data:image/png;base64, {!! base64_encode($content['img']) !!} " alt="">
                <div class="qr-code-left-footer" style="width: 80px;margin-left: 14px;margin-top:-5px">
                    {{$content['identity_code']}}
                </div>
            </div>
            <div class="qr-code-right" style="float: left;width: 220px;margin-left:80px;margin-top:20px;">
                <div class="catgory-name" style="letter-spacing: 2px;padding-top: 3px;">
                    {{$content['category_name']}}
                </div>
                <div class="model-name" style="margin-top: 3px;">
                    {{$content['model_name']}}
                </div>
                <div class="serial_number-name" style="margin-top: 3px;">
                    {{$content['serial_number']}}
                </div>
                <div class="made_at" style="margin-top: 3px;">
                    出厂{{date('Y-m-d',strtotime($content['made_at']))}}
                </div>
            </div>
        </div>

    @endforeach
</div>
</body>
</html>

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
    <style>
        .print-type-1 {
            width: 360px;
            height: 82px;
            margin-left: 8px;
            padding-top: 0;
            font-size: 22px;
            padding-bottom: 0;
            clear: both;
        }

        .print-type-2 {
            width: 250px;
            height: 120px;
            margin-left: 0;
            margin-top: 0;
            padding-top: 0;
            font-size: 14px;
            padding-bottom: 0;
            clear: both;
        }

        .print-type-3 {
            width: 280px;
            height: 160px;
            margin-left: 5px;
            padding-top: 0;
            font-size: 16px;
            padding-bottom: 0;
            clear: both;
        }
    </style>
</head>

<body onload="window.print();">
{{--<body>--}}
<div>
    @if(env('ORGANIZATION_CODE') == 'B050')
        @if(request('size_type',1) == 1)
            {{--35*20--}}

        @elseif(request('size_type',1)==2)
            {{--20*12--}}
            @foreach($contents as $content)
                <div class="qr-code print-type-2">
                    <div class="qr-code-left" style="width: 60px;margin-top:10px;float: left;">
                        <img src="data:image/png;base64, {!! base64_encode($content['img']) !!} " alt="">
                        <div class="qr-code-left-footer" style="width: 60px; margin-left: 8px;margin-top: 0; font-size:14px">
                            {{ $content['identity_code'] }}
                        </div>
                    </div>
                    <div class="qr-code-right" style="float: left; width: 120px; margin-left:28px; line-height:22px; margin-top:2px;">
                        <div class="catgory-name" style="letter-spacing: 0; padding-top: 5px;">
                            {{ $content['category_name'] }}
                        </div>
                        <div class="model-name" style="margin-top: 0;">
                            {{ $content['model_name'] }}
                        </div>
                        <div class="serial_number-name" style="margin-top: 0;">
                            {{ date('Y-m-d',strtotime($content['made_at'])) }}(15)
                        </div>
                        <div class="made_at" style="margin-top: 0;">
                            {{ $content['serial_number'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        @elseif(request('size_type',1)==3)
            {{--40*25--}}
            @foreach($contents as $content)
                <div class="qr-code print-type-3">
                    <div class="qr-code-left" style="width: 130px; margin-top: 0; float: left;">
                        <img src="data:image/png;base64, {!! base64_encode($content['img']) !!} " alt="">
                        <div class="qr-code-left-footer" style="width: 80px; margin-left: 12px; margin-top:-5px">
                            {{ $content['identity_code'] }}
                        </div>
                    </div>
                    <div class="qr-code-right" style="width: 150px; margin-left: 160px; margin-top: 0;">
                        <div class="catgory-name" style="letter-spacing: 2px; padding-top: 10px;">
                            {{ $content['entire_model_name'] }}
                        </div>
                        <div class="serial_number-name" style="margin-top: 2px;">
                            {{ $content['model_name'] }}
                        </div>
                        <div class="model-name" style="margin-top: 2px;">
                            出厂{{ date('Ymd',strtotime($content['made_at'])) }}(15)
                        </div>
                        <div class="made_at" style="margin-top: 2px;">
                            {{ $content['serial_number'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @else
        @if(request('size_type',1) == 1)
            {{--35*20--}}
            @foreach($contents as $content)
                <div class="qr-code print-type-1" style="margin-top:9px">
                    <div class="qr-code-left" style="width: 60px;margin-top:4px;float: left;">
                        <img src="data:image/png;base64, {!! base64_encode($content['img']) !!} " alt="">
                        <div class="qr-code-left-footer" style="width: 80px; margin-left:10px ; margin-top:-8px;">
                            {{ $content['identity_code'] }}
                        </div>
                        <!--<p style="line-height: 1mm;">&nbsp;</p>-->
                    </div>
                    <div class="qr-code-right" style="float: left; width: 200px; margin-left:100px; margin-top:4px;">
                        <div class="catgory-name" style="letter-spacing: 2px;padding-top: 5px;">
                            {{ $content['category_name'] }}
                        </div>
                        <div class="model-name" style="margin-top: 4px;">
                            {{ $content['model_name'] }}
                        </div>
                        <div class="serial_number-name" style="margin-top: 4px;">
                            {{ $content['serial_number'] }}
                        </div>
                        <div class="made_at" style="margin-top: 4px;">
                            出厂{{ date('Y-m-d',strtotime($content['made_at'])) }}
                        </div>
                    </div>
                </div>
            @endforeach
        @elseif(request('size_type',1)==2)
            {{--20*12--}}
            @foreach($contents as $content)
                <div class="qr-code print-type-2">
                    <div class="qr-code-left" style="width: 60px;margin-top:10px;float: left;">
                        <img src="data:image/png;base64, {!! base64_encode($content['img']) !!} " alt="">
                        <div class="qr-code-left-footer" style="width: 60px;margin-left: 10px;margin-top: -5px;font-size:14px">
                            {{ $content['identity_code'] }}
                        </div>
                    </div>
                    <div class="qr-code-right" style="float: left;width: 120px;margin-left:10px;line-height:22px;margin-top:2px;">
                        <div class="catgory-name" style="letter-spacing: 2px;padding-top: 5px;">
                            {{ $content['category_name'] }}
                        </div>
                        <div class="model-name" style="margin-top: 1px;">
                            {{ $content['model_name'] }}
                        </div>
                        <div class="serial_number-name" style="margin-top: 1px;">
                            {{ $content['serial_number'] }}
                        </div>
                        <div class="made_at" style="margin-top: 3px;">
                            出厂{{ date('Y-m-d',strtotime($content['made_at'])) }}
                        </div>
                    </div>
                </div>
            @endforeach
        @elseif(request('size_type',1)==3)
            {{--40*25--}}
            @foreach($contents as $content)
                <div class="qr-code print-type-3">
                    <div class="qr-code-left" style="width: 60px;margin-top:10px;float: left;">
                        <img src="data:image/png;base64, {!! base64_encode($content['img']) !!} " alt="">
                        <div class="qr-code-left-footer" style="width: 80px;margin-left: 14px;margin-top:-5px">
                            {{ $content['identity_code'] }}
                        </div>
                    </div>
                    <div class="qr-code-right" style="float: left;width: 220px;margin-left:120px;margin-top:20px;">
                        <div class="catgory-name" style="letter-spacing: 2px;padding-top: 3px;">
                            {{ $content['category_name'] }}
                        </div>
                        <div class="model-name" style="margin-top: 3px;">
                            {{ $content['model_name'] }}
                        </div>
                        <div class="serial_number-name" style="margin-top: 3px;">
                            {{ $content['serial_number'] }}
                        </div>
                        <div class="made_at" style="margin-top: 3px;">
                            出厂{{ date('Y-m-d',strtotime($content['made_at'])) }}
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @endif
</div>
</body>
</html>

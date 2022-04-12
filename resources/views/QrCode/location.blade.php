<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>打印二维码</title>
    <link rel="stylesheet" href="/AdminLTE/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <script src="/AdminLTE/bower_components/jquery/dist/jquery.min.js"></script>
    <style>
        .print-type-1 {
            font-size: 16px;
        }

        .print-type-2 {
            font-size: 12px;
        }

        .print-type-3 {
            font-size: 22px;
        }

        .print-type-sm-1 {
            font-size: 13px;
        }

        .print-type-sm-2 {
            font-size: 10px;
        }

        .print-type-sm-3 {
            font-size: 14px;
        }
    </style>
</head>

<body onload="window.print();">
{{--<body>--}}
@if(env('ORGANIZATION_CODE') == 'B050')
    @foreach($contents as $content)
        <div class="print-type-{{ $type }}">
            <div class="row">
                {{--二维码--}}
                <div class="col-xs-5 col-xs-offset-1">
                    <img src="data:image/png;base64, {!! base64_encode($content['img']) !!} " alt=""/>
                </div>
                {{--文字--}}
                <div class="col-xs-6">
                    <div>
                        <div style="letter-spacing: 2px;">
                            {{ $content['storehouse_name'] }}
                        </div>
                        <div>
                            {{ $content['area_name'] }}
                        </div>
                        <div>
                            {{ $content['platoon_name'] }}
                        </div>
                        <div>
                            {{ $content['tier_name'] }}
                        </div>
                    </div>
                </div>
                {{--编码--}}
                <div class="col-xs-11 col-xs-offset-1">
                    {{ $content['unique_code'] }}
                </div>
            </div>
        </div>
    @endforeach
@else
    @foreach($contents as $content)
        <div class="print-type-{{ $type }}">
            <div class="row">
                {{--二维码--}}
                <div class="col-xs-5 col-xs-offset-1">
                    <img src="data:image/png;base64, {!! base64_encode($content['img']) !!} " alt=""/>
                </div>
                {{--文字--}}
                <div class="col-xs-6">
                    <div>
                        <div style="letter-spacing: 2px;">
                            {{ $content['storehouse_name'] }}
                        </div>
                        <div>
                            {{ $content['area_name'] }}
                        </div>
                        <div>
                            {{ $content['platoon_name'] }}
                        </div>
                        <div>
                            {{ $content['tier_name'] }}
                        </div>
                    </div>
                </div>
                {{--编码--}}
                <div class="col-xs-11 col-xs-offset-1">
                    {{ $content['unique_code'] }}
                </div>
            </div>
        </div>
    @endforeach
@endif

</body>
</html>

<?php

namespace App\Services;

use App\Facades\JsonResponseFacade;
use \Exception;
use \Closure;
use Throwable;

class CommonService
{

    /**
     * 根据APP_DEBUG参数打印错误
     * @param Throwable $e
     * @param array $extra
     * @param Closure|null $closure
     */
    final public static function ddExceptionWithAppDebug(Throwable $e, array $extra = [], Closure $closure = null)
    {
        $err_msg = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getfile(),
            'line' => $e->getline(),
            'trace' => $e->getTrace(),
            'extra' => $extra,
        ];
        logger()->error($e->getMessage(), $err_msg);

        if (request()->ajax()) {
            return JsonResponseFacade::errorException($e);
        } else {
            if ($closure) $closure($e);
            if (env('APP_DEBUG')) dd($err_msg);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 百度坐标系(BD-09) -> 高德百度坐标系(GCJ-02)
     * @param mixed ...$points 经纬度
     * @return mixed
     * @throws Exception
     */
    final public static function bd02_to_gcj02(...$points): array
    {
        list($lon, $lat) = $points;
        if (!is_double($lon)) throw new Exception('经度必须是double类型');
        if (!is_double($lat)) throw new Exception('纬度必须是double类型');

        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lon - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $gg_lon = $z * cos($theta);
        $gg_lat = $z * sin($theta);
        // 保留小数点后六位
        $data['lon'] = round($gg_lon, 6);
        $data['lat'] = round($gg_lat, 6);
        return $data;
    }

    /**
     * 高德坐标系(GCJ-02) -> 百度坐标系(BD-09)
     * @param mixed ...$points 经纬度
     * @return mixed
     */
    final public static function gcj02_to_bd09(...$points): array
    {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        list($x, $y) = $points;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $bd_lon = $z * cos($theta) + 0.0065;
        $bd_lat = $z * sin($theta) + 0.006;
        // 保留小数点后六位
        $data['lon'] = round($bd_lon, 6);
        $data['lat'] = round($bd_lat, 6);
        return $data;
    }
}

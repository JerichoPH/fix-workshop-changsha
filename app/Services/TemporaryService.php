<?php

namespace App\Services;


use App\Facades\MeasurementFacade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jericho\Excel\ExcelReadHelper;
use Jericho\TextHelper;
use Psy\Util\Str;

/**
 * Class TemporaryService
 * @package App\Services
 */
class TemporaryService
{
    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    final public function fun1()
    {
        $time = Carbon::create()->format('Y-m-d');
        $excel = ExcelReadHelper::FROM_STORAGE(storage_path("temporary/设备单元（型号） - 株洲.xlsx"))->withSheetIndex(1);
        $tmp = [];
        foreach ($excel['success'] as $row) {
            $tmp[] = [
                'created_at' => $time,
                'updated_at' => $time,
                'name' => $row[0],
                'unique_code' => $row[1],
                'category_unique_code' => substr($row[1], 0, 3),
                'entire_model_unique_code' => substr($row[1], 0, 5),
            ];
        }
        return DB::table('part_models')->insert($tmp);
    }

    final public function fun2()
    {
        $excel = ExcelReadHelper::FROM_STORAGE(storage_path("temporary/转辙机台账.xlsx"))
            ->originRow(4)
            ->withSheetIndex(0, function ($row) {
                return $row[1];
            });
        $success = array_unique($excel['success']);
        $fail = $excel['fail'];
        return [$success, $fail];
    }

    final public function fun3()
    {
        $part_instances = TextHelper::parseJson(Storage::disk("temporary")->get("part_instances.json"));
        foreach ($part_instances as $part_instance) {
            dd($part_instance);
            $repeat = DB::table('pivot_entire_model_and_factories');
        }


        $entire_instances = TextHelper::parseJson(Storage::disk("temporary")->get("entire_instances.json"));
        $tmp = [];
        foreach ($entire_instances as $entire_instance) {
            dd($entire_instance);
//            $repeat = DB::table('pivot_entire_model_and_factories')
//                ->where('entire_model_unique_code', $entire_instance['entire_model_unique_code'])
//                ->where('factory_name', $entire_instance['factory_name'])
//                ->first();

//            if ($repeat == null) {
//                DB::table('pivot_entire_model_and_factories')->insert([
//                    'entire_model_unique_code' => $entire_instance['entire_model_unique_code'],
//                    'factory_name' => $entire_instance['factory_name'],
//                ]);
//                dump('ok');
//            } else {
//                dump($repeat);
//            }
        }

        dd($tmp);
    }

    /**
     * @param string $identityCode
     * @return string
     * @throws \Exception
     */
    final public function fun4(string $identityCode)
    {
        $len = strlen($identityCode);
        switch ($len) {
            case 14:
                $length = str_pad(dechex($len), 2, '0', STR_PAD_LEFT);  # 长度位（1位）
                $asciiHex = str_pad(dechex(intval(TextHelper::strToAscii(substr($identityCode, 0, 1)))), 2, '0', STR_PAD_LEFT);  # 字母位（1位）
                $categoryUniqueCode = str_pad(dechex(intval(substr($identityCode, 1, 2))), 2, '0', STR_PAD_LEFT);  # 种码位（1位）
                $entireModelUniqueCode = str_pad(dechex(intval(substr($identityCode, 3, 2))), 2, '0', STR_PAD_LEFT);  # 类码位（1位）
                $sectionUniqueCode = str_pad(dechex(intval(substr($identityCode, 6, 3))), 4, '0', STR_PAD_LEFT);  # 段码位（2位）
                $serialNumberLength = "05";
                $serialNumberCode = str_pad(dechex(intval(substr($identityCode, 9, 5))), 8, '0', STR_PAD_LEFT);  # 序码位（4位）
                return "{$length}{$asciiHex}{$categoryUniqueCode}{$entireModelUniqueCode}{$sectionUniqueCode}{$serialNumberLength}{$serialNumberCode}";  # 组合RFID EPC CODE
                break;
            case 19:
                $length = str_pad(dechex($len), 2, '0', STR_PAD_LEFT);  # 长度位（1位）
                $asciiHex = str_pad(dechex(intval(TextHelper::strToAscii(substr($identityCode, 0, 1)))), 2, '0', STR_PAD_LEFT);  # 字母位（1位）
                $categoryUniqueCode = str_pad(dechex(intval(substr($identityCode, 1, 2))), 2, '0', STR_PAD_LEFT);  # 种码位（1位）
                $entireModelUniqueCode = str_pad(dechex(intval(substr($identityCode, 3, 2))), 2, '0', STR_PAD_LEFT);  # 类码位（1位）
                $partModelUniqueCode = str_pad(dechex(intval(substr($identityCode, 5, 2))), 2, '0', STR_PAD_LEFT);  # 形码位（1位）
                $sectionUniqueCode = str_pad(dechex(intval(substr($identityCode, 8, 3))), 4, '0', STR_PAD_LEFT);  # 段码位（2位）
                $serialNumberLength = "08";
                $serialNumberCode = str_pad(dechex(intval(substr($identityCode, 11, 8))), 8, '0', STR_PAD_LEFT);  # 序码位（4位）
                return "{$length}{$asciiHex}{$categoryUniqueCode}{$entireModelUniqueCode}{$partModelUniqueCode}{$sectionUniqueCode}{$serialNumberLength}{$serialNumberCode}";  # 组合RFID EPC CODE
                break;
            default:
                throw new \Exception("参数错误");
                break;
        }
    }

    /**
     * @param string $rfidEpcCode
     * @return string
     * @throws \Exception
     */
    final public function fun5(string $rfidEpcCode)
    {
        $rfidEpcCode = str_split($rfidEpcCode, 2);
        $length = hexdec($rfidEpcCode[0]);
        switch ($length) {
            case 14:
                $char = TextHelper::asciiToStr(hexdec($rfidEpcCode[1]));  # 字母位（1位）
                $categoryUniqueCode = str_pad(hexdec($rfidEpcCode[2]), 2, '0', STR_PAD_LEFT);  # 种码位（1位）
                $entireModelUniqueCode = str_pad(hexdec($rfidEpcCode[3]), 2, '0', STR_PAD_LEFT);  # 类码位（1位）
                $sectionUniqueCode = 'B' . str_pad(hexdec($rfidEpcCode[4] . $rfidEpcCode[5]), 3, '0', STR_PAD_LEFT);  # 段码位（2位）
                $serialNumberCode = str_pad(hexdec($rfidEpcCode[7] . $rfidEpcCode[8] . $rfidEpcCode[9] . $rfidEpcCode[10]), hexdec($rfidEpcCode[6]), '0', STR_PAD_LEFT);  # 序码位（4位）
                return "{$char}{$categoryUniqueCode}{$entireModelUniqueCode}{$sectionUniqueCode}{$serialNumberCode}";
                break;
            case 19:
                $char = TextHelper::asciiToStr(hexdec($rfidEpcCode[1]));  # 字母位（1位）
                $categoryUniqueCode = str_pad(hexdec($rfidEpcCode[2]), 2, '0', STR_PAD_LEFT);  # 种码位（1位）
                $entireModelUniqueCode = str_pad(hexdec($rfidEpcCode[3]), 2, '0', STR_PAD_LEFT);  # 类码位（1位）
                $partModelUniqueCode = str_pad(hexdec($rfidEpcCode[4]), 2, '0', STR_PAD_LEFT);  # 形码位（1位）
                $sectionUniqueCode = 'B' . str_pad(hexdec($rfidEpcCode[5] . $rfidEpcCode[6]), 3, '0', STR_PAD_LEFT);  # 段码位（2位）
                $serialNumberCode = str_pad(hexdec($rfidEpcCode[8] . $rfidEpcCode[9] . $rfidEpcCode[10] . $rfidEpcCode[11]), hexdec($rfidEpcCode[7]), '0', STR_PAD_LEFT);  # 序码位（4位）
                return "{$char}{$categoryUniqueCode}{$entireModelUniqueCode}{$partModelUniqueCode}{$sectionUniqueCode}{$serialNumberCode}";
                break;
            default:
                throw new \Exception("解析错误");
                break;
        }
    }
}
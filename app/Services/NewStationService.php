<?php

namespace App\Services;

use App\Exceptions\EntireInstanceLockException;
use App\Exceptions\ExcelInException;
use App\Facades\CodeFacade;
use App\Facades\EntireInstanceLogFacade;
use App\Facades\FixWorkflowFacade;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceCount;
use App\Model\EntireInstanceLock;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\Maintain;
use App\Model\OverhaulEntireInstance;
use App\Model\PartInstance;
use App\Model\PartModel;
use App\Model\V250TaskEntireInstance;
use App\Model\V250TaskOrder;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use PHPExcel_Cell;
use PHPExcel_Exception;
use PHPExcel_Reader_Exception;
use PHPExcel_Style_Alignment;
use PHPExcel_Writer_Exception;
use Throwable;

class NewStationService
{
    /**
     * 生成上传excel错误报告
     * @param array $excel_errors
     * @param string $filename
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    private function _makeErrorExcel(array $excel_errors, string $filename)
    {
        ExcelWriteHelper::save(
            function ($excel) use ($excel_errors) {
                $excel_error_row = 2;
                $excel->setActiveSheetIndex(0);
                $current_sheet = $excel->getActiveSheet();

                // 次行数据
                $first_row_data = [
                    ['context' => '位置', 'color' => 'black', 'width' => 20],
                    ['context' => '错误项', 'color' => 'black', 'width' => 20],
                    ['context' => '错误值', 'color' => 'black', 'width' => 20],
                    ['context' => '错误说明', 'color' => 'black', 'width' => 20],
                ];

                // 填充次行数据
                foreach ($first_row_data as $col => $second_row_datum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                    $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }

                foreach ($excel_errors as $excel_error) {
                    foreach ($excel_error as $col => $excel_err) {
                        $current_sheet->setCellValueExplicit("A{$excel_error_row}", "第{$excel_err['row']}行 {$col}列");
                        $current_sheet->setCellValueExplicit("B{$excel_error_row}", $excel_err['name']);
                        $current_sheet->setCellValueExplicit("C{$excel_error_row}", $excel_err['value']);
                        $current_sheet->setCellValueExplicit("D{$excel_error_row}", $excel_err['error_message']);
                        $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(0))->setWidth(30);
                        $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(1))->setWidth(30);
                        $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(2))->setWidth(30);
                        $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(3))->setWidth(50);
                        $excel_error_row++;
                    }
                }

                return $excel;
            },
            $filename
        );
    }

    /**
     * 下载上传新站赋码Excel模板
     * @param Request $request
     * @return RedirectResponse
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final public function downloadUploadCreateDeviceExcelTemplate(Request $request)
    {
        switch ($request->get('workAreaType')) {
            default:
                return back()->with('danger', '工区参数错误');
            case 'pointSwitch':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        // 整机 A~X
                        $current_sheet->setCellValueExplicit('A1', '整机');
                        $current_sheet->getStyle('A1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('A1:X1');
                        $current_sheet->getStyle('A1:X1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 电机 Y~AD
                        $current_sheet->setCellValueExplicit('Y1', '电机');
                        $current_sheet->getStyle('Y1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('Y1:AD1');
                        $current_sheet->getStyle('Y1:AD1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 移位接触器(左) AE~AJ
                        $current_sheet->setCellValueExplicit('AE1', '移位接触器(左)');
                        $current_sheet->getStyle('AE1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AE1:AJ1');
                        $current_sheet->getStyle('AE1:AJ1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 移位接触器(右) AK~AP
                        $current_sheet->setCellValueExplicit('AK1', '移位接触器(右)');
                        $current_sheet->getStyle('AK1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AK1:AP1');
                        $current_sheet->getStyle('AK1:AP1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 减速器 AQ~AV
                        $current_sheet->setCellValueExplicit('AQ1', '减速器');
                        $current_sheet->getStyle('AQ1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AQ1:AV1');
                        $current_sheet->getStyle('AQ1:AV1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 油泵 AW~BB
                        $current_sheet->setCellValueExplicit('AW1', '油泵');
                        $current_sheet->getStyle('AW1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AW1:BB1');
                        $current_sheet->getStyle('AW1:BB1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 自动开闭器 BC~BH
                        $current_sheet->setCellValueExplicit('BC1', '自动开闭器');
                        $current_sheet->getStyle('BC1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('BC1:BH1');
                        $current_sheet->getStyle('BC1:BH1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 摩擦连接器 BI~BN
                        $current_sheet->setCellValueExplicit('BI1', '摩擦连接器');
                        $current_sheet->getStyle('BI1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('BI1:BN1');
                        $current_sheet->getStyle('BI1:BN1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

                        // 首行数据2 A~X
                        $first_row_data = [
                            ['context' => '所编号*', 'color' => 'red', 'width' => 20],  // A
                            ['context' => '种类*', 'color' => 'red', 'width' => 20],  // B
                            ['context' => '类型*', 'color' => 'red', 'width' => 20],  // C
                            ['context' => '型号*', 'color' => 'red', 'width' => 20],  // D
                            ['context' => '状态*(新购、上道、备品、成品、待修)', 'color' => 'red', 'width' => 30],  // E
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '车站', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '道岔号', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '检测/检修人', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '检测/检修时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // L
                            ['context' => '验收人', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '验收时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '抽验人', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '抽验时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // P
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // Q
                            ['context' => '周期修年(非周期修写0)', 'color' => 'black', 'width' => 25],  // R
                            ['context' => '开向(左、右)', 'color' => 'black', 'width' => 20],  // S
                            ['context' => '线制', 'color' => 'black', 'width' => 20],  // T
                            ['context' => '表示杆特征', 'color' => 'black', 'width' => 20],  // U
                            ['context' => '道岔类型', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '防挤压保护罩(是、否)', 'color' => 'black', 'width' => 20],  // W
                            ['context' => '牵引', 'color' => 'black', 'width' => 20],  // X
                            // 电机 Y~AD
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // Z
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // AD
                            // 移位接触器(左) AE~AJ
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // AF
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // AJ
                            // 移位接触器(右) AK~AP
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // AL
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // AP
                            // 减速器 AQ~AV
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // AR
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // AV
                            // 油泵 AW~BB
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // AX
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // BB
                            // 自动开闭器 BC~BH
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // BD
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // BH
                            // 摩擦连接器 BI~BN
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // BJ
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // BN
                        ];
                        // 填充首行数据2
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 次行数据
                        $second_row_data = [
                            // 整机 A~X
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '新购', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // Q
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // R
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // S
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // T
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // U
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // W
                            ['context' => '', 'color' => 'black', 'width' => 20],  // X
                            // 电机 Y~AD
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // Z
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AD
                            // 移位接触器(左) AE~AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            // 移位接触器(右) AK~AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            // 减速器 AQ~AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            // 油泵 AW~BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            // 自动开闭器 BC~BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            // 摩擦连接器 BI~BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}3", $context);
                            $current_sheet->getStyle("{$col_for_excel}3")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第三行数据
                        $third_row_data = [
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '新购', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // Q
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // R
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // S
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // T
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // U
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // W
                            ['context' => '', 'color' => 'black', 'width' => 20],  // X
                            // 电机 Y~AD
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // Z
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AD
                            // 移位接触器(左) AE~AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            // 移位接触器(右) AK~AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            // 减速器 AQ~AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            // 油泵 AW~BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            // 自动开闭器 BC~BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            // 摩擦连接器 BI~BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                        ];
                        // 填充第三行数据
                        foreach ($third_row_data as $col => $third_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $third_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}4", $context);
                            $current_sheet->getStyle("{$col_for_excel}4")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第四行数据
                        $fourth_row_data = [
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '新购', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // Q
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // R
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // S
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // T
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // U
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // W
                            ['context' => '', 'color' => 'black', 'width' => 20],  // X
                            // 电机 Y~AD
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // Z
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AD
                            // 移位接触器(左) AE~AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            // 移位接触器(右) AK~AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            // 减速器 AQ~AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            // 油泵 AW~BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            // 自动开闭器 BC~BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            // 摩擦连接器 BI~BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                        ];
                        // 填充第四行数据
                        foreach ($fourth_row_data as $col => $fourth_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $fourth_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}5", $context);
                            $current_sheet->getStyle("{$col_for_excel}5")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "上传设备赋码模板",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
            case 'reply':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        $first_row_data = [
                            ['context' => '所编号*', 'color' => 'red', 'width' => 20],
                            ['context' => '种类*', 'color' => 'red', 'width' => 20],
                            ['context' => '类型*', 'color' => 'red', 'width' => 20],
                            ['context' => '型号*', 'color' => 'red', 'width' => 20],
                            ['context' => '状态*(新购、上道、备品、成品、待修)', 'color' => 'red', 'width' => 30],
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],
                            ['context' => '车站', 'color' => 'black', 'width' => 20],
                            ['context' => '组合位置', 'color' => 'black', 'width' => 20],
                            ['context' => '检测/检修人', 'color' => 'black', 'width' => 20],
                            ['context' => '检测/检修时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '验收人', 'color' => 'black', 'width' => 20],
                            ['context' => '验收时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '抽验人', 'color' => 'black', 'width' => 20],
                            ['context' => '抽验时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],
                            ['context' => '周期修年(非周期修写0)', 'color' => 'black', 'width' => 25],
                        ];

                        // 填充首行数据
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        // 次行数据
                        $second_row_data = [
                            ['context' => '20210301001', 'color' => 'black', 'width' => 20],
                            ['context' => '继电器', 'color' => 'black', 'width' => 20],
                            ['context' => '无极继电器', 'color' => 'black', 'width' => 20],
                            ['context' => 'JWXC-1000', 'color' => 'black', 'width' => 20],
                            ['context' => '新购', 'color' => 'black', 'width' => 30],
                            ['context' => '20210301002', 'color' => 'black', 'width' => 20],
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],
                            ['context' => '常德', 'color' => 'black', 'width' => 20],
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '15', 'color' => 'black', 'width' => 20],
                            ['context' => '5', 'color' => 'black', 'width' => 25],
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第三行数据
                        $third_row_data = [
                            ['context' => '20210301002', 'color' => 'black', 'width' => 20],
                            ['context' => '继电器', 'color' => 'black', 'width' => 20],
                            ['context' => '无极继电器', 'color' => 'black', 'width' => 20],
                            ['context' => 'JWXC-1000', 'color' => 'black', 'width' => 20],
                            ['context' => '新购', 'color' => 'black', 'width' => 30],
                            ['context' => '20210301002', 'color' => 'black', 'width' => 20],
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],
                            ['context' => '常德', 'color' => 'black', 'width' => 20],
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '15', 'color' => 'black', 'width' => 20],
                            ['context' => '5', 'color' => 'black', 'width' => 25],
                        ];
                        // 填充第三行数据
                        foreach ($third_row_data as $col => $third_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $third_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}3", $context);
                            $current_sheet->getStyle("{$col_for_excel}3")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第四行数据
                        $fourth_row_data = [
                            ['context' => '20210301003', 'color' => 'black', 'width' => 20],
                            ['context' => '继电器', 'color' => 'black', 'width' => 20],
                            ['context' => '无极继电器', 'color' => 'black', 'width' => 20],
                            ['context' => 'JWXC-1000', 'color' => 'black', 'width' => 20],
                            ['context' => '新购', 'color' => 'black', 'width' => 30],
                            ['context' => '20210301003', 'color' => 'black', 'width' => 20],
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],
                            ['context' => '常德', 'color' => 'black', 'width' => 20],
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '15', 'color' => 'black', 'width' => 20],
                            ['context' => '5', 'color' => 'black', 'width' => 25],
                        ];
                        // 填充第四行数据
                        foreach ($fourth_row_data as $col => $fourth_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $fourth_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}4", $context);
                            $current_sheet->getStyle("{$col_for_excel}4")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "上传设备赋码模板",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
            case 'synthesize':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        $first_row_data = [
                            ['context' => '所编号*', 'color' => 'red', 'width' => 20],
                            ['context' => '种类*', 'color' => 'red', 'width' => 20],
                            ['context' => '类型*', 'color' => 'red', 'width' => 20],
                            ['context' => '型号(设备不填此项)*', 'color' => 'red', 'width' => 20],
                            ['context' => '状态*(新购、上道、备品、成品、待修)', 'color' => 'red', 'width' => 30],
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],
                            ['context' => '车站', 'color' => 'black', 'width' => 20],
                            ['context' => '组合位置', 'color' => 'black', 'width' => 20],
                            ['context' => '检测/检修人', 'color' => 'black', 'width' => 20],
                            ['context' => '检测/检修时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '验收人', 'color' => 'black', 'width' => 20],
                            ['context' => '验收时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '抽验人', 'color' => 'black', 'width' => 20],
                            ['context' => '抽验时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],
                            ['context' => '周期修年(非周期修写0)', 'color' => 'black', 'width' => 25],
                        ];

                        // 填充首行数据
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        // 次行数据
                        $second_row_data = [
                            ['context' => '20210316001', 'color' => 'black', 'width' => 20],
                            ['context' => '密贴检查装置', 'color' => 'black', 'width' => 20],
                            ['context' => 'JM', 'color' => 'black', 'width' => 20],
                            ['context' => '', 'color' => 'black', 'width' => 20],
                            ['context' => '新购', 'color' => 'black', 'width' => 30],
                            ['context' => '20210316002', 'color' => 'black', 'width' => 20],
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],
                            ['context' => '常德', 'color' => 'black', 'width' => 20],
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '15', 'color' => 'black', 'width' => 20],
                            ['context' => 0, 'color' => 'black', 'width' => 25],
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第三行数据
                        $third_row_data = [
                            ['context' => '20210316002', 'color' => 'black', 'width' => 20],
                            ['context' => '密贴检查装置', 'color' => 'black', 'width' => 20],
                            ['context' => 'JM', 'color' => 'black', 'width' => 20],
                            ['context' => '', 'color' => 'black', 'width' => 20],
                            ['context' => '新购', 'color' => 'black', 'width' => 30],
                            ['context' => '20210316002', 'color' => 'black', 'width' => 20],
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],
                            ['context' => '常德', 'color' => 'black', 'width' => 20],
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '15', 'color' => 'black', 'width' => 20],
                            ['context' => 0, 'color' => 'black', 'width' => 25],
                        ];
                        // 填充第三行数据
                        foreach ($third_row_data as $col => $third_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $third_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}3", $context);
                            $current_sheet->getStyle("{$col_for_excel}3")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第四行数据
                        $fourth_row_data = [
                            ['context' => '20210316003', 'color' => 'black', 'width' => 20],
                            ['context' => '密贴检查装置', 'color' => 'black', 'width' => 20],
                            ['context' => 'JM', 'color' => 'black', 'width' => 20],
                            ['context' => '', 'color' => 'black', 'width' => 20],
                            ['context' => '新购', 'color' => 'black', 'width' => 30],
                            ['context' => '20210316003', 'color' => 'black', 'width' => 20],
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],
                            ['context' => '常德', 'color' => 'black', 'width' => 20],
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],
                            ['context' => '密贴检查装置', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '密贴检查装置', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '密贴检查装置', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '15', 'color' => 'black', 'width' => 20],
                            ['context' => 0, 'color' => 'black', 'width' => 25],
                        ];
                        // 填充第四行数据
                        foreach ($fourth_row_data as $col => $fourth_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $fourth_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}4", $context);
                            $current_sheet->getStyle("{$col_for_excel}4")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "上传设备赋码模板",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
        }
    }

    /**
     * 下载上传设备补充数据Excel模板
     * @param Request $request
     * @return RedirectResponse
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    final public function downloadUploadEditDeviceExcelTemplate(Request $request)
    {
        switch ($request->get('workAreaType')) {
            default:
                return back()->with('danger', '工区参数错误');
            case 'pointSwitch':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        // 整机 A~X
                        $current_sheet->setCellValueExplicit('A1', '整机');
                        $current_sheet->getStyle('A1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('A1:X1');
                        $current_sheet->getStyle('A1:X1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 电机 Y~AD
                        $current_sheet->setCellValueExplicit('Y1', '电机');
                        $current_sheet->getStyle('Y1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('Y1:AD1');
                        $current_sheet->getStyle('Y1:AD1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 移位接触器(左) AE~AJ
                        $current_sheet->setCellValueExplicit('AE1', '移位接触器(左)');
                        $current_sheet->getStyle('AE1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AE1:AJ1');
                        $current_sheet->getStyle('AE1:AJ1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 移位接触器(右) AK~AP
                        $current_sheet->setCellValueExplicit('A1', '移位接触器(右)');
                        $current_sheet->getStyle('A1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('A1:AP1');
                        $current_sheet->getStyle('A1:AP1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 减速器 AQ~AV
                        $current_sheet->setCellValueExplicit('AQ1', '减速器');
                        $current_sheet->getStyle('AQ1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AQ1:AV1');
                        $current_sheet->getStyle('AQ1:AV1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 油泵 AW~BB
                        $current_sheet->setCellValueExplicit('AW1', '油泵');
                        $current_sheet->getStyle('AW1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('AW1:BB1');
                        $current_sheet->getStyle('AW1:BB1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 自动开闭器 BC~BH
                        $current_sheet->setCellValueExplicit('BC1', '自动开闭器');
                        $current_sheet->getStyle('BC1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('BC1:BH1');
                        $current_sheet->getStyle('BC1:BH1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        // 摩擦连接器 BI~BN
                        $current_sheet->setCellValueExplicit('BI1', '摩擦连接器');
                        $current_sheet->getStyle('BI1')->getFont()->setColor(ExcelWriteHelper::getFontColor('black'));
                        $current_sheet->mergeCells('BI1:BN1');
                        $current_sheet->getStyle('BI1:BN1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

                        // 首行数据2 A~X
                        $first_row_data = [
                            ['context' => '所编号*', 'color' => 'red', 'width' => 20],  // A
                            ['context' => '种类*', 'color' => 'red', 'width' => 20],  // B
                            ['context' => '类型*', 'color' => 'red', 'width' => 20],  // C
                            ['context' => '型号*', 'color' => 'red', 'width' => 20],  // D
                            ['context' => '状态*(新购、上道、备品、成品、待修)', 'color' => 'red', 'width' => 30],  // E
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '车站', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '道岔号', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '检测/检修人', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '检测/检修时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // L
                            ['context' => '验收人', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '验收时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '抽验人', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '抽验时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],  // P
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // Q
                            ['context' => '周期修年(非周期修写0)', 'color' => 'black', 'width' => 25],  // R
                            ['context' => '开向(左、右)', 'color' => 'black', 'width' => 20],  // S
                            ['context' => '线制', 'color' => 'black', 'width' => 20],  // T
                            ['context' => '表示杆特征', 'color' => 'black', 'width' => 20],  // U
                            ['context' => '道岔类型', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '防挤压保护罩(是、否)', 'color' => 'black', 'width' => 20],  // W
                            ['context' => '牵引', 'color' => 'black', 'width' => 20],  // X
                            // 电机 Y~AD
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // Z
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // AD
                            // 移位接触器(左) AE~AJ
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // AF
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // AJ
                            // 移位接触器(右) AK~AP
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // AL
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // AP
                            // 减速器 AQ~AV
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // AR
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // AV
                            // 油泵 AW~BB
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // AX
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // BB
                            // 自动开闭器 BC~BH
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // BD
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // BH
                            // 摩擦连接器 BI~BN
                            ['context' => '所编号', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],  // BJ
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '型号', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],  // BN
                        ];
                        // 填充首行数据2
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 次行数据
                        $second_row_data = [
                            // 整机 A~X
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '新购', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // Q
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // R
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // S
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // T
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // U
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // W
                            ['context' => '', 'color' => 'black', 'width' => 20],  // X
                            // 电机 Y~AD
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // Z
                            ['context' => '20210302001', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AD
                            // 移位接触器(左) AE~AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            // 移位接触器(右) AK~AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            // 减速器 AQ~AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            // 油泵 AW~BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            // 自动开闭器 BC~BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            // 摩擦连接器 BI~BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}3", $context);
                            $current_sheet->getStyle("{$col_for_excel}3")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第三行数据
                        $third_row_data = [
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '新购', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // Q
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // R
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // S
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // T
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // U
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // W
                            ['context' => '', 'color' => 'black', 'width' => 20],  // X
                            // 电机 Y~AD
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // Z
                            ['context' => '20210302002', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AD
                            // 移位接触器(左) AE~AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            // 移位接触器(右) AK~AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            // 减速器 AQ~AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            // 油泵 AW~BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            // 自动开闭器 BC~BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            // 摩擦连接器 BI~BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                        ];
                        // 填充第三行数据
                        foreach ($third_row_data as $col => $third_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $third_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}4", $context);
                            $current_sheet->getStyle("{$col_for_excel}4")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }
                        // 第四行数据
                        $fourth_row_data = [
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // A
                            ['context' => '转辙机', 'color' => 'black', 'width' => 20],  // B
                            ['context' => 'ZD6', 'color' => 'black', 'width' => 20],  // C
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // D
                            ['context' => '新购', 'color' => 'black', 'width' => 30],  // E
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // F
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // G
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // H
                            ['context' => '常德', 'color' => 'black', 'width' => 20],  // I
                            ['context' => '3#', 'color' => 'black', 'width' => 20],  // J
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // K
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // L
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // M
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // N
                            ['context' => '周志刚', 'color' => 'black', 'width' => 20],  // O
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],  // P
                            ['context' => 15, 'color' => 'black', 'width' => 20],  // Q
                            ['context' => 5, 'color' => 'black', 'width' => 25],  // R
                            ['context' => '左', 'color' => 'black', 'width' => 20],  // S
                            ['context' => '4线制', 'color' => 'black', 'width' => 20],  // T
                            ['context' => '加强型', 'color' => 'black', 'width' => 20],  // U
                            ['context' => 'J6', 'color' => 'black', 'width' => 20],  // V
                            ['context' => '是', 'color' => 'black', 'width' => 20],  // W
                            ['context' => '', 'color' => 'black', 'width' => 20],  // X
                            // 电机 Y~AD
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // Y
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],  // Z
                            ['context' => '20210302003', 'color' => 'black', 'width' => 20],  // AA
                            ['context' => 'ZD6-D', 'color' => 'black', 'width' => 20],  // AB
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],  // AC
                            ['context' => 10, 'color' => 'black', 'width' => 20],  // AD
                            // 移位接触器(左) AE~AJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AJ
                            // 移位接触器(右) AK~AP
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AO
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AP
                            // 减速器 AQ~AV
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AQ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AR
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AS
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AT
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AU
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AV
                            // 油泵 AW~BB
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AW
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AX
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AY
                            ['context' => '', 'color' => 'black', 'width' => 20],  // AZ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BA
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BB
                            // 自动开闭器 BC~BH
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BC
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BD
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BE
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BF
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BG
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BH
                            // 摩擦连接器 BI~BN
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BI
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BJ
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BK
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BL
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BM
                            ['context' => '', 'color' => 'black', 'width' => 20],  // BN
                        ];
                        // 填充第四行数据
                        foreach ($fourth_row_data as $col => $fourth_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $fourth_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}5", $context);
                            $current_sheet->getStyle("{$col_for_excel}5")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "上传设备数据补充模板",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
            case 'reply':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        $first_row_data = [
                            ['context' => '所编号*', 'color' => 'red', 'width' => 20],
                            ['context' => '种类*', 'color' => 'red', 'width' => 20],
                            ['context' => '类型*', 'color' => 'red', 'width' => 20],
                            ['context' => '型号*', 'color' => 'red', 'width' => 20],
                            ['context' => '状态*(新购、上道、备品、成品、待修)', 'color' => 'red', 'width' => 30],
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],
                            ['context' => '车站', 'color' => 'black', 'width' => 20],
                            ['context' => '组合位置', 'color' => 'black', 'width' => 20],
                            ['context' => '检测/检修人', 'color' => 'black', 'width' => 20],
                            ['context' => '检测/检修时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '验收人', 'color' => 'black', 'width' => 20],
                            ['context' => '验收时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '抽验人', 'color' => 'black', 'width' => 20],
                            ['context' => '抽验时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],
                            ['context' => '周期修年(非周期修写0)', 'color' => 'black', 'width' => 25],
                        ];

                        // 填充首行数据
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        // 次行数据
                        $second_row_data = [
                            ['context' => '20210301001', 'color' => 'black', 'width' => 20],
                            ['context' => '继电器', 'color' => 'black', 'width' => 20],
                            ['context' => '无极继电器', 'color' => 'black', 'width' => 20],
                            ['context' => 'JWXC-1000', 'color' => 'black', 'width' => 20],
                            ['context' => '新购', 'color' => 'black', 'width' => 30],
                            ['context' => '1234567', 'color' => 'black', 'width' => 20],
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],
                            ['context' => '常德', 'color' => 'black', 'width' => 20],
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '袁满', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '15', 'color' => 'black', 'width' => 20],
                            ['context' => '5', 'color' => 'black', 'width' => 25],
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "上传设备数据补充模板",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
            case 'synthesize':
                ExcelWriteHelper::download(
                    function ($excel) {
                        $excel->setActiveSheetIndex(0);
                        $current_sheet = $excel->getActiveSheet();

                        // 首行数据
                        $first_row_data = [
                            ['context' => '所编号*', 'color' => 'red', 'width' => 20],
                            ['context' => '种类*', 'color' => 'red', 'width' => 20],
                            ['context' => '类型*', 'color' => 'red', 'width' => 20],
                            ['context' => '型号(设备不填此项)*', 'color' => 'red', 'width' => 20],
                            ['context' => '状态*(新购、上道、备品、成品、待修)', 'color' => 'red', 'width' => 30],
                            ['context' => '厂编号', 'color' => 'black', 'width' => 20],
                            ['context' => '厂家', 'color' => 'black', 'width' => 20],
                            ['context' => '生产日期', 'color' => 'black', 'width' => 20],
                            ['context' => '车站', 'color' => 'black', 'width' => 20],
                            ['context' => '组合位置', 'color' => 'black', 'width' => 20],
                            ['context' => '检测/检修人', 'color' => 'black', 'width' => 20],
                            ['context' => '检测/检修时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '验收人', 'color' => 'black', 'width' => 20],
                            ['context' => '验收时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '抽验人', 'color' => 'black', 'width' => 20],
                            ['context' => '抽验时间(YYYY-MM-DD格式)', 'color' => 'black', 'width' => 30],
                            ['context' => '寿命(年)', 'color' => 'black', 'width' => 20],
                            ['context' => '周期修年(非周期修写0)', 'color' => 'black', 'width' => 25],
                        ];

                        // 填充首行数据
                        foreach ($first_row_data as $col => $firstRowDatum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                            $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        // 次行数据
                        $second_row_data = [
                            ['context' => '20210316001', 'color' => 'black', 'width' => 20],
                            ['context' => '密贴检查装置', 'color' => 'black', 'width' => 20],
                            ['context' => 'JM', 'color' => 'black', 'width' => 20],
                            ['context' => '', 'color' => 'black', 'width' => 20],
                            ['context' => '新购', 'color' => 'black', 'width' => 30],
                            ['context' => '1234567', 'color' => 'black', 'width' => 20],
                            ['context' => '西安铁路信号工厂', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 20],
                            ['context' => '常德', 'color' => 'black', 'width' => 20],
                            ['context' => 'Q03-02-03-01', 'color' => 'black', 'width' => 20],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '周再勇', 'color' => 'black', 'width' => 20],
                            ['context' => '2020-01-01', 'color' => 'black', 'width' => 30],
                            ['context' => '15', 'color' => 'black', 'width' => 20],
                            ['context' => 0, 'color' => 'black', 'width' => 25],
                        ];
                        // 填充次行数据
                        foreach ($second_row_data as $col => $second_row_datum) {
                            $col_for_excel = ExcelWriteHelper::int2Excel($col);
                            ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                            $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                            $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                            $current_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                        }

                        return $excel;
                    },
                    "上传设备数据补充模板",
                    ExcelWriteHelper::$VERSION_5
                );
                break;
        }
    }

    /**
     * 上传设备赋码Excel
     * @param Request $request
     * @param string $sn
     * @param V250TaskOrder $v250_task_order
     * @return RedirectResponse
     * @throws ExcelInException
     * @throws EntireInstanceLockException
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     * @throws Throwable
     */
    final public function uploadCreateDevice(Request $request, string $sn, V250TaskOrder $v250_task_order)
    {
        $excel_errors = [];
        $statuses = [
            '新购' => 'BUY_IN',
            '上道' => 'INSTALLED',
            '备品' => 'INSTALLING',
            '成品' => 'FIXED',
            '待修' => 'FIXING',
        ];

        switch ($request->get('workAreaType')) {
            default:
                return back()->with('danger', '工区参数错误');
            case 'pointSwitch':
                // 读取excel数据
                $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->originRow(3)
                    ->withSheetIndex(0);
                $current_row = 3;
                // 转辙机工区
                // 所编号、种类*、类型*、型号*、状态*(新购、上道、备品、成品、待修)、厂编号、厂家、生产日期、
                // 车站、组合位置、检测/检修人、检测/检修时间(YYYY-MM-DD格式)、验收人、验收时间(YYYY-MM-DD格式)、抽验人、抽验时间(YYYY-MM-DD格式)
                // 寿命(年)、周期修年(非周期修写0)、开向(左、右)、线制、表示杆特征、道岔类型、防挤压保护罩(是、否)、牵引
                // 电机：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 移位接触器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 减速器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 油泵：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 自动开闭器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 摩擦减速器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                $new_entire_instances = [];
                $excel_error = [];
                // 数据验证
                foreach ($excel['success'] as $row_datum) {
                    if (empty(array_filter($row_datum, function ($item) {
                        return !empty($item);
                    }))) continue;
                    list(
                        $om_serial_number, $om_category_name, $om_entire_model_name, $om_sub_model_name, $om_status_mame, $o_factory_device_code, $om_factory_name, $o_made_at,
                        $o_station_name, $o_maintain_location_code, $o_fixer_name, $o_fixed_at, $o_checker_name, $o_checked_at, $o_spot_checker_name, $o_spot_checked_at,
                        $o_life_year, $o_cycle_fix_year, $o_open_direction, $o_line_name, $o_said_rod, $o_crossroad_type, $o_extrusion_protect, $o_traction,
                        $dj_serial_number, $dj_factory_name, $dj_factory_device_code, $dj_model_name, $dj_made_at, $dj_life_year,
                        $ywjcq_serial_number_l, $ywjcq_factory_name_l, $ywjcq_factory_device_code_l, $ywjcq_model_name_l, $ywjcq_made_at_l, $ywjcq_life_year_l,
                        $ywjcq_serial_number_r, $ywjcq_factory_name_r, $ywjcq_factory_device_code_r, $ywjcq_model_name_r, $ywjcq_made_at_r, $ywjcq_life_year_r,
                        $jsq_serial_number, $jsq_factory_name, $jsq_factory_device_code, $jsq_model_name, $jsq_made_at, $jsq_life_year,
                        $yb_serial_number, $yb_factory_name, $yb_factory_device_code, $yb_model_name, $yb_made_at, $yb_life_year,
                        $zdkbq_serial_number, $zdkbq_factory_name, $zdkbq_factory_device_code, $zdkbq_model_name, $zdkbq_made_at, $zdkbq_life_year,
                        $mcljq_serial_number, $mcljq_factory_name, $mcljq_factory_device_code, $mcljq_model_name, $mcljq_made_at, $mcljq_life_year
                        ) = $row_datum;

                    // 以下是严重错误，不允许通过
                    // 验证所编号和厂编号
                    if (!$om_serial_number && !$o_factory_device_code) throw new ExcelInException('所编号或厂编号必填一个');
                    // 验证种类
                    if (!$om_category_name) throw new ExcelInException("第{$current_row}行，种类不能为空");
                    $category = Category::with([])->where('name', $om_category_name)->first();
                    if (!$category) throw new ExcelInException("第{$current_row}行，种类：{$om_category_name}不存在");
                    // 验证类型
                    if (!$om_entire_model_name) throw new ExcelInException("第{$current_row}行，类型不能为空");
                    $em = EntireModel::with([])->where('is_sub_model', false)->where('category_unique_code', $category->unique_code)->where('name', $om_entire_model_name)->first();
                    if (!$em) throw new ExcelInException("第{$current_row}行，类型：{$category->name} > {$om_entire_model_name}不存在");
                    // 验证型号
                    if (!$om_sub_model_name) throw new ExcelInException("第{$current_row}行，型号不能为空");
                    $sm = EntireModel::with([])->where('is_sub_model', true)->where('parent_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                    $pm = PartModel::with([])->where('entire_model_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                    if (!$sm && !$pm) throw new ExcelInException("第{$current_row}行，型号：{$category->name} > {$em->name} > {$om_sub_model_name}不存在");
                    if (!$sm && $pm) $sm = $pm;
                    // 验证厂家
                    if ($om_factory_name)
                        if (!Factory::with([])->where('name', $om_factory_name)->first())
                            throw new ExcelInException("第{$current_row}行，没有找到厂家：{$om_factory_name}");
                    // 验证状态
                    if (!$om_status_mame) throw new ExcelInException("第{$current_row}行，状态不能为空");
                    if (!array_key_exists($om_status_mame, $statuses)) throw new ExcelInException("第{$current_row}行，设备状态：{$om_status_mame}错误，只能填写：" . implode('、', array_flip($statuses)));
                    $status = $statuses[$om_status_mame];
                    // 验证所编号是否重复
                    if ($om_serial_number) {
                        if (EntireInstance::with([])->where('serial_number', $om_serial_number)->where('model_unique_code', $sm->unique_code)->first()) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}({$om_sub_model_name})重复");
                    }

                    // 以下是非严重错误，可以通过
                    // 验证厂编号
                    if (!$o_factory_device_code) {
                        $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂编号', $o_factory_device_code, '没有填写', 'red');
                    }
                    // 验证厂家
                    // if ($om_factory_name) {
                    //     $factory = Factory::with([])->where('name', $om_factory_name)->first();
                    //     if (!$factory) $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, "厂家名称填写不规范，现有厂家名称中没有找到：{$om_factory_name}", 'red');
                    // } else {
                    //     $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, '没有填写厂家', 'red');
                    //     $om_factory_name = '';
                    // }

                    // 验证生产日期
                    if ($o_made_at) {
                        try {
                            $o_made_at = date('Y-m-d',strtotime(ExcelWriteHelper::getExcelDate($o_made_at)));
                        } catch (Exception $e) {
                            $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, $e->getMessage());
                            $o_made_at = null;
                        }
                    } else {
                        $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, '没有填写生产日期');
                        $o_made_at = null;
                    }

                    // 验证车站
                    if ($o_station_name) {
                        $station = Maintain::with(['Parent'])->where('name', $o_station_name)->first();
                        // todo: 刷线别、现场车间、车站数据之后，需要根据新的数据库来修改
                        // $station = Station::with([])->where('name', $oStationName)->where('name', $oStationName)->first();
                        if (!$station) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "没有找到车站：{$o_station_name}");
                        if ($station) {
                            if (!$station->Parent) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "车站：{$o_station_name}，没有找到上一级车间");
                        }
                    } else {
                        $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, '没有填写车站');
                        $station = null;
                        $o_station_name = '';
                    }

                    // 验证检测/检修人
                    $fixer = null;
                    if ($o_fixer_name) {
                        $fixer = Account::with([])->where('nickname', $o_fixer_name)->first();
                        if (!$fixer) $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修人', $o_fixer_name, "没有找到检修人：{$o_fixer_name}");
                    }

                    // 验证检修时间
                    if ($o_fixed_at) {
                        try {
                            $o_fixed_at = ExcelWriteHelper::getExcelDate($o_fixed_at);
                        } catch (Exception $e) {
                            $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修时间', $o_fixed_at, $e->getMessage());
                            $o_fixed_at = null;
                        }
                    }

                    // 验证验收人
                    $checker = null;
                    if ($o_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        $checker = Account::with([])->where('nickname', $o_checker_name)->first();
                        if (!$checker) $excel_error['M'] = ExcelWriteHelper::makeErrorResult($current_row, '验收人', $o_fixer_name, "没有找到验收人：{$o_checker_name}");
                    }

                    // 验证检修时间
                    if ($o_checked_at) {
                        try {
                            $o_checked_at = ExcelWriteHelper::getExcelDate($o_checked_at);
                        } catch (Exception $e) {
                            $excel_error['N'] = ExcelWriteHelper::makeErrorResult($current_row, '验收时间', $o_checked_at, $e->getMessage());
                            $o_checked_at = null;
                        }
                    }

                    // 验证抽验人
                    $spot_checker = null;
                    if ($o_spot_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        if (is_null($checker)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，没有验收人");
                        $spot_checker = Account::with([])->where('nickname', $o_spot_checker_name)->first();
                        if (!$spot_checker) $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验人', $o_spot_checker_name, "没有找到抽验人：{$o_spot_checker_name}");
                    }

                    // 验证抽验时间
                    if ($o_spot_checked_at) {
                        try {
                            $o_spot_checked_at = ExcelWriteHelper::getExcelDate($o_spot_checked_at);
                        } catch (Exception $e) {
                            $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验时间', $o_spot_checked_at, $e->getMessage());
                            $o_spot_checked_at = null;
                        }
                    }

                    // 验证寿命
                    if (is_numeric($o_life_year)) {
                        if ($o_life_year < 0) {
                            $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                            $scraping_at = null;
                        } else {
                            $scraping_at = Carbon::parse($o_made_at)->addYears($o_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                        $scraping_at = null;
                    }

                    // 周期修年限
                    if (is_numeric($o_cycle_fix_year)) {
                        if ($o_cycle_fix_year < 0) {
                            $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                            $o_cycle_fix_year = 0;
                        }
                    } else {
                        $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                        $o_cycle_fix_year = 0;
                    }

                    // 验证开向
                    if (!$o_open_direction) {
                        $excel_error['S'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向未填写');
                        $o_open_direction = '';
                    } else {
                        if (!in_array($o_open_direction, ['左', '右'])) $excel_error['S'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向只能填写：左、右');
                    }

                    // 验证线制
                    if (!$o_line_name) {
                        $excel_error['T'] = ExcelWriteHelper::makeErrorResult($current_row, '线制', $o_line_name, '线制未填写');
                        $o_line_name = '';
                    }

                    // 验证表示杆特征
                    if (!$o_said_rod) {
                        $excel_error['U'] = ExcelWriteHelper::makeErrorResult($current_row, '表示杆特征', $o_said_rod, '表示杆特征未填写');
                        $o_said_rod = '';
                    }

                    // 验证道岔类型
                    if (!$o_crossroad_type) {
                        $excel_error['V'] = ExcelWriteHelper::makeErrorResult($current_row, '道岔类型', $o_crossroad_type, '道岔类型未填写');
                        $o_crossroad_type = '';
                    }

                    // 验证防挤压保护罩
                    if ($o_extrusion_protect) {
                        if (!in_array($o_extrusion_protect, ['是', '否'])) $excel_error['W'] = ExcelWriteHelper::makeErrorResult($current_row, '防挤压保护罩(是、否)', $o_extrusion_protect, '只能填写：是、否');
                        if ($o_extrusion_protect == '是') {
                            $o_extrusion_protect = true;
                        } else {
                            $o_extrusion_protect = false;
                        }
                    } else {
                        $o_extrusion_protect = false;
                    }

                    // 验证牵引
                    if (!$o_traction) {
                        $excel_error['X'] = ExcelWriteHelper::makeErrorResult($current_row, '牵引', $o_crossroad_type, '牵引未填写');
                        $o_traction = '';
                    }

                    // 电机：所编号Y、厂家Z、厂编号AA、型号AB、生产日期AC、寿命(年)AD
                    $check_dj = function () use ($current_row, &$dj_serial_number, &$dj_factory_name, &$dj_factory_device_code, &$dj_model_name, &$dj_made_at, &$dj_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 Z
                        if ($dj_factory_name) {
                            $dj_factory = Factory::with([])->where('name', $dj_factory_name)->first();
                            if (!$dj_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(电机)：{$dj_factory_name}");
                            // if (!$dj_factory) {
                            //     $excel_error['AA'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $dj_factory_name, "没有找到厂家：{$dj_factory_name}");
                            // }
                        } else {
                            $excel_error['Z'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $dj_factory_name, '没有填写电机厂家');
                            $dj_factory_name = '';
                        }
                        // 验证厂编号 AA
                        if (!$dj_factory_device_code) {
                            $excel_error['AA'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂编号', $dj_factory_device_code, '没有填写电机编号');
                            $dj_factory_device_code = '';
                        }
                        // 验证型号 AB
                        $dj_model = null;
                        if ($dj_serial_number && $dj_model_name) {
                            $dj_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $dj_model_name)->first();
                            if (!$dj_model) throw new ExcelInException("第{$current_row}行，没有找到电机型号：{$dj_model_name}");
                        }
                        // 验证所编号 Y
                        if ($dj_serial_number && $dj_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $dj_serial_number)->where('device_model_unique_code', $dj_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，电机：{$dj_serial_number}所编号重复");
                        }
                        // 验证生产日期 AC
                        if ($dj_made_at) {
                            try {
                                $dj_made_at = ExcelWriteHelper::getExcelDate($dj_made_at);
                            } catch (Exception $e) {
                                $excel_error['AC'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $dj_made_at, $e->getMessage());
                                $dj_made_at = null;
                            }
                        } else {
                            $excel_error['AC'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $dj_made_at, '没有填写电机生产日期');
                            $dj_made_at = null;
                        }
                        // 验证寿命 AD
                        $dj_scraping_at = null;
                        if (is_numeric($dj_life_year)) {
                            if ($dj_life_year < 0) {
                                $excel_error['AD'] = ExcelWriteHelper::makeErrorResult($current_row, '电机寿命(年)', $dj_life_year, '电机寿命必须填写正整数');
                                $dj_scraping_at = null;
                            } else {
                                $dj_scraping_at = Carbon::parse($dj_made_at)->addYears($dj_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AD'] = ExcelWriteHelper::makeErrorResult($current_row, '电机寿命(年)', $dj_life_year, '电机寿命必须填写正整数');
                            $dj_scraping_at = null;
                        }

                        return ($dj_serial_number && $dj_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $dj_factory_name,
                            'factory_device_code' => $dj_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $dj_made_at,
                            'scraping_at' => $dj_scraping_at,
                            'device_model_unique_code' => $dj_model->unique_code,
                            'serial_number' => $dj_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 移位接触器(左)：所编号AE、厂家AF、厂编号AG、型号AH、生产日期AI、寿命(年)AJ
                    $check_ywjcq_l = function () use ($current_row, &$ywjcq_serial_number_l, &$ywjcq_factory_name_l, &$ywjcq_factory_device_code_l, &$ywjcq_model_name_l, &$ywjcq_made_at_l, &$ywjcq_life_year_l, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 AF
                        if ($ywjcq_factory_name_l) {
                            $ywjcq_factory = Factory::with([])->where('name', $ywjcq_factory_name_l)->first();
                            if (!$ywjcq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(移位接触器(左))：{$ywjcq_factory_name_l}");
                            // if (!$ywjcq_factory) {
                            //     $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name, "没有找到厂家：{$ywjcq_factory_name}");
                            // }
                        } else {
                            $excel_error['AF'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name_l, '没有填写移位接触器厂家');
                            $ywjcq_factory_name_l = '';
                        }
                        // 验证厂编号 AE
                        if (!$ywjcq_factory_device_code_l) {
                            $excel_error['AE'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)厂编号', $ywjcq_factory_device_code_l, '没有填写移位接触器(左)编号');
                            $ywjcq_factory_device_code_l = '';
                        }
                        // 验证型号AH
                        $ywjcq_model = null;
                        if ($ywjcq_serial_number_l && $ywjcq_model_name_l) {
                            $ywjcq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $ywjcq_model_name_l)->first();
                            if (!$ywjcq_model) throw new ExcelInException("第{$current_row}行，没有移位接触器(左)型号：{$ywjcq_model_name_l}");
                        }
                        // 验证所编号 AE
                        if ($ywjcq_serial_number_l && $ywjcq_model_name_l) {
                            $pi = PartInstance::with([])->where('serial_number', $ywjcq_serial_number_l)->where('device_model_unique_code', $ywjcq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，移位接触器(左)：{$ywjcq_serial_number_l}所编号重复");
                        }
                        // 验证生产日期 AI
                        if ($ywjcq_made_at_l) {
                            try {
                                $ywjcq_made_at_l = ExcelWriteHelper::getExcelDate($ywjcq_made_at_l);
                            } catch (Exception $e) {
                                $excel_error['AI'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)生产日期', $ywjcq_made_at_l, $e->getMessage());
                                $ywjcq_made_at_l = null;
                            }
                        } else {
                            $excel_error['AI'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)生产日期', $ywjcq_made_at_l, '没有填写移位接触器生产日期');
                            $ywjcq_made_at_l = null;
                        }
                        $ywjcq_scraping_at = null;
                        // 验证寿命 AJ
                        if (is_numeric($ywjcq_life_year_l)) {
                            if ($ywjcq_life_year_l < 0) {
                                $excel_error['AJ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)寿命(年)', $ywjcq_life_year_l, '移位接触器寿(左)命必须填写正整数');
                                $ywjcq_scraping_at = null;
                            } else {
                                $ywjcq_scraping_at = Carbon::parse($ywjcq_made_at_l)->addYears($ywjcq_life_year_l)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AJ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器寿命(年)', $ywjcq_life_year_l, '移位接触器寿命必须填写正整数');
                            $ywjcq_scraping_at = null;
                        }

                        return ($ywjcq_serial_number_l && $ywjcq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $ywjcq_factory_name_l,
                            'factory_device_code' => $ywjcq_factory_device_code_l,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $ywjcq_made_at_l,
                            'scraping_at' => $ywjcq_scraping_at,
                            'device_model_unique_code' => $ywjcq_model->unique_code,
                            'serial_number' => $ywjcq_serial_number_l,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 移位接触器(右)：所编号AK、厂家AL、厂编号AM、型号AN、生产日期AO、寿命(年)AP
                    $check_ywjcq_r = function () use ($current_row, &$ywjcq_serial_number_r, &$ywjcq_factory_name_r, &$ywjcq_factory_device_code_r, &$ywjcq_model_name_r, &$ywjcq_made_at_r, &$ywjcq_life_year_r, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 AL
                        if ($ywjcq_factory_name_r) {
                            $ywjcq_factory = Factory::with([])->where('name', $ywjcq_factory_name_r)->first();
                            if (!$ywjcq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(移位接触器 右)：{$ywjcq_factory_name_r}");
                            // if (!$ywjcq_factory) {
                            //     $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name, "没有找到厂家：{$ywjcq_factory_name}");
                            // }
                        } else {
                            $excel_error['AL'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家(右)', $ywjcq_factory_name_r, '没有填写移位接触器(右)厂家');
                            $ywjcq_factory_name_r = '';
                        }
                        // 验证厂编号 AM
                        if (!$ywjcq_factory_device_code_r) {
                            $excel_error['AN'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂编号(右)', $ywjcq_factory_device_code_r, '没有填写移位接触器(右)编号');
                            $ywjcq_factory_device_code_r = '';
                        }
                        // 验证型号 AN
                        $ywjcq_model = null;
                        if ($ywjcq_serial_number_r && $ywjcq_model_name_r) {
                            $ywjcq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $ywjcq_model_name_r)->first();
                            if (!$ywjcq_model) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(右）型号：{$ywjcq_model_name_r}");
                        }
                        // 验证所编号 AK
                        if ($ywjcq_serial_number_r && $ywjcq_model_name_r) {
                            $pi = PartInstance::with([])->where('serial_number', $ywjcq_serial_number_r)->where('device_model_unique_code', $ywjcq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，移位接触器(右)：{$ywjcq_serial_number_r}所编号重复");
                        }
                        // 验证生产日期 AO
                        if ($ywjcq_made_at_r) {
                            try {
                                $ywjcq_made_at_r = ExcelWriteHelper::getExcelDate($ywjcq_made_at_r);
                            } catch (Exception $e) {
                                $excel_error['AP'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器生产日期', $ywjcq_made_at_r, $e->getMessage());
                                $ywjcq_made_at_r = null;
                            }
                        } else {
                            $excel_error['AP'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器生产日期', $ywjcq_made_at_r, '没有填写移位接触器生产日期');
                            $ywjcq_made_at_l = null;
                        }
                        $ywjcq_scraping_at = null;
                        // 验证寿命 AP
                        if (is_numeric($ywjcq_life_year_r)) {
                            if ($ywjcq_life_year_r < 0) {
                                $excel_error['AQ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器寿命(年)', $ywjcq_life_year_r, '移位接触器寿命必须填写正整数');
                                $ywjcq_scraping_at = null;
                            } else {
                                $ywjcq_scraping_at = Carbon::parse($ywjcq_made_at_r)->addYears($ywjcq_life_year_r)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AQ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器寿命(年)', $ywjcq_life_year_r, '移位接触器寿命必须填写正整数');
                            $ywjcq_scraping_at = null;
                        }

                        return ($ywjcq_serial_number_r && $ywjcq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $ywjcq_factory_name_r,
                            'factory_device_code' => $ywjcq_factory_device_code_r,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $ywjcq_made_at_r,
                            'scraping_at' => $ywjcq_scraping_at,
                            'device_model_unique_code' => $ywjcq_model->unique_code,
                            'serial_number' => $ywjcq_serial_number_r,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 减速器：所编号AQ、厂家AR、厂编号AS、型号AT、生产日期AU、寿命(年)AV
                    $check_jsq = function () use ($current_row, &$jsq_serial_number, &$jsq_factory_name, &$jsq_factory_device_code, &$jsq_model_name, &$jsq_made_at, &$jsq_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 AR
                        if ($jsq_factory_name) {
                            $jsq_factory = Factory::with([])->where('name', $jsq_factory_name)->first();
                            if (!$jsq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(减速器)：{$jsq_factory_name}");
                            // if (!$jsq_factory) {
                            //     $excel_error['AM'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂家', $jsq_factory_name, "没有找到厂家：{$jsq_factory_name}");
                            // }
                        } else {
                            $excel_error['AR'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂家', $jsq_factory_name, '没有填写减速器厂家');
                            $jsq_factory_name = '';
                        }
                        // 验证厂编号 AS
                        if (!$jsq_factory_device_code) {
                            $excel_error['AS'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂编号', $jsq_factory_device_code, '没有填写减速器编号');
                            $jsq_factory_device_code = '';
                        }
                        // 验证型号 AT
                        $jsq_model = null;
                        if ($jsq_serial_number && $jsq_model_name) {
                            $jsq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $jsq_model_name)->first();
                            if (!$jsq_model) throw new ExcelInException("第{$current_row}行，没有找到减速器型号：{$jsq_model_name}");
                        }
                        // 验证所编号 AQ
                        if ($jsq_serial_number && $jsq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $jsq_serial_number)->where('device_model_unique_code', $jsq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，减速器：{$jsq_serial_number}所编号重复");
                        }
                        // 验证生产日期 AU
                        if ($jsq_made_at) {
                            try {
                                $jsq_made_at = ExcelWriteHelper::getExcelDate($jsq_made_at);
                            } catch (Exception $e) {
                                $excel_error['AU'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器生产日期', $jsq_made_at, $e->getMessage());
                                $jsq_made_at = null;
                            }
                        } else {
                            $excel_error['AU'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器生产日期', $jsq_made_at, '没有填写减速器生产日期');
                            $jsq_made_at = null;
                        }
                        $jsq_scraping_at = null;
                        // 验证寿命 AV
                        if (is_numeric($jsq_life_year)) {
                            if ($jsq_life_year < 0) {
                                $excel_error['AV'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器寿命(年)', $jsq_life_year, '减速器寿命必须填写正整数');
                                $jsq_scraping_at = null;
                            } else {
                                $jsq_scraping_at = Carbon::parse($jsq_made_at)->addYears($jsq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AV'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器寿命(年)', $jsq_life_year, '减速器寿命必须填写正整数');
                            $jsq_scraping_at = null;
                        }

                        return ($jsq_serial_number && $jsq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $jsq_factory_name,
                            'factory_device_code' => $jsq_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $jsq_made_at,
                            'scraping_at' => $jsq_scraping_at,
                            'device_model_unique_code' => $jsq_model->unique_code,
                            'serial_number' => $jsq_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 油泵：所编号AW、厂家AX、厂编号AY、型号AZ、生产日期BA、寿命(年)BB
                    $check_yb = function () use ($current_row, &$yb_serial_number, &$yb_factory_name, &$yb_factory_device_code, &$yb_model_name, &$yb_made_at, &$yb_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 AX
                        if ($yb_factory_name) {
                            $yb_factory = Factory::with([])->where('name', $yb_factory_name)->first();
                            if (!$yb_factory) throw new ExcelInException("第{$current_row}行，厂家没有找到(油泵)：{$yb_factory_name}");
                            // if (!$yb_factory) {
                            //     $excel_error['AS'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂家', $yb_factory_name, "没有找到厂家：{$yb_factory_name}");
                            // }
                        } else {
                            $excel_error['AW'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂家', $yb_factory_name, '没有填写油泵厂家');
                            $yb_factory_name = '';
                        }
                        // 验证厂编号 AY
                        if (!$yb_factory_device_code) {
                            $excel_error['AX'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂编号', $yb_factory_device_code, '没有填写油泵编号');
                            $yb_factory_device_code = '';
                        }
                        // 验证型号 AZ
                        $yb_model = null;
                        if ($yb_serial_number && $yb_model_name) {
                            $yb_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $yb_model_name)->first();
                            if (!$yb_model) throw new ExcelInException("第{$current_row}行，没有找到油泵型号：{$yb_model_name}");
                        }
                        // 验证所编号 AW
                        if ($yb_serial_number && $yb_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $yb_serial_number)->where('device_model_unique_code', $yb_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，油泵：{$yb_serial_number}所编号重复");
                        }
                        // 验证生产日期 BA
                        if ($yb_made_at) {
                            try {
                                $yb_made_at = ExcelWriteHelper::getExcelDate($yb_made_at);
                            } catch (Exception $e) {
                                $excel_error['BA'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵生产日期', $yb_made_at, $e->getMessage());
                                $yb_made_at = null;
                            }
                        } else {
                            $excel_error['BA'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵生产日期', $yb_made_at, '没有填写油泵生产日期');
                            $yb_made_at = null;
                        }
                        $yb_scraping_at = null;
                        // 验证寿命 BB
                        if (is_numeric($yb_life_year)) {
                            if ($yb_life_year < 0) {
                                $excel_error['BB'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵寿命(年)', $yb_life_year, '油泵寿命必须填写正整数');
                                $yb_scraping_at = null;
                            } else {
                                $yb_scraping_at = Carbon::parse($yb_made_at)->addYears($yb_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BB'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵寿命(年)', $yb_life_year, '油泵寿命必须填写正整数');
                            $yb_scraping_at = null;
                        }

                        return ($yb_serial_number && $yb_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $yb_factory_name,
                            'factory_device_code' => $yb_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $yb_made_at,
                            'scraping_at' => $yb_scraping_at,
                            'device_model_unique_code' => $yb_model->unique_code,
                            'serial_number' => $yb_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 自动开闭器：所编号BC、厂家BD、厂编号BE、型号BF、生产日期BG、寿命(年)BH
                    $check_zdkbq = function () use ($current_row, &$zdkbq_serial_number, &$zdkbq_factory_name, &$zdkbq_factory_device_code, &$zdkbq_model_name, &$zdkbq_made_at, &$zdkbq_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 BD
                        if ($zdkbq_factory_name) {
                            $zdkbq_factory = Factory::with([])->where('name', $zdkbq_factory_name)->first();
                            if (!$zdkbq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(自动开闭器)：{$zdkbq_factory_name}");
                            // if (!$zdkbq_factory) {
                            //     $excel_error['AY'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂家', $zdkbq_factory_name, "没有找到厂家：{$zdkbq_factory_name}");
                            // }
                        } else {
                            $excel_error['BD'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂家', $zdkbq_factory_name, '没有填写自动开闭器厂家');
                            $zdkbq_factory_name = '';
                        }
                        // 验证厂编号 BE
                        if (!$zdkbq_factory_device_code) {
                            $excel_error['BE'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂编号', $zdkbq_factory_device_code, '没有填写自动开闭器编号');
                            $zdkbq_factory_device_code = '';
                        }
                        // 验证型号 BF
                        $zdkbq_model = null;
                        if ($zdkbq_serial_number && $zdkbq_model_name) {
                            $zdkbq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $zdkbq_model_name)->first();
                            if (!$zdkbq_model) throw new ExcelInException("第{$current_row}行，没有找到自动开闭器型号：{$zdkbq_model_name}");
                        }
                        // 验证所编号 BC
                        if ($zdkbq_serial_number && $zdkbq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $zdkbq_serial_number)->where('device_model_unique_code', $zdkbq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，自动开闭器：{$zdkbq_serial_number}所编号重复");
                        }
                        // 验证生产日期 BG
                        if ($zdkbq_made_at) {
                            try {
                                $zdkbq_made_at = ExcelWriteHelper::getExcelDate($zdkbq_made_at);
                            } catch (Exception $e) {
                                $excel_error['BG'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器生产日期', $zdkbq_made_at, $e->getMessage());
                                $zdkbq_made_at = null;
                            }
                        } else {
                            $excel_error['BG'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器生产日期', $zdkbq_made_at, '没有填写自动开闭器生产日期');
                            $zdkbq_made_at = null;
                        }
                        $zdkbq_scraping_at = null;
                        // 验证寿命 BH
                        if (is_numeric($zdkbq_life_year)) {
                            if ($zdkbq_life_year < 0) {
                                $excel_error['BH'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器寿命(年)', $zdkbq_life_year, '自动开闭器寿命必须填写正整数');
                                $zdkbq_scraping_at = null;
                            } else {
                                $zdkbq_scraping_at = Carbon::parse($zdkbq_made_at)->addYears($zdkbq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BH'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器寿命(年)', $zdkbq_life_year, '自动开闭器寿命必须填写正整数');
                            $zdkbq_scraping_at = null;
                        }

                        return ($zdkbq_serial_number && $zdkbq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $zdkbq_factory_name,
                            'factory_device_code' => $zdkbq_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $zdkbq_made_at,
                            'scraping_at' => $zdkbq_scraping_at,
                            'device_model_unique_code' => $zdkbq_model->unique_code,
                            'serial_number' => $zdkbq_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 摩擦连接器：所编号BI、厂家BJ、厂编号BK、型号BL、生产日期BM、寿命(年)BN
                    $check_mcljq = function () use ($current_row, &$mcljq_serial_number, &$mcljq_factory_name, &$mcljq_factory_device_code, &$mcljq_model_name, &$mcljq_made_at, &$mcljq_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 BJ
                        if ($mcljq_factory_name) {
                            $mcljq_factory = Factory::with([])->where('name', $mcljq_factory_name)->first();
                            if (!$mcljq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(摩擦连接器)：{$mcljq_factory_name}");
                            // if (!$mcljq_factory) {
                            //     $excel_error['BE'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $mcljq_factory_name, "没有找到厂家：{$mcljq_factory_name}");
                            // }
                        } else {
                            $excel_error['BJ'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器厂家', $mcljq_factory_name, '没有填写摩擦连接器厂家');
                            $mcljq_factory_name = '';
                        }
                        // 验证厂编号 BK
                        if (!$mcljq_factory_device_code) {
                            $excel_error['BK'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器厂编号', $mcljq_factory_device_code, '没有填写摩擦连接器编号');
                            $mcljq_factory_device_code = '';
                        }
                        // 验证型号 BL
                        $mcljq_model = null;
                        if ($mcljq_serial_number && $mcljq_model_name) {
                            $mcljq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $mcljq_model_name)->first();
                            if (!$mcljq_model) throw new ExcelInException("第{$current_row}行，没有找到摩擦连接器型号：{$mcljq_model_name}");
                        }
                        // 验证所编号 BI
                        if ($mcljq_serial_number && $mcljq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $mcljq_serial_number)->where('device_model_unique_code', $mcljq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，摩擦连接器：{$mcljq_serial_number}所编号重复");
                        }
                        // 验证生产日期 BM
                        if ($mcljq_made_at) {
                            try {
                                $mcljq_made_at = ExcelWriteHelper::getExcelDate($mcljq_made_at);
                            } catch (Exception $e) {
                                $excel_error['BM'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器生产日期', $mcljq_made_at, $e->getMessage());
                                $mcljq_made_at = null;
                            }
                        } else {
                            $excel_error['BM'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $mcljq_made_at, '没有填写电机生产日期');
                            $mcljq_made_at = null;
                        }
                        // 验证寿命 BN
                        $mcljq_scraping_at = null;
                        if (is_numeric($mcljq_life_year)) {
                            if ($mcljq_life_year < 0) {
                                $excel_error['BN'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器寿命(年)', $mcljq_life_year, '摩擦连接器寿命必须填写正整数');
                                $mcljq_scraping_at = null;
                            } else {
                                $mcljq_scraping_at = Carbon::parse($mcljq_made_at)->addYears($mcljq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BN'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器寿命(年)', $mcljq_life_year, '摩擦连接器寿命必须填写正整数');
                            $mcljq_scraping_at = null;
                        }

                        return ($mcljq_serial_number && $mcljq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $mcljq_factory_name,
                            'factory_device_code' => $mcljq_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $mcljq_made_at,
                            'scraping_at' => $mcljq_scraping_at,
                            'device_model_unique_code' => $mcljq_model->unique_code,
                            'serial_number' => $mcljq_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 写入待插入数据
                    $new_entire_instances[] = [
                        'entire_model_unique_code' => $sm->entire_model_unique_code,
                        'serial_number' => $om_serial_number,
                        'status' => $status,
                        'maintain_station_name' => $o_station_name,
                        'crossroad_number' => $o_maintain_location_code ?? '',
                        'factory_name' => $om_factory_name,
                        'factory_device_code' => $o_factory_device_code,
                        'identity_code' => '',
                        'category_unique_code' => $category->unique_code,
                        'category_name' => $category->name,
                        'fix_cycle_unit' => 'YEAR',
                        'fix_cycle_value' => $o_cycle_fix_year,
                        'made_at' => $o_made_at,
                        'scarping_at' => $scraping_at,
                        'model_unique_code' => $sm->unique_code,
                        'model_name' => $sm->name,
                        'maintain_workshop_name' => $station ? ($station->Parent ? $station->Parent->name : '') : '',
                        'v250_task_order_sn' => $v250_task_order->serial_number,
                        'fixer_id' => $fixer ? $fixer->id : null,
                        'fixed_at' => $fixer ? $o_fixed_at : null,
                        'checker_id' => $checker ? $checker->id : null,
                        'checked_at' => $checker ? $o_checked_at : null,
                        'spot_checker_id' => $spot_checker ? $spot_checker->id : null,
                        'spot_checked_at' => $spot_checker ? $o_spot_checked_at : null,
                        'open_direction' => $o_open_direction,
                        'line_name' => $o_line_name,
                        'said_rod' => $o_said_rod,
                        'crossroad_type' => $o_crossroad_type,
                        'extrusion_protect' => $o_extrusion_protect,
                        'traction' => $o_traction,
                        'work_area_unique_code' => $v250_task_order->work_area_unique_code,
                        'part_instances' => [
                            'dj' => $check_dj(),  // 电机
                            'ywjcq_l' => $check_ywjcq_l(),  // 移位接触器(左)
                            'ywjcq_r' => $check_ywjcq_r(), // 移位接触器(右)
                            'jsq' => $check_jsq(),  // 减速器
                            'yb' => $check_yb(),  // 油泵
                            'zdkbq' => $check_zdkbq(),  // 自动开闭器
                            'mcljq' => $check_mcljq(),  // 摩擦连接器
                        ],
                    ];
                    // 错误数据统计
                    if (!empty($excel_error)) $excel_errors[] = $excel_error;

                    $current_row++;
                }

                // 按照型号进行分组
                $new_entire_instances = collect($new_entire_instances)->groupBy('entire_model_unique_code')->toArray();
                // 获取设备对应型号总数
                $entire_instance_counts = EntireInstanceCount::with([])->pluck('count', 'entire_model_unique_code');

                // 赋码
                foreach ($new_entire_instances as $e => &$new_entire_instance) {
                    $c = $entire_instance_counts->get($e, 0);

                    foreach ($new_entire_instance as $k => $ei) {
                        // 整件赋码
                        $em = EntireModel::with(['Category', 'Category.Race'])->where('unique_code', $e)->first();
                        if (!$em) throw new ExcelInException("没有找到类型或型号(1)：{$e}");
                        $new_entire_instance[$k]['identity_code'] = "{$em->unique_code}"
                            . env('ORGANIZATION_CODE')
                            . str_pad(++$c, $em->Category->Race->serial_number_length, '0', 0);
                        $entire_instance_counts[$e] = $c;

                        // 部件赋码
                        foreach ($ei['part_instances'] as $pk => $part_instance) {
                            $pic = $entire_instance_counts->get($part_instance['device_model_unique_code'], 0);
                            if ($part_instance) {
                                $new_entire_instance[$k]['part_instances'][$pk]['entire_instance_identity_code'] = $new_entire_instance[$k]['identity_code'];
                                $new_entire_instance[$k]['part_instances'][$pk]['identity_code'] = $part_instance['device_model_unique_code'] . env('ORGANIZATION_CODE') . str_pad(++$pic, 8, '0', 0);
                                $new_entire_instance[$k]['part_instances'][$pk]['entire_instance_serial_number'] = $new_entire_instance[$k]['serial_number'];
                                $entire_instance_counts[$part_instance['device_model_unique_code']] = $pic;
                            }
                        }
                    }
                }

                // 写入数据库
                DB::begintransaction();
                // 生成入所单
                // $warehouse_report = WarehouseReport::with([])->create([
                //     'processor_id' => $request->get('account_id'),
                //     'processed_at' => date('Y-m-d H:i:s'),
                //     'type' => strtoupper($request->get('type')),
                //     'direction' => 'IN',
                //     'serial_number' => $new_warehouse_report_sn = Code::makeSerialNumber(strtoupper($request->get('type')) . '_IN'),
                //     'status' => 'DONE',
                //     'v250_task_order_sn' => $v250_task_order->serial_number,
                // ]);

                // 添加新设备
                $current_row_for_fix_workflow = 3;
                $inserted_count = 0;
                $new_entire_instances = array_collapse($new_entire_instances);
                foreach ($new_entire_instances as $new_entire_instance) {
                    $fix_workflow_datum = [
                        'fixer_id' => $new_entire_instance['fixer_id'],
                        'fixed_at' => $new_entire_instance['fixed_at'],
                        'checker_id' => $new_entire_instance['checker_id'],
                        'checked_at' => $new_entire_instance['checked_at'],
                        'spot_checker_id' => $new_entire_instance['spot_checker_id'],
                        'spot_checked_at' => $new_entire_instance['spot_checked_at'],
                    ];
                    unset(
                        $new_entire_instance['fixer_id'],
                        $new_entire_instance['fixed_at'],
                        $new_entire_instance['checker_id'],
                        $new_entire_instance['checked_at'],
                        $new_entire_instance['spot_checker_id'],
                        $new_entire_instance['spot_checked_at']
                    );
                    $nei = EntireInstance::with([])->create(array_except($new_entire_instance, ['part_instances']));

                    // 设备加锁
                    EntireInstanceLock::setOnlyLock($nei->identity_code, ['NEW_STATION'], "设备：{$nei->identity_code}在新站设备：{$sn}中被使用。");

                    // 入所单设备
                    $nwrei = WarehouseReportEntireInstance::with([])->create([
                        'warehouse_report_serial_number' => $warehouse_report->serial_number,
                        'entire_instance_identity_code' => $nei->identity_code,
                    ]);

                    // 任务单设备
                    $v250_task_entire_instance = V250TaskEntireInstance::with(['EntireInstance'])->create([
                        'v250_task_order_sn' => $sn,
                        'entire_instance_identity_code' => $nei->identity_code,
                        'fixer_id' => @$fix_workflow_datum['fixer_id'] ? $fix_workflow_datum['fixer_id'] : 0,
                        'checker_id' => @$fix_workflow_datum['checker_id'] ? $fix_workflow_datum['checker_id'] : 0,
                        'fixed_at' => @$fix_workflow_datum['fixed_at'] ? $fix_workflow_datum['fixed_at'] : null,
                        'checked_at' => @$fix_workflow_datum['checked_at'] ? $fix_workflow_datum['checked_at'] : null,
                        'spot_checker_id' => @$fix_workflow_datum['spot_checker_id'] ? $fix_workflow_datum['spot_checker_id'] : 0,
                        'spot_checked_at' => @$fix_workflow_datum['spot_checked_at'] ? $fix_workflow_datum['spot_checked_at'] : null,
                    ]);

                    // 生成日志
                    EntireInstanceLogFacade::makeOne('新购赋码', $new_entire_instance['identity_code'], 0, '', $o_made_at ? ($scraping_at ? "出厂日期：{$o_made_at}；到期日期：{$scraping_at}；" : "出厂日期：{$o_made_at}；") : '');  // 新购入所
                    EntireInstanceLogFacade::makeOne('入所', $new_entire_instance['identity_code'], 1, "/warehouse/report/{$nwrei->warehouse_report_serial_number}?show_type=D&page=1&current_work_area=&direction=IN&updated_at=", "经办人：" . session('account.nickname') . '；');

                    $inserted_count++;

                    // 如果有检修人和验收人生成检修单
                    if ($fix_workflow_datum['fixer_id'] || ($fix_workflow_datum['checker_id'] && $fix_workflow_datum['checked_at'])) {
                        FixWorkflowFacade::mockEmptyWithOutEditFixed(
                            $nei,
                            $fix_workflow_datum['fixed_at'],
                            $fix_workflow_datum['checked_at'],
                            $fix_workflow_datum['fixer_id'],
                            $fix_workflow_datum['checker_id'],
                            $fix_workflow_datum['spot_checked_at'],
                            $fix_workflow_datum['spot_checker_id']
                        );

                        // 如果设备没有分配，则分配
                        $overhual_entire_instance = OverhaulEntireInstance::with([])
                            ->where('entire_instance_identity_code', $nei->identity_code)
                            ->where('v250_task_order_sn', $v250_task_order->serial_number)
                            ->first();
                        $overhual_entire_instance_status = $fix_workflow_datum['checker_id'] ? (strtotime($v250_task_order->expiring_at) < strtotime($fix_workflow_datum['checked_at']) ? '2' : '1') : '0';
                        $overhual_entire_instance_datum = [
                            'v250_task_order_sn' => $v250_task_order->serial_number,
                            'entire_instance_identity_code' => $nei->identity_code,
                            'fixer_id' => $fix_workflow_datum['fixer_id'],
                            'fixed_at' => $fix_workflow_datum['fixed_at'],
                            'checker_id' => $fix_workflow_datum['checker_id'],
                            'checked_at' => $fix_workflow_datum['checked_at'],
                            'spot_checker_id' => $fix_workflow_datum['spot_checker_id'],
                            'spot_checked_at' => $fix_workflow_datum['spot_checked_at'],
                            'allocate_at' => date('Y-m-d H:i:s'),
                            'deadline' => $v250_task_order->expiring_at,
                            'status' => $overhual_entire_instance_status,
                        ];
                        if ($overhual_entire_instance && @$overhual_entire_instance->status ?: '' == '0') {
                            $overhual_entire_instance->fill($overhual_entire_instance_datum)->saveOrFail();
                        } else {
                            OverhaulEntireInstance::with([])->create($overhual_entire_instance_datum);
                        }
                    }

                    // 添加部件
                    foreach ($new_entire_instance['part_instances'] as $part_instance) {
                        if ($part_instance) PartInstance::with([])->create($part_instance);
                    }
                    $current_row_for_fix_workflow++;
                }

                // 更新该型号下的所有设备总数
                foreach ($entire_instance_counts as $e => $c) {
                    EntireInstanceCount::with([])->where('entire_model_unique_code', $e)->updateOrCreate([
                        'entire_model_unique_code' => $e,
                        'count' => $c,
                    ]);
                }

                DB::commit();
                $with_msg = "设备赋码：{$inserted_count}条。";

                if (!empty($excel_errors)) {
                    $root_dir = storage_path('v250TaskOrder/upload/' . strtoupper($request->get('type')) . '/errorExcels/createDevice');
                    if (!is_dir($root_dir)) FileSystem::init($root_dir)->makeDir();
                    $this->_makeErrorExcel($excel_errors, "{$root_dir}/{$sn}");

                    $v250_task_order->fill(['is_upload_create_device_excel_error' => true])->saveOrFail();

                    $with_msg = "设备赋码：{$inserted_count}条。" . '其中' . count($excel_errors) . '行有错误。';
                }

                return redirect("/v250TaskOrder/{$sn}/uploadCreateDeviceReport?" . http_build_query([
                        'warehouseReportSN' => $new_warehouse_report_sn,
                        'page' => $request->get('page'),
                        'type' => $request->get('type'),
                    ]))
                    ->with('success', $with_msg);
            case 'reply':
            case 'synthesize':
                // 读取excel数据
                $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->originRow(2)
                    ->withSheetIndex(0);
                $current_row = 2;
                // 继电器和综合工区
                // 所编号、种类*、类型*、型号*、状态*(新购、上道、备品、成品、待修)、厂编号、厂家、生产日期、
                // 车站、组合位置、检测/检修人、检测/检修时间(YYYY-MM-DD格式)、验收人、验收时间(YYYY-MM-DD格式)、抽验人、抽验时间(YYYY-MM-DD格式)
                // 寿命(年)、周期修年(非周期修写0)、开向(左、右)、线制、表示杆特征、道岔类型、转辙机组合类型、防挤压保护罩(是、否)、牵引
                $new_entire_instances = [];
                $excel_error = [];
                // 数据验证
                foreach ($excel['success'] as $row_datum) {
                    // 如果整行都没有数据则跳过
                    if (empty(array_filter($row_datum, function ($value) {
                        return !empty($value) && !is_null($value);
                    }))) continue;
                    list(
                        $om_serial_number, $om_category_name, $om_entire_model_name, $om_sub_model_name, $om_status_mame, $o_factory_device_code, $om_factory_name, $o_made_at,
                        $o_station_name, $o_maintain_location_code, $o_fixer_name, $o_fixed_at, $o_checker_name, $o_checked_at, $o_spot_checker_name, $o_spot_checked_at,
                        $o_life_year, $o_cycle_fix_year
                        ) = $row_datum;

                    // 以下是严重错误，不允许通过
                    // 验证所编号和厂编号
                    if (!$om_serial_number && !$o_factory_device_code) throw new ExcelInException('所编号或厂编号必填一个');
                    // 验证种类
                    if (!$om_category_name) throw new ExcelInException("第{$current_row}行，种类不能为空");
                    $category = Category::with([])->where('name', $om_category_name)->first();
                    if (!$category) throw new ExcelInException("第{$current_row}行，种类：{$om_category_name}不存在");
                    // 验证类型
                    if (!$om_entire_model_name) throw new ExcelInException("第{$current_row}行，类型不能为空");
                    $em = EntireModel::with([])->where('is_sub_model', false)->where('category_unique_code', $category->unique_code)->where('name', $om_entire_model_name)->first();
                    if (!$em) throw new ExcelInException("第{$current_row}行，类型：{$category->name} > {$om_entire_model_name}不存在");
                    // 验证厂家
                    if ($om_factory_name)
                        if (!Factory::with([])->where('name', $om_factory_name)->first())
                            throw new ExcelInException("第{$current_row}行，没有找到厂家：{$om_factory_name}");

                    // 判断是否是设备
                    if (substr($em->unique_code, 0, 1) == 'S') {
                        // 设备
                        $is_device = false;
                    } else {
                        // 器材
                        $is_device = true;
                        // 验证型号
                        if (!$om_sub_model_name) throw new ExcelInException("第{$current_row}行，型号不能为空");
                        $sm = EntireModel::with([])->where('is_sub_model', true)->where('parent_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                        $pm = PartModel::with([])->where('entire_model_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                        if (!$sm && !$pm) throw new ExcelInException("第{$current_row}行，型号：{$category->name} > {$em->name} > {$om_sub_model_name}不存在");
                        if (!$sm && $pm) $sm = $pm;
                    }

                    // 验证状态
                    if (!$om_status_mame) throw new ExcelInException("第{$current_row}行，状态不能为空");
                    if (!array_key_exists($om_status_mame, $statuses)) throw new ExcelInException("第{$current_row}行，设备状态：{$om_status_mame}错误，只能填写：" . implode('、', array_flip($statuses)));
                    $status = $statuses[$om_status_mame];
                    // 验证所编号是否重复
                    if (EntireInstance::with([])->where('serial_number', $om_serial_number)->where('model_unique_code', $is_device ? $sm->unique_code : $em->unique_code)->first()) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}({$om_sub_model_name})重复");
                    // 验证长编号是否重复
                    if ($o_factory_device_code)
                        if (EntireInstance::with([])->where('factory_device_code', $o_factory_device_code)->first()) throw new ExcelInException("第{$current_row}行，设备：{$o_factory_device_code}重复，厂编号重复");

                    // 以下是非严重错误，可以通过
                    // 验证厂编号
                    if (!$o_factory_device_code) {
                        $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂编号', $o_factory_device_code, '没有填写', 'red');
                    }
                    // 验证厂家
                    // if ($om_factory_name) {
                    //     $factory = Factory::with([])->where('name', $om_factory_name)->first();
                    //     if (!$factory) $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, "厂家名称填写不规范，现有厂家名称中没有找到：{$om_factory_name}", 'red');
                    // } else {
                    //     $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, '没有填写厂家', 'red');
                    //     $om_factory_name = '';
                    // }

                    // 验证生产日期
                    if ($o_made_at) {
                        try {
                            $o_made_at = date('Y-m-d',strtotime(ExcelWriteHelper::getExcelDate($o_made_at)));
                        } catch (Exception $e) {
                            $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, $e->getMessage());
                            $o_made_at = null;
                        }
                    } else {
                        $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, '没有填写生产日期');
                        $o_made_at = null;
                    }

                    // 验证车站
                    if ($o_station_name) {
                        $station = Maintain::with(['Parent'])->where('name', $o_station_name)->first();
                        // todo: 刷线别、现场车间、车站数据之后，需要根据新的数据库来修改
                        //         $station = Station::with([])->where('name', $oStationName)->where('name', $oStationName)->first();
                        if (!$station) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "没有找到车站：{$o_station_name}");
                        if ($station) {
                            if (!$station->Parent) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "车站：{$o_station_name}，没有找到上一级车间");
                        }
                    } else {
                        $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, '没有填写车站');
                        $station = null;
                        $o_station_name = '';
                    }

                    // 验证检测/检修人
                    $fixer = null;
                    if ($o_fixer_name) {
                        $fixer = Account::with([])->where('nickname', $o_fixer_name)->first();
                        if (!$fixer) $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修人', $o_fixer_name, "没有找到检修人：{$o_fixer_name}");
                    }

                    // 验证检修时间
                    if ($o_fixed_at) {
                        try {
                            $o_fixed_at = ExcelWriteHelper::getExcelDate($o_fixed_at);
                        } catch (Exception $e) {
                            $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修时间', $o_fixed_at, $e->getMessage());
                            $o_fixed_at = null;
                        }
                    }

                    // 验证验收人
                    $checker = null;
                    if ($o_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        $checker = Account::with([])->where('nickname', $o_checker_name)->first();
                        if (!$checker) $excel_error['M'] = ExcelWriteHelper::makeErrorResult($current_row, '验收人', $o_fixer_name, "没有找到验收人：{$o_checker_name}");
                    }

                    // 验证检修时间
                    if ($o_checked_at) {
                        try {
                            $o_checked_at = ExcelWriteHelper::getExcelDate($o_checked_at);
                        } catch (Exception $e) {
                            $excel_error['N'] = ExcelWriteHelper::makeErrorResult($current_row, '验收时间', $o_checked_at, $e->getMessage());
                            $o_checked_at = null;
                        }
                    }

                    // 验证抽验人
                    $spot_checker = null;
                    if ($o_spot_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        if (is_null($checker)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，没有验收人");
                        $spot_checker = Account::with([])->where('nickname', $o_spot_checker_name)->first();
                        if (!$spot_checker) $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验人', $o_spot_checker_name, "没有找到抽验人：{$o_spot_checker_name}");
                    }

                    // 验证抽验时间
                    if ($o_spot_checked_at) {
                        try {
                            $o_spot_checked_at = ExcelWriteHelper::getExcelDate($o_spot_checked_at);
                        } catch (Exception $e) {
                            $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验时间', $o_spot_checked_at, $e->getMessage());
                            $o_spot_checked_at = null;
                        }
                    }

                    // 验证寿命
                    if (is_numeric($o_life_year)) {
                        if ($o_life_year < 0) {
                            $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                            $scraping_at = null;
                        } else {
                            $scraping_at = Carbon::parse($o_made_at)->addYears($o_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                        $scraping_at = null;
                    }

                    // 周期修年限
                    if (is_numeric($o_cycle_fix_year)) {
                        if ($o_cycle_fix_year < 0) {
                            $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                            $o_cycle_fix_year = 0;
                        }
                    } else {
                        $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                        $o_cycle_fix_year = 0;
                    }

                    // 写入待插入数据
                    $new_entire_instances[] = [
                        'entire_model_unique_code' => $is_device ? $sm->unique_code : $em->unique_code,
                        'serial_number' => $om_serial_number,
                        'status' => $status,
                        'maintain_station_name' => $o_station_name,
                        'maintain_location_code' => $o_maintain_location_code,
                        'factory_name' => $om_factory_name,
                        'factory_device_code' => $o_factory_device_code,
                        'identity_code' => '',
                        'category_unique_code' => $category->unique_code,
                        'category_name' => $category->name,
                        'fix_cycle_unit' => 'YEAR',
                        'fix_cycle_value' => $o_cycle_fix_year,
                        'made_at' => $o_made_at,
                        'scarping_at' => $scraping_at,
                        'model_unique_code' => $is_device ? $sm->unique_code : $em->unique_code,
                        'model_name' => $is_device ? $sm->name : $em->name,
                        'maintain_workshop_name' => $station ? ($station->Parent ? $station->Parent->name : '') : '',
                        'v250_task_order_sn' => $v250_task_order->serial_number,
                        'work_area_unique_code' => $v250_task_order->work_area_unique_code,
                        'fixer_id' => $fixer ? $fixer->id : null,
                        'fixed_at' => $fixer ? $o_fixed_at : null,
                        'checker_id' => $checker ? $checker->id : null,
                        'checked_at' => $checker ? $o_checked_at : null,
                        'spot_checker_id' => $spot_checker ? $spot_checker->id : null,
                        'spot_checked_at' => $spot_checker ? $o_spot_checked_at : null,
                    ];
                    // 错误数据统计
                    if (!empty($excel_error)) $excel_errors[] = $excel_error;

                    $current_row++;
                }

                // 按照型号进行分组
                $new_entire_instances = collect($new_entire_instances)->groupBy('model_unique_code')->toArray();
                // 获取设备对应型号总数
                $entire_instance_counts = EntireInstanceCount::with([])->whereIn('entire_model_unique_code', array_keys($new_entire_instances))->pluck('count', 'entire_model_unique_code');

                // 赋码
                foreach ($new_entire_instances as $e => &$new_entire_instance) {
                    $c = $entire_instance_counts->get($e, 0);

                    foreach ($new_entire_instance as $k => $ei) {
                        $em = EntireModel::with(['Category', 'Category.Race'])->where('unique_code', $e)->first();
                        if (!$em) throw new ExcelInException("没有找到类型或型号(2)：{$sn}");

                        $new_entire_instance[$k]['identity_code'] = "{$em->unique_code}"
                            . env('ORGANIZATION_CODE')
                            . str_pad(
                                ++$c,
                                $em->Category->Race->serial_number_length,
                                '0',
                                STR_PAD_LEFT
                            );

                        $entire_instance_counts[$e] = $c;
                    }
                }

                // 写入数据库
                DB::begintransaction();
                // 生成入所单
                $warehouse_report = WarehouseReport::with([])->create([
                    'processor_id' => $request->get('account_id'),
                    'processed_at' => date('Y-m-d H:i:s'),
                    'type' => strtoupper($request->get('type')),
                    'direction' => 'IN',
                    'serial_number' => $new_warehouse_report_sn = CodeFacade::makeSerialNumber(strtoupper($request->get('type')) . '_IN'),
                    'status' => 'DONE',
                    'v250_task_order_sn' => $v250_task_order->serial_number,
                ]);

                // 添加新设备
                $current_row_for_fix_workflow = 2;
                $inserted_count = 0;
                $new_entire_instances = array_collapse($new_entire_instances);
                foreach ($new_entire_instances as $new_entire_instance) {
                    $fix_workflow_datum = [
                        'fixer_id' => $new_entire_instance['fixer_id'],
                        'fixed_at' => $new_entire_instance['fixed_at'],
                        'checker_id' => $new_entire_instance['checker_id'],
                        'checked_at' => $new_entire_instance['checked_at'],
                        'spot_checker_id' => $new_entire_instance['spot_checker_id'],
                        'spot_checked_at' => $new_entire_instance['spot_checked_at'],
                    ];
                    unset(
                        $new_entire_instance['fixer_id'],
                        $new_entire_instance['fixed_at'],
                        $new_entire_instance['checker_id'],
                        $new_entire_instance['checked_at'],
                        $new_entire_instance['spot_checker_id'],
                        $new_entire_instance['spot_checked_at']
                    );
                    $nei = EntireInstance::with([])->create($new_entire_instance);

                    // 设备加锁
                    EntireInstanceLock::setOnlyLock($nei->identity_code, ['NEW_STATION'], "设备：{$nei->identity_code}在新站设备：{$sn}中被使用。");

                    // 入所单设备
                    $nwrei = WarehouseReportEntireInstance::with([])->create([
                        'warehouse_report_serial_number' => $warehouse_report->serial_number,
                        'entire_instance_identity_code' => $nei->identity_code,
                    ]);

                    // 任务单设备
                    $v250_task_entire_instance = V250TaskEntireInstance::with(['EntireInstance'])->create([
                        'v250_task_order_sn' => $sn,
                        'entire_instance_identity_code' => $nei->identity_code,
                        'fixer_id' => @$fix_workflow_datum['fixer_id'] ? $fix_workflow_datum['fixer_id'] : 0,
                        'checker_id' => @$fix_workflow_datum['checker_id'] ? $fix_workflow_datum['checker_id'] : 0,
                        'fixed_at' => @$fix_workflow_datum['fixed_at'] ? $fix_workflow_datum['fixed_at'] : null,
                        'checked_at' => @$fix_workflow_datum['checked_at'] ? $fix_workflow_datum['checked_at'] : null,
                        'spot_checker_id' => @$fix_workflow_datum['spot_checker_id'] ? $fix_workflow_datum['spot_checker_id'] : 0,
                        'spot_checked_at' => @$fix_workflow_datum['spot_checked_at'] ? $fix_workflow_datum['spot_checked_at'] : null,
                    ]);

                    // 生成日志
                    EntireInstanceLogFacade::makeOne('新购赋码', $new_entire_instance['identity_code'], 0, '', $o_made_at ? ($scraping_at ? "出厂日期：{$o_made_at}；到期日期：{$scraping_at}；" : "出厂日期：{$o_made_at}；") : '');  // 新购入所
                    EntireInstanceLogFacade::makeOne('入所', $new_entire_instance['identity_code'], 1, "/warehouse/report/{$nwrei->warehouse_report_serial_number}?show_type=D&page=1&current_work_area=&direction=IN&updated_at=", "经办人：" . session('account.nickname') . '；');

                    $inserted_count++;

                    // 如果有检修人和验收人生成检修单
                    if ($fix_workflow_datum['fixer_id'] || ($fix_workflow_datum['checker_id'] && $fix_workflow_datum['checked_at'])) {
                        FixWorkflowFacade::mockEmptyWithOutEditFixed(
                            $nei,
                            $fix_workflow_datum['fixed_at'],
                            $fix_workflow_datum['checked_at'],
                            $fix_workflow_datum['fixer_id'],
                            $fix_workflow_datum['checker_id'],
                            $fix_workflow_datum['spot_checked_at'],
                            $fix_workflow_datum['spot_checker_id']
                        );

                        // 如果设备没有分配，则分配
                        $overhual_entire_instance = OverhaulEntireInstance::with([])
                            ->where('entire_instance_identity_code', $nei->identity_code)
                            ->where('v250_task_order_sn', $v250_task_order->serial_number)
                            ->first();
                        $overhual_entire_instance_status = $fix_workflow_datum['checker_id'] ? (strtotime($v250_task_order->expiring_at) < strtotime($fix_workflow_datum['checked_at']) ? '2' : '1') : '0';
                        $overhual_entire_instance_datum = [
                            'v250_task_order_sn' => $v250_task_order->serial_number,
                            'entire_instance_identity_code' => $nei->identity_code,
                            'fixer_id' => $fix_workflow_datum['fixer_id'],
                            'fixed_at' => $fix_workflow_datum['fixed_at'],
                            'checker_id' => $fix_workflow_datum['checker_id'],
                            'checked_at' => $fix_workflow_datum['checked_at'],
                            'spot_checker_id' => $fix_workflow_datum['spot_checker_id'],
                            'spot_checked_at' => $fix_workflow_datum['spot_checked_at'],
                            'allocate_at' => date('Y-m-d H:i:s'),
                            'deadline' => $v250_task_order->expiring_at,
                            'status' => $overhual_entire_instance_status,
                        ];
                        if ($overhual_entire_instance && @$overhual_entire_instance->status ?: '' == '0') {
                            $overhual_entire_instance->fill($overhual_entire_instance_datum)->saveOrFail();
                        } else {
                            OverhaulEntireInstance::with([])->create($overhual_entire_instance_datum);
                        }
                    }

                    $current_row_for_fix_workflow++;
                }

                // 更新该型号下的所有设备总数
                foreach ($entire_instance_counts as $e => $c) {
                    EntireInstanceCount::with([])->where('entire_model_unique_code', $e)->firstOrCreate([
                        'entire_model_unique_code' => $e,
                        'count' => $c,
                    ]);
                }

                DB::commit();
                $with_msg = "设备赋码：{$inserted_count}条。";

                if (!empty($excel_errors)) {
                    $root_dir = storage_path('v250TaskOrder/upload/' . strtoupper($request->get('type')) . '/errorExcels/createDevice');
                    if (!is_dir($root_dir)) FileSystem::init($root_dir)->makeDir();
                    $this->_makeErrorExcel($excel_errors, "{$root_dir}/{$sn}");

                    $v250_task_order->fill(['is_upload_create_device_excel_error' => true])->saveOrFail();

                    $with_msg = "设备赋码：{$inserted_count}条。" . '其中' . count($excel_errors) . '行有错误。';
                }

                return redirect("/v250TaskOrder/{$sn}/uploadCreateDeviceReport?" . http_build_query([
                        'warehouseReportSN' => $new_warehouse_report_sn,
                        'page' => $request->get('page'),
                        'type' => $request->get('type'),
                    ]))
                    ->with('success', $with_msg);
                break;
        }
    }

    /**
     * 上传设备补充数据Excel
     * @param Request $request
     * @param string $sn
     * @param V250TaskOrder $v250_task_order
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|View
     * @throws ExcelInException
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     * @throws Throwable
     */
    final public function uploadEditDevice(Request $request, string $sn, V250TaskOrder $v250_task_order)
    {
        $excel_errors = [];
        $statuses = [
            '新购' => 'BUY_IN',
            '上道' => 'INSTALLED',
            '备品' => 'INSTALLING',
            '成品' => 'FIXED',
            '待修' => 'FIXING',
        ];

        switch ($request->get('workAreaType')) {
            default:
                return back()->with('danger', '工区参数错误');
            case 'pointSwitch':
                // 读取excel数据
                $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->originRow(3)
                    ->withSheetIndex(0);
                $current_row = 3;
                // 转辙机工区
                // 所编号、种类*、类型*、型号*、状态*(新购、上道、备品、成品、待修)、厂编号、厂家、生产日期、
                // 车站、组合位置、检测/检修人、检测/检修时间(YYYY-MM-DD格式)、验收人、验收时间(YYYY-MM-DD格式)、抽验人、抽验时间(YYYY-MM-DD格式)
                // 寿命(年)、周期修年(非周期修写0)、开向(左、右)、线制、表示杆特征、道岔类型、防挤压保护罩(是、否)、牵引
                // 电机：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 移位接触器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 减速器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 油泵：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 自动开闭器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                // 摩擦减速器：所编号、厂家、厂编号、型号、生产日期、寿命(年)
                $edit_entire_instances = [];
                $excel_error = [];
                // 数据验证
                foreach ($excel['success'] as $row_datum) {
                    if (empty(array_filter($row_datum, function ($item) {
                        return !empty($item);
                    }))) continue;
                    list(
                        $om_serial_number, $om_category_name, $om_entire_model_name, $om_sub_model_name, $om_status_mame, $o_factory_device_code, $om_factory_name, $o_made_at,
                        $o_station_name, $o_maintain_location_code, $o_fixer_name, $o_fixed_at, $o_checker_name, $o_checked_at, $o_spot_checker_name, $o_spot_checked_at,
                        $o_life_year, $o_cycle_fix_year, $o_open_direction, $o_line_name, $o_said_rod, $o_crossroad_type, $o_extrusion_protect, $o_traction,
                        $dj_serial_number, $dj_factory_name, $dj_factory_device_code, $dj_model_name, $dj_made_at, $dj_life_year,
                        $ywjcq_serial_number_l, $ywjcq_factory_name_l, $ywjcq_factory_device_code_l, $ywjcq_model_name_l, $ywjcq_made_at_l, $ywjcq_life_year_l,
                        $ywjcq_serial_number_r, $ywjcq_factory_name_r, $ywjcq_factory_device_code_r, $ywjcq_model_name_r, $ywjcq_made_at_r, $ywjcq_life_year_r,
                        $jsq_serial_number, $jsq_factory_name, $jsq_factory_device_code, $jsq_model_name, $jsq_made_at, $jsq_life_year,
                        $yb_serial_number, $yb_factory_name, $yb_factory_device_code, $yb_model_name, $yb_made_at, $yb_life_year,
                        $zdkbq_serial_number, $zdkbq_factory_name, $zdkbq_factory_device_code, $zdkbq_model_name, $zdkbq_made_at, $zdkbq_life_year,
                        $mcljq_serial_number, $mcljq_factory_name, $mcljq_factory_device_code, $mcljq_model_name, $mcljq_made_at, $mcljq_life_year
                        ) = $row_datum;

                    // 以下是严重错误，不允许通过
                    // 验证所编号和厂编号
                    if (!$om_serial_number && !$o_factory_device_code) throw new ExcelInException('所编号或厂编号必填一个');
                    // 验证种类
                    if (!$om_category_name) throw new ExcelInException("第{$current_row}行，种类不能为空");
                    $category = Category::with([])->where('name', $om_category_name)->first();
                    if (!$category) throw new ExcelInException("第{$current_row}行，种类：{$om_category_name}不存在");
                    // 验证类型
                    if (!$om_entire_model_name) throw new ExcelInException("第{$current_row}行，类型不能为空");
                    $em = EntireModel::with([])->where('is_sub_model', false)->where('category_unique_code', $category->unique_code)->where('name', $om_entire_model_name)->first();
                    if (!$em) throw new ExcelInException("第{$current_row}行，类型：{$category->name} > {$om_entire_model_name}不存在");
                    // 验证型号
                    if (!$om_sub_model_name) throw new ExcelInException("第{$current_row}行，型号不能为空");
                    $sm = EntireModel::with([])->where('is_sub_model', true)->where('parent_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                    $pm = PartModel::with([])->where('entire_model_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                    if (!$sm && !$pm) throw new ExcelInException("第{$current_row}行，型号：{$category->name} > {$em->name} > {$om_sub_model_name}不存在");
                    if (!$sm && $pm) $sm = $pm;
                    // 验证状态
                    if (!$om_status_mame) throw new ExcelInException("第{$current_row}行，状态不能为空");
                    if (!array_key_exists($om_status_mame, $statuses)) throw new ExcelInException("第{$current_row}行，设备状态：{$om_status_mame}错误，只能填写：" . implode('、', array_flip($statuses)));
                    $status = $statuses[$om_status_mame];
                    // 验证所编号是否存在
                    $ei = EntireInstance::with([])->where('serial_number', $om_serial_number)->where('model_unique_code', $sm->unique_code)->get();
                    if ($ei->isEmpty()) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}({$om_sub_model_name})不存在");
                    if ($ei->count() > 1) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}({$om_sub_model_name})存在多台设备");
                    $v250_task_entire_instance = V250TaskEntireInstance::with(['Fixer'])->where('v250_task_order_sn', $v250_task_order->serial_number)->where('entire_instance_identity_code', $ei->first()->identity_code)->first();
                    // 验证厂家
                    if ($om_factory_name)
                        if (!Factory::with([])->where('name', $om_factory_name)->first())
                            throw new ExcelInException("第{$current_row}行，没有找到厂家：{$om_factory_name}");

                    // 以下是非严重错误，可以通过
                    // 验证厂编号
                    if (!$o_factory_device_code) {
                        $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂编号', $o_factory_device_code, '没有填写', 'red');
                    }
                    // 验证厂家
                    // if ($om_factory_name) {
                    //     $factory = Factory::with([])->where('name', $om_factory_name)->first();
                    //     if (!$factory) $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, "厂家名称填写不规范，现有厂家名称中没有找到：{$om_factory_name}", 'red');
                    // } else {
                    //     $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, '没有填写厂家', 'red');
                    //     $om_factory_name = '';
                    // }

                    // 验证生产日期
                    if ($o_made_at) {
                        try {
                            $o_made_at = date('Y-m-d',strtotime(ExcelWriteHelper::getExcelDate($o_made_at)));
                        } catch (Exception $e) {
                            $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, $e->getMessage());
                            $o_made_at = null;
                        }
                    } else {
                        $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, '没有填写生产日期');
                        $o_made_at = null;
                    }

                    // 验证车站
                    if ($o_station_name) {
                        $station = Maintain::with(['Parent'])->where('name', $o_station_name)->first();
                        // todo: 刷线别、现场车间、车站数据之后，需要根据新的数据库来修改
                        // $station = Station::with([])->where('name', $oStationName)->where('name', $oStationName)->first();
                        if (!$station) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "没有找到车站：{$o_station_name}");
                        if ($station) {
                            if (!$station->Parent) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "车站：{$o_station_name}，没有找到上一级车间");
                        }
                    } else {
                        $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, '没有填写车站');
                        $station = null;
                        $o_station_name = '';
                    }

                    // 验证检测/检修人
                    $fixer = null;
                    if ($o_fixer_name) {
                        $fixer = Account::with([])->where('nickname', $o_fixer_name)->first();
                        if (!$fixer) $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修人', $o_fixer_name, "没有找到检修人：{$o_fixer_name}");
                    }

                    // 验证检修时间
                    if ($o_fixed_at) {
                        try {
                            $o_fixed_at = ExcelWriteHelper::getExcelDate($o_fixed_at);
                        } catch (Exception $e) {
                            $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修时间', $o_fixed_at, $e->getMessage());
                            $o_fixed_at = null;
                        }
                    }

                    // 验证验收人
                    $checker = null;
                    if ($o_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        $checker = Account::with([])->where('nickname', $o_checker_name)->first();
                        if (!$checker) $excel_error['M'] = ExcelWriteHelper::makeErrorResult($current_row, '验收人', $o_fixer_name, "没有找到验收人：{$o_checker_name}");
                    }

                    // 验证检修时间
                    if ($o_checked_at) {
                        try {
                            $o_checked_at = ExcelWriteHelper::getExcelDate($o_checked_at);
                        } catch (Exception $e) {
                            $excel_error['N'] = ExcelWriteHelper::makeErrorResult($current_row, '验收时间', $o_checked_at, $e->getMessage());
                            $o_checked_at = null;
                        }
                    }

                    // 验证抽验人
                    $spot_checker = null;
                    if ($o_spot_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        if (is_null($checker)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，没有验收人");
                        $spot_checker = Account::with([])->where('nickname', $o_spot_checker_name)->first();
                        if (!$spot_checker) $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验人', $o_spot_checker_name, "没有找到抽验人：{$o_spot_checker_name}");
                    }

                    // 验证抽验时间
                    if ($o_spot_checked_at) {
                        try {
                            $o_spot_checked_at = ExcelWriteHelper::getExcelDate($o_spot_checked_at);
                        } catch (Exception $e) {
                            $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验时间', $o_spot_checked_at, $e->getMessage());
                            $o_spot_checked_at = null;
                        }
                    }

                    // 验证寿命
                    if (is_numeric($o_life_year)) {
                        if ($o_life_year < 0) {
                            $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                            $scraping_at = null;
                        } else {
                            $scraping_at = Carbon::parse($o_made_at)->addYears($o_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                        $scraping_at = null;
                    }

                    // 周期修年限
                    if (is_numeric($o_cycle_fix_year)) {
                        if ($o_cycle_fix_year < 0) {
                            $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                            $o_cycle_fix_year = 0;
                        }
                    } else {
                        $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                        $o_cycle_fix_year = 0;
                    }

                    // 验证开向
                    if (!$o_open_direction) {
                        $excel_error['S'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向未填写');
                        $o_open_direction = '';
                    } else {
                        if (!in_array($o_open_direction, ['左', '右'])) $excel_error['S'] = ExcelWriteHelper::makeErrorResult($current_row, '开向(左、右)', $o_open_direction, '开向只能填写：左、右');
                    }

                    // 验证线制
                    if (!$o_line_name) {
                        $excel_error['T'] = ExcelWriteHelper::makeErrorResult($current_row, '线制', $o_line_name, '线制未填写');
                        $o_line_name = '';
                    }

                    // 验证表示杆特征
                    if (!$o_said_rod) {
                        $excel_error['U'] = ExcelWriteHelper::makeErrorResult($current_row, '表示杆特征', $o_said_rod, '表示杆特征未填写');
                        $o_said_rod = '';
                    }

                    // 验证道岔类型
                    if (!$o_crossroad_type) {
                        $excel_error['V'] = ExcelWriteHelper::makeErrorResult($current_row, '道岔类型', $o_crossroad_type, '道岔类型未填写');
                        $o_crossroad_type = '';
                    }

                    // 验证防挤压保护罩
                    if ($o_extrusion_protect) {
                        if (!in_array($o_extrusion_protect, ['是', '否'])) $excel_error['X'] = ExcelWriteHelper::makeErrorResult($current_row, '防挤压保护罩(是、否)', $o_extrusion_protect, '只能填写：是、否');
                        if ($o_extrusion_protect == '是') {
                            $o_extrusion_protect = true;
                        } else {
                            $o_extrusion_protect = false;
                        }
                    } else {
                        $o_extrusion_protect = false;
                    }

                    // 验证牵引
                    if (!$o_traction) {
                        $excel_error['W'] = ExcelWriteHelper::makeErrorResult($current_row, '牵引', $o_crossroad_type, '牵引未填写');
                        $o_traction = '';
                    }

                    // 电机：所编号Y、厂家Z、厂编号AA、型号AB、生产日期AC、寿命(年)AD
                    $check_dj = function () use ($current_row, &$dj_serial_number, &$dj_factory_name, &$dj_factory_device_code, &$dj_model_name, &$dj_made_at, &$dj_life_year, &$excel_error, $pm, $v250_task_order) {
                        $is_new = false;
                        // 验证厂家 Z
                        if ($dj_factory_name) {
                            $dj_factory = Factory::with([])->where('name', $dj_factory_name)->first();
                            if (!$dj_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(电机)：{$dj_factory_name}");
                            // if (!$dj_factory) {
                            //     $excel_error['AA'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $dj_factory_name, "没有找到厂家：{$dj_factory_name}");
                            // }
                        } else {
                            $excel_error['Z'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $dj_factory_name, '没有填写电机厂家');
                            $dj_factory_name = '';
                        }
                        // 验证厂编号 AA
                        if (!$dj_factory_device_code) {
                            $excel_error['AA'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂编号', $dj_factory_device_code, '没有填写电机编号');
                            $dj_factory_device_code = '';
                        }
                        // 验证型号 AB
                        $dj_model = null;
                        if ($dj_serial_number && $dj_model_name) {
                            $dj_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $dj_model_name)->first();
                            if (!$dj_model) throw new ExcelInException("第{$current_row}行，没有找到电机型号：{$dj_model_name}");
                        }
                        // 验证所编号 Y
                        if ($dj_serial_number && $dj_model_name) {
                            $pi = PartInstance::with(['DeviceModel'])->where('serial_number', $dj_serial_number)->where('device_model_unique_code', $dj_model->unique_code)->first();
                            if (!$pi) {
                                $is_new = true;
                            } else {
                                if ($dj_model->unique_code != $pi->device_model_unique_code) throw new ExcelInException("第{$current_row}行，电机：{$dj_serial_number}不能修改型号");
                            }
                        }
                        // 验证生产日期 AC
                        if ($dj_made_at) {
                            try {
                                $dj_made_at = ExcelWriteHelper::getExcelDate($dj_made_at);
                            } catch (Exception $e) {
                                $excel_error['AC'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $dj_made_at, $e->getMessage());
                                $dj_made_at = null;
                            }
                        } else {
                            $excel_error['AC'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $dj_made_at, '没有填写电机生产日期');
                            $dj_made_at = null;
                        }
                        // 验证寿命 AD
                        $dj_scraping_at = null;
                        if (is_numeric($dj_life_year)) {
                            if ($dj_life_year < 0) {
                                $excel_error['AD'] = ExcelWriteHelper::makeErrorResult($current_row, '电机寿命(年)', $dj_life_year, '电机寿命必须填写正整数');
                                $dj_scraping_at = null;
                            } else {
                                $dj_scraping_at = Carbon::parse($dj_made_at)->addYears($dj_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AD'] = ExcelWriteHelper::makeErrorResult($current_row, '电机寿命(年)', $dj_life_year, '电机寿命必须填写正整数');
                            $dj_scraping_at = null;
                        }

                        return ($dj_serial_number && $dj_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'status' => 'BUY_IN',
                            'factory_name' => $dj_factory_name,
                            'factory_device_code' => $dj_factory_device_code,
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $dj_made_at,
                            'scraping_at' => $dj_scraping_at,
                            'device_model_unique_code' => $dj_model->unique_code,
                            'serial_number' => $dj_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code,
                            'is_new' => $is_new,
                        ] : null;
                    };

                    // 移位接触器(左)：所编号AE、厂家AF、厂编号AG、型号AH、生产日期AI、寿命(年)AJ
                    $check_ywjcq_l = function () use ($current_row, &$ywjcq_serial_number_l, &$ywjcq_factory_name_l, &$ywjcq_factory_device_code_l, &$ywjcq_model_name_l, &$ywjcq_made_at_l, &$ywjcq_life_year_l, &$excel_error, $pm, $v250_task_order) {
                        $is_new = false;
                        // 验证厂家 AF
                        if ($ywjcq_factory_name_l) {
                            $ywjcq_factory = Factory::with([])->where('name', $ywjcq_factory_name_l)->first();
                            if (!$ywjcq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(移位接触器(左))：{$ywjcq_factory_name_l}");
                            // if (!$ywjcq_factory) {
                            //     $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name, "没有找到厂家：{$ywjcq_factory_name}");
                            // }
                        } else {
                            $excel_error['AF'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)厂家', $ywjcq_factory_name_l, '没有填写移位接触器(左)厂家');
                            $ywjcq_factory_name_l = '';
                        }
                        // 验证厂编号 AG
                        if (!$ywjcq_factory_device_code_l) {
                            $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)厂编号', $ywjcq_factory_device_code_l, '没有填写移位接触器(左)厂编号');
                            $ywjcq_factory_device_code_l = '';
                        }
                        // 验证型号 AH
                        $ywjcq_model = null;
                        if ($ywjcq_serial_number_l && $ywjcq_model_name_l) {
                            $ywjcq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $ywjcq_model_name_l)->first();
                            if (!$ywjcq_model) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(左)型号：{$ywjcq_model_name_l}");
                        }
                        // 验证所编号 AE
                        if ($ywjcq_serial_number_l && $ywjcq_model_name_l) {
                            $pi = PartInstance::with([])->where('serial_number', $ywjcq_serial_number_l)->where('device_model_unique_code', $ywjcq_model->unique_code)->first();
                            if (!$pi) {
                                $is_new = true;
                            } else {
                                if ($ywjcq_model->unique_code != $pi->device_model_unique_code) throw new ExcelInException("第{$current_row}行，移位接触器(左)：{$ywjcq_serial_number_l}不能修改型号");
                            }
                        }
                        // 验证生产日期 AI
                        if ($ywjcq_made_at_l) {
                            try {
                                $ywjcq_made_at_l = ExcelWriteHelper::getExcelDate($ywjcq_made_at_l);
                            } catch (Exception $e) {
                                $excel_error['AI'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器生产日期', $ywjcq_made_at_l, $e->getMessage());
                                $ywjcq_made_at_l = null;
                            }
                        } else {
                            $excel_error['AI'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)生产日期', $ywjcq_made_at_l, '没有填写移位接触器(左)生产日期');
                            $ywjcq_made_at_l = null;
                        }
                        $ywjcq_scraping_at = null;
                        // 验证寿命 AJ
                        if (is_numeric($ywjcq_life_year_l)) {
                            if ($ywjcq_life_year_l < 0) {
                                $excel_error['AJ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)寿命(年)', $ywjcq_life_year_l, '移位接触器(左)寿命必须填写正整数');
                                $ywjcq_scraping_at = null;
                            } else {
                                $ywjcq_scraping_at = Carbon::parse($ywjcq_made_at_l)->addYears($ywjcq_life_year_l)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AJ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)寿命(年)', $ywjcq_life_year_l, '移位接触器(左)寿命必须填写正整数');
                            $ywjcq_scraping_at = null;
                        }

                        return ($ywjcq_serial_number_l && $ywjcq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'status' => 'BUY_IN',
                            'factory_name' => $ywjcq_factory_name_l,
                            'factory_device_code' => $ywjcq_factory_device_code_l,
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $ywjcq_made_at_l,
                            'scraping_at' => $ywjcq_scraping_at,
                            'device_model_unique_code' => $ywjcq_model->unique_code,
                            'serial_number' => $ywjcq_serial_number_l,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code,
                            'is_new' => $is_new,
                        ] : null;
                    };

                    // 移位接触器(右)：所编号AK、厂家AL、厂编号AM、型号AN、生产日期AO、寿命(年)AP
                    $check_ywjcq_r = function () use ($current_row, &$ywjcq_serial_number_r, &$ywjcq_factory_name_r, &$ywjcq_factory_device_code_r, &$ywjcq_model_name_r, &$ywjcq_made_at_r, &$ywjcq_life_year_r, &$excel_error, $pm, $v250_task_order) {
                        $is_new = false;
                        // 验证厂家 AL
                        if ($ywjcq_factory_name_r) {
                            $ywjcq_factory = Factory::with([])->where('name', $ywjcq_factory_name_r)->first();
                            if (!$ywjcq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(移位接触器(右))：{$ywjcq_factory_name_r}");
                            // if (!$ywjcq_factory) {
                            //     $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name, "没有找到厂家：{$ywjcq_factory_name}");
                            // }
                        } else {
                            $excel_error['AL'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)厂家', $ywjcq_factory_name_r, '没有填写移位接触器(右)厂家');
                            $ywjcq_factory_name_r = '';
                        }
                        // 验证厂编号 AM
                        if (!$ywjcq_factory_device_code_r) {
                            $excel_error['AM'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)厂编号', $ywjcq_factory_device_code_r, '没有填写移位接触器(右)厂编号');
                            $ywjcq_factory_device_code_r = '';
                        }
                        // 验证型号 AN
                        $ywjcq_model = null;
                        if ($ywjcq_serial_number_r && $ywjcq_model_name_r) {
                            $ywjcq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $ywjcq_model_name_r)->first();
                            if (!$ywjcq_model) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(右)型号：{$ywjcq_model_name_r}");
                        }
                        // 验证所编号 AK
                        if ($ywjcq_serial_number_r && $ywjcq_model_name_r) {
                            $pi = PartInstance::with([])->where('serial_number', $ywjcq_serial_number_r)->where('device_model_unique_code', $ywjcq_model->unique_code)->first();
                            if (!$pi) {
                                $is_new = true;
                            } else {
                                if ($ywjcq_model->unique_code != $pi->device_model_unique_code) throw new ExcelInException("第{$current_row}行，移位接触器(右)：{$ywjcq_serial_number_r}不能修改型号");
                            }
                        }
                        // 验证生产日期 AO
                        if ($ywjcq_made_at_r) {
                            try {
                                $ywjcq_made_at_r = ExcelWriteHelper::getExcelDate($ywjcq_made_at_r);
                            } catch (Exception $e) {
                                $excel_error['AO'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器生产日期', $ywjcq_made_at_r, $e->getMessage());
                                $ywjcq_made_at_r = null;
                            }
                        } else {
                            $excel_error['AO'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)生产日期', $ywjcq_made_at_r, '没有填写移位接触器(右)生产日期');
                            $ywjcq_made_at_r = null;
                        }
                        $ywjcq_scraping_at = null;
                        // 验证寿命 AP
                        if (is_numeric($ywjcq_life_year_r)) {
                            if ($ywjcq_life_year_r < 0) {
                                $excel_error['AP'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)寿命(年)', $ywjcq_life_year_r, '移位接触器(右)寿命必须填写正整数');
                                $ywjcq_scraping_at = null;
                            } else {
                                $ywjcq_scraping_at = Carbon::parse($ywjcq_made_at_r)->addYears($ywjcq_life_year_r)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AP'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)寿命(年)', $ywjcq_life_year_r, '移位接触器(右)寿命必须填写正整数');
                            $ywjcq_scraping_at = null;
                        }

                        return ($ywjcq_serial_number_r && $ywjcq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'status' => 'BUY_IN',
                            'factory_name' => $ywjcq_factory_name_r,
                            'factory_device_code' => $ywjcq_factory_device_code_r,
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $ywjcq_made_at_r,
                            'scraping_at' => $ywjcq_scraping_at,
                            'device_model_unique_code' => $ywjcq_model->unique_code,
                            'serial_number' => $ywjcq_serial_number_r,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code,
                            'is_new' => $is_new,
                        ] : null;
                    };

                    // 减速器：所编号AQ、厂家AR、厂编号AS、型号AT、生产日期AU、寿命(年)AV
                    $check_jsq = function () use ($current_row, &$jsq_serial_number, &$jsq_factory_name, &$jsq_factory_device_code, &$jsq_model_name, &$jsq_made_at, &$jsq_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 AR
                        if ($jsq_factory_name) {
                            $jsq_factory = Factory::with([])->where('name', $jsq_factory_name)->first();
                            if (!$jsq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(减速器)：{$jsq_factory_name}");
                            // if (!$jsq_factory) {
                            //     $excel_error['AM'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂家', $jsq_factory_name, "没有找到厂家：{$jsq_factory_name}");
                            // }
                        } else {
                            $excel_error['AR'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂家', $jsq_factory_name, '没有填写减速器厂家');
                            $jsq_factory_name = '';
                        }
                        // 验证厂编号 AS
                        if (!$jsq_factory_device_code) {
                            $excel_error['AS'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂编号', $jsq_factory_device_code, '没有填写减速器编号');
                            $jsq_factory_device_code = '';
                        }
                        // 验证型号 AT
                        $jsq_model = null;
                        if ($jsq_serial_number && $jsq_model_name) {
                            $jsq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $jsq_model_name)->first();
                            if (!$jsq_model) throw new ExcelInException("第{$current_row}行，没有找到减速器型号：{$jsq_model_name}");
                        }
                        // 验证所编号 AQ
                        if ($jsq_serial_number && $jsq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $jsq_serial_number)->where('device_model_unique_code', $jsq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，减速器：{$jsq_serial_number}所编号重复");
                        }
                        // 验证生产日期 AU
                        if ($jsq_made_at) {
                            try {
                                $jsq_made_at = ExcelWriteHelper::getExcelDate($jsq_made_at);
                            } catch (Exception $e) {
                                $excel_error['AU'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器生产日期', $jsq_made_at, $e->getMessage());
                                $jsq_made_at = null;
                            }
                        } else {
                            $excel_error['AU'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器生产日期', $jsq_made_at, '没有填写减速器生产日期');
                            $jsq_made_at = null;
                        }
                        $jsq_scraping_at = null;
                        // 验证寿命 AV
                        if (is_numeric($jsq_life_year)) {
                            if ($jsq_life_year < 0) {
                                $excel_error['AV'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器寿命(年)', $jsq_life_year, '减速器寿命必须填写正整数');
                                $jsq_scraping_at = null;
                            } else {
                                $jsq_scraping_at = Carbon::parse($jsq_made_at)->addYears($jsq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['AV'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器寿命(年)', $jsq_life_year, '减速器寿命必须填写正整数');
                            $jsq_scraping_at = null;
                        }

                        return ($jsq_serial_number && $jsq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $jsq_factory_name,
                            'factory_device_code' => $jsq_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $jsq_made_at,
                            'scraping_at' => $jsq_scraping_at,
                            'device_model_unique_code' => $jsq_model->unique_code,
                            'serial_number' => $jsq_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 油泵：所编号AW、厂家AX、厂编号AY、型号AZ、生产日期BA、寿命(年)BB
                    $check_yb = function () use ($current_row, &$yb_serial_number, &$yb_factory_name, &$yb_factory_device_code, &$yb_model_name, &$yb_made_at, &$yb_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 AX
                        if ($yb_factory_name) {
                            $yb_factory = Factory::with([])->where('name', $yb_factory_name)->first();
                            if (!$yb_factory) throw new ExcelInException("第{$current_row}行，厂家没有找到(油泵)：{$yb_factory_name}");
                            // if (!$yb_factory) {
                            //     $excel_error['AS'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂家', $yb_factory_name, "没有找到厂家：{$yb_factory_name}");
                            // }
                        } else {
                            $excel_error['AX'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂家', $yb_factory_name, '没有填写油泵厂家');
                            $yb_factory_name = '';
                        }
                        // 验证厂编号 AY
                        if (!$yb_factory_device_code) {
                            $excel_error['AY'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂编号', $yb_factory_device_code, '没有填写油泵编号');
                            $yb_factory_device_code = '';
                        }
                        // 验证型号 AZ
                        $yb_model = null;
                        if ($yb_serial_number && $yb_model_name) {
                            $yb_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $yb_model_name)->first();
                            if (!$yb_model) throw new ExcelInException("第{$current_row}行，没有找到油泵型号：{$yb_model_name}");
                        }
                        // 验证所编号 AW
                        if ($yb_serial_number && $yb_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $yb_serial_number)->where('device_model_unique_code', $yb_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，油泵：{$yb_serial_number}所编号重复");
                        }
                        // 验证生产日期 BA
                        if ($yb_made_at) {
                            try {
                                $yb_made_at = ExcelWriteHelper::getExcelDate($yb_made_at);
                            } catch (Exception $e) {
                                $excel_error['BA'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵生产日期', $yb_made_at, $e->getMessage());
                                $yb_made_at = null;
                            }
                        } else {
                            $excel_error['BA'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵生产日期', $yb_made_at, '没有填写油泵生产日期');
                            $yb_made_at = null;
                        }
                        $yb_scraping_at = null;
                        // 验证寿命 BB
                        if (is_numeric($yb_life_year)) {
                            if ($yb_life_year < 0) {
                                $excel_error['BB'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵寿命(年)', $yb_life_year, '油泵寿命必须填写正整数');
                                $yb_scraping_at = null;
                            } else {
                                $yb_scraping_at = Carbon::parse($yb_made_at)->addYears($yb_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BB'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵寿命(年)', $yb_life_year, '油泵寿命必须填写正整数');
                            $yb_scraping_at = null;
                        }

                        return ($yb_serial_number && $yb_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $yb_factory_name,
                            'factory_device_code' => $yb_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $yb_made_at,
                            'scraping_at' => $yb_scraping_at,
                            'device_model_unique_code' => $yb_model->unique_code,
                            'serial_number' => $yb_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 自动开闭器：所编号BC、厂家BD、厂编号BE、型号BF、生产日期BG、寿命(年)BH
                    $check_zdkbq = function () use ($current_row, &$zdkbq_serial_number, &$zdkbq_factory_name, &$zdkbq_factory_device_code, &$zdkbq_model_name, &$zdkbq_made_at, &$zdkbq_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 BD
                        if ($zdkbq_factory_name) {
                            $zdkbq_factory = Factory::with([])->where('name', $zdkbq_factory_name)->first();
                            if (!$zdkbq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(自动开闭器)：{$zdkbq_factory_name}");
                            // if (!$zdkbq_factory) {
                            //     $excel_error['AY'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂家', $zdkbq_factory_name, "没有找到厂家：{$zdkbq_factory_name}");
                            // }
                        } else {
                            $excel_error['BD'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂家', $zdkbq_factory_name, '没有填写自动开闭器厂家');
                            $zdkbq_factory_name = '';
                        }
                        // 验证厂编号 BE
                        if (!$zdkbq_factory_device_code) {
                            $excel_error['BE'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂编号', $zdkbq_factory_device_code, '没有填写自动开闭器编号');
                            $zdkbq_factory_device_code = '';
                        }
                        // 验证型号 BF
                        $zdkbq_model = null;
                        if ($zdkbq_serial_number && $zdkbq_model_name) {
                            $zdkbq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $zdkbq_model_name)->first();
                            if (!$zdkbq_model) throw new ExcelInException("第{$current_row}行，没有找到自动开闭器型号：{$zdkbq_model_name}");
                        }
                        // 验证所编号 BC
                        if ($zdkbq_serial_number && $zdkbq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $zdkbq_serial_number)->where('device_model_unique_code', $zdkbq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，自动开闭器：{$zdkbq_serial_number}所编号重复");
                        }
                        // 验证生产日期 BG
                        if ($zdkbq_made_at) {
                            try {
                                $zdkbq_made_at = ExcelWriteHelper::getExcelDate($zdkbq_made_at);
                            } catch (Exception $e) {
                                $excel_error['BG'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器生产日期', $zdkbq_made_at, $e->getMessage());
                                $zdkbq_made_at = null;
                            }
                        } else {
                            $excel_error['BG'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器生产日期', $zdkbq_made_at, '没有填写自动开闭器生产日期');
                            $zdkbq_made_at = null;
                        }
                        $zdkbq_scraping_at = null;
                        // 验证寿命 BH
                        if (is_numeric($zdkbq_life_year)) {
                            if ($zdkbq_life_year < 0) {
                                $excel_error['BH'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器寿命(年)', $zdkbq_life_year, '自动开闭器寿命必须填写正整数');
                                $zdkbq_scraping_at = null;
                            } else {
                                $zdkbq_scraping_at = Carbon::parse($zdkbq_made_at)->addYears($zdkbq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BH'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器寿命(年)', $zdkbq_life_year, '自动开闭器寿命必须填写正整数');
                            $zdkbq_scraping_at = null;
                        }

                        return ($zdkbq_serial_number && $zdkbq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $zdkbq_factory_name,
                            'factory_device_code' => $zdkbq_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $zdkbq_made_at,
                            'scraping_at' => $zdkbq_scraping_at,
                            'device_model_unique_code' => $zdkbq_model->unique_code,
                            'serial_number' => $zdkbq_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 摩擦连接器：所编号BI、厂家BJ、厂编号BK、型号BL、生产日期BM、寿命(年)BN
                    $check_mcljq = function () use ($current_row, &$mcljq_serial_number, &$mcljq_factory_name, &$mcljq_factory_device_code, &$mcljq_model_name, &$mcljq_made_at, &$mcljq_life_year, &$excel_error, $pm, $v250_task_order) {
                        // 验证厂家 BJ
                        if ($mcljq_factory_name) {
                            $mcljq_factory = Factory::with([])->where('name', $mcljq_factory_name)->first();
                            if (!$mcljq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(摩擦连接器)：{$mcljq_factory_name}");
                            // if (!$mcljq_factory) {
                            //     $excel_error['BE'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $mcljq_factory_name, "没有找到厂家：{$mcljq_factory_name}");
                            // }
                        } else {
                            $excel_error['BJ'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器厂家', $mcljq_factory_name, '没有填写摩擦连接器厂家');
                            $mcljq_factory_name = '';
                        }
                        // 验证厂编号 BK
                        if (!$mcljq_factory_device_code) {
                            $excel_error['BK'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器厂编号', $mcljq_factory_device_code, '没有填写摩擦连接器编号');
                            $mcljq_factory_device_code = '';
                        }
                        // 验证型号 BL
                        $mcljq_model = null;
                        if ($mcljq_serial_number && $mcljq_model_name) {
                            $mcljq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $mcljq_model_name)->first();
                            if (!$mcljq_model) throw new ExcelInException("第{$current_row}行，没有找到摩擦连接器型号：{$mcljq_model_name}");
                        }
                        // 验证所编号 BI
                        if ($mcljq_serial_number && $mcljq_model_name) {
                            $pi = PartInstance::with([])->where('serial_number', $mcljq_serial_number)->where('device_model_unique_code', $mcljq_model->unique_code)->first();
                            if ($pi) throw new ExcelInException("第{$current_row}行，摩擦连接器：{$mcljq_serial_number}所编号重复");
                        }
                        // 验证生产日期 BM
                        if ($mcljq_made_at) {
                            try {
                                $mcljq_made_at = ExcelWriteHelper::getExcelDate($mcljq_made_at);
                            } catch (Exception $e) {
                                $excel_error['BM'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器生产日期', $mcljq_made_at, $e->getMessage());
                                $mcljq_made_at = null;
                            }
                        } else {
                            $excel_error['BM'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $mcljq_made_at, '没有填写电机生产日期');
                            $mcljq_made_at = null;
                        }
                        // 验证寿命 BN
                        $mcljq_scraping_at = null;
                        if (is_numeric($mcljq_life_year)) {
                            if ($mcljq_life_year < 0) {
                                $excel_error['BN'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器寿命(年)', $mcljq_life_year, '摩擦连接器寿命必须填写正整数');
                                $mcljq_scraping_at = null;
                            } else {
                                $mcljq_scraping_at = Carbon::parse($mcljq_made_at)->addYears($mcljq_life_year)->format('Y-m-d');
                            }
                        } else {
                            $excel_error['BN'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器寿命(年)', $mcljq_life_year, '摩擦连接器寿命必须填写正整数');
                            $mcljq_scraping_at = null;
                        }

                        return ($mcljq_serial_number && $mcljq_model) ? [
                            'part_model_unique_code' => $pm->unique_code,
                            'part_model_name' => $pm->name,
                            'entire_instance_identity_code' => '',
                            'status' => 'BUY_IN',
                            'factory_name' => $mcljq_factory_name,
                            'factory_device_code' => $mcljq_factory_device_code,
                            'identity_code' => '',
                            'entire_instance_serial_number' => '',
                            'category_unique_code' => $pm->category_unique_code,
                            'entire_model_unique_code' => $pm->entire_model_unique_code,
                            'part_category_id' => $pm->part_category_id,
                            'made_at' => $mcljq_made_at,
                            'scraping_at' => $mcljq_scraping_at,
                            'device_model_unique_code' => $mcljq_model->unique_code,
                            'serial_number' => $mcljq_serial_number,
                            'work_area_unique_code' => $v250_task_order->work_area_unique_code
                        ] : null;
                    };

                    // 写入待插入数据
                    $edit_entire_instances[] = [
                        'entire_model_unique_code' => $sm->entire_model_unique_code,
                        'serial_number' => $om_serial_number,
                        'status' => $status,
                        'maintain_station_name' => $o_station_name,
                        'crossroad_number' => $o_maintain_location_code ?? '',
                        'factory_name' => $om_factory_name,
                        'factory_device_code' => $o_factory_device_code,
                        'category_unique_code' => $category->unique_code,
                        'category_name' => $category->name,
                        'fix_cycle_unit' => 'YEAR',
                        'fix_cycle_value' => $o_cycle_fix_year,
                        'made_at' => $o_made_at,
                        'scarping_at' => $scraping_at,
                        'model_unique_code' => $sm->unique_code,
                        'model_name' => $sm->name,
                        'maintain_workshop_name' => $station ? ($station->Parent ? $station->Parent->name : '') : '',
                        'v250_task_order_sn' => $sn,
                        'fixer_id' => $fixer ? $fixer->id : null,
                        'fixed_at' => $fixer ? $o_fixed_at : null,
                        'checker_id' => $checker ? $checker->id : null,
                        'checked_at' => $checker ? $o_checked_at : null,
                        'spot_checker_id' => $spot_checker ? $spot_checker->id : null,
                        'spot_checked_at' => $spot_checker ? $o_spot_checked_at : null,
                        'open_direction' => $o_open_direction,
                        'line_name' => $o_line_name,
                        'said_rod' => $o_said_rod,
                        'crossroad_type' => $o_crossroad_type,
                        'extrusion_protect' => $o_extrusion_protect,
                        'traction' => $o_traction,
                        'part_instances' => [
                            'dj' => $check_dj(),  // 电机
                            'ywjcq_l' => $check_ywjcq_l(),  // 移位接触器（左）
                            'ywjcq_r' => $check_ywjcq_r(),  // 移位接触器（右）
                            'jsq' => $check_jsq(),  // 减速器
                            'yb' => $check_yb(),  // 油泵
                            'zdkbq' => $check_zdkbq(),  // 自动开闭器
                            'mcljq' => $check_mcljq(),  // 摩擦连接器
                        ],
                    ];
                    // 错误数据统计

                    if (!empty($excel_error)) $excel_errors[] = $excel_error;

                    $current_row++;
                }

                // 按照型号进行分组
                $edit_entire_instances = collect($edit_entire_instances)->groupBy('entire_model_unique_code')->toArray();
                // 获取设备对应型号总数
                $entire_instance_counts = EntireInstanceCount::with([])->pluck('count', 'entire_model_unique_code');

                // 部件补充赋码
                foreach ($edit_entire_instances as $e => &$edit_entire_instance) {
                    foreach ($edit_entire_instance as $k => $ei) {
                        // 部件赋码
                        foreach ($ei['part_instances'] as $pk => $part_instance) {
                            if ($part_instance) {
                                $part_instances = PartInstance::with([])->where('serial_number', $part_instance['serial_number'])->where('device_model_unique_code', $part_instance['device_model_unique_code'])->get();
                                if ($part_instances->count() > 1) throw new ExcelInException("第{$current_row}行，部件：{$part_instance['serial_number']}({$part_instance['device_model_unique_code']})重复");
                                if ($part_instances->isEmpty()) {
                                    // 没找到部件，进行新赋码
                                    $pic = $entire_instance_counts->get($part_instance['device_model_unique_code'], 0);
                                    $edit_entire_instance[$k]['part_instances'][$pk]['entire_instance_identity_code'] = $edit_entire_instance[$k]['identity_code'];
                                    $edit_entire_instance[$k]['part_instances'][$pk]['identity_code'] = $part_instance['device_model_unique_code'] . env('ORGANIZATION_CODE') . str_pad(++$pic, 8, '0', 0);
                                    $edit_entire_instance[$k]['part_instances'][$pk]['entire_instance_serial_number'] = $edit_entire_instance[$k]['serial_number'];
                                    $edit_entire_instance[$k]['part_instances'][$pk]['is_new'] = true;
                                    $entire_instance_counts[$part_instance['device_model_unique_code']] = $pic;
                                } else {
                                    // 找到部件，进行数据修改
                                    $edit_entire_instance[$k]['part_instances'][$pk]['is_new'] = false;
                                }
                            }
                        }
                    }
                }

                // 写入数据库
                DB::begintransaction();
                // 修改设备
                $edited_count = 0;
                $edited_identity_codes = [];
                $edit_entire_instances = array_collapse($edit_entire_instances);
                foreach ($edit_entire_instances as $edit_entire_instance) {
                    $fix_workflow_datum = [
                        'fixer_id' => $edit_entire_instance['fixer_id'],
                        'fixed_at' => $edit_entire_instance['fixed_at'],
                        'checker_id' => $edit_entire_instance['checker_id'],
                        'checked_at' => $edit_entire_instance['checked_at'],
                        'spot_checker_id' => $edit_entire_instance['spot_checker_id'],
                        'spot_checked_at' => $edit_entire_instance['spot_checked_at'],
                    ];
                    unset(
                        $edit_entire_instance['fixer_id'],
                        $edit_entire_instance['fixed_at'],
                        $edit_entire_instance['checker_id'],
                        $edit_entire_instance['checked_at'],
                        $edit_entire_instance['spot_checker_id'],
                        $edit_entire_instance['spot_checked_at']
                    );
                    $nei = EntireInstance::with([])
                        ->where('serial_number', $edit_entire_instance['serial_number'])
                        ->where('model_unique_code', $edit_entire_instance['model_unique_code'])
                        ->first();
                    $nei->fill(array_except($edit_entire_instance, ['part_instances']))->saveOrFail();
                    $edited_identity_codes[] = $nei->identity_code;

                    $edited_count++;

                    // 如果有检修人和验收人生成检修单
                    if ($fix_workflow_datum['fixer_id'] || ($fix_workflow_datum['checker_id'] && $fix_workflow_datum['checked_at'])) {
                        FixWorkflowFacade::mockEmptyWithOutEditFixed(
                            $nei,
                            $fix_workflow_datum['fixed_at'],
                            $fix_workflow_datum['checked_at'],
                            $fix_workflow_datum['fixer_id'],
                            $fix_workflow_datum['checker_id'],
                            $fix_workflow_datum['spot_checked_at'],
                            $fix_workflow_datum['spot_checker_id']
                        );

                        // 如果设备没有分配，则分配
                        $overhual_entire_instance = OverhaulEntireInstance::with([])
                            ->where('entire_instance_identity_code', $nei->identity_code)
                            ->where('v250_task_order_sn', $v250_task_order->serial_number)
                            ->first();
                        $overhual_entire_instance_status = $fix_workflow_datum['checker_id'] ? (strtotime($v250_task_order->expiring_at) < strtotime($fix_workflow_datum['checked_at']) ? '2' : '1') : '0';
                        $overhual_entire_instance_datum = [
                            'v250_task_order_sn' => $v250_task_order->serial_number,
                            'entire_instance_identity_code' => $nei->identity_code,
                            'fixer_id' => $fix_workflow_datum['fixer_id'],
                            'fixed_at' => $fix_workflow_datum['fixed_at'],
                            'checker_id' => $fix_workflow_datum['checker_id'],
                            'checked_at' => $fix_workflow_datum['checked_at'],
                            'spot_checker_id' => $fix_workflow_datum['spot_checker_id'],
                            'spot_checked_at' => $fix_workflow_datum['spot_checked_at'],
                            'allocate_at' => date('Y-m-d H:i:s'),
                            'deadline' => $v250_task_order->expiring_at,
                            'status' => $overhual_entire_instance_status,
                        ];
                        if ($overhual_entire_instance && @$overhual_entire_instance->status ?: '' == '0') {
                            $overhual_entire_instance->fill($overhual_entire_instance_datum)->saveOrFail();
                        } else {
                            OverhaulEntireInstance::with([])->create($overhual_entire_instance_datum);
                        }
                    }

                    // 添加或修改部件
                    foreach ($edit_entire_instance['part_instances'] as $part_instance) {
                        if ($part_instance) {
                            if ($part_instance['is_new'] == true) {
                                // 如果是新部件，添加数据
                                PartInstance::with([])->create(array_except($part_instance, ['is_new']));
                            } else {
                                // 如果是老部件，修改数据
                                PartInstance::with([])->where('serial_number', $part_instance['serial_number'])->where('device_model_unique_code', $part_instance['device_model_unique_code'])->update(array_except($part_instance, ['is_new']));
                            }
                        }
                    }
                }

                // 更新该型号下的所有设备总数
                foreach ($entire_instance_counts as $e => $c) {
                    EntireInstanceCount::with([])->where('entire_model_unique_code', $e)->updateOrCreate([
                        'entire_model_unique_code' => $e,
                        'count' => $c,
                    ]);
                }
                DB::commit();

                $v250_task_entire_instances = V250TaskEntireInstance::with([
                    'EntireInstance',
                    'Fixer',
                    'Checker',
                    'SpotChecker'
                ])
                    ->whereIn('entire_instance_identity_code', $edited_identity_codes)
                    ->where('v250_task_order_sn', $v250_task_order->serial_number)
                    ->paginate(200);
                $has_edit_device_error = false;
                $edit_device_error_filename = "";
                $with_msg = "上传设备数据补充：{$edited_count}条。";


                if (!empty($excel_errors)) {
                    $root_dir = storage_path('v250TaskOrder/upload/' . strtoupper($request->get('type')) . '/errorExcels/createDevice');
                    if (!is_dir($root_dir)) FileSystem::init($root_dir)->makeDir();
                    $this->_makeErrorExcel($excel_errors, "{$root_dir}/{$sn}");

                    $v250_task_order->fill(['is_upload_edit_device_excel_error' => true])->saveOrFail();

                    $has_edit_device_error = true;
                    $edit_device_error_filename = "{$root_dir}/{$sn}.xls";
                    $with_msg = "上传设备数据补充：{$edited_count}条。" . '其中' . count($excel_errors) . '行有错误。';
                }

                return view('V250TaskOrder.uploadEditDeviceReport', [
                    'taskOrder' => $v250_task_order,
                    'entireInstances' => $v250_task_entire_instances,
                    'hasEditDeviceError' => $has_edit_device_error,
                    'editDeviceErrorFilename' => $edit_device_error_filename
                ])
                    ->with('warning', $with_msg);
            case 'reply':
            case 'synthesize':
                // 读取excel数据
                $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                    ->originRow(2)
                    ->withSheetIndex(0);
                $current_row = 2;
                // 所编号、种类*、类型*、型号*、状态*(新购、上道、备品、成品、待修)、厂编号、厂家、生产日期、
                // 车站、组合位置、检测/检修人、检测/检修时间(YYYY-MM-DD格式)、验收人、验收时间(YYYY-MM-DD格式)、抽验人、抽验时间(YYYY-MM-DD格式)
                // 寿命(年)、周期修年(非周期修写0)
                // 继电器和综合工区
                $edit_entire_instances = [];
                $excel_error = [];
                // 数据验证
                foreach ($excel['success'] as $row_datum) {
                    if (empty(array_filter($row_datum, function ($item) {
                        return !empty($item);
                    }))) continue;
                    list(
                        $om_serial_number, $om_category_name, $om_entire_model_name, $om_sub_model_name, $om_status_mame, $o_factory_device_code, $om_factory_name, $o_made_at,
                        $o_station_name, $o_maintain_location_code, $o_fixer_name, $o_fixed_at, $o_checker_name, $o_checked_at, $o_spot_checker_name, $o_spot_checked_at,
                        $o_life_year, $o_cycle_fix_year
                        ) = $row_datum;

                    // 以下是严重错误，不允许通过
                    // 验证所编号和厂编号
                    if (!$om_serial_number && !$o_factory_device_code) throw new ExcelInException('所编号或厂编号必填一个');
                    // 验证种类
                    if (!$om_category_name) throw new ExcelInException("第{$current_row}行，种类不能为空");
                    $category = Category::with([])->where('name', $om_category_name)->first();
                    if (!$category) throw new ExcelInException("第{$current_row}行，种类：{$om_category_name}不存在");
                    // 验证类型
                    if (!$om_entire_model_name) throw new ExcelInException("第{$current_row}行，类型不能为空");
                    $em = EntireModel::with([])->where('is_sub_model', false)->where('category_unique_code', $category->unique_code)->where('name', $om_entire_model_name)->first();
                    if (!$em) throw new ExcelInException("第{$current_row}行，类型：{$category->name} > {$om_entire_model_name}不存在");
                    // 验证厂家
                    if ($om_factory_name)
                        if (!Factory::with([])->where('name', $om_factory_name)->first())
                            throw new ExcelInException("第{$current_row}行，没有找到厂家：{$om_factory_name}");

                    if (substr($em->unique_code, 0, 1) == 'S') {
                        // 设备
                        $is_device = false;
                    } else {
                        // 器材
                        $is_device = true;
                        // 验证型号
                        if (!$om_sub_model_name) throw new ExcelInException("第{$current_row}行，型号不能为空");
                        $sm = EntireModel::with([])->where('is_sub_model', true)->where('parent_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                        $pm = PartModel::with([])->where('entire_model_unique_code', $em->unique_code)->where('name', $om_sub_model_name)->first();
                        if (!$sm && !$pm) throw new ExcelInException("第{$current_row}行，型号：{$category->name} > {$em->name} > {$om_sub_model_name}不存在");
                        if (!$sm && $pm) $sm = $pm;
                    }

                    // 验证状态
                    if (!$om_status_mame) throw new ExcelInException("第{$current_row}行，状态不能为空");
                    if (!array_key_exists($om_status_mame, $statuses)) throw new ExcelInException("第{$current_row}行，设备状态：{$om_status_mame}错误，只能填写：" . implode('、', array_flip($statuses)));
                    $status = $statuses[$om_status_mame];
                    // 验证所编号是否存在
                    $ei = EntireInstance::with([])->where('serial_number', $om_serial_number)->where('model_unique_code', $is_device ? $sm->unique_code : $em->unique_code)->get();
                    if ($ei->isEmpty()) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}({$om_sub_model_name})不存在");
                    if ($ei->count() > 1) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}({$om_sub_model_name})存在多台设备");
                    $v250_task_entire_instance = V250TaskEntireInstance::with(['Fixer'])->where('v250_task_order_sn', $v250_task_order->serial_number)->where('entire_instance_identity_code', $ei->first()->identity_code)->first();

                    // 以下是非严重错误，可以通过
                    // 验证厂编号
                    if (!$o_factory_device_code) {
                        $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂编号', $o_factory_device_code, '没有填写', 'red');
                    }
                    // 验证厂家
                    if ($om_factory_name) {
                        $factory = Factory::with([])->where('name', $om_factory_name)->first();
                        if (!$factory) $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, "厂家名称填写不规范，现有厂家名称中没有找到：{$om_factory_name}", 'red');
                    } else {
                        $excel_error['G'] = ExcelWriteHelper::makeErrorResult($current_row, '厂家', $om_factory_name, '没有填写厂家', 'red');
                        $om_factory_name = '';
                    }

                    // 验证生产日期
                    if ($o_made_at) {
                        try {
                            $o_made_at = date('Y-m-d',strtotime(ExcelWriteHelper::getExcelDate($o_made_at)));
                        } catch (Exception $e) {
                            $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, $e->getMessage());
                            $o_made_at = null;
                        }
                    } else {
                        $excel_error['H'] = ExcelWriteHelper::makeErrorResult($current_row, '生产日期', $o_made_at, '没有填写生产日期');
                        $o_made_at = null;
                    }

                    // 验证车站
                    if ($o_station_name) {
                        $station = Maintain::with(['Parent'])->where('name', $o_station_name)->first();
                        // todo: 刷线别、现场车间、车站数据之后，需要根据新的数据库来修改
                        // $station = Station::with([])->where('name', $oStationName)->where('name', $oStationName)->first();
                        if (!$station) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "没有找到车站：{$o_station_name}");
                        if ($station) {
                            if (!$station->Parent) $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, "车站：{$o_station_name}，没有找到上一级车间");
                        }
                    } else {
                        $excel_error['I'] = ExcelWriteHelper::makeErrorResult($current_row, '车站', $o_station_name, '没有填写车站');
                        $station = null;
                        $o_station_name = '';
                    }

                    // 验证检测/检修人
                    $fixer = null;
                    if ($o_fixer_name) {
                        $fixer = Account::with([])->where('nickname', $o_fixer_name)->first();
                        if (!$fixer) $excel_error['K'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修人', $o_fixer_name, "没有找到检修人：{$o_fixer_name}");
                    } else {
                        $fixer = $v250_task_entire_instance->Fixer ?? null;
                    }

                    // 验证检修时间
                    if ($o_fixed_at) {
                        try {
                            $o_fixed_at = ExcelWriteHelper::getExcelDate($o_fixed_at);
                        } catch (Exception $e) {
                            $excel_error['L'] = ExcelWriteHelper::makeErrorResult($current_row, '检测/检修时间', $o_fixed_at, $e->getMessage());
                            $o_fixed_at = null;
                        }
                    }

                    // 验证验收人
                    $checker = null;
                    if ($o_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        $checker = Account::with([])->where('nickname', $o_checker_name)->first();
                        if (!$checker) $excel_error['M'] = ExcelWriteHelper::makeErrorResult($current_row, '验收人', $o_fixer_name, "没有找到验收人：{$o_checker_name}");
                    } else {
                        $checker = $v250_task_entire_instance->Checker;
                    }

                    // 验证检修时间
                    if ($o_checked_at) {
                        try {
                            $o_checked_at = ExcelWriteHelper::getExcelDate($o_checked_at);
                        } catch (Exception $e) {
                            $excel_error['N'] = ExcelWriteHelper::makeErrorResult($current_row, '验收时间', $o_checked_at, $e->getMessage());
                            $o_checked_at = null;
                        }
                    }

                    // 验证抽验人
                    $spot_checker = null;
                    if ($o_spot_checker_name) {
                        if (is_null($fixer)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，分配了验收人，但没有分配检测/检修人或检测/检修人({$o_fixer_name})不存在");
                        if (is_null($checker)) throw new ExcelInException("第{$current_row}行，设备：{$om_serial_number}，没有验收人");
                        $spot_checker = Account::with([])->where('nickname', $o_spot_checker_name)->first();
                        if (!$spot_checker) $excel_error['O'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验人', $o_spot_checker_name, "没有找到抽验人：{$o_spot_checker_name}");
                    }

                    // 验证抽验时间
                    if ($o_spot_checked_at) {
                        try {
                            $o_spot_checked_at = ExcelWriteHelper::getExcelDate($o_spot_checked_at);
                        } catch (Exception $e) {
                            $excel_error['P'] = ExcelWriteHelper::makeErrorResult($current_row, '抽验时间', $o_spot_checked_at, $e->getMessage());
                            $o_spot_checked_at = null;
                        }
                    }

                    // 验证寿命
                    if (is_numeric($o_life_year)) {
                        if ($o_life_year < 0) {
                            $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                            $scraping_at = null;
                        } else {
                            $scraping_at = Carbon::parse($o_made_at)->addYears($o_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['Q'] = ExcelWriteHelper::makeErrorResult($current_row, '寿命(年)', $o_life_year, '寿命必须填写正整数');
                        $scraping_at = null;
                    }

                    // 周期修年限
                    if (is_numeric($o_cycle_fix_year)) {
                        if ($o_cycle_fix_year < 0) {
                            $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                            $o_cycle_fix_year = 0;
                        }
                    } else {
                        $excel_error['R'] = ExcelWriteHelper::makeErrorResult($current_row, '周期修年(非周期修写0)', $o_life_year, '周期修年限必须填写正整数');
                        $o_cycle_fix_year = 0;
                    }

                    // 写入待修改数据
                    $edit_entire_instances[] = [
                        'entire_model_unique_code' => $is_device ? $sm->unique_code : $em->unique_code,
                        'serial_number' => $om_serial_number,
                        'status' => $status,
                        'maintain_station_name' => $o_station_name,
                        'maintain_location_code' => $o_maintain_location_code,
                        'factory_name' => $om_factory_name,
                        'factory_device_code' => $o_factory_device_code,
                        'category_unique_code' => $category->unique_code,
                        'category_name' => $category->name,
                        'fix_cycle_unit' => 'YEAR',
                        'fix_cycle_value' => $o_cycle_fix_year,
                        'made_at' => $o_made_at,
                        'scarping_at' => $scraping_at,
                        'model_unique_code' => $is_device ? $sm->unique_code : $em->unique_code,
                        'model_name' => $is_device ? $sm->name : $em->name,
                        'maintain_workshop_name' => $station ? ($station->Parent ? $station->Parent->name : '') : '',
                        'v250_task_order_sn' => $sn,
                        'fixer_id' => $fixer ? $fixer->id : null,
                        'fixed_at' => $fixer ? $o_fixed_at : null,
                        'checker_id' => $checker ? $checker->id : null,
                        'checked_at' => $checker ? $o_checked_at : null,
                        'spot_checker_id' => $spot_checker ? $spot_checker->id : null,
                        'spot_checked_at' => $spot_checker ? $o_spot_checked_at : null,
                    ];

                    // 错误数据统计
                    if (!empty($excel_error)) $excel_errors[] = $excel_error;

                    $current_row++;
                }

                // 写入数据库
                DB::begintransaction();
                // 修改设备
                $edited_count = 0;
                $edited_identity_codes = [];
                foreach ($edit_entire_instances as $edit_entire_instance) {
                    $fix_workflow_datum = [
                        'fixer_id' => $edit_entire_instance['fixer_id'],
                        'fixed_at' => $edit_entire_instance['fixed_at'],
                        'checker_id' => $edit_entire_instance['checker_id'],
                        'checked_at' => $edit_entire_instance['checked_at'],
                        'spot_checker_id' => $edit_entire_instance['spot_checker_id'],
                        'spot_checked_at' => $edit_entire_instance['spot_checked_at']
                    ];
                    unset(
                        $edit_entire_instance['fixer_id'],
                        $edit_entire_instance['fixed_at'],
                        $edit_entire_instance['checker_id'],
                        $edit_entire_instance['checked_at'],
                        $edit_entire_instance['spot_checker_id'],
                        $edit_entire_instance['spot_checked_at']
                    );
                    array_filter($edit_entire_instance, function ($val) {
                        return (!empty($val) && !is_null($val));
                    });
                    $nei = EntireInstance::with([])
                        ->where('serial_number', $edit_entire_instance['serial_number'])
                        ->where('model_unique_code', $edit_entire_instance['model_unique_code'])
                        ->first();
                    $nei->fill($edit_entire_instance)->saveOrFail();
                    $edited_identity_codes[] = $nei->identity_code;

                    $edited_count++;

                    // 如果有检修人和验收人生成检修单
                    if ($fix_workflow_datum['fixer_id'] || ($fix_workflow_datum['checker_id'] && $fix_workflow_datum['checked_at'])) {
                        FixWorkflowFacade::mockEmptyWithOutEditFixed(
                            $nei,
                            $fix_workflow_datum['fixed_at'],
                            $fix_workflow_datum['checked_at'],
                            $fix_workflow_datum['fixer_id'],
                            $fix_workflow_datum['checker_id'],
                            $fix_workflow_datum['spot_checked_at'],
                            $fix_workflow_datum['spot_checker_id']
                        );

                        // 修改v2.5.0新版任务设备数据
                        V250TaskEntireInstance::with([])
                            ->where('v250_task_order_sn', $v250_task_order->serial_number)
                            ->where('entire_instance_identity_code', $nei->identity_code)
                            ->update([
                                'updated_at' => date('Y-m-d'),
                                'fixer_id' => $fix_workflow_datum['fixer_id'],
                                'fixed_at' => $fix_workflow_datum['fixed_at'],
                                'checker_id' => $fix_workflow_datum['checker_id'],
                                'checked_at' => $fix_workflow_datum['checked_at'],
                                'spot_checker_id' => $fix_workflow_datum['spot_checker_id'],
                                'spot_checked_at' => $fix_workflow_datum['spot_checked_at']
                            ]);

                        // 如果设备没有分配，则分配
                        $overhual_entire_instance = OverhaulEntireInstance::with([])
                            ->where('entire_instance_identity_code', $nei->identity_code)
                            ->where('v250_task_order_sn', $v250_task_order->serial_number)
                            ->first();
                        $overhual_entire_instance_status = $fix_workflow_datum['checker_id'] ? (strtotime($v250_task_order->expiring_at) < strtotime($fix_workflow_datum['checked_at']) ? '2' : '1') : '0';
                        $overhual_entire_instance_datum = [
                            'v250_task_order_sn' => $v250_task_order->serial_number,
                            'entire_instance_identity_code' => $nei->identity_code,
                            'fixer_id' => $fix_workflow_datum['fixer_id'],
                            'fixed_at' => $fix_workflow_datum['fixed_at'],
                            'checker_id' => $fix_workflow_datum['checker_id'],
                            'checked_at' => $fix_workflow_datum['checked_at'],
                            'spot_checker_id' => $fix_workflow_datum['spot_checker_id'],
                            'spot_checked_at' => $fix_workflow_datum['spot_checked_at'],
                            'allocate_at' => date('Y-m-d H:i:s'),
                            'deadline' => $v250_task_order->expiring_at,
                            'status' => $overhual_entire_instance_status,
                        ];
                        if ($overhual_entire_instance && @$overhual_entire_instance->status ?: '' == '0') {
                            $overhual_entire_instance->fill($overhual_entire_instance_datum)->saveOrFail();
                        } else {
                            OverhaulEntireInstance::with([])->create($overhual_entire_instance_datum);
                        }
                    }
                }
                DB::commit();

                $v250_task_entire_instances = V250TaskEntireInstance::with([
                    'EntireInstance',
                    'Fixer',
                    'Checker',
                    'SpotChecker'
                ])
                    ->whereIn('entire_instance_identity_code', $edited_identity_codes)
                    ->where('v250_task_order_sn', $v250_task_order->serial_number)
                    ->paginate(200);
                $has_edit_device_error = false;
                $edit_device_error_filename = "";
                $with_msg = "上传设备数据补充：{$edited_count}条。";

                if (!empty($excel_errors)) {
                    $root_dir = 'v250TaskOrder/upload/' . strtoupper($request->get('type')) . '/errorExcels/editDevice';
                    if (!is_dir(storage_path($root_dir))) FileSystem::init(storage_path($root_dir))->makeDir();
                    $this->_makeErrorExcel($excel_errors, storage_path("{$root_dir}/{$sn}"));

                    $v250_task_order->fill(['is_upload_edit_device_excel_error' => true])->saveOrFail();

                    $has_edit_device_error = true;
                    $edit_device_error_filename = "{$root_dir}/{$sn}.xls";
                    $with_msg = "上传设备数据补充：{$edited_count}条。" . '其中' . count($excel_errors) . '行有错误。';

                }

                return view('V250TaskOrder.uploadEditDeviceReport', [
                    'taskOrder' => $v250_task_order,
                    'entireInstances' => $v250_task_entire_instances,
                    'hasEditDeviceError' => $has_edit_device_error,
                    'editDeviceErrorFilename' => $edit_device_error_filename,
                ])
                    ->with('warning', $with_msg);
        }
    }
}

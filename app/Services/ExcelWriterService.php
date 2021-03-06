<?php

namespace App\Services;

class ExcelWriterService
{
    /**
     * 提供下载
     * @param \Closure $closure
     * @param string $filename
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function download(\Closure $closure, string $filename)
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel = $closure($objPHPExcel);
        header('Content-Type: text/html; charset=utf-8;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        $objPHPExcel->disconnectWorksheets();
    }

    /**
     * 保存到文件
     * @param \Closure $closure
     * @param string $filename
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function save(\Closure $closure, string $filename)
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel = $closure($objPHPExcel);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save(storage_path($filename . '.xlsx'));
        $objPHPExcel->disconnectWorksheets();
    }
}

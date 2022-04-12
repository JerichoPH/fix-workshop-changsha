<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ExcelReaderService
{
    private $_objPHPExcel;

    /**
     * 初始化
     * @param Request $request
     * @param string $fileName
     * @return ExcelReaderService
     * @throws \PHPExcel_Reader_Exception
     */
    public function init(Request $request, string $fileName): ExcelReaderService
    {
        if (!$request->hasFile($fileName)) throw new \Exception('上传文件失败');
        $inputFileName = $request->file($fileName);
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $this->_objPHPExcel = $objReader->load($inputFileName);
        return $this;
    }

    /**
     * 直接读取文件
     * @param string $filename
     * @return ExcelReaderService
     * @throws \PHPExcel_Reader_Exception
     */
    public function file(string $filename): ExcelReaderService
    {
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($filename);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $this->_objPHPExcel = $objReader->load($filename);
        return $this;
    }

    /**
     * 获取全部Excel内容
     * @param int $originRow
     * @param int $finishRow
     * @param \Closure $closure
     * @return array
     */
    public function readAll(int $originRow, int $finishRow = 0, \Closure $closure = null): array
    {
        $total = [];
        foreach ($this->_objPHPExcel->getSheetNames() as $sheetName) {
            $total[$sheetName][] = $this->readSheetByName($sheetName, $originRow, $finishRow, $closure);
        }

        $this->_objPHPExcel->disconnectWorksheets();

        return $total;
    }


    /**
     * 根据Sheet名称读取内容
     * @param string $sheetName
     * @param int $originRow
     * @param int $finishRow
     * @param \Closure|null $closure
     * @return array
     */
    public function readSheetByName(string $sheetName, int $originRow, int $finishRow = 0, \Closure $closure = null): array
    {
        $success = [];
        $fail = [];

        $sheet = $this->_objPHPExcel->getSheetByName($sheetName);

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        if ($originRow < 1) $originRow = 2;
        if ($finishRow > $highestRow) $finishRow = $highestRow;
        if ($finishRow == 0) $finishRow = $highestRow;

        for ($i = $originRow; $i <= $finishRow; $i++) {
            $row = $sheet->rangeToArray('A' . $i . ':' . $highestColumn . $i, NULL, TRUE, FALSE)[0];
            if ($closure) {
                $data = $closure($row);
                if ($data == null) {
                    $fail[] = $row;
                    continue;
                } else {
                    $success[] = $data;
                }
            } else {
                $success[] = $row;
            }
        }

        $this->_objPHPExcel->disconnectWorksheets();

        return ['success' => $success, 'fail' => $fail];
    }

    /**
     * 根据Sheet索引号读取内容
     * @param int $sheetIndex
     * @param int $originRow
     * @param int $finishRow
     * @param \Closure|null $closure
     * @return array
     */
    public function readSheetByIndex(int $sheetIndex,int $originRow, int $finishRow = 0, \Closure $closure = null): array
    {
        $success = [];
        $fail = [];

        $this->_objPHPExcel->setActiveSheetIndex($sheetIndex);
        $sheet = $this->_objPHPExcel->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        if ($originRow < 1) $originRow = 2;
        if ($finishRow > $highestRow) $finishRow = $highestRow;
        if ($finishRow == 0) $finishRow = $highestRow;

        for ($i = $originRow; $i <= $finishRow; $i++) {
            $row = $sheet->rangeToArray('A' . $i . ':' . $highestColumn . $i, NULL, TRUE, FALSE)[0];
            if ($closure) {
                $data = $closure($row);
                if ($data == null) {
                    $fail[] = $row;
                    continue;
                } else {
                    $success[] = $data;
                }
            } else {
                $success[] = $row;
            }
        }

        $this->_objPHPExcel->disconnectWorksheets();

        return ['success' => $success, 'fail' => $fail];
    }
}

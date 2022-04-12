<?php

namespace Jericho\Excel;

use Illuminate\Http\Request;

class ExcelReadHelper
{
    private static $_INS;  # 本类对象
    private $_objPHPExcel;  # Excel操作类对象
    private $_originRow = 2;  # 起始读取行数
    private $_finishRow = 0;  # 最大读取行数

    /**
     * ExcelReadHelper constructor.
     * @param Request $request
     * @param string $filename
     * @throws \Exception
     */
    final private function __construct()
    {
    }

    /**
     * 通过request生成新对象
     * @param Request $request
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \PHPExcel_Reader_Exception
     */
    public static function NEW_FROM_REQUEST(Request $request, string $filename)
    {
        $self = new self();
        if (!$request->hasFile($filename)) throw new \Exception('上传文件失败');
        $inputFile = $request->file($filename);
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($inputFile);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $self->_objPHPExcel = $objReader->load($inputFile);
        return $self;
    }

    /**
     * 通过storage生成新对象
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \PHPExcel_Reader_Exception
     */
    final public static function NEW_FROM_STORAGE(string $filename)
    {
        $self = new self();
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($filename);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $self->_objPHPExcel = $objReader->load($filename);
        return $self;
    }


    /**
     * @param Request $request
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \Exception
     */
    final public static function INS(Request $request, string $filename): ExcelReadHelper
    {
        if (!self::$_INS) {
            self::$_INS = new self();
            if (!$request->hasFile($filename)) throw new \Exception('上传文件失败');
            $inputFile = $request->file($filename);
            # 读取excel文件
            $inputFileType = \PHPExcel_IOFactory::identify($inputFile);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            self::$_INS->_objPHPExcel = null;
            self::$_INS->_objPHPExcel = $objReader->load($inputFile);
        }
        return self::$_INS;
    }

    /**
     * 通过上传文件
     * @param Request $request
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \Exception
     */
    final public static function FROM_REQUEST(Request $request, string $filename): self
    {
        if (!self::$_INS) {
            self::$_INS = new self();
            if (!$request->hasFile($filename)) throw new \Exception('上传文件失败');
            $inputFile = $request->file($filename);
            # 读取excel文件
            $inputFileType = \PHPExcel_IOFactory::identify($inputFile);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            self::$_INS->_objPHPExcel = null;
            self::$_INS->_objPHPExcel = $objReader->load($inputFile);
        }
        return self::$_INS;
    }

    /**
     * 读取本地文件
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Reader_Exception
     */
    public static function FROM_STORAGE(string $filename): self
    {
        if (!self::$_INS) {
            self::$_INS = new self();
            # 读取excel文件
            $inputFileType = \PHPExcel_IOFactory::identify($filename);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            self::$_INS->_objPHPExcel = null;
            self::$_INS->_objPHPExcel = $objReader->load($filename);
        }
        return self::$_INS;
    }

    /**
     * 直接读取文件
     * @param string $filename
     * @return ExcelReadHelper
     * @throws \PHPExcel_Reader_Exception
     */
    final public function file(string $filename): ExcelReadHelper
    {
        # 读取excel文件
        $inputFileType = \PHPExcel_IOFactory::identify($filename);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $this->_objPHPExcel = $objReader->load($filename);
        return $this;
    }

    /**
     * 设置读取起始行数
     * @param int $originRow
     * @return ExcelReadHelper
     */
    final public function originRow(int $originRow): ExcelReadHelper
    {
        $this->_originRow = $originRow > 0 ? $originRow : 1;
        return $this;
    }

    /**
     * 设置末尾行数
     * @param int $finishRow
     * @return ExcelReadHelper
     */
    final public function finishRow(int $finishRow): ExcelReadHelper
    {
        $this->_finishRow = $finishRow > 0 ?: 1;
        $this->_finishRow = $this->_finishRow <= $this->_originRow ?: 1;
        return $this;
    }

    /**
     * 获取全部Excel内容
     * @return array
     */
    final public function all(): array
    {
        $total = [];
        foreach ($this->_objPHPExcel->getSheetNames() as $sheetName) {
            $total[$sheetName][] = $this->withSheetName($sheetName);
        }

        $this->_objPHPExcel->disconnectWorksheets();

        return $total;
    }

    /**
     * 根据Sheet名称读取内容
     * @param string $sheetName
     * @param \Closure $closure
     * @return array
     */
    final public function withSheetName(string $sheetName, \Closure $closure = null): array
    {
        $success = [];
        $fail = [];

        $sheet = $this->_objPHPExcel->getSheetByName($sheetName);

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        if ($this->_originRow > $highestRow) $this->_originRow = intval($highestRow);
        if (($this->_finishRow == 0) || ($this->_finishRow > $highestRow)) $this->_finishRow = intval($highestRow);

        for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
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
     * 根据索引获取Sheet
     * @param int $sheetIndex
     * @param \Closure|null $closure
     * @return array
     * @throws \PHPExcel_Exception
     */
    final public function withSheetIndex(int $sheetIndex, \Closure $closure = null): array
    {
        $success = [];
        $fail = [];

        $this->_objPHPExcel->setActiveSheetIndex($sheetIndex);
        $sheet = $this->_objPHPExcel->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        if ($this->_originRow > $highestRow) $this->_originRow = intval($highestRow);
        if (($this->_finishRow == 0) || ($this->_finishRow > $highestRow)) $this->_finishRow = intval($highestRow);

        for ($i = $this->_originRow; $i <= $this->_finishRow; $i++) {
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
     * Excel时间转日期时间
     * @param int $t
     * @return false|string
     */
    final public static function excelToDatetime(int $t)
    {
        return gmdate('Y-m-d', intval(($t - 25569) * 3600 * 24));
    }

    /**
     * Excel时间戳转Unix时间戳
     * @param int $t
     * @return int
     */
    final public static function excelToTimestamp(int $t)
    {
        return intval($t - 25569);
    }

    /**
     * 时间戳转Excel时间戳
     * @param int $t
     * @return int
     */
    final public static function timestampToExcel(int $t)
    {
        return $t + 25569;
    }
}

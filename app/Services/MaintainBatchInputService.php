<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Jericho\Excel\ExcelReadHelper;
use Jericho\TextHelper;

class MaintainBatchInputService
{
    private $_diskName = 'local';
    private $_filename = null;
    private $_type = null;
    private $_request = null;

    /**
     * @param Request $request
     * @param string $filename
     * @return MaintainBatchInputService
     */
    final  public function FROM_REQUEST(Request $request, string $filename): self
    {
        $this->_request = $request;
        $this->_filename = $filename;
        $this->_type = __FUNCTION__;
        return $this;
    }

    /**
     * @param string $diskName
     * @param string $filename
     * @return $this
     */
    final public function FROM_STORAGE(string $diskName, string $filename): self
    {
        $this->_diskName = $diskName;
        $this->_filename = $filename;
        $this->_type = __FUNCTION__;
        return $this;
    }

    /**
     * @param int $sheetIndex
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PHPExcel_Exception
     * @throws \Exception
     */
    final public function withSheetIndex(int $sheetIndex = 0)
    {
        $time = date('Y-m-d H:i:s');
        switch (strtoupper($this->_type)) {
            case 'FROM_STORAGE':
                return ExcelReadHelper::FROM_STORAGE(storage_path("{$this->_diskName}/{$this->_filename}"))
                    ->withSheetIndex($sheetIndex, function ($row) use ($time) {
                        list($name, $unique_code, $parent_unique_code, $type) = $row;
                        if ($name == null || $unique_code == null || $parent_unique_code == null || $type == null) return null;
                        return [
                            'created_at' => $time,
                            'updated_at' => $time,
                            'name' => $name,
                            'unique_code' => $unique_code,
                            'parent_unique_code' => $parent_unique_code,
                            'type' => $type
                        ];
                    });
                break;
            case 'FROM_REQUEST':
                return ExcelReadHelper::FROM_REQUEST($this->_request, $this->_filename)
                    ->withSheetIndex($sheetIndex, function ($row) use ($time) {
                        list($name, $unique_code, $parent_unique_code, $type) = $row;
                        return [
                            'created_at' => $time,
                            'updated_at' => $time,
                            'name' => $name,
                            'unique_code' => $unique_code,
                            'parent_unique_code' => $parent_unique_code,
                            'type' => $type
                        ];
                    });
                break;
            default:
                throw new \Exception('参数错误：（FROM_STORAGE|FORM_REQUEST）');
                break;
        }
    }

    /**
     * @param string $sheetName
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Exception
     */
    final public function withSheetName(string $sheetName)
    {
        $time = date('Y-m-d H:i:s');
        switch (strtoupper($this->_type)) {
            case 'FROM_STORAGE':
                return ExcelReadHelper::FROM_STORAGE(storage_path("{$this->_diskName}/{$this->_filename}"))
                    ->withSheetName($sheetName, function ($row) use ($time) {
                        list($name, $unique_code, $parent_unique_code, $type) = $row;
                        return [
                            'created_at' => $time,
                            'updated_at' => $time,
                            'name' => $name,
                            'unique_code' => $unique_code,
                            'parent_unique_code' => $parent_unique_code,
                            'type' => $type
                        ];
                    });
                break;
            case 'FROM_REQUEST':
                return ExcelReadHelper::FROM_REQUEST(request(), $this->_filename)
                    ->withSheetName($sheetName, function ($row) use ($time) {
                        list($name, $unique_code, $parent_unique_code, $type) = $row;
                        return [
                            'created_at' => $time,
                            'updated_at' => $time,
                            'name' => $name,
                            'unique_code' => $unique_code,
                            'parent_unique_code' => $parent_unique_code,
                            'type' => $type
                        ];
                    });
                break;
            default:
                throw new \Exception('参数错误：（FROM_STORAGE|FORM_REQUEST）');
                break;
        }

    }
}

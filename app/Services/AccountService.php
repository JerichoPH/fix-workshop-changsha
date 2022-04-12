<?php

namespace App\Services;

use App\Exceptions\ExcelInException;
use App\Facades\AccountFacade;
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
use App\Model\PivotRoleAccount;
use App\Model\V250TaskEntireInstance;
use App\Model\WarehouseReport;
use App\Model\WarehouseReportEntireInstance;
use App\Model\WorkArea;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;

class AccountService
{
    public $workArea = 0;

    public function getWorkArea()
    {
        $this->workArea = DB::table('accounts')->where('id', session('account.id'))->first(['work_area'])->work_area;
        return $this;
    }

    public function getModels()
    {
        switch ($this->workArea) {
            case 0:
            default:
                // 全部
                $whereCategoryUniqueCodeIn = '';
                break;
            case 1:
                // 转辙机工区
                $whereCategoryUniqueCodeIn = "and em.category_unique_code in ('S03')";
                break;
            case 2:
                // 继电器工区
                $whereCategoryUniqueCodeIn = "and em.category_unique_code in ('Q01')";
                break;
            case 3:
                // 综合工区
                $whereCategoryUniqueCodeIn = "and em.category_unique_code not in ('S03','Q01')";
                break;
        }

        // 获取型号
        $sql = "select pm.name, em.name as parent_name
from part_models pm
         join entireModels em on em.unique_code = pm.entire_model_unique_code
where pm.deleted_at is null
  and em.deleted_at is null
  and pm.name is not null
  and pm.name <> ''
  and em.name is not null
  and em.name <> ''
  {$whereCategoryUniqueCodeIn}";
        $a = DB::select($sql);

        // 获取子类
        $sql = "select em.name, em2.name as parent_name
from entireModels em
         left join entireModels em2 on em2.unique_code = em.parent_unique_code
where em.deleted_at is null
  and em2.deleted_at is null
  and em2.name is not null
  and em2.name <> ''
  {$whereCategoryUniqueCodeIn}";
        $b = DB::select($sql);

        // 制作空数据
        $entireModelCount = [];
        $subModelCount = [];
        $comboModelCount = [];
        foreach ($a as $entireModel) {
            // 填充父级数据
            if (!key_exists($entireModel->parent_name, $entireModelCount)) $entireModelCount[$entireModel->parent_name] = 0;
            // 填充子集数据
            if (!key_exists($entireModel->name, $subModelCount)) $subModelCount[$entireModel->name] = 0;
            // 填充组合数据
            if (!key_exists($entireModel->parent_name, $comboModelCount)) {
                $comboModelCount[$entireModel->parent_name] = [];
            } else {
                if (!key_exists($entireModel->name, $comboModelCount[$entireModel->parent_name])) $comboModelCount[$entireModel->parent_name][$entireModel->name] = 0;
            }
        }

        foreach ($b as $entireModel) {
            // 填充腹肌数据
            if (!key_exists($entireModel->parent_name, $entireModelCount)) $entireModelCount[$entireModel->parent_name] = 0;
            // 填充子集数据
            if (!key_exists($entireModel->name, $subModelCount)) $subModelCount[$entireModel->name] = 0;
            // 填充组合数据
            if (!key_exists($entireModel->parent_name, $comboModelCount)) {
                $comboModelCount[$entireModel->parent_name] = [];
            } else {
                if (!key_exists($entireModel->name, $comboModelCount[$entireModel->parent_name])) $comboModelCount[$entireModel->parent_name][$entireModel->name] = 0;
            }
        }

        return [$entireModelCount, $subModelCount, $comboModelCount];
    }


    /**
     * 链接工区SQL
     * @param Builder $db
     * @param string $work_area
     * @return Builder
     */
    public function workAreaWithDb(Builder $db, string $work_area = ''): Builder
    {
        switch (empty($work_area) ? session('account.work_area') : $work_area) {
            case '转辙机工区':
                return $db->where("ei.category_unique_code", "S03");
                break;
            case '继电器工区':
                return $db->where("ei.category_unique_code", "Q01");
                break;
            case '综合工区':
                return $db->whereNotIn("ei.category_unique_code", ["S03", "Q01"]);
                break;
            default:
                return $db;
                break;
        }
    }

    /**
     * 下载批量上传人员excel模板(现场)
     */
    final public function downloadUploadCreateAccountBySceneExcelTemplate()
    {
        ExcelWriteHelper::download(
            function ($excel) {
                $excel->setActiveSheetIndex(0);
                $current_sheet = $excel->getActiveSheet();

                // 首行数据
                $first_row_data = [
                    ['context' => '车间*', 'color' => 'red', 'width' => 20],
                    ['context' => '工区', 'color' => 'black', 'width' => 20],
                    ['context' => '用户名*', 'color' => 'red', 'width' => 20],
                    ['context' => '姓名', 'color' => 'black', 'width' => 20],
                    ['context' => '手机号', 'color' => 'black', 'width' => 20],
                    ['context' => '职务*', 'color' => 'red', 'width' => 20],
                ];

                // 填充首行数据
                foreach ($first_row_data as $col => $firstRowDatum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                    $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }

                // 次行数据
                $second_row_data = [
                    ['context' => '安庆电务车间', 'color' => 'black', 'width' => 20],
                    ['context' => '', 'color' => 'black', 'width' => 20],
                    ['context' => '余宏伟', 'color' => 'black', 'width' => 20],
                    ['context' => '余宏伟', 'color' => 'black', 'width' => 20],
                    ['context' => '188********', 'color' => 'black', 'width' => 20],
                    ['context' => '支部书记', 'color' => 'black', 'width' => 20],
                ];
                // 填充次行数据
                foreach ($second_row_data as $col => $second_row_datum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                    $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }
                // 第三行数据
                $third_row_data = [
                    ['context' => '安庆电务车间', 'color' => 'black', 'width' => 20],
                    ['context' => '安庆北信号工区', 'color' => 'black', 'width' => 20],
                    ['context' => '丁晓明', 'color' => 'black', 'width' => 20],
                    ['context' => '丁晓明', 'color' => 'black', 'width' => 20],
                    ['context' => '188********', 'color' => 'black', 'width' => 20],
                    ['context' => '工长', 'color' => 'black', 'width' => 20],
                ];
                // 填充第三行数据
                foreach ($third_row_data as $col => $third_row_datum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $third_row_datum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}3", $context);
                    $current_sheet->getStyle("{$col_for_excel}3")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }
                // 第四行数据
                $fourth_row_data = [
                    ['context' => '安庆电务车间', 'color' => 'black', 'width' => 20],
                    ['context' => '安庆信号检查工区', 'color' => 'black', 'width' => 20],
                    ['context' => '王益三', 'color' => 'black', 'width' => 20],
                    ['context' => '王益三', 'color' => 'black', 'width' => 20],
                    ['context' => '188********', 'color' => 'black', 'width' => 20],
                    ['context' => '职工', 'color' => 'black', 'width' => 20],
                ];
                // 填充第四行数据
                foreach ($fourth_row_data as $col => $fourth_row_datum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $fourth_row_datum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}4", $context);
                    $current_sheet->getStyle("{$col_for_excel}4")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }

                return $excel;
            },
            "上传人员模板(现场)",
            ExcelWriteHelper::$VERSION_5
        );
    }

    /**
     * 批量上传用户(现场)
     * @param Request $request
     * @return ExcelInException|\Illuminate\Http\RedirectResponse
     * @throws \PHPExcel_Exception
     */
    final public function uploadCreateAccountByScene(Request $request)
    {
        $new_password = bcrypt('123123');
        $origin_row = 2;
        $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
            ->originRow($origin_row)
            ->withSheetIndex(0);

        $current_row = $origin_row;

        $ranks_flip = array_flip(Account::$RANKS);

        // 车间* 工区 账号* 姓名 电话 职务*
        $new_accounts = [];

        // 准备写入数据
        foreach (array_chunk($excel['success'], 50) as $successes) {
            foreach ($successes as $row_datum) {
                // 如果整行都没有数据则跳过
                if (empty(array_filter($row_datum, function ($value) {
                    return !empty($value) && !is_null($value);
                }))) continue;

                list($om_workshop_name, $o_work_area_name, $om_account, $o_nickname, $om_phone, $om_rank) = $row_datum;

                // 验证现场车间
                if (!$om_workshop_name) return new ExcelInException("第{$current_row}行，所属车间不能为空");
                $workshop = Maintain::with([])->where('name', $om_workshop_name)->where('type', 'SCENE_WORKSHOP')->where('parent_unique_code', env('ORGANIZATION_CODE'))->first();
                if (!$workshop) continue;
                // if (!$workshop) return new ExcelInException("第{$current_row}行，现场车间：{$om_workshop_name}没有找到");
                // 验证工区
                $work_area = null;
                if ($o_work_area_name) {
                    $work_area = WorkArea::with([])->where('name', $o_work_area_name)->where('paragraph_unique_code', env('ORGANIZATION_CODE'))->first();
                    // 如果工区不存在，则创建工区
                    if (!$work_area) {
                        $work_area = WorkArea::with([])->create([
                            'workshop_unique_code' => $workshop->unique_code,
                            'name' => $o_work_area_name,
                            'unique_code' => WorkArea::generateUniqueCode(),
                            'type' => 'scene',
                            'paragraph_unique_code' => env('ORGANIZATION_CODE'),
                        ]);
                    }
                }
                // 验证账号
                if (!$om_account) return new ExcelInException("第{$current_row}行，账号不能为空");
                if (!(strlen($om_account) > 1)) return new ExcelInException("第{$current_row}行，账号长度不能小于2位");
                $om_account = preg_replace('/( )*/', '', $om_account);  // 去掉名字中间的空格
                if (Account::with([])->where('account', $om_account)->first()) return new ExcelInException("第{$current_row}行，账号：{$om_account}被占用");
                // 验证姓名
                if (!$o_nickname) $o_nickname = $om_account;
                if (Account::with([])->where('nickname', $o_nickname)->first()) return new ExcelInException("第{$current_row}行，姓名：{$o_nickname}被占用");
                // 职务
                if (!$om_rank) $om_rank = '无';
                if (in_array($om_rank, ['车间主任', '车间副主任', '车间干部'])) {
                    $om_rank = "现场{$om_rank}";
                }
                if (in_array($om_rank, ['工长', '副工长', '职工'])) {
                    if ($workshop && !$work_area) {
                        # 车间
                        $om_rank = "现场车间{$om_rank}";
                    } else {
                        # 工区
                        $om_rank = "现场工区{$om_rank}";
                    }
                }

                if (!array_key_exists($om_rank, $ranks_flip)) return new ExcelInException("第{$current_row}行，职务({$om_rank})只能填写：" . join("、", array_keys($ranks_flip)));

                // 写入待插入数据
                $new_accounts[] = [
                    'account' => $om_account,
                    'password' => $new_password,
                    'phone' => $om_phone,
                    'nickname' => $o_nickname,
                    'identity_code' => Str::upper(md5(time() . Str::random())),
                    'workshop_code' => env('ORGANIZATION_CODE'),
                    'work_area' => 0,
                    'workshop_unique_code' => $workshop->unique_code,
                    'work_area_unique_code' => @$work_area ? $work_area->unique_code : '',
                    'rank' => $ranks_flip[$om_rank],
                ];
                $current_row++;
            }
        }

        file_put_contents(storage_path('newAccounts.json'), json_encode($new_accounts, 256));

        // 写入数据库
        DB::begintransaction();
        $new_accounts_by_chunk = array_chunk($new_accounts, 50);
        foreach ($new_accounts_by_chunk as $chunk) {
            foreach ($chunk as $new_account) {
                $account = Account::with([])->create($new_account);
                PivotRoleAccount::with([])->create(['rbac_role_id' => 1, 'account_id' => $account->id,]);
            }
        }
        DB::commit();
        $with_msg = "添加人员：" . count($new_accounts) . "条。";

        return back()->with('success', $with_msg);
    }

    /**
     * 下载批量上传人员excel模板(电务段)
     */
    final public function downloadUploadCreateAccountByParagraphExcelTemplate()
    {
        ExcelWriteHelper::download(
            function ($excel) {
                $excel->setActiveSheetIndex(0);
                $current_sheet = $excel->getActiveSheet();

                // 首行数据
                $first_row_data = [
                    ['context' => '用户名*', 'color' => 'red', 'width' => 20],
                    ['context' => '姓名', 'color' => 'black', 'width' => 20],
                    ['context' => '手机号', 'color' => 'black', 'width' => 20],
                    ['context' => '职务*', 'color' => 'red', 'width' => 20],
                ];

                // 填充首行数据
                foreach ($first_row_data as $col => $firstRowDatum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $firstRowDatum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                    $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }

                // 次行数据
                $second_row_data = [
                    ['context' => '闫其道', 'color' => 'black', 'width' => 20],
                    ['context' => '闫其道', 'color' => 'black', 'width' => 20],
                    ['context' => '188********', 'color' => 'black', 'width' => 20],
                    ['context' => '工程师', 'color' => 'black', 'width' => 20],
                ];
                // 填充次行数据
                foreach ($second_row_data as $col => $second_row_datum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $second_row_datum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}2", $context);
                    $current_sheet->getStyle("{$col_for_excel}2")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }
                // 第三行数据
                $third_row_data = [
                    ['context' => '闫其道', 'color' => 'black', 'width' => 20],
                    ['context' => '闫其道', 'color' => 'black', 'width' => 20],
                    ['context' => '188********', 'color' => 'black', 'width' => 20],
                    ['context' => '工程师', 'color' => 'black', 'width' => 20],
                ];
                // 填充第三行数据
                foreach ($third_row_data as $col => $third_row_datum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $third_row_datum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}3", $context);
                    $current_sheet->getStyle("{$col_for_excel}3")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }
                // 第四行数据
                $fourth_row_data = [
                    ['context' => '闫其道', 'color' => 'black', 'width' => 20],
                    ['context' => '闫其道', 'color' => 'black', 'width' => 20],
                    ['context' => '188********', 'color' => 'black', 'width' => 20],
                    ['context' => '工程师', 'color' => 'black', 'width' => 20],
                ];
                // 填充第四行数据
                foreach ($fourth_row_data as $col => $fourth_row_datum) {
                    $col_for_excel = ExcelWriteHelper::int2Excel($col);
                    ['context' => $context, 'color' => $color, 'width' => $width] = $fourth_row_datum;
                    $current_sheet->setCellValueExplicit("{$col_for_excel}4", $context);
                    $current_sheet->getStyle("{$col_for_excel}4")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                    $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                }

                return $excel;
            },
            "上传人员模板(电务段)",
            ExcelWriteHelper::$VERSION_5
        );
    }

    /**
     * 批量上传用户(电务段)
     * @param Request $request
     * @return ExcelInException|\Illuminate\Http\RedirectResponse
     * @throws \PHPExcel_Exception
     */
    final public function uploadCreateAccountByParagraph(Request $request)
    {
        $origin_row = 2;
        $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
            ->originRow($origin_row)
            ->withSheetIndex(0);

        $current_row = $origin_row;

        $ranks_flip = array_flip(Account::$RANKS);

        // 车间* 工区 账号* 姓名 电话 职务*
        $new_accounts = [];
        // 数据验证
        foreach ($excel['success'] as $row_datum) {
            // 如果整行都没有数据则跳过
            if (empty(array_filter($row_datum, function ($value) {
                return !empty($value) && !is_null($value);
            }))) continue;
            list($om_account, $o_nickname, $om_phone, $om_rank) = $row_datum;

            // 验证账号
            if (!$om_account) return new ExcelInException("第{$current_row}行，账号不能为空");
            if (!(strlen($om_account) > 1)) return new ExcelInException("第{$current_row}行，账号长度不能小于2位");
            $om_account = preg_replace('/( )*/', '', $om_account);
            if (Account::with([])->where('account', $om_account)->first()) return new ExcelInException("第{$current_row}行，账号：{$om_account}被占用");
            // 验证姓名
            if (!$o_nickname) $o_nickname = $om_account;
            if (Account::with([])->where('nickname', $o_nickname)->first()) return new ExcelInException("第{$current_row}行，姓名：{$o_nickname}被占用");
            // 职务
            if (!$om_rank) $om_rank = '无';
            if (!array_key_exists($om_rank, $ranks_flip)) return new ExcelInException("第{$current_row}行，职务「{$om_rank}」只能填写：" . join("、", array_keys($ranks_flip)));

            // 写入待插入数据
            $new_accounts[] = [
                'account' => $om_account,
                'password' => bcrypt('123123'),
                'phone' => $om_phone,
                'nickname' => $o_nickname,
                'identity_code' => Str::upper(md5(time() . Str::random())),
                'workshop_code' => env('ORGANIZATION_CODE'),
                'work_area' => 0,
                'rank' => $ranks_flip[$om_rank],
            ];
            $current_row++;
        }

        file_put_contents(storage_path('newAccounts.json'), json_encode($new_accounts, 256));

        // 写入数据库
        DB::begintransaction();
        $new_accounts_by_chunk = array_chunk($new_accounts, 50);
        foreach ($new_accounts_by_chunk as $chunk) {
            foreach ($chunk as $new_account) {
                $account = Account::with([])->create($new_account);
                PivotRoleAccount::with([])->create(['rbac_role_id' => 1, 'account_id' => $account->id]);
            }
        }
        DB::commit();
        $with_msg = "添加人员：" . count($new_accounts) . "条。";

        return back()->with('success', $with_msg);
    }
}

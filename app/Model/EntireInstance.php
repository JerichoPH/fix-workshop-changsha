<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\EntireInstance
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $entire_model_unique_code 设备型号统一代码
 * @property string|null $entire_model_id_code 整件型号代码
 * @property string|null $serial_number 设备出所流水号
 * @property string $status 设备状态
 *  'BUY_IN' => '新购',
 *  'INSTALLING' => '备品',
 *  'INSTALLED' => '上道',
 *  'TRANSFER_OUT' => '出所在途',
 *  'TRANSFER_IN' => '入所在途',
 *  'UNINSTALLED' => '下道',
 *  'FIXING' => '待修',
 *  'FIXED' => '成品',
 *  'RETURN_FACTORY' => '送修',
 *  'FACTORY_RETURN' => '送修入所',
 *  'SCRAP' => '报废',
 *  'FRMLOSS' => '报损',
 *  'SEND_REPAIR'=>'送修',
 * @property string|null $maintain_station_name 台账：站名称
 * @property string|null $maintain_location_code 台账位置代码
 * @property int $work_area 所属工区 0、未分配 1、转辙机工区 2、继电器工区 3、综合工区
 * @property int|null $is_main 主/备用设备标识
 * @property string $factory_name 工厂名称
 * @property string|null $factory_device_code 出场设备号
 * @property string|null $identity_code 身份识别码
 * @property int|null $last_installed_time 最后一次安装时间
 * @property string|null $in_warehouse_time 是否在库
 * @property string $category_unique_code 设备类型唯一代码
 * @property string|null $category_name 类型名称
 * @property string|null $fix_workflow_serial_number 所在工单序列号
 * @property string|null $last_warehouse_report_serial_number_by_out 最后一次出库单流水号
 * @property int|null $is_flush_serial_number 出库时是否需要刷新所编号
 * @property int|null $next_auto_making_fix_workflow_time 下次自动生成工单时间
 * @property int|null $next_fixing_time 下次检修时间
 * @property string|null $next_auto_making_fix_workflow_at 下次自动生成检修单的日期
 * @property string|null $next_fixing_month 下次检修月份
 * @property string|null $next_fixing_day 下次检修日期
 * @property string $fix_cycle_unit 周期修单位
 * @property int $fix_cycle_value 周期修长度数值
 * @property int|null $cycle_fix_count 周期修入所次数
 * @property int|null $un_cycle_fix_count 非周期修入所次数
 * @property string|null $made_at 出场日期
 * @property string|null $scarping_at 预计报废日期
 * @property string|null $residue_use_year 剩余年限
 * @property string|null $old_number 设备老编号
 * @property string|null $purpose 用途
 * @property string $warehouse_name 仓库名称
 * @property string $location_unique_code 仓库位置
 * @property string $to_direction 去向
 * @property string $crossroad_number 岔道号
 * @property string $traction 牵引
 * @property string|null $source 来源
 * @property string|null $source_crossroad_number 来源岔道号
 * @property string|null $source_traction 来源牵引
 * @property string|null $forecast_install_at 理论上道日期
 * @property string|null $line_unique_code 线制
 * @property string $line_name 线制名称
 * @property string|null $open_direction 开向
 * @property string|null $said_rod 表示杆特征
 * @property string|null $scarped_note 报废原因
 * @property string|null $railway_name 路局名称
 * @property string|null $section_name 站名
 * @property string|null $base_name 基地名称
 * @property string|null $rfid_code RFID TID
 * @property string|null $scene_workshop_status 现场车间状态（速普瑞）
 * @property string|null $rfid_epc EPC码
 * @property string|null $note 说明
 * @property string|null $before_fixed_at 预检日期（株洲导入）
 * @property string|null $before_fixer_name 预检人姓名（株洲导入)
 * @property int|null $useable_year 0
 * @property string $crossroad_type 道岔类型
 * @property int|null $allocated_to 分配到员工
 * @property string|null $allocated_at 分配到员工时间
 * @property string $point_switch_group_type 转辙机分组类型：单双机
 * @property int $extrusion_protect 挤压保护罩
 * @property string $model_unique_code 型号代码
 * @property string $model_name 型号名称
 * @property int|null $in_warehouse
 * @property string $in_warehouse_breakdown_explain 入所故障描述
 * @property string|null $last_fix_workflow_at 最后检修时间
 * @property int $is_rent 是否是租借状态
 * @property string $emergency_identity_code 应急中心仓库码
 * @property string $bind_device_code 绑定设备代码
 * @property string $bind_device_type_code 绑定设备类型代码
 * @property string $bind_device_type_name 绑定设备型号名称
 * @property string $bind_crossroad_number 绑定设备所在道岔名称
 * @property string $bind_crossroad_id 绑定设备所在道岔编号
 * @property string $bind_station_name 绑定设备所在车站名称
 * @property string $bind_station_code 绑定设备所在车站编号
 * @property string|null $last_out_at 最后出所时间
 * @property int $is_bind_location 是否绑定位置0未绑定，1绑定
 * @property string $maintain_workshop_name
 * @property string|null $last_take_stock_at 最后盘点时间
 * @property int $last_repair_material_id 最后送修单设备关联
 * @property string $v250_task_order_sn 2.5.0新版任务单编号
 * @property string $work_area_unique_code 所属工区
 * @property string|null $warehousein_at 入库时间
 * @property string|null $is_overhaul 是否分配检修0:未分配,1:已分配
 * @property string $fixer_name 检修人姓名
 * @property string|null $fixed_at 检测时间
 * @property string $checker_name 验收人姓名
 * @property string|null $checked_at 验收时间
 * @property string $spot_checker_name 抽验人姓名
 * @property string|null $spot_checked_at 抽验时间
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\BreakdownLog[] $BreakdownLogs
 * @property-read int|null $breakdown_logs_count
 * @property-read \App\Model\Category $Category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\EntireInstanceLog[] $EntireInstanceLogs
 * @property-read int|null $entire_instance_logs_count
 * @property-read \App\Model\EntireModel $EntireModel
 * @property-read \App\Model\EntireModelIdCode $EntireModelIdCode
 * @property-read \App\Model\Factory $Factory
 * @property-read \App\Model\FixWorkflow $FixWorkflow
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\FixWorkflow[] $FixWorkflows
 * @property-read int|null $fix_workflows_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\Measurement[] $Measurements
 * @property-read int|null $measurements_count
 * @property-read \App\Model\PartInstance $PartInstance
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\PartInstance[] $PartInstances
 * @property-read int|null $part_instances_count
 * @property-read \App\Model\PartModel $PartModel
 * @property-read \App\Model\Maintain $Station
 * @property-read \App\Model\EntireModel $SubModel
 * @property-read \App\Model\WarehouseReport $WarehouseReportByOut
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\WarehouseReport[] $WarehouseReports
 * @property-read int|null $warehouse_reports_count
 * @property-read \App\Model\Position $WithPosition
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Model\SendRepairInstance[] $WithSendRepairInstances
 * @property-read int|null $with_send_repair_instances_count
 * @property-read array $storehouse_location
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereAllocatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereAllocatedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBaseName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBeforeFixedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBeforeFixerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBindCrossroadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBindCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBindDeviceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBindDeviceTypeCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBindDeviceTypeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBindStationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereBindStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereCategoryUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereCheckerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereCrossroadType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereCycleFixCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereEmergencyIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereEntireModelIdCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereEntireModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereExtrusionProtect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereFactoryDeviceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereFactoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereFixCycleUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereFixCycleValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereFixWorkflowSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereFixedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereFixerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereForecastInstallAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereInWarehouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereInWarehouseBreakdownExplain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereInWarehouseTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereIsBindLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereIsFlushSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereIsMain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereIsOverhaul($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereIsRent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLastFixWorkflowAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLastInstalledTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLastOutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLastRepairMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLastTakeStockAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLastWarehouseReportSerialNumberByOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLineUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereLocationUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereMadeAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereMaintainLocationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereMaintainStationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereMaintainWorkshopName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereModelUniqueCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereNextAutoMakingFixWorkflowAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereNextAutoMakingFixWorkflowTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereNextFixingDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereNextFixingMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereNextFixingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereOldNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereOpenDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance wherePointSwitchGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereRailwayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereResidueUseYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereRfidCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereRfidEpc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSaidRod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereScarpedNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereScarpingAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSceneWorkshopStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSectionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSourceCrossroadNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSourceTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSpotCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereSpotCheckerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereToDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereTraction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereUnCycleFixCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereUseableYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereV250TaskOrderSn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereWarehouseName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereWarehouseinAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereWorkArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstance whereWorkAreaUniqueCode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstance withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Model\EntireInstance withoutTrashed()
 * @mixin \Eloquent
 */
class EntireInstance extends Model
{
    use SoftDeletes;

    public static $STATUSES = [
        'BUY_IN' => '新购',
        'INSTALLING' => '现场备品',
        'INSTALLED' => '上道使用',
        'TRANSFER_OUT' => '出所在途',
        'TRANSFER_IN' => '入所在途',
        'UNINSTALLED' => '下道',
        'FIXING' => '入所检修',
        'FIXED' => '成品',
        // 'RETURN_FACTORY' => '送修',
        'FACTORY_RETURN' => '送修入所',
        'SCRAP' => '报废',
        'FRMLOSS' => '报损',
        'SEND_REPAIR' => '送修',
        'REPAIRING' => '检修中',
    ];

    public static $STATUSES_FOR_BI = [
        'INSTALLING' => '现场备品',
        'INSTALLED' => '上道使用',
        'TRANSFER_OUT' => '出所在途',
        'TRANSFER_IN' => '入所在途',
        'FIXING' => '待修',
        'FIXED' => '所内备品',
        'SCRAP' => '报废',
        'SEND_REPAIR' => '送修中',
    ];

    public static $STATUSS = [
        'INSTALLED' => '上道',
        'INSTALLING' => '备品',
        'FIXED' => '成品',
        'RETURN_FACTORY' => '送修',
        'UNINSTALLED' => '下道',
        'SCRAP' => '报废',
    ];

    public static $FIX_CYCLE_UNIT = [
        'YEAR' => '年',
        'MONTH' => '月',
        'WEEK' => '周',
        'DAY' => '日',
    ];
    protected $guarded = [];

    public static function getNextCode(string $entireModelUniqueCode)
    {
        $entireModel = EntireModel::with([
            'Category',
            'Category.Race',
        ])
            ->where('unique_code', $entireModelUniqueCode)
            ->firstOrFail();

        $maxCode = self::with([])->where('entire_model_unique_code', $entireModelUniqueCode)->max('identity_code');
        $nextCode = intval(substr($maxCode, strlen($entireModelUniqueCode) + 4)) + 1;

        return "{$entireModel->unique_code}"
            . env('ORGANIZATION_CODE')
            . str_pad(
                $nextCode,
                $entireModel->Category->Race->serial_number_length,
                '0',
                STR_PAD_LEFT
            );
    }

    public static function flipFixCycleUnit($value)
    {
        return array_flip(self::$FIX_CYCLE_UNIT)[$value];
    }

    public function BreakdownLogs()
    {
        return $this->hasMany(BreakdownLog::class, 'entire_instance_identity_code', 'identity_code');
    }

    public function prototype(string $attributeKey)
    {
        return $this->attributes[$attributeKey];
    }

    final public function getStatusAttribute($value)
    {
        return @self::$STATUSES[$value] ?: '无';
    }

    public static function getStorehouseLocation(string $value)
    {

    }

    /**
     * 获取仓库位置名称和代码
     * @param $value
     * @return array
     */
    final public function getStorehouseLocationAttribute($value): array
    {
        $name = '无';
        $has_img = false;
        if ($this->location_unique_code) {


            $first = substr($this->location_unique_code, 0, 7);
            $last = substr($this->location_unique_code, 7);
            $tmp = str_split($last, 2);

            $dbs = [
                DB::table('storehouses')->select(['name']),
                DB::table('areas')->select(['name']),
                DB::table('platoons')->select(['name']),
                DB::table('shelves')->select(['name']),
                DB::table('tiers')->select(['name']),
                DB::table('positions')->select(['name']),
            ];
            $names = collect([]);

            foreach ($tmp as $key => $val) {
                $tmp_code = '';
                for ($i = 0; $i <= $key; $i++) $tmp_code .= $tmp[$i];
                $tmp_code = $first . $tmp_code;

                $names[$tmp_code] = $dbs[$key]->where('unique_code', $tmp_code)->first();
            }

            if ($names->isNotEmpty()) $name = $names->pluck('name')->implode('');

            $has_img = boolval(count($tmp) == 6);
        }

        return ['code' => $this->location_unique_code, 'name' => $name, 'has_img' => $has_img];
    }

    // @fixme 以前叫做MaintainStation 没有发现被使用过，更名为：Station
    final public function Station()
    {
        return $this->hasOne(Maintain::class, 'name', 'maintain_station_name');
    }

    /**
     * 类型
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function EntireModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'entire_model_unique_code');
    }

    /**
     * 种类
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function Category()
    {
        return $this->hasOne(Category::class, 'unique_code', 'category_unique_code');
    }

    /**
     * 子类
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function SubModel()
    {
        return $this->hasOne(EntireModel::class, 'unique_code', 'model_unique_code');
    }

    /**
     * 部件种类
     */
    final public function PartCategory(): HasOne
    {
        return $this->hasOne(PartCategory::class, "id", "part_category_id");
    }

    /**
     * 型号
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function PartModel()
    {
        return $this->hasOne(PartModel::class, 'unique_code', 'model_unique_code');
    }

    /**
     * 部件列表
     * @return HasMany
     */
    final public function PartInstances(): HasMany
    {
        return $this->hasMany(EntireInstance::class, 'entire_instance_identity_code', 'identity_code');
    }

    final public function PartInstance()
    {
        return $this->hasOne(PartInstance::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 最后一张检修单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function FixWorkflow()
    {
        return $this->hasOne(FixWorkflow::class, 'serial_number', 'fix_workflow_serial_number');
    }

    /**
     * 检修单历史
     * @return HasMany
     */
    final public function FixWorkflows()
    {
        return $this->hasMany(FixWorkflow::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 检测标准值
     * @return HasMany
     */
    final public function Measurements()
    {
        return $this->hasMany(Measurement::class, 'entire_model_unique_code', 'entire_model_unique_code');
    }

    /**
     * 出所单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function WarehouseReportByOut()
    {
        return $this->hasOne(WarehouseReport::class, 'serial_number', 'last_warehouse_report_serial_number_by_out');
    }

    /**
     * 出入所单
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    final public function WarehouseReports()
    {
        return $this->belongsToMany(WarehouseReport::class, 'warehouse_report_entire_instances', 'warehouse_report_serial_number', 'entire_instance_identity_code');
    }

    final public function EntireModelIdCode()
    {
        return $this->hasOne(EntireModelIdCode::class, 'code', 'entire_model_id_code');
    }

    // final public function ExtraTag()
    // {
    //     return $this->hasMany(PivotEntireInstanceAndExtraTag::class, 'entire_instance_identity_code', 'identity_code');
    // }

    /**
     * 日志
     * @return HasMany
     */
    final public function EntireInstanceLogs()
    {
        return $this->hasMany(EntireInstanceLog::class, 'entire_instance_identity_code', 'identity_code');
    }

    /**
     * 仓库位置
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    final public function WithPosition()
    {
        return $this->belongsTo(Position::class, 'location_unique_code', 'unique_code');
    }

    /**
     * 供应商
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    final public function Factory()
    {
        return $this->hasOne(Factory::class, 'name', 'factory_name');
    }

    /**
     * 最后一张送修单
     * @return HasMany
     */
    final public function WithSendRepairInstances()
    {
        return $this->hasMany(SendRepairInstance::class, 'material_unique_code', 'identity_code');
    }
}

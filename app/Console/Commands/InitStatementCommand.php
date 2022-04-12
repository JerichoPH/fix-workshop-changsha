<?php

namespace App\Console\Commands;

use App\Model\Account;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InitStatementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:statement';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $_sql = [
        "drop table if exists station_install_location_records;",
        "create table station_install_location_records
(
    id                            int auto_increment
        primary key,
    created_at                    datetime               not null,
    updated_at                    datetime               not null,
    entire_instance_identity_code varchar(50) default '' not null comment '设备编号',
    processor_id                  int         default 0  not null comment '处理人',
    maintain_station_unique_code  varchar(50) default '' not null comment '车站代码',
    maintain_station_name         varchar(50) default '' not null comment '车站名称',
    maintain_location_code        varchar(50) default '' not null comment '室内位置代码',
    crossroad_number              varchar(50) default '' not null comment '道岔号',
    is_indoor                     tinyint(1)  default 0  not null comment '是否是室内设备',
    section_unique_code           varchar(50) default '' not null comment '区间代码',
    open_direction                varchar(50) default '' not null comment '开向'
)
    comment '车站上道位置编码';",
        "drop table if exists task_station_check_account_levels;",
        "create table task_station_check_account_levels
(
    id         int                    not null,
    created_at datetime               not null,
    updated_at datetime               not null,
    level      int         default 0  not null comment '级别',
    name       varchar(50) default '' not null comment '级别名称',
    constraint task_station_check_account_level_id_uindex
        unique (id)
)
    comment '人员级别（现场检查任务）';",
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    final public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    final public function handle()
    {
        foreach ($this->_sql as $k => $v) {
            if ($v) $this->_statement($k, $v);
        }
    }

    private function _statement(string $comment, string $sql)
    {
        $this->comment($comment);
        DB::statement($sql);
    }
}

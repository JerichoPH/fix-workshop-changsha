<?php

namespace App\Console\Commands;

use App\Facades\StatisticsFacade;
use Carbon\Carbon;
use Hprose\Http\Client;
use Illuminate\Console\Command;
use Jericho\TextHelper;
use Symfony\Component\VarDumper\VarDumper;

class SqlBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sqlBackup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取数据库备份文件，自动解压缩，自动恢复数据库，自动进行统计';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $time = Carbon::now()->toDateString();
        $db_name = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        $url = env('SQL_BACKUP_URL');
        $now = Carbon::now();
        $year = $now->year;
        $organization_code = env('ORGANIZATION_CODE');
        $organization_name = env('ORGANIZATION_NAME');
        $client = new Client("http://{$url}/rpc/sqlBackup", false);

//        shell_exec("mysqldump --extended-insert -u{$username} -p'{$password}' {$db_name} | gzip > " . public_path("{$time}.sql.gz"));
        shell_exec("mysqldump -u{$username} -p'{$password}' {$db_name} | gzip > " . public_path("{$time}.sql.gz"));
        $sqlFilename = "{$time}.sql.gz";

        # 检查SQL文件是否存在
        $sqlFileDir = public_path($sqlFilename);
        if (is_file($sqlFileDir)) {
            dump($organization_code, $organization_name);
            # 将sql备份文件发送给maintain_group
            $res = $client->sqlFile(
                $organization_code,
                $organization_name,
                $sqlFilename,
                file_get_contents(public_path($sqlFilename))
            );
            if (empty($res)) $res = '无错误';
            dump("备份sql：{$res}");
        } else {
            dump('error');
        }
        return null;
    }
}

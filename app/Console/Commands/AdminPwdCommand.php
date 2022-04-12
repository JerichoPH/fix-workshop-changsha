<?php

namespace App\Console\Commands;

use App\Facades\TextFacade;
use App\Model\Account;
use Illuminate\Console\Command;

class AdminPwdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminPwd {new_pwd?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @throws \Throwable
     */
    public function handle()
    {
        if($this->argument('new_pwd')){
            $new_pwd = $this->argument('new_pwd');
        }else{
            $new_pwd = TextFacade::rand('num', 6);
        }
        $account = Account::with([])->where('account','admin')->first();
        $account->fill(['password'=>bcrypt($new_pwd)])->saveOrFail();
        $this->info("新密码：{$new_pwd}");
    }
}

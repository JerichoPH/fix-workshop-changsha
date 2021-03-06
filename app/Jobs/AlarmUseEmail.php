<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jericho\EmailHelper;

class AlarmUseEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 120;

    private $_title = null;
    private $_content = null;
    private $_to = null;

    /**
     * Create a new job instance.
     *
     * AlarmUseEmail constructor.
     * @param $title
     * @param $content
     * @param $to
     */
    public function __construct($title, $content, $to)
    {
        $this->_title = $title;
        $this->_content = $content;
        $this->_to = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        EmailHelper::send($this->_title, $this->_content, $this->_to);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\InternalPipeline\SendWeeklyInactiveUserJob;

class SendWeeklyInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-wekkly-inactive-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Weekly inactive users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        SendWeeklyInactiveUserJob::dispatch()->onQueue('low');
    }
}

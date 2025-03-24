<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\InternalPipeline\SendMonthlyPopular;

class SendPopularInMonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-popular-in-month {--test : Run in test mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send popular things in last month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $testing = $this->option('test') ?? false;
        SendMonthlyPopular::dispatch($testing)->onQueue('low');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use  App\Jobs\InternalPipeline\SendWeeklyPopularPostsJob;

class SendWeeklyPopularPosts extends Command
{
    protected $signature = 'app:send-weekly-popular-posts {--test : Run in test mode}';
    protected $description = 'Envia um email com os posts mais populares da semana para os usuários';

    public function handle()
    {
        $testing = $this->option('test') ?? false;
        SendWeeklyPopularPostsJob::dispatch($testing)->onQueue('low');
    }
}

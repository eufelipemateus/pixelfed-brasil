<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use  App\Jobs\InternalPipeline\SendWeeklyPopularPostsJob;

class SendWeeklyPopularPosts extends Command
{
    protected $signature = 'app:send-weekly-popular-posts';
    protected $description = 'Envia um email com os posts mais populares da semana para os usuários';

    public function handle()
    {
        SendWeeklyPopularPostsJob::dispatch()->onQueue('low');
    }
}

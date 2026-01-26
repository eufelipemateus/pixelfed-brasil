<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\PurgeInactiveInstancesJob;

class PurgeInactiveInstances extends Command
{
    protected $signature = 'instances:purge-inactive';
    protected $description = 'Remove posts, fotos e vídeos de instâncias inativas há mais de 2 anos';

    public function handle()
    {
        PurgeInactiveInstancesJob::dispatch();
        $this->info('Job de limpeza de instâncias inativas disparado!');
    }
}

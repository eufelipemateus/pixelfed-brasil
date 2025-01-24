<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\MediaPipeline\MigrateToCloudJob;

class MigrateToCloud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-to-cloud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pixelfed BR Migrate to Cloud';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $this->info('Dispatching MigrateToCloudJob...');
        MigrateToCloudJob::dispatch()->onQueue('move');
        $this->info('Job dispatched. Check the logs for progress.');
    }
}

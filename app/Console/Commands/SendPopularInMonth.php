<?php

namespace App\Console\Commands;

use App\Jobs\InternalPipeline\DefinePopularUsers;
use App\Models\Profile;
use Illuminate\Console\Command;

class SendPopularInMonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:define-popular-users {--test : Preview without updating profiles}';

    protected $aliases = ['app:send-popular-in-month'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Define popular profiles from likes received last month';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $month = now('UTC')->subMonthNoOverflow()->format('Y-m');

        if ($this->option('test')) {
            $ids = DefinePopularUsers::popularProfileIds($month);
            $profiles = Profile::whereIn('id', $ids)->get(['id', 'username'])->keyBy('id');

            $this->info('Perfis populares para '.$month.':');
            foreach ($ids as $id) {
                if ($profile = $profiles->get($id)) {
                    $this->line('@'.$profile->username.' ('.$id.')');
                }
            }

            return self::SUCCESS;
        }

        DefinePopularUsers::dispatch($month)->onQueue('low');

        return self::SUCCESS;
    }
}

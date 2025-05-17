<?php

namespace App\Jobs\FelipeMateusPipeline;

use App\User;
use App\Services\FelipeMateus\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Enums\StatusEnums;

class SyncFelipeMateusSubscribers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        User::chunk(
            100, function ($users) {
                foreach ($users as $user) {
                    ProcessUserSyncJob::dispatch($user)->delay(now()->addMilliseconds(500));
                }
            }
        );
    }

}

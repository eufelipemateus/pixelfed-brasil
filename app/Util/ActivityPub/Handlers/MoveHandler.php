<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Bus;
use App\Jobs\MovePipeline\ProcessMovePipeline;
use App\Jobs\MovePipeline\MoveMigrateFollowersPipeline;
use App\Jobs\MovePipeline\UnfollowLegacyAccountMovePipeline;
use App\Jobs\MovePipeline\CleanupLegacyAccountMovePipeline;
use Illuminate\Support\Facades\Log;
use Throwable;

class MoveHandler implements ActivityHandler
{

    public function __construct(protected array $headers, protected $profile, protected array $payload) {}


    public function handle(): void
    {


        $actor = $this->payload['actor'];
        $activity = $this->payload['object'];
        $target = $this->payload['target'];
        if (
            ! Helpers::validateUrl($actor) ||
            ! Helpers::validateUrl($activity) ||
            ! Helpers::validateUrl($target)
        ) {
            return;
        }

        Bus::chain([
            new ProcessMovePipeline($target, $activity),
            new MoveMigrateFollowersPipeline($target, $activity),
            new UnfollowLegacyAccountMovePipeline($target, $activity),
            new CleanupLegacyAccountMovePipeline($target, $activity),
        ])
            ->catch(function (Throwable $e) {
                Log::error($e);
            })
            ->onQueue('move')
            ->delay(now()->addMinutes(random_int(1, 3)))
            ->dispatch();
    }
}

<?php

namespace App\Util\ActivityPub\Handlers;

use App\Jobs\ProfilePipeline\HandleUpdateActivity;
use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Status;
use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\Validator\UpdatePersonValidator;

class UpdateHandler implements ActivityHandler
{

    public function __construct(protected array $headers, protected $profile, protected array $payload) {}


    public function handle(): void
    {
        $activity = $this->payload['object'];

        if (! isset($activity['type'], $activity['id'])) {
            return;
        }

        if (! Helpers::validateUrl($activity['id'])) {
            return;
        }

        if ($activity['type'] === 'Note') {
            if (Status::whereObjectUrl($activity['id'])->exists()) {
                StatusRemoteUpdatePipeline::dispatch($activity);
            }
        } elseif ($activity['type'] === 'Person') {
            if (UpdatePersonValidator::validate($this->payload)) {
                HandleUpdateActivity::dispatch($this->payload)->onQueue('low');
            }
        }
    }
}

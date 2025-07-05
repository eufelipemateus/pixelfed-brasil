<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Jobs\StoryPipeline\StoryFetch;
use App\Util\ActivityPub\Helpers;

class AddHandler implements ActivityHandler
{
    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {
        if (
            !isset(
                $this->payload['actor'],
                $this->payload['object']
            )
        ) {
            return;
        }

        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];

        if (! Helpers::validateUrl($actor)) {
            return;
        }

        if (! isset($obj['type'])) {
            return;
        }

        switch ($obj['type']) {
            case 'Story':
                StoryFetch::dispatch($this->payload);
                break;
        }
    }
}

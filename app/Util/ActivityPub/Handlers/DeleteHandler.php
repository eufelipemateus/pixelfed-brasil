<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Status;
use App\Story;
use App\Profile;
use App\Jobs\StoryPipeline\StoryExpire;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Util\ActivityPub\Helpers;

class DeleteHandler implements ActivityHandler
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
        if (is_string($obj) == true && $actor == $obj && Helpers::validateUrl($obj)) {
            $profile = Profile::whereRemoteUrl($obj)->first();
            if (! $profile || $profile->private_key != null) {
                return;
            }
            DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');

            return;
        } else {
            if (
                !isset(
                    $obj['id'],
                    $this->payload['object'],
                    $this->payload['object']['id'],
                    $this->payload['object']['type']
                )
            ) {
                return;
            }
            $type = $this->payload['object']['type'];
            $typeCheck = in_array($type, ['Person', 'Tombstone', 'Story']);
            if (! Helpers::validateUrl($actor) || ! Helpers::validateUrl($obj['id']) || ! $typeCheck) {
                return;
            }
            if (parse_url($obj['id'], PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
                return;
            }
            $id = $this->payload['object']['id'];
            switch ($type) {
                case 'Person':
                    $profile = Profile::whereRemoteUrl($actor)->first();
                    if (! $profile || $profile->private_key != null) {
                        return;
                    }
                    DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');

                    return;
                    break;

                case 'Tombstone':
                    $profile = Profile::whereRemoteUrl($actor)->first();
                    if (! $profile || $profile->private_key != null) {
                        return;
                    }

                    $status = Status::where('object_url', $id)->first();
                    if (! $status) {
                        $status = Status::where('url', $id)->first();
                        if (! $status) {
                            return;
                        }
                    }
                    if ($status->profile_id != $profile->id) {
                        return;
                    }
                    if ($status->scope && in_array($status->scope, ['public', 'unlisted', 'private'])) {
                        if ($status->type && ! in_array($status->type, ['story:reaction', 'story:reply', 'reply'])) {
                            FeedRemoveRemotePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');
                        }
                    }
                    RemoteStatusDelete::dispatch($status)->onQueue('high');

                    return;
                    break;

                case 'Story':
                    $story = Story::whereObjectId($id)
                        ->first();
                    if ($story) {
                        StoryExpire::dispatch($story)->onQueue('story');
                    }

                    return;
                    break;

                default:
                    return;
                    break;
            }
        }
    }
}

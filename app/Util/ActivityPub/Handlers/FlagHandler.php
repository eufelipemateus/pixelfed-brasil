<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Helpers;
use App\Status;
use App\Instance;
use App\Models\RemoteReport;
use App\Profile;
use Purify;

class FlagHandler implements ActivityHandler
{


    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {

        if (
            !isset(
                $this->payload['id'],
                $this->payload['type'],
                $this->payload['actor'],
                $this->payload['object']
            )
        ) {
            return;
        }

        $id = $this->payload['id'];
        $actor = $this->payload['actor'];

        if (Helpers::validateLocalUrl($id) || parse_url($id, PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
            return;
        }

        $content = null;
        if (isset($this->payload['content'])) {
            if (strlen($this->payload['content']) > 5000) {
                $content = Purify::clean(substr($this->payload['content'], 0, 5000) . ' ... (truncated message due to exceeding max length)');
            } else {
                $content = Purify::clean($this->payload['content']);
            }
        }
        $object = $this->payload['object'];

        if (empty($object) || (! is_array($object) && ! is_string($object))) {
            return;
        }

        if (is_array($object) && count($object) > 100) {
            return;
        }

        $objects = collect([]);
        $accountId = null;

        foreach ($object as $objectUrl) {
            if (! Helpers::validateLocalUrl($objectUrl)) {
                return;
            }

            if (str_contains($objectUrl, '/users/')) {
                $username = last(explode('/', $objectUrl));
                $profileId = Profile::whereUsername($username)->first();
                if ($profileId) {
                    $accountId = $profileId->id;
                }
            } elseif (str_contains($objectUrl, '/p/')) {
                $postId = last(explode('/', $objectUrl));
                $objects->push($postId);
            } else {
                continue;
            }
        }

        if (! $accountId && ! $objects->count()) {
            return;
        }

        if ($objects->count()) {
            $obc = $objects->count();
            if ($obc > 25) {
                if ($obc > 30) {
                    return;
                } else {
                    $objLimit = $objects->take(20);
                    $objects = collect($objLimit->all());
                    $obc = $objects->count();
                }
            }
            $count = Status::whereProfileId($accountId)->find($objects)->count();
            if ($obc !== $count) {
                return;
            }
        }

        $instanceHost = parse_url($id, PHP_URL_HOST);

        $instance = Instance::updateOrCreate([
            'domain' => $instanceHost,
        ]);

        $report = new RemoteReport();
        $report->status_ids = $objects->toArray();
        $report->comment = $content;
        $report->account_id = $accountId;
        $report->uri = $id;
        $report->instance_id = $instance->id;
        $report->report_meta = [
            'actor' => $actor,
            'object' => $object,
        ];
        $report->save();
    }
}

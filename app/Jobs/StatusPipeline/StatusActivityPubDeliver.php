<?php

namespace App\Jobs\StatusPipeline;

use App\Models\Profile;
use App\Models\Status;
use App\Services\ActivityPubFanoutService;
use App\Services\FractalService;
use App\Services\InstanceService;
use App\Transformer\ActivityPub\Verb\CreateNote;
use App\Transformer\ActivityPub\Verb\CreateQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StatusActivityPubDeliver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;

        // Verify status exists
        if (! $status) {
            Log::info('StatusActivityPubDeliver: Status no longer exists, skipping job');

            return;
        }

        $profile = $status->profile;

        // Verify profile exists
        if (! $profile) {
            Log::info("StatusActivityPubDeliver: Profile no longer exists for status {$status->id}, skipping job");

            return;
        }

        // ignore group posts
        // if($status->group_id != null) {
        //     return;
        // }

        if ($status->local == false || $status->url || $status->uri) {
            return;
        }

        if (! in_array($status->scope, ['public', 'unlisted', 'private'], true)) {
            return;
        }

        $audience = $profile->getAudienceInbox($status->scope);

        $parentInbox = [];

        $mentions = $status->mentions
            ->filter(function ($f) {
                return $f->domain !== null;
            })
            ->values()
            ->map(function ($m) {
                return $m->sharedInbox ?? $m->inbox_url;
            })
            ->toArray();

        if ($status->in_reply_to_profile_id) {
            $parent = Profile::find($status->in_reply_to_profile_id);
            if ($parent && $parent->domain !== null) {
                $parentInbox = [
                    $parent->sharedInbox ?? $parent->inbox_url,
                ];
            }
        }

        $audience = array_merge($audience, $mentions, $parentInbox);

        if ($this->shouldDeliverToKnownInstances($status, $profile)) {
            $audience = array_merge($audience, InstanceService::getPublicSharedInboxes());
        }

        $audience = array_values(array_unique(array_filter(
            $audience,
            fn ($inbox) => is_string($inbox) && trim($inbox) !== ''
        )));

        if ($audience === []) {
            return;
        }

        switch ($status->type) {
            case 'poll':
                $activitypubObject = new CreateQuestion;
                break;

            default:
                $activitypubObject = new CreateNote;
                break;
        }

        $activity = FractalService::item($status, $activitypubObject);

        /*
         * FEP-8fcf: followers-only posts rely on the receiving server's copy
         * of our followers collection for access control, so they carry a
         * Collection-Synchronization header that lets it detect drift.
         */
        ActivityPubFanoutService::dispatch(
            $profile,
            $activity,
            $audience,
            $status->scope === 'private',
            (int) $status->id
        );
    }

    private function shouldDeliverToKnownInstances(Status $status, Profile $profile): bool
    {
        if (
            $status->scope !== 'public' ||
            (bool) $profile->unlisted ||
            ! (bool) config('federation.activitypub.delivery.allow_share_all')
        ) {
            return false;
        }

        return (bool) $profile->user?->is_admin || (bool) $profile->is_popular;
    }
}

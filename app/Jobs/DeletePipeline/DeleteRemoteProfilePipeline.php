<?php

namespace App\Jobs\DeletePipeline;

use App\Enums\StatusEnums;
use App\Jobs\InstancePipeline\FinalizePurgeInactiveProfileJob;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\Like;
use App\Models\MediaTag;
use App\Models\Mention;
use App\Models\Notification;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Profile;
use App\Models\QuoteAuthorization;
use App\Models\Report;
use App\Models\Status;
use App\Models\StatusEdit;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\UserFilter;
use App\Services\AccountService;
use App\Services\DirectMessageService;
use App\Services\InstancePurgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteRemoteProfilePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    protected ?int $purgeInstanceId = null;

    protected ?string $purgeGeneration = null;

    public $timeout = 900;

    public $tries = 3;

    public $maxExceptions = 1;

    public $deleteWhenMissingModels = true;

    public function __construct(
        Profile $profile,
        ?int $purgeInstanceId = null,
        ?string $purgeGeneration = null
    ) {
        $this->profile = $profile;
        $this->purgeInstanceId = $purgeInstanceId;
        $this->purgeGeneration = $purgeGeneration;
    }

    public function handle()
    {
        $profile = $this->profile;

        // Verify profile exists
        if (! $profile) {
            Log::info('DeleteRemoteProfilePipeline: Profile no longer exists, skipping job');

            return null;
        }

        $pid = $profile->id;

        if ($profile->domain == null || $profile->private_key) {
            return null;
        }

        if ($this->purgeInstanceId !== null) {
            $authorized = InstancePurgeService::authorizeDestructiveProfileStep(
                $this->purgeInstanceId,
                $pid,
                $this->purgeGeneration ?? '',
                function (Profile $storedProfile) {
                    $storedProfile->status = StatusEnums::DELETE_QUEUE;
                    $storedProfile->save();
                }
            );

            if (! $authorized) {
                InstancePurgeService::releaseProfileClaim(
                    $pid,
                    $this->purgeInstanceId,
                    $this->purgeGeneration ?? ''
                );

                Log::info('DeleteRemoteProfilePipeline: purge cancelado após revalidação', [
                    'instance_id' => $this->purgeInstanceId,
                    'profile_id' => $pid,
                    'domain' => $profile->domain,
                ]);

                return null;
            }
        } else {
            $profile->status = StatusEnums::DELETE_QUEUE;
            $profile->save();
        }

        AccountService::del($pid);

        // Delete statuses. chunkById, not chunk: RemoteStatusDelete
        // soft-deletes the rows it is handed, so OFFSET paging would skip rows
        // as the live set shrinks. Keyset paging on the monotonic id is stable.
        $statuses = Status::withTrashed()
            ->whereProfileId($pid)
            ->orderBy('id');

        if ($this->purgeInstanceId !== null) {
            $statuses->limit(InstancePurgeService::statusBatchSize())
                ->get()
                ->each(function ($status) {
                    RemoteStatusDelete::dispatch(
                        $status,
                        $this->purgeInstanceId,
                        $this->purgeGeneration
                    )->onQueue('delete');
                });
        } else {
            $statuses->chunkById(50, function ($statuses) {
                foreach ($statuses as $status) {
                    RemoteStatusDelete::dispatch(
                        $status,
                        $this->purgeInstanceId,
                        $this->purgeGeneration
                    )->onQueue('delete');
                }
            });
        }

        // Safety net: purge any edit-history rows for this profile in case a
        // per-status delete job was skipped or failed (mirrors DeleteAccountPipeline).
        StatusEdit::whereProfileId($pid)->delete();

        // Delete Poll Votes
        PollVote::whereProfileId($pid)->delete();

        // Delete Polls
        Poll::whereProfileId($pid)->delete();

        // Delete Avatar
        if ($profile->avatar && $profile->avatar->exists) {
            $profile->avatar->forceDelete();
        }

        // Delete media tags
        MediaTag::whereProfileId($pid)->delete();

        // Delete DMs
        DirectMessage::whereFromId($pid)->orWhere('to_id', $pid)->delete();
        Conversation::whereFromId($pid)->orWhere('to_id', $pid)->delete();
        app(DirectMessageService::class)->purgeProfile($pid);

        // Delete FollowRequests
        FollowRequest::whereFollowingId($pid)
            ->orWhere('follower_id', $pid)
            ->delete();

        // Delete relationships
        Follower::whereProfileId($pid)
            ->orWhere('following_id', $pid)
            ->delete();

        // Delete likes
        Like::whereProfileId($pid)->forceDelete();

        // Delete Story Views + Stories
        StoryView::whereProfileId($pid)->delete();
        Story::whereProfileId($pid)->cursor()->each(function ($story) {
            $path = storage_path('app/'.$story->path);
            if (is_file($path)) {
                unlink($path);
            }
            $story->forceDelete();
        });

        // Delete mutes/blocks. Per-row delete (not a bulk ->delete()) so the
        // UserFilterObserver fires and UserFilterService::unmute()/unblock()
        // clears each muting/blocking user's Redis cache; a bulk delete would
        // leave stale entries (and inflated mute/block counts) for up to the
        // 90-day cache TTL. chunkById is safe while deleting.
        UserFilter::whereFilterableType(Profile::class)
            ->whereFilterableId($pid)
            ->chunkById(100, function ($filters) {
                foreach ($filters as $filter) {
                    $filter->delete();
                }
            });

        // Delete mentions
        Mention::whereProfileId($pid)->forceDelete();

        // Delete quote approval stamps issued to this actor
        QuoteAuthorization::whereActorId($pid)->delete();

        // Delete notifications. chunkById, not chunk: the loop force-deletes
        // rows, so OFFSET paging would skip half of them. The profile/actor
        // match is grouped so chunkById's appended id constraint ANDs against
        // the whole predicate instead of only the actor_id branch.
        Notification::where(function ($query) use ($pid) {
            $query->where('profile_id', $pid)
                ->orWhere('actor_id', $pid);
        })
            ->chunkById(50, function ($notifications) {
                foreach ($notifications as $n) {
                    $n->forceDelete();
                }
            });

        // Delete reports
        Report::whereProfileId($pid)->orWhere('reported_profile_id', $pid)->forceDelete();

        // Delete profile
        $storedProfile = Profile::withTrashed()->findOrFail($profile->id);
        if (! $storedProfile->trashed()) {
            $storedProfile->delete();
        }

        if ($this->purgeInstanceId !== null) {
            FinalizePurgeInactiveProfileJob::dispatch(
                $pid,
                $this->purgeInstanceId,
                $this->purgeGeneration ?? ''
            )
                ->delay(now()->addMinutes(10))
                ->onQueue('delete');
        }

        return 1;
    }

    public function failed(Throwable $exception): void
    {
        if ($this->purgeInstanceId !== null && $this->profile) {
            InstancePurgeService::recordProfileFailure(
                $this->profile->id,
                $this->purgeInstanceId,
                $this->purgeGeneration ?? '',
                $exception
            );
        }

        Log::error('DeleteRemoteProfilePipeline: exclusão do perfil falhou', [
            'instance_id' => $this->purgeInstanceId,
            'profile_id' => $this->profile?->id,
            'attempt' => $this->attempts(),
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
        ]);
    }
}

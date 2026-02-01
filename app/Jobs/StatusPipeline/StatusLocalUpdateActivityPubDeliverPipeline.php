<?php

namespace App\Jobs\StatusPipeline;

use Illuminate\Support\Facades\Log;
use App\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\UpdateNote;
use App\Jobs\ActivityPub\PubDeliver;


class StatusLocalUpdateActivityPubDeliverPipeline implements ShouldQueue
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
		if (!$status) {
			Log::info("StatusLocalUpdateActivityPubDeliverPipeline: Status no longer exists, skipping job");
			return;
		}

		$profile = $status->profile;
		// Verify profile exists
		if (!$profile) {
			Log::info("StatusLocalUpdateActivityPubDeliverPipeline: Profile no longer exists for status {$status->id}, skipping job");
			return;
		}

		if($status->local == false || $status->url || $status->uri) {
			return;
		}

        $audience = $status->profile->getAudienceInbox($status->scope);

        if (empty($audience) || !in_array($status->scope, ['public', 'unlisted', 'private'])) {
            // Return on profiles with no remote followers
            return;
        }

        switch ($status->type) {
            case 'poll':
                // Polls not yet supported
                return;

            default:
                $activitypubObject = new UpdateNote();
                break;
        }


        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($status, $activitypubObject);
        $activity = $fractal->createData($resource)->toArray();

        $payload = json_encode($activity);

        foreach (array_chunk($audience, 100) as $chunk) {
            PubDeliver::dispatch($activity, $profile, $payload, $chunk)->onQueue('deliver');
        }
    }
}

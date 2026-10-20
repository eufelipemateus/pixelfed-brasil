<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\InstancePipeline\FinalizePurgeInactiveInstanceJob;
use App\Jobs\InstancePipeline\FinalizePurgeInactiveProfileJob;
use App\Jobs\InstancePipeline\PurgeInactiveInstanceJob;
use App\Jobs\InstancePipeline\PurgeInactiveInstancesJob;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Instance;
use App\Models\InstanceModerationRule;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Services\InstanceActivityService;
use App\Services\InstancePurgeService;
use App\Services\InstanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurgeInactiveInstancesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'federation.purge_inactive_after_years' => 2,
            'federation.purge_inactive_grace_days' => 30,
            'federation.purge_inactive_profiles_per_run' => 100,
            'federation.purge_inactive_statuses_per_run' => 100,
            'federation.purge_inactive_media_per_run' => 100,
            'federation.purge_inactive_profile_retention_days' => 0,
            'federation.activitypub.delivery.failure_threshold' => 5,
        ]);
    }

    public function test_active_instance_is_not_selected(): void
    {
        $instance = $this->createInactiveInstance('active.example', [
            'last_active_at' => now(),
            'nodeinfo_last_fetched' => now(),
            'delivery_timeout' => false,
            'delivery_failures' => 0,
        ]);

        $selected = InstancePurgeService::inactiveInstancesQuery()->pluck('id');

        $this->assertFalse($selected->contains($instance->id));
    }

    public function test_instance_with_null_nodeinfo_is_not_selected(): void
    {
        $instance = $this->createInactiveInstance('null-nodeinfo.example', [
            'nodeinfo_last_fetched' => null,
        ]);

        $selected = InstancePurgeService::inactiveInstancesQuery()->pluck('id');

        $this->assertFalse($selected->contains($instance->id));
    }

    public function test_instance_without_observed_federated_activity_is_not_selected(): void
    {
        $instance = $this->createInactiveInstance('unknown-activity.example', [
            'last_active_at' => null,
        ]);

        $selected = InstancePurgeService::inactiveInstancesQuery()->pluck('id');

        $this->assertFalse($selected->contains($instance->id));
    }

    public function test_instance_without_delivery_failures_is_not_selected(): void
    {
        $instance = $this->createInactiveInstance('reachable.example', [
            'delivery_timeout' => false,
            'delivery_failures' => 0,
        ]);

        $selected = InstancePurgeService::inactiveInstancesQuery()->pluck('id');

        $this->assertFalse($selected->contains($instance->id));
    }

    public function test_inactive_instance_is_selected_for_purge(): void
    {
        $instance = $this->createInactiveInstance('inactive.example');

        $selected = InstancePurgeService::inactiveInstancesQuery()->pluck('id');

        $this->assertTrue($selected->contains($instance->id));
    }

    public function test_revalidation_ignores_instance_that_became_active(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('race.example');

        $profileId = $this->insertRemoteProfile('race.example', 'race-user');

        InstanceActivityService::record($instance->domain);

        (new PurgeInactiveInstanceJob($instance->id))->handle();

        Queue::assertNotPushed(DeleteRemoteProfilePipeline::class);
        $this->assertDatabaseHas('profiles', ['id' => $profileId]);
    }

    public function test_first_pass_only_marks_a_candidate(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('candidate.example', [
            'purge_candidate_at' => null,
            'purge_started_at' => null,
        ]);
        $this->insertRemoteProfile('candidate.example', 'candidate-user');

        (new PurgeInactiveInstanceJob($instance->id))->handle();

        $this->assertNotNull($instance->refresh()->purge_candidate_at);
        $this->assertNull($instance->purge_started_at);
        Queue::assertNotPushed(DeleteRemoteProfilePipeline::class);
    }

    public function test_profile_dispatch_is_limited_per_run(): void
    {
        Queue::fake();
        config(['federation.purge_inactive_profiles_per_run' => 2]);

        $instance = $this->createInactiveInstance('bounded.example');
        Profile::factory()->remote()->count(3)->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);

        (new PurgeInactiveInstanceJob($instance->id))->handle();

        Queue::assertPushed(DeleteRemoteProfilePipeline::class, 2);
        Queue::assertPushed(FinalizePurgeInactiveInstanceJob::class, 1);
        $this->assertNotNull($instance->refresh()->purge_started_at);
    }

    public function test_initial_profile_delete_limits_status_fan_out(): void
    {
        Queue::fake();
        config(['federation.purge_inactive_statuses_per_run' => 2]);

        $instance = $this->createInactiveInstance('initial-status-batch.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        Status::factory()->count(3)->create([
            'profile_id' => $profile->id,
            'local' => false,
        ]);
        $generation = $this->claimProfile($profile, $instance);

        (new DeleteRemoteProfilePipeline($profile, $instance->id, $generation))->handle();

        Queue::assertPushed(RemoteStatusDelete::class, 2);
        Queue::assertPushed(FinalizePurgeInactiveProfileJob::class, 1);
        $this->assertNotNull(Profile::withTrashed()->find($profile->id)?->deleted_at);
    }

    public function test_freshly_queued_profile_is_not_dispatched_twice(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('queued.example');
        Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
            'purge_instance_id' => $instance->id,
            'purge_generation' => $instance->purge_generation,
            'purge_queued_at' => now(),
            'purge_retry_count' => 1,
        ]);

        (new PurgeInactiveInstanceJob($instance->id))->handle();

        Queue::assertNotPushed(DeleteRemoteProfilePipeline::class);
    }

    public function test_stale_queued_profile_is_dispatched_again_for_recovery(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('stale-queued.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
            'purge_instance_id' => $instance->id,
            'purge_generation' => $instance->purge_generation,
            'purge_queued_at' => now()->subDays(2),
            'purge_retry_count' => 1,
        ]);

        (new PurgeInactiveInstanceJob($instance->id))->handle();

        Queue::assertPushed(DeleteRemoteProfilePipeline::class, 1);
        $this->assertSame(2, $profile->refresh()->purge_retry_count);
    }

    public function test_activity_cancels_a_candidate_and_resets_delivery_failures(): void
    {
        $instance = $this->createInactiveInstance('resumed.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
            'purge_instance_id' => $instance->id,
            'purge_generation' => $instance->purge_generation,
            'purge_queued_at' => now(),
            'purge_retry_count' => 1,
        ]);

        InstanceActivityService::record($instance->domain);

        $instance->refresh();
        $this->assertTrue($instance->last_active_at->isToday());
        $this->assertNull($instance->purge_candidate_at);
        $this->assertNull($instance->purge_started_at);
        $this->assertFalse((bool) $instance->delivery_timeout);
        $this->assertSame(0, (int) $instance->delivery_failures);
        $this->assertNull($profile->refresh()->purge_instance_id);
        $this->assertNull($instance->purge_generation);
        $this->assertNull($profile->purge_generation);
        $this->assertNull($profile->purge_queued_at);
        $this->assertSame(0, $profile->purge_retry_count);
    }

    public function test_queued_profile_delete_revalidates_before_mutating_data(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('queued-race.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);

        $generation = $this->claimProfile($profile, $instance);

        InstanceActivityService::record($instance->domain);
        (new DeleteRemoteProfilePipeline($profile, $instance->id, $generation))->handle();

        $this->assertDatabaseHas('profiles', [
            'id' => $profile->id,
            'deleted_at' => null,
        ]);
    }

    public function test_finalize_deletes_normal_instance_when_no_profiles_remain(): void
    {
        $instance = $this->createInactiveInstance('normal.example', [
            'banned' => false,
            'unlisted' => false,
            'auto_cw' => false,
            'notes' => null,
        ]);

        (new FinalizePurgeInactiveInstanceJob($instance->id, $instance->purge_generation))->handle();

        $this->assertDatabaseMissing('instances', ['id' => $instance->id]);
        $this->assertDatabaseMissing('instance_moderation_rules', ['domain' => 'normal.example']);
    }

    public function test_banned_instance_preserves_minimal_moderation_rule(): void
    {
        $instance = $this->createInactiveInstance('banned.example', [
            'banned' => true,
            'unlisted' => false,
            'auto_cw' => true,
            'notes' => 'historical moderation note',
        ]);

        (new FinalizePurgeInactiveInstanceJob($instance->id, $instance->purge_generation))->handle();

        $this->assertDatabaseMissing('instances', ['id' => $instance->id]);
        $this->assertDatabaseHas('instance_moderation_rules', [
            'domain' => 'banned.example',
            'banned' => 1,
            'auto_cw' => 1,
        ]);

        InstanceService::refresh();
        $this->assertContains('banned.example', InstanceService::getBannedDomains());
    }

    public function test_instance_is_not_deleted_when_profiles_still_exist(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('partial-failure.example');

        $this->insertRemoteProfile('partial-failure.example', 'keep-user');

        (new FinalizePurgeInactiveInstanceJob($instance->id, $instance->purge_generation))->handle();

        $this->assertDatabaseHas('instances', ['id' => $instance->id]);
    }

    public function test_soft_deleted_profile_prevents_instance_finalization(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('soft-deleted.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $profile->delete();

        (new FinalizePurgeInactiveInstanceJob($instance->id, $instance->purge_generation))->handle();

        $this->assertDatabaseHas('instances', ['id' => $instance->id]);
        $this->assertDatabaseHas('profiles', ['id' => $profile->id]);
    }

    public function test_old_soft_deleted_profile_is_sent_to_recovery(): void
    {
        Queue::fake();
        config(['federation.purge_inactive_profile_retention_days' => 30]);

        $instance = $this->createInactiveInstance('recover-soft-deleted.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $profile->delete();
        DB::table('profiles')->where('id', $profile->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);

        (new PurgeInactiveInstanceJob($instance->id))->handle();

        Queue::assertPushed(FinalizePurgeInactiveProfileJob::class, 1);
        Queue::assertNotPushed(DeleteRemoteProfilePipeline::class);
    }

    public function test_profile_retention_wait_does_not_release_job_repeatedly(): void
    {
        config(['federation.purge_inactive_profile_retention_days' => 30]);

        $instance = $this->createInactiveInstance('retention.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $profile->delete();
        $generation = $this->claimProfile($profile, $instance);

        $job = (new FinalizePurgeInactiveProfileJob($profile->id, $instance->id, $generation))
            ->withFakeQueueInteractions();
        $job->handle();

        $job->assertNotReleased();
        $this->assertNotNull(Profile::withTrashed()->find($profile->id));
    }

    public function test_profile_finalizer_recovers_remaining_status_jobs(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('recover-status.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $status = Status::factory()->create([
            'profile_id' => $profile->id,
            'local' => false,
        ]);
        $profile->delete();
        $generation = $this->claimProfile($profile, $instance);

        (new FinalizePurgeInactiveProfileJob($profile->id, $instance->id, $generation))->handle();

        Queue::assertPushed(RemoteStatusDelete::class, 1);
        $this->assertNotNull(Status::withTrashed()->find($status->id));
        $this->assertNotNull(Profile::withTrashed()->find($profile->id));
    }

    public function test_queued_status_and_media_deletes_stop_when_instance_becomes_active(): void
    {
        $instance = $this->createInactiveInstance('cancel-children.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $status = Status::factory()->create([
            'profile_id' => $profile->id,
            'local' => false,
        ]);
        $media = Media::create([
            'status_id' => null,
            'profile_id' => $profile->id,
            'media_path' => 'public/m/cancelled.jpg',
            'mime' => 'image/jpeg',
            'size' => 1,
            'order' => 1,
        ]);

        $generation = $this->claimProfile($profile, $instance);

        InstanceActivityService::record($instance->domain);

        (new RemoteStatusDelete($status, $instance->id, $generation))->handle();
        (new MediaDeletePipeline($media, $instance->id, $generation))->handle();

        $this->assertNotNull(Status::find($status->id));
        $this->assertNotNull(Media::find($media->id));
    }

    public function test_profile_finalizer_physically_removes_confirmed_profile(): void
    {
        $instance = $this->createInactiveInstance('finalize-profile.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $profile->delete();
        $generation = $this->claimProfile($profile, $instance);

        (new FinalizePurgeInactiveProfileJob($profile->id, $instance->id, $generation))->handle();

        $this->assertNull(Profile::withTrashed()->find($profile->id));
    }

    public function test_profile_finalizer_detaches_and_dispatches_media_with_missing_status(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('orphan-media.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $status = Status::factory()->create([
            'profile_id' => $profile->id,
            'local' => false,
        ]);
        $media = Media::create([
            'status_id' => $status->id,
            'profile_id' => $profile->id,
            'media_path' => 'public/m/orphan.jpg',
            'mime' => 'image/jpeg',
            'size' => 1,
            'order' => 1,
        ]);
        DB::table('statuses')->where('id', $status->id)->delete();
        $profile->delete();
        $generation = $this->claimProfile($profile, $instance);

        (new FinalizePurgeInactiveProfileJob($profile->id, $instance->id, $generation))->handle();

        Queue::assertPushed(MediaDeletePipeline::class, 1);
        Queue::assertPushed(FinalizePurgeInactiveProfileJob::class, 1);
        $this->assertNull($media->refresh()->status_id);
    }

    public function test_profile_finalizer_limits_status_fan_out_and_schedules_next_batch(): void
    {
        Queue::fake();
        config(['federation.purge_inactive_statuses_per_run' => 2]);

        $instance = $this->createInactiveInstance('status-batch.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        Status::factory()->count(3)->create([
            'profile_id' => $profile->id,
            'local' => false,
        ]);
        $profile->delete();
        $generation = $this->claimProfile($profile, $instance);

        (new FinalizePurgeInactiveProfileJob($profile->id, $instance->id, $generation))->handle();

        Queue::assertPushed(RemoteStatusDelete::class, 2);
        Queue::assertPushed(FinalizePurgeInactiveProfileJob::class, 1);
    }

    public function test_profile_finalizer_limits_media_fan_out_and_schedules_next_batch(): void
    {
        Queue::fake();
        config(['federation.purge_inactive_media_per_run' => 2]);

        $instance = $this->createInactiveInstance('media-batch.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);

        foreach (range(1, 3) as $index) {
            Media::create([
                'status_id' => null,
                'profile_id' => $profile->id,
                'media_path' => "public/m/batch-{$index}.jpg",
                'mime' => 'image/jpeg',
                'size' => 1,
                'order' => $index,
            ]);
        }

        $profile->delete();
        $generation = $this->claimProfile($profile, $instance);

        (new FinalizePurgeInactiveProfileJob($profile->id, $instance->id, $generation))->handle();

        Queue::assertPushed(MediaDeletePipeline::class, 2);
        Queue::assertPushed(FinalizePurgeInactiveProfileJob::class, 1);
    }

    public function test_profile_finalizer_recovers_soft_deleted_media_after_interrupted_storage_cleanup(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('trashed-media.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $media = Media::create([
            'status_id' => null,
            'profile_id' => $profile->id,
            'media_path' => 'public/m/interrupted.jpg',
            'mime' => 'image/jpeg',
            'size' => 1,
            'order' => 1,
        ]);
        $media->delete();
        $profile->delete();
        $generation = $this->claimProfile($profile, $instance);

        (new FinalizePurgeInactiveProfileJob($profile->id, $instance->id, $generation))->handle();

        Queue::assertPushed(MediaDeletePipeline::class, 1);
        Queue::assertPushed(FinalizePurgeInactiveProfileJob::class, 1);
    }

    public function test_job_from_previous_generation_cannot_delete_after_purge_restarts(): void
    {
        $instance = $this->createInactiveInstance('generation.example');
        $profile = Profile::factory()->remote()->create([
            'domain' => $instance->domain,
            'private_key' => null,
        ]);
        $status = Status::factory()->create([
            'profile_id' => $profile->id,
            'local' => false,
        ]);
        $oldGeneration = $this->claimProfile($profile, $instance);

        InstanceActivityService::record($instance->domain);
        $instance->refresh();
        $instance->forceFill([
            'last_active_at' => now()->subYears(3),
            'nodeinfo_last_fetched' => now()->subYears(3),
            'delivery_timeout' => true,
            'delivery_failures' => 5,
            'purge_candidate_at' => now()->subDays(31),
            'purge_started_at' => null,
            'purge_generation' => null,
        ])->save();

        $newGeneration = InstancePurgeService::beginPurge($instance->id);
        $this->assertNotNull($newGeneration);
        $this->assertNotSame($oldGeneration, $newGeneration);
        $this->claimProfile($profile, $instance->refresh());

        (new RemoteStatusDelete($status, $instance->id, $oldGeneration))->handle();

        $this->assertNotNull(Status::find($status->id));
    }

    public function test_activity_backfill_is_bounded_and_resumable(): void
    {
        $first = Instance::create(['domain' => 'backfill-one.example']);
        $second = Instance::create(['domain' => 'backfill-two.example']);
        Profile::factory()->remote()->create([
            'domain' => $first->domain,
            'last_status_at' => now()->subYear(),
        ]);
        Profile::factory()->remote()->create([
            'domain' => $second->domain,
            'last_status_at' => now()->subMonths(6),
        ]);

        $this->artisan('instances:backfill-activity --chunk=1 --limit=1')
            ->assertExitCode(0);

        $this->assertNotNull($first->refresh()->last_active_at);
        $this->assertNull($second->refresh()->last_active_at);

        $this->artisan("instances:backfill-activity --chunk=1 --after-id={$first->id}")
            ->assertExitCode(0);

        $this->assertNotNull($second->refresh()->last_active_at);
    }

    public function test_persisted_moderation_is_applied_and_removed_when_unblocked(): void
    {
        InstanceModerationRule::create([
            'domain' => 'recreated.example',
            'banned' => true,
        ]);

        $instance = Instance::create(['domain' => 'recreated.example']);
        $this->assertTrue((bool) $instance->banned);

        $instance->forceFill([
            'banned' => false,
            'unlisted' => false,
            'auto_cw' => false,
            'notes' => null,
        ])->save();

        $this->assertDatabaseMissing('instance_moderation_rules', [
            'domain' => 'recreated.example',
        ]);
    }

    public function test_moderation_backup_includes_rules_without_instance_rows(): void
    {
        InstanceModerationRule::create([
            'domain' => 'rule-only.example',
            'banned' => true,
        ]);
        InstanceService::refresh();

        $response = (new AdminController)->downloadBackup(Request::create('/'));
        ob_start();
        $response->sendContent();
        $json = json_decode((string) ob_get_clean(), true);

        $this->assertContains('rule-only.example', $json['banned']);
    }

    public function test_unique_lock_key_is_stable_for_same_instance(): void
    {
        $jobA = new PurgeInactiveInstanceJob(55);
        $jobB = new PurgeInactiveInstanceJob(55);

        $this->assertSame($jobA->uniqueId(), $jobB->uniqueId());
        $this->assertSame('purge-inactive-instance:55', $jobA->uniqueId());
    }

    public function test_dry_run_does_not_dispatch_destructive_jobs_or_mutate_data(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('dry-run.example');

        $profileId = $this->insertRemoteProfile('dry-run.example', 'dry-user');

        $this->artisan('instances:purge-inactive --dry-run')
            ->assertExitCode(0);

        Queue::assertNotPushed(PurgeInactiveInstancesJob::class);
        Queue::assertNotPushed(PurgeInactiveInstanceJob::class);
        Queue::assertNotPushed(DeleteRemoteProfilePipeline::class);

        $this->assertDatabaseHas('instances', ['id' => $instance->id]);
        $this->assertDatabaseHas('profiles', ['id' => $profileId]);
    }

    public function test_idempotent_resume_after_partial_state(): void
    {
        Queue::fake();

        $instance = $this->createInactiveInstance('idempotent.example', [
            'banned' => true,
            'notes' => 'must persist',
        ]);

        $profileId = $this->insertRemoteProfile('idempotent.example', 'idempotent-user');

        (new FinalizePurgeInactiveInstanceJob($instance->id, $instance->purge_generation))->handle();
        $this->assertDatabaseHas('instances', ['id' => $instance->id]);

        DB::table('profiles')->where('id', $profileId)->delete();

        (new FinalizePurgeInactiveInstanceJob($instance->id, $instance->purge_generation))->handle();
        (new FinalizePurgeInactiveInstanceJob($instance->id, $instance->purge_generation))->handle();

        $this->assertDatabaseMissing('instances', ['id' => $instance->id]);
        $this->assertDatabaseHas('instance_moderation_rules', [
            'domain' => 'idempotent.example',
            'banned' => 1,
        ]);

        $rules = InstanceModerationRule::query()->where('domain', 'idempotent.example')->count();
        $this->assertSame(1, $rules);
    }

    private function insertRemoteProfile(string $domain, string $username): int
    {
        $id = (int) (Carbon::now()->valueOf().random_int(10, 99));

        DB::table('profiles')->insert([
            'id' => $id,
            'user_id' => null,
            'domain' => $domain,
            'username' => $username,
            'name' => $username,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function createInactiveInstance(string $domain, array $attributes = []): Instance
    {
        $instance = Instance::create(['domain' => $domain]);

        $instance->forceFill(array_merge([
            'last_active_at' => now()->subYears(3),
            'nodeinfo_last_fetched' => now()->subYears(3),
            'delivery_timeout' => true,
            'delivery_failures' => 5,
            'purge_candidate_at' => now()->subDays(31),
            'purge_started_at' => now()->subDay(),
            'purge_generation' => (string) Str::uuid(),
        ], $attributes))->save();

        return $instance->refresh();
    }

    private function claimProfile(Profile $profile, Instance $instance): string
    {
        $generation = $instance->purge_generation;

        Profile::withTrashed()->whereKey($profile->id)->update([
            'purge_instance_id' => $instance->id,
            'purge_generation' => $generation,
            'purge_queued_at' => now(),
            'purge_retry_count' => 1,
        ]);

        return $generation;
    }
}

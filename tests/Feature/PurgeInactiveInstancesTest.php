<?php

namespace Tests\Feature;

use App\Instance;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\InstancePipeline\FinalizePurgeInactiveInstanceJob;
use App\Jobs\InstancePipeline\PurgeInactiveInstanceJob;
use App\Jobs\InstancePipeline\PurgeInactiveInstancesJob;
use App\Models\InstanceModerationRule;
use App\Services\InstancePurgeService;
use App\Services\InstanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PurgeInactiveInstancesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['federation.purge_inactive_after_years' => 2]);
    }

    public function testActiveInstanceIsNotSelected(): void
    {
        $instance = Instance::query()->create([
            'domain' => 'active.example',
            'nodeinfo_last_fetched' => now(),
        ]);

        $selected = InstancePurgeService::inactiveInstancesQuery()->pluck('id');

        $this->assertFalse($selected->contains($instance->id));
    }

    public function testInstanceWithNullNodeinfoIsNotSelected(): void
    {
        $instance = Instance::query()->create([
            'domain' => 'null-nodeinfo.example',
            'nodeinfo_last_fetched' => null,
            'created_at' => now()->subYears(8),
            'updated_at' => now()->subYears(8),
        ]);

        $selected = InstancePurgeService::inactiveInstancesQuery()->pluck('id');

        $this->assertFalse($selected->contains($instance->id));
    }

    public function testInactiveInstanceIsSelectedForPurge(): void
    {
        $instance = Instance::query()->create([
            'domain' => 'inactive.example',
            'nodeinfo_last_fetched' => now()->subYears(3),
        ]);

        $selected = InstancePurgeService::inactiveInstancesQuery()->pluck('id');

        $this->assertTrue($selected->contains($instance->id));
    }

    public function testRevalidationIgnoresInstanceThatBecameActive(): void
    {
        Queue::fake();

        $instance = Instance::query()->create([
            'domain' => 'race.example',
            'nodeinfo_last_fetched' => now()->subYears(3),
        ]);

        $profileId = $this->insertRemoteProfile('race.example', 'race-user');

        $instance->nodeinfo_last_fetched = now();
        $instance->save();

        (new PurgeInactiveInstanceJob($instance->id))->handle();

        Queue::assertNotPushed(DeleteRemoteProfilePipeline::class);
        $this->assertDatabaseHas('profiles', ['id' => $profileId]);
    }

    public function testFinalizeDeletesNormalInstanceWhenNoProfilesRemain(): void
    {
        $instance = Instance::query()->create([
            'domain' => 'normal.example',
            'nodeinfo_last_fetched' => now()->subYears(4),
            'banned' => false,
            'unlisted' => false,
            'auto_cw' => false,
            'notes' => null,
        ]);

        (new FinalizePurgeInactiveInstanceJob($instance->id))->handle();

        $this->assertDatabaseMissing('instances', ['id' => $instance->id]);
        $this->assertDatabaseMissing('instance_moderation_rules', ['domain' => 'normal.example']);
    }

    public function testBannedInstancePreservesMinimalModerationRule(): void
    {
        $instance = Instance::query()->create([
            'domain' => 'banned.example',
            'nodeinfo_last_fetched' => now()->subYears(4),
            'banned' => true,
            'unlisted' => false,
            'auto_cw' => true,
            'notes' => 'historical moderation note',
        ]);

        (new FinalizePurgeInactiveInstanceJob($instance->id))->handle();

        $this->assertDatabaseMissing('instances', ['id' => $instance->id]);
        $this->assertDatabaseHas('instance_moderation_rules', [
            'domain' => 'banned.example',
            'banned' => 1,
            'auto_cw' => 1,
        ]);

        InstanceService::refresh();
        $this->assertContains('banned.example', InstanceService::getBannedDomains());
    }

    public function testInstanceIsNotDeletedWhenProfilesStillExist(): void
    {
        $instance = Instance::query()->create([
            'domain' => 'partial-failure.example',
            'nodeinfo_last_fetched' => now()->subYears(4),
        ]);

        $this->insertRemoteProfile('partial-failure.example', 'keep-user');

        (new FinalizePurgeInactiveInstanceJob($instance->id))->handle();

        $this->assertDatabaseHas('instances', ['id' => $instance->id]);
    }

    public function testUniqueLockKeyIsStableForSameInstance(): void
    {
        $jobA = new PurgeInactiveInstanceJob(55);
        $jobB = new PurgeInactiveInstanceJob(55);

        $this->assertSame($jobA->uniqueId(), $jobB->uniqueId());
        $this->assertSame('purge-inactive-instance:55', $jobA->uniqueId());
    }

    public function testDryRunDoesNotDispatchDestructiveJobsOrMutateData(): void
    {
        Queue::fake();

        $instance = Instance::query()->create([
            'domain' => 'dry-run.example',
            'nodeinfo_last_fetched' => now()->subYears(3),
        ]);

        $profileId = $this->insertRemoteProfile('dry-run.example', 'dry-user');

        $this->artisan('instances:purge-inactive --dry-run')
            ->assertExitCode(0);

        Queue::assertNotPushed(PurgeInactiveInstancesJob::class);
        Queue::assertNotPushed(PurgeInactiveInstanceJob::class);
        Queue::assertNotPushed(DeleteRemoteProfilePipeline::class);

        $this->assertDatabaseHas('instances', ['id' => $instance->id]);
        $this->assertDatabaseHas('profiles', ['id' => $profileId]);
    }

    public function testIdempotentResumeAfterPartialState(): void
    {
        $instance = Instance::query()->create([
            'domain' => 'idempotent.example',
            'nodeinfo_last_fetched' => now()->subYears(4),
            'banned' => true,
            'notes' => 'must persist',
        ]);

        $profileId = $this->insertRemoteProfile('idempotent.example', 'idempotent-user');

        (new FinalizePurgeInactiveInstanceJob($instance->id))->handle();
        $this->assertDatabaseHas('instances', ['id' => $instance->id]);

        DB::table('profiles')->where('id', $profileId)->delete();

        (new FinalizePurgeInactiveInstanceJob($instance->id))->handle();
        (new FinalizePurgeInactiveInstanceJob($instance->id))->handle();

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
        $id = (int) (Carbon::now()->valueOf() . random_int(10, 99));

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
}

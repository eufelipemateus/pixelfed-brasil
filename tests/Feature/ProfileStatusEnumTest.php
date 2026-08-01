<?php

namespace Tests\Feature;

use App\Enums\StatusEnums;
use App\Profile;
use App\Services\ActivityPubDeliveryService;
use App\Util\ActivityPub\Outbox;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProfileStatusEnumTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('username')->unique();
                $table->string('name')->nullable();
                $table->string('domain')->nullable();
                $table->string('remote_url')->nullable();
                $table->boolean('is_private')->default(false);
                $table->unsignedInteger('status_count')->default(0);
                $table->unsignedInteger('following_count')->default(0);
                $table->unsignedInteger('followers_count')->default(0);
                $table->text('private_key')->nullable();
                $table->text('public_key')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('moved_to_profile_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('statuses')) {
            Schema::create('statuses', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->unsignedBigInteger('profile_id');
                $table->string('scope')->default('public');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('avatars')) {
            Schema::create('avatars', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->unsignedBigInteger('profile_id');
                $table->string('media_path')->nullable();
                $table->string('remote_url')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_null_database_status_is_exposed_as_active_and_all_states_round_trip(): void
    {
        $profile = $this->createProfile(['status' => null]);

        $this->assertSame(StatusEnums::ACTIVE, $profile->fresh()->status);

        foreach ([StatusEnums::DISABLED, StatusEnums::SUSPENDED, StatusEnums::DELETE_QUEUE, StatusEnums::DELETED, StatusEnums::ACTIVE] as $status) {
            $profile->status = $status;
            $profile->save();

            $this->assertSame($status, $profile->fresh()->status);
            $this->assertSame($status->value(), $profile->fresh()->getRawOriginal('status'));
        }
    }

    public function test_active_local_profile_can_queue_activitypub_delivery(): void
    {
        config(['app.env' => 'testing']);
        $profile = $this->createProfile(['status' => null]);

        $this->assertNull(ActivityPubDeliveryService::queue()
            ->from($profile)
            ->to('https://example.com/inbox')
            ->payload(['type' => 'Follow'])
            ->send());
    }

    public function test_inactive_local_profiles_cannot_queue_activitypub_delivery(): void
    {
        foreach ([StatusEnums::DISABLED, StatusEnums::SUSPENDED, StatusEnums::DELETE_QUEUE, StatusEnums::DELETED] as $status) {
            $profile = $this->createProfile(['status' => $status]);

            try {
                ActivityPubDeliveryService::queue()->from($profile)->to('https://example.com/inbox')->payload(['type' => 'Follow'])->send();
                $this->fail("{$status->name} profile was allowed to deliver");
            } catch (HttpException $exception) {
                $this->assertSame(400, $exception->getStatusCode());
            }
        }
    }

    public function test_active_profile_outbox_does_not_enter_blocked_account_validation(): void
    {
        config([
            'instance.enable_cc' => false,
            'federation.activitypub.enabled' => true,
            'federation.activitypub.outbox' => true,
        ]);
        $profile = $this->createProfile(['status' => null]);

        $outbox = Outbox::get($profile);

        $this->assertSame('OrderedCollection', $outbox['type']);
    }

    public function test_webfinger_accepts_active_profile_and_rejects_inactive_profiles(): void
    {
        config(['federation.webfinger.enabled' => true, 'pixelfed.domain.app' => 'pixelfed.test']);
        $active = $this->createProfile(['username' => 'active_user', 'status' => null]);

        $this->getJson(route('well-known.webfinger', ['resource' => 'https://pixelfed.test/users/'.$active->username]))
            ->assertOk();

        foreach ([StatusEnums::DISABLED, StatusEnums::SUSPENDED, StatusEnums::DELETE_QUEUE, StatusEnums::DELETED] as $index => $status) {
            $profile = $this->createProfile(['username' => 'blocked_'.$index, 'status' => $status]);
            $this->getJson(route('well-known.webfinger', ['resource' => 'https://pixelfed.test/users/'.$profile->username]))
                ->assertStatus(400);
        }
    }

    private function createProfile(array $attributes = []): Profile
    {
        return Profile::create(array_merge([
            'username' => 'status_'.str()->random(12),
            'domain' => null,
            'is_private' => false,
            'status_count' => 0,
            'following_count' => 0,
            'followers_count' => 0,
        ], $attributes));
    }
}

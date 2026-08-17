<?php

namespace Tests\Unit\ActivityPub;

use App\Jobs\HomeFeedPipeline\FeedInsertRemotePipeline;
use App\Profile;
use App\Services\HomeTimelineService;
use App\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedInsertRemotePipelineArticleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_inserts_remote_article_text_status_into_local_followers_home_feed(): void
    {
        config(['pixelfed.domain.app' => 'pixelfed.test']);

        $author = Profile::create([
            'user_id' => null,
            'domain' => 'video.example',
            'username' => 'author_remote',
            'remote_url' => 'https://video.example/users/author_remote',
            'sharedInbox' => 'https://video.example/inbox',
            'inbox_url' => 'https://video.example/users/author_remote/inbox',
            'outbox_url' => 'https://video.example/users/author_remote/outbox',
            'key_id' => 'https://video.example/users/author_remote#main-key',
            'public_key' => 'test-public-key',
            'followers_count' => 1,
            'following_count' => 1,
            'last_fetched_at' => now(),
            'webfinger' => 'author_remote@video.example',
        ]);

        $localFollower = Profile::create([
            'user_id' => 123,
            'domain' => null,
            'username' => 'local_follower',
            'last_fetched_at' => now(),
            'webfinger' => 'local_follower@pixelfed.test',
        ]);

        DB::table('followers')->insert([
            'profile_id' => $localFollower->id,
            'following_id' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $status = Status::create([
            'profile_id' => $author->id,
            'uri' => 'https://video.example/articles/10/read',
            'url' => 'https://video.example/articles/10/read',
            'object_url' => 'https://video.example/articles/10',
            'local' => false,
            'visibility' => 'public',
            'scope' => 'public',
            'caption' => 'article status',
            'rendered' => 'article status',
            'type' => 'text',
            'entities' => json_encode(['ap_object_type' => 'article']),
        ]);

        Cache::forget('profile:following:'.$localFollower->id);
        Cache::forget('pf:services:follow:local-follower-ids:v1:'.$author->id);
        Redis::del(HomeTimelineService::CACHE_KEY.$localFollower->id);

        (new FeedInsertRemotePipeline($status->id, $author->id))->handle();

        $homeIds = HomeTimelineService::get($localFollower->id, 0, 10);
        $this->assertContains((string) $status->id, $homeIds);
    }

    #[Test]
    public function it_does_not_insert_plain_remote_text_status_without_article_marker(): void
    {
        config(['pixelfed.domain.app' => 'pixelfed.test']);

        $author = Profile::create([
            'user_id' => null,
            'domain' => 'video.example',
            'username' => 'author_plain_remote',
            'remote_url' => 'https://video.example/users/author_plain_remote',
            'sharedInbox' => 'https://video.example/inbox',
            'inbox_url' => 'https://video.example/users/author_plain_remote/inbox',
            'outbox_url' => 'https://video.example/users/author_plain_remote/outbox',
            'key_id' => 'https://video.example/users/author_plain_remote#main-key',
            'public_key' => 'test-public-key',
            'followers_count' => 1,
            'following_count' => 1,
            'last_fetched_at' => now(),
            'webfinger' => 'author_plain_remote@video.example',
        ]);

        $localFollower = Profile::create([
            'user_id' => 124,
            'domain' => null,
            'username' => 'local_follower_2',
            'last_fetched_at' => now(),
            'webfinger' => 'local_follower_2@pixelfed.test',
        ]);

        DB::table('followers')->insert([
            'profile_id' => $localFollower->id,
            'following_id' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $status = Status::create([
            'profile_id' => $author->id,
            'uri' => 'https://video.example/statuses/plain-1',
            'url' => 'https://video.example/statuses/plain-1',
            'object_url' => 'https://video.example/statuses/plain-1',
            'local' => false,
            'visibility' => 'public',
            'scope' => 'public',
            'caption' => 'plain text status',
            'rendered' => 'plain text status',
            'type' => 'text',
            'entities' => json_encode(['ap_object_type' => 'note']),
        ]);

        Cache::forget('profile:following:'.$localFollower->id);
        Cache::forget('pf:services:follow:local-follower-ids:v1:'.$author->id);
        Redis::del(HomeTimelineService::CACHE_KEY.$localFollower->id);

        (new FeedInsertRemotePipeline($status->id, $author->id))->handle();

        $homeIds = HomeTimelineService::get($localFollower->id, 0, 10);
        $this->assertNotContains((string) $status->id, $homeIds);
    }
}

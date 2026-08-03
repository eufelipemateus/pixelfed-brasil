<?php

namespace Tests\Unit\ActivityPub;

use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Profile;
use App\Services\InstanceService;
use App\Status;
use App\Util\ActivityPub\Handlers\CreateHandler;
use App\Util\ActivityPub\Handlers\DeleteHandler;
use App\Util\ActivityPub\Handlers\UpdateHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateObjectIngestionTest extends TestCase
{
    use RefreshDatabase;

    private Profile $actor;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pixelfed.media_types' => 'image/jpeg,image/png,video/mp4,video/webm',
            'pixelfed.max_album_length' => 4,
            'pixelfed.domain.app' => 'pixelfed.test',
            'autospam.live_filters.enabled' => false,
            'federation.activitypub.ingest.store_notes_without_followers' => true,
        ]);

        $this->actor = Profile::create([
            'user_id' => null,
            'domain' => 'video.example',
            'username' => 'alice_remote',
            'remote_url' => 'https://video.example/users/alice',
            'sharedInbox' => 'https://video.example/inbox',
            'inbox_url' => 'https://video.example/users/alice/inbox',
            'outbox_url' => 'https://video.example/users/alice/outbox',
            'key_id' => 'https://video.example/users/alice#main-key',
            'public_key' => 'test-public-key',
            'followers_count' => 10,
            'following_count' => 3,
            'last_fetched_at' => now(),
            'webfinger' => 'alice@video.example',
        ]);
    }

    #[Test]
    public function it_ingests_public_video_from_create_object_type_video(): void
    {
        $payload = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/videos/123',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'content' => '<p>Video federado <script>alert(1)</script></p>',
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'video/mp4',
                        'url' => 'https://cdn.video.example/media/123.mp4',
                        'name' => 'Descricao alternativa',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payload))->handle();

        $status = Status::where('object_url', 'https://video.example/videos/123')->first();

        $this->assertNotNull($status);
        $this->assertFalse((bool) $status->local);
        $this->assertSame('public', $status->visibility);
        $this->assertSame($this->actor->id, $status->profile_id);
        $this->assertSame('video', $status->type);
        $this->assertStringContainsString('Video federado', $status->caption);
        $this->assertStringNotContainsString('<script>', $status->rendered);
        $this->assertTrue(Carbon::parse('2026-08-01T12:00:00Z')->equalTo($status->created_at->copy()->tz('UTC')));

        $this->assertCount(1, $status->media);
        $media = $status->media->first();
        $this->assertTrue((bool) $media->remote_media);
        $this->assertSame('https://cdn.video.example/media/123.mp4', $media->media_path);
        $this->assertSame('https://cdn.video.example/media/123.mp4', $media->remote_url);
        $this->assertSame('video/mp4', $media->mime);
    }

    #[Test]
    public function it_uses_html_link_as_canonical_url_and_video_link_as_media(): void
    {
        $payload = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/videos/abc',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'content' => 'Link list video',
                'url' => [
                    [
                        'type' => 'Link',
                        'mediaType' => 'text/html',
                        'href' => 'https://video.example/w/123',
                    ],
                    [
                        'type' => 'Link',
                        'mediaType' => 'video/mp4',
                        'href' => 'https://cdn.video.example/123.mp4',
                    ],
                ],
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'video/mp4',
                        'url' => 'https://cdn.video.example/123.mp4',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payload))->handle();

        $status = Status::where('object_url', 'https://video.example/videos/abc')->first();

        $this->assertNotNull($status);
        $this->assertSame('https://video.example/w/123', $status->uri);
        $this->assertSame('https://video.example/w/123', $status->url);
        $this->assertCount(1, $status->media);
        $this->assertSame('https://cdn.video.example/123.mp4', $status->media->first()->remote_url);
    }

    #[Test]
    public function it_ingests_article_as_text_with_content_fallback_and_unlisted_scope(): void
    {
        $payload = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/articles/1',
                'type' => 'Article',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://video.example/users/alice/followers'],
                'cc' => ['https://www.w3.org/ns/activitystreams#Public'],
                'content' => null,
                'summary' => '<b>Resumo</b>',
                'name' => 'Titulo',
                'attachment' => [
                    [
                        'type' => 'Document',
                        'mediaType' => 'text/html',
                        'url' => 'https://video.example/articles/1/read',
                    ],
                ],
                'url' => [
                    [
                        'type' => 'Link',
                        'mediaType' => 'text/html',
                        'href' => 'https://video.example/articles/1/read',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payload))->handle();

        $status = Status::where('object_url', 'https://video.example/articles/1')->first();

        $this->assertNotNull($status);
        $this->assertSame('text', $status->type);
        $this->assertSame('unlisted', $status->visibility);
        $this->assertStringContainsString('Resumo', $status->caption);
        $this->assertCount(0, $status->media);
    }

    #[Test]
    public function it_ignores_non_public_article_instead_of_forcing_unlisted(): void
    {
        $payload = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/articles/private',
                'type' => 'Article',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://video.example/users/alice/followers'],
                'cc' => ['https://video.example/users/bob'],
                'content' => 'privado',
                'attachment' => [
                    [
                        'type' => 'Document',
                        'mediaType' => 'text/html',
                        'url' => 'https://video.example/articles/private/read',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payload))->handle();

        $this->assertNull(Status::where('object_url', 'https://video.example/articles/private')->first());
    }

    #[Test]
    public function it_rejects_actor_and_attributed_to_mismatch(): void
    {
        $payload = $this->makeCreatePayload([
            'actor' => 'https://video.example/users/alice',
            'object' => [
                'id' => 'https://video.example/videos/mismatch',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/bob',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'video/mp4',
                        'url' => 'https://cdn.video.example/media/mismatch.mp4',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payload))->handle();

        $this->assertNull(Status::where('object_url', 'https://video.example/videos/mismatch')->first());
    }

    #[Test]
    public function it_is_idempotent_for_duplicate_create_payload(): void
    {
        $payload = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/videos/dup',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'video/mp4',
                        'url' => 'https://cdn.video.example/media/dup.mp4',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payload))->handle();
        (new CreateHandler([], null, $payload))->handle();

        $this->assertSame(1, Status::where('object_url', 'https://video.example/videos/dup')->count());
        $status = Status::where('object_url', 'https://video.example/videos/dup')->first();
        $this->assertSame(1, $status->media()->count());
    }

    #[Test]
    public function it_rejects_invalid_urls_and_banned_domains_without_persisting(): void
    {
        DB::table('instances')->insert([
            'domain' => 'blocked.example',
            'url' => 'https://blocked.example',
            'name' => 'Blocked Example',
            'admin_url' => 'https://blocked.example/about',
            'limit_reason' => null,
            'unlisted' => false,
            'auto_cw' => false,
            'banned' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        InstanceService::refresh();

        $cases = [
            [
                'id' => 'http://video.example/videos/invalid',
                'url' => 'https://video.example/w/invalid',
            ],
            [
                'id' => 'file://video.example/videos/invalid',
                'url' => 'https://video.example/w/invalid-file',
            ],
            [
                'id' => 'javascript:alert(1)',
                'url' => 'https://video.example/w/invalid-js',
            ],
            [
                'id' => 'https://localhost/videos/invalid',
                'url' => 'https://video.example/w/invalid2',
            ],
            [
                'id' => 'https://127.0.0.1/videos/invalid',
                'url' => 'https://video.example/w/invalid3',
            ],
            [
                'id' => 'https://blocked.example/videos/invalid',
                'url' => 'https://blocked.example/w/invalid',
            ],
        ];

        foreach ($cases as $index => $case) {
            $payload = $this->makeCreatePayload([
                'object' => [
                    'id' => $case['id'],
                    'type' => 'Video',
                    'attributedTo' => 'https://video.example/users/alice',
                    'published' => '2026-08-01T12:00:00Z',
                    'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                    'url' => $case['url'],
                    'attachment' => [
                        [
                            'type' => 'Video',
                            'mediaType' => 'video/mp4',
                            'url' => 'https://cdn.video.example/media/url-invalid-'.$index.'.mp4',
                        ],
                    ],
                ],
            ]);

            (new CreateHandler([], null, $payload))->handle();
        }

        $this->assertSame(0, Status::where('uri', 'like', '%invalid%')->count());
        $this->assertSame(0, Status::where('object_url', 'like', '%blocked.example%')->count());
    }

    #[Test]
    public function it_rejects_video_when_media_mime_is_invalid(): void
    {
        $payload = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/videos/mime-invalid',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'text/html',
                        'url' => 'https://cdn.video.example/media/mime-invalid',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payload))->handle();

        $this->assertNull(Status::where('object_url', 'https://video.example/videos/mime-invalid')->first());
    }

    #[Test]
    public function it_does_not_treat_html_url_without_mediatype_as_media(): void
    {
        $payload = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/videos/no-media-from-url',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'url' => [
                    [
                        'type' => 'Link',
                        'href' => 'https://video.example/watch?v=123.mp4',
                    ],
                ],
                'attachment' => [
                    [
                        'type' => 'Document',
                        'mediaType' => 'text/html',
                        'url' => 'https://video.example/watch?v=123.mp4',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payload))->handle();

        $this->assertNull(Status::where('object_url', 'https://video.example/videos/no-media-from-url')->first());
    }

    #[Test]
    public function it_rejects_when_published_is_invalid_or_too_far_in_future(): void
    {
        $payloadInvalid = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/videos/invalid-published',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => 'invalid-date',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'video/mp4',
                        'url' => 'https://cdn.video.example/media/invalid-date.mp4',
                    ],
                ],
            ],
        ]);

        $payloadFuture = $this->makeCreatePayload([
            'object' => [
                'id' => 'https://video.example/videos/future-published',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => now()->addDays(2)->toIso8601String(),
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'video/mp4',
                        'url' => 'https://cdn.video.example/media/future-date.mp4',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $payloadInvalid))->handle();
        (new CreateHandler([], null, $payloadFuture))->handle();

        $this->assertNull(Status::where('object_url', 'https://video.example/videos/invalid-published')->first());
        $this->assertNull(Status::where('object_url', 'https://video.example/videos/future-published')->first());
    }

    #[Test]
    public function imported_video_status_is_locatable_for_update_and_delete_handlers(): void
    {
        Queue::fake();

        $objectId = 'https://video.example/videos/compat-1';

        $createPayload = $this->makeCreatePayload([
            'id' => 'https://video.example/activities/create-compat-1',
            'object' => [
                'id' => $objectId,
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'video/mp4',
                        'url' => 'https://cdn.video.example/media/compat-1.mp4',
                    ],
                ],
            ],
        ]);

        (new CreateHandler([], null, $createPayload))->handle();

        $status = Status::whereObjectUrl($objectId)->first();
        $this->assertNotNull($status);

        $updatePayload = [
            'type' => 'Update',
            'actor' => 'https://video.example/users/alice',
            'object' => [
                'id' => $objectId,
                'type' => 'Note',
            ],
        ];

        (new UpdateHandler([], null, $updatePayload))->handle();
        Queue::assertPushed(StatusRemoteUpdatePipeline::class);

        $deletePayload = [
            'type' => 'Delete',
            'actor' => 'https://video.example/users/alice',
            'object' => [
                'id' => $objectId,
                'type' => 'Tombstone',
            ],
        ];

        (new DeleteHandler([], null, $deletePayload))->handle();
        Queue::assertPushed(RemoteStatusDelete::class);

        $this->assertNotNull(Status::where('id', $status->id)->first());
    }

    private function makeCreatePayload(array $override = []): array
    {
        $base = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://video.example/activities/create-1',
            'type' => 'Create',
            'actor' => 'https://video.example/users/alice',
            'object' => [
                'id' => 'https://video.example/videos/default',
                'type' => 'Video',
                'attributedTo' => 'https://video.example/users/alice',
                'published' => '2026-08-01T12:00:00Z',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'cc' => ['https://video.example/users/alice/followers'],
                'content' => 'default',
                'url' => 'https://video.example/w/default',
                'attachment' => [
                    [
                        'type' => 'Video',
                        'mediaType' => 'video/mp4',
                        'url' => 'https://cdn.video.example/media/default.mp4',
                    ],
                ],
            ],
        ];

        return array_replace_recursive($base, $override);
    }
}

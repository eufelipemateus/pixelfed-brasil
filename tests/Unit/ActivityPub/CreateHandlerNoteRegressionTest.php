<?php

namespace Tests\Unit\ActivityPub;

use App\Profile;
use App\Status;
use App\Util\ActivityPub\Handlers\CreateHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateHandlerNoteRegressionTest extends TestCase
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
            'domain' => 'mastodon.example',
            'username' => 'note_remote',
            'remote_url' => 'https://mastodon.example/users/note_remote',
            'sharedInbox' => 'https://mastodon.example/inbox',
            'inbox_url' => 'https://mastodon.example/users/note_remote/inbox',
            'outbox_url' => 'https://mastodon.example/users/note_remote/outbox',
            'key_id' => 'https://mastodon.example/users/note_remote#main-key',
            'public_key' => 'test-public-key',
            'followers_count' => 10,
            'following_count' => 2,
            'last_fetched_at' => now(),
            'webfinger' => 'note_remote@mastodon.example',
        ]);
    }

    #[Test]
    public function it_keeps_note_with_image_attachment_working(): void
    {
        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://mastodon.example/activities/note-create-1',
            'type' => 'Create',
            'actor' => 'https://mastodon.example/users/note_remote',
            'object' => [
                'id' => 'https://mastodon.example/statuses/101',
                'type' => 'Note',
                'published' => '2026-08-01T12:00:00Z',
                'attributedTo' => 'https://mastodon.example/users/note_remote',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'cc' => ['https://mastodon.example/users/note_remote/followers'],
                'content' => 'Nota com imagem',
                'attachment' => [
                    [
                        'type' => 'Document',
                        'mediaType' => 'image/jpeg',
                        'url' => 'https://cdn.mastodon.example/media/101.jpg',
                    ],
                ],
            ],
        ];

        (new CreateHandler([], null, $payload))->handle();

        $status = Status::where('object_url', 'https://mastodon.example/statuses/101')->first();

        $this->assertNotNull($status);
        $this->assertFalse((bool) $status->local);
        $this->assertSame('public', $status->visibility);
        $this->assertCount(1, $status->media);
        $this->assertSame('image/jpeg', $status->media->first()->mime);
    }

    #[Test]
    public function it_keeps_note_reply_path_working(): void
    {
        $original = Status::create([
            'profile_id' => $this->actor->id,
            'uri' => 'https://mastodon.example/statuses/original',
            'url' => 'https://mastodon.example/statuses/original',
            'object_url' => 'https://mastodon.example/statuses/original',
            'local' => false,
            'visibility' => 'public',
            'scope' => 'public',
            'caption' => 'original',
            'rendered' => 'original',
            'type' => 'text',
        ]);

        $replyUri = 'https://mastodon.example/statuses/reply-1';
        $existingReply = Status::create([
            'profile_id' => $this->actor->id,
            'uri' => $replyUri,
            'url' => $replyUri,
            'object_url' => $replyUri,
            'local' => false,
            'visibility' => 'public',
            'scope' => 'public',
            'caption' => 'reply-existing',
            'rendered' => 'reply-existing',
            'type' => 'text',
        ]);

        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://mastodon.example/activities/note-reply-1',
            'type' => 'Create',
            'actor' => 'https://mastodon.example/users/note_remote',
            'object' => [
                'id' => $replyUri,
                'type' => 'Note',
                'published' => '2026-08-01T13:00:00Z',
                'attributedTo' => 'https://mastodon.example/users/note_remote',
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'cc' => ['https://mastodon.example/users/note_remote/followers'],
                'inReplyTo' => $original->uri,
                'content' => 'reply',
            ],
        ];

        (new CreateHandler([], null, $payload))->handle();

        $this->assertNotNull(Status::where('object_url', $replyUri)->first());
        $this->assertSame($existingReply->id, Status::where('object_url', $replyUri)->first()->id);
    }
}

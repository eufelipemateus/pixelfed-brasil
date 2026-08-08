<?php

namespace Tests\Feature;

use App\DirectMessage;
use App\Models\Conversation;
use App\Profile;
use App\Services\DirectMessageService;
use App\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectMessageIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_between_profiles_applies_cursor_to_both_directions(): void
    {
        [$first, $second] = $this->makeProfiles();
        $ids = [];

        foreach (range(1, 24) as $index) {
            $from = $index % 2 ? $first : $second;
            $to = $index % 2 ? $second : $first;
            $ids[] = $this->makeDm($from, $to)->id;
        }

        $firstPage = DirectMessage::betweenProfiles($first->id, $second->id)
            ->orderByDesc('id')->limit(8)->pluck('id')->all();
        $secondPage = DirectMessage::betweenProfiles($first->id, $second->id)
            ->where('id', '<', min($firstPage))
            ->orderByDesc('id')->limit(8)->pluck('id')->all();
        $newer = DirectMessage::betweenProfiles($first->id, $second->id)
            ->where('id', '>', max($secondPage))
            ->orderBy('id')->pluck('id')->all();

        $this->assertSame(array_reverse(array_slice($ids, 16, 8)), $firstPage);
        $this->assertSame(array_reverse(array_slice($ids, 8, 8)), $secondPage);
        $this->assertSame(array_slice($ids, 16, 8), $newer);
        $this->assertSame([], array_values(array_intersect($firstPage, $secondPage)));
    }

    public function test_deleting_latest_dm_reconciles_conversation_and_deleting_all_removes_it(): void
    {
        [$first, $second] = $this->makeProfiles();
        $older = $this->makeDm($first, $second);
        $latest = $this->makeDm($first, $second);
        DirectMessageService::updateConversation($latest);

        DirectMessageService::deleteDm($latest);

        $conversation = Conversation::whereFromId($first->id)->whereToId($second->id)->firstOrFail();
        $this->assertSame((string) $older->id, (string) $conversation->dm_id);
        $this->assertSame((string) $older->status_id, (string) $conversation->status_id);

        DirectMessageService::deleteDm($older);
        $this->assertFalse(Conversation::whereFromId($first->id)->whereToId($second->id)->exists());
    }

    public function test_inbox_limit_is_applied_after_conversation_deduplication(): void
    {
        $owner = $this->makeProfile('owner');
        $senders = collect(['a', 'b', 'c', 'd'])->map(fn ($name) => $this->makeProfile($name));

        foreach ([50, 10, 10, 10] as $index => $count) {
            foreach (range(1, $count) as $unused) {
                $this->makeDm($senders[$index], $owner);
            }
        }

        $ranked = DirectMessage::query()
            ->select('direct_messages.*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY from_id ORDER BY id DESC) AS conversation_rank')
            ->whereToId($owner->id)
            ->whereIsHidden(false)
            ->whereHas('status');

        $conversations = DirectMessage::query()
            ->fromSub($ranked, 'direct_messages')
            ->where('conversation_rank', 1)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $this->assertCount(4, $conversations);
        $this->assertCount(4, $conversations->unique('from_id'));
    }

    public function test_repair_command_reports_then_removes_orphan_dm(): void
    {
        [$first, $second] = $this->makeProfiles();
        $dm = $this->makeDm($first, $second);
        DirectMessageService::updateConversation($dm);
        Status::whereKey($dm->status_id)->forceDelete();

        $this->artisan('pixelfed:repair-direct-messages --dry-run')->assertSuccessful();
        $this->assertDatabaseHas('direct_messages', ['id' => $dm->id]);

        $this->artisan('pixelfed:repair-direct-messages')->assertSuccessful();
        $this->assertDatabaseMissing('direct_messages', ['id' => $dm->id]);
        $this->assertDatabaseMissing('conversations', ['from_id' => $first->id, 'to_id' => $second->id]);
    }

    private function makeDm(Profile $from, Profile $to): DirectMessage
    {
        $status = new Status;
        $status->profile_id = $from->id;
        $status->caption = 'test message';
        $status->rendered = 'test message';
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $to->id;
        $status->save();

        $dm = new DirectMessage;
        $dm->from_id = $from->id;
        $dm->to_id = $to->id;
        $dm->status_id = $status->id;
        $dm->type = 'text';
        $dm->is_hidden = false;
        $dm->save();

        return $dm;
    }

    private function makeProfiles(): array
    {
        return [$this->makeProfile('first'), $this->makeProfile('second')];
    }

    private function makeProfile(string $name): Profile
    {
        $profile = new Profile;
        $profile->username = 'dm-'.$name.'-'.bin2hex(random_bytes(4));
        $profile->save();

        return $profile;
    }
}

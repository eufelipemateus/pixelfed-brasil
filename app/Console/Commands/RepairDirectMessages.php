<?php

namespace App\Console\Commands;

use App\DirectMessage;
use App\Models\Conversation;
use App\Services\DirectMessageService;
use Illuminate\Console\Command;

class RepairDirectMessages extends Command
{
    protected $signature = 'pixelfed:repair-direct-messages {--dry-run : Report inconsistencies without changing data}';

    protected $description = 'Diagnose and repair orphan direct messages and invalid conversation metadata';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $stats = [
            'orphan_found' => 0,
            'orphan_repaired' => 0,
            'orphan_removed' => 0,
            'conversations_repaired' => 0,
            'invalid_conversations_removed' => 0,
            'duplicates_removed' => 0,
        ];

        DirectMessage::query()
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('statuses')
                    ->whereColumn('statuses.id', 'direct_messages.status_id')
                    ->whereNull('statuses.deleted_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($dms) use ($dryRun, &$stats) {
                foreach ($dms as $dm) {
                    $stats['orphan_found']++;
                    DirectMessageService::logOrphan($dm);

                    if ($dryRun) {
                        continue;
                    }

                    DirectMessageService::deleteDm($dm);
                    $stats['orphan_removed']++;
                }
            });

        Conversation::query()->orderBy('id')->chunkById(100, function ($conversations) use ($dryRun, &$stats) {
            foreach ($conversations as $conversation) {
                $latest = DirectMessage::query()
                    ->whereFromId($conversation->from_id)
                    ->whereToId($conversation->to_id)
                    ->whereHas('status')
                    ->orderByDesc('id')
                    ->first();

                if (! $latest) {
                    $stats['invalid_conversations_removed']++;
                    if (! $dryRun) {
                        $conversation->delete();
                    }

                    continue;
                }

                $invalid = (string) $conversation->dm_id !== (string) $latest->id
                    || (string) $conversation->status_id !== (string) $latest->status_id
                    || $conversation->type !== $latest->type
                    || (bool) $conversation->is_hidden !== (bool) $latest->is_hidden;

                if ($invalid) {
                    $stats['conversations_repaired']++;
                    if (! $dryRun) {
                        DirectMessageService::updateConversation($latest, true);
                    }
                }
            }
        });

        $duplicates = Conversation::query()
            ->select('from_id', 'to_id')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('from_id', 'to_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = Conversation::whereFromId($duplicate->from_id)
                ->whereToId($duplicate->to_id)
                ->orderByDesc('id')
                ->get();
            $stats['duplicates_removed'] += max(0, $rows->count() - 1);
            if (! $dryRun) {
                $rows->skip(1)->each->delete();
                DirectMessageService::reconcileDirection($duplicate->from_id, $duplicate->to_id);
            }
        }

        $this->table(['Metric', 'Count'], [
            ['Orphan DMs found', $stats['orphan_found']],
            ['Orphan DMs repaired', $stats['orphan_repaired']],
            ['Orphan DMs removed', $stats['orphan_removed']],
            ['Conversations repaired', $stats['conversations_repaired']],
            ['Invalid conversation references removed', $stats['invalid_conversations_removed']],
            ['Duplicate conversations removed', $stats['duplicates_removed']],
        ]);
        $this->info($dryRun ? 'Dry run complete; no data was changed.' : 'Direct message repair complete.');

        return self::SUCCESS;
    }
}

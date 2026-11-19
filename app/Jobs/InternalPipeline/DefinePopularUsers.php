<?php

namespace App\Jobs\InternalPipeline;

use App\Models\Profile;
use App\Services\AccountService;
use App\Services\ModLogService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DefinePopularUsers implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 86400;

    public function __construct(public string $month) {}

    public function uniqueId(): string
    {
        return 'define_popular_users:'.$this->month;
    }

    /**
     * Return the 20 eligible profiles with the most likes received in the month.
     *
     * @return array<int, int|string>
     */
    public static function popularProfileIds(string $month): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $month.'-01', 'UTC');
        $end = $start->addMonth();

        return Profile::query()
            ->join('users', 'users.id', '=', 'profiles.user_id')
            ->join('statuses', 'statuses.profile_id', '=', 'profiles.id')
            ->join('likes', 'likes.status_id', '=', 'statuses.id')
            ->where('profiles.is_private', false)
            ->where('profiles.unlisted', false)
            ->where('profiles.cw', false)
            ->whereNull('users.status')
            ->whereNull('users.deleted_at')
            ->where(function ($query) {
                $query->where('users.is_admin', false)->orWhereNull('users.is_admin');
            })
            ->whereNull('statuses.deleted_at')
            ->whereNull('likes.deleted_at')
            ->where('statuses.is_nsfw', false)
            ->where('statuses.scope', 'public')
            ->where('statuses.visibility', 'public')
            ->whereIn('statuses.type', [
                'photo',
                'photo:album',
                'photo:video:album',
                'video',
                'video:album',
            ])
            ->where('likes.created_at', '>=', $start)
            ->where('likes.created_at', '<', $end)
            ->groupBy('profiles.id')
            ->orderByRaw('COUNT(likes.id) DESC')
            ->orderBy('profiles.id')
            ->limit(20)
            ->pluck('profiles.id')
            ->all();
    }

    public function handle(): void
    {
        $newPopularIds = self::popularProfileIds($this->month);

        $affectedIds = DB::transaction(function () use ($newPopularIds) {
            $currentPopular = Profile::with('user')
                ->where('is_popular', true)
                ->lockForUpdate()
                ->get();
            $currentIds = $currentPopular->pluck('id')->all();
            $removedIds = array_values(array_diff($currentIds, $newPopularIds));
            $addedIds = array_values(array_diff($newPopularIds, $currentIds));

            $addedProfiles = Profile::with('user')->whereIn('id', $addedIds)->get();

            Profile::whereIn('id', $removedIds)->update(['is_popular' => false]);
            Profile::whereIn('id', $addedIds)->update(['is_popular' => true]);

            foreach ($currentPopular->whereIn('id', $removedIds) as $profile) {
                $this->logProfileChange($profile, 'system.user.unpopular', 'Perfil removido da lista dos populares.');
            }

            foreach ($addedProfiles as $profile) {
                $this->logProfileChange($profile, 'system.user.popular', 'Perfil adicionado à lista dos populares.');
            }

            return array_values(array_unique(array_merge($currentIds, $newPopularIds)));
        });

        // The account transformer caches the popular label for up to 12 hours.
        // Invalidate all participants on every run so a retry also repairs caches.
        foreach ($affectedIds as $id) {
            AccountService::del($id);
        }

        info('Perfis populares atualizados.', [
            'month' => $this->month,
            'profile_ids' => $newPopularIds,
        ]);
    }

    private function logProfileChange(Profile $profile, string $action, string $message): void
    {
        if (! $profile->user) {
            return;
        }

        ModLogService::boot()
            ->objectUid($profile->user->id)
            ->objectId($profile->id)
            ->objectType(Profile::class)
            ->action($action)
            ->message($message)
            ->accessLevel('mod')
            ->user($profile->user)
            ->save();
    }
}

<?php

namespace App\Jobs\InternalPipeline;

use App\Profile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ModLogService;
use Illuminate\Support\Facades\DB;

class DefinePopularUsers implements ShouldQueue
{
    use Queueable;

    public  $newPopularProfiles;

    /**
     * Create a new job instance.
     */
    public function __construct($newPopularProfiles)
    {
        $this->newPopularProfiles = $newPopularProfiles;
    }

    /**
     * Execute the job.
     *
     */
    public function handle(): void
    {
        DB::transaction(
            function () {

                $popularUsers = Profile::with('user')->where('is_popular', true)->get();
                $currentPopularIds = $popularUsers->pluck('id')->all();
                $newPopularProfileIds = collect($this->newPopularProfiles)->pluck('id')->all();
                $newPopularProfileIdsSet = array_flip($newPopularProfileIds);

                // Perfis que deixam de ser populares
                $usersToUnpopular = $popularUsers->filter(
                    fn($user) => !isset($newPopularProfileIdsSet[$user->id])
                );
                $idsToUnpopular = $usersToUnpopular->pluck('id')->all();
                Profile::whereIn('id', $idsToUnpopular)->update(['is_popular' => false]);

                foreach ($usersToUnpopular as $profile) {
                    $this->_logProfileChange($profile, 'system.user.unpopular', 'Perfil removido da lista dos populares.');
                }
                info("Perfis removidos da popularidade: ", $idsToUnpopular);

                // Perfis que passam a ser populares
                $newlyPopularIds = array_diff($newPopularProfileIds, $currentPopularIds);
                $newlyPopularIdsSet = array_flip($newlyPopularIds);

                $newlyPopularProfiles = collect($this->newPopularProfiles)
                    ->filter(fn($profile) => isset($newlyPopularIdsSet[$profile->id]));

                Profile::whereIn('id', $newlyPopularIds)->update(['is_popular' => true]);

                foreach ($newlyPopularProfiles as $profile) {
                    $this->_logProfileChange($profile, 'system.user.popular', 'Perfil adicionado à lista dos populares.');
                }

                info("Perfis marcados como populares: ", $newPopularProfileIds);
            }
        );
    }



    /**
     * Log a profile change action to the moderation log.
     *
     * @param \App\Profile $profile The profile being updated.
     * @param string       $action  The action performed.
     * @param string       $message The message to log.
     *
     * @return void
     */
    private function _logProfileChange($profile, $action, $message)
    {
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

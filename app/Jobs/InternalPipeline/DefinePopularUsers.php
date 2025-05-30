<?php

namespace App\Jobs\InternalPipeline;

use App\Profile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ModLogService;

class DefinePopularUsers implements ShouldQueue
{
    use Queueable;

    public  $newPopularProfiles = [];

    /**
     * Create a new job instance.
     */
    public function __construct($newPopularProfiles)
    {
        $this->newPopularProfiles = $newPopularProfiles;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $popularUsers = Profile::where('is_popular', true)->get();
        $newPopularProfileIds = collect($this->newPopularProfiles)->pluck('id')->all();

        $usersToUnpopular = $popularUsers->filter(
            fn($user) => !in_array($user->id, $newPopularProfileIds)
        );

        $idsToUnpopular = $usersToUnpopular->pluck('id')->all();
        Profile::whereIn('id', $idsToUnpopular)->update(['is_popular' => false]);

        foreach ($usersToUnpopular as $profile) {
            $this->_logProfileChange($profile, 'system.user.unpopular', 'Perfil removido da lista dos populares.');
        }
        info("Perfis removidos da popularidade: ", $idsToUnpopular);

        Profile::whereIn('id', $newPopularProfileIds)->update(['is_popular' => true]);
        foreach ($this->newPopularProfiles as $profile) {
            $this->_logProfileChange($profile, 'system.user.popular', 'Perfil adicionado à lista dos populares.');
        }

        info("Perfis marcados como populares: ", $newPopularProfileIds);
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
            ->objectUid($profile->id)
            ->objectId($profile->id)
            ->objectType(Profile::class)
            ->action($action)
            ->message($message)
            ->accessLevel('mod')
            ->user($profile->user)
            ->save();
    }
}

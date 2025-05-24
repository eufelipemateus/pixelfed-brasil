<?php

namespace App\Jobs\InternalPipeline;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Mail\DesactiveInactiveAccountNotification;
use App\User;
use App\Services\ModLogService;
use App\Enums\StatusEnums;

class DesactiveInactiveUserJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        User::whereNull('status')
            ->whereNull('deleted_at')
            ->whereNull('last_active_at')
            ->whereNull("email_verified_at")
            ->where('created_at', '<', now()->subDays(60))
            ->chunk(
                100,
                function ($users) {
                    foreach ($users as $user) {
                        info('Disactive inactive user ' . $user->username);
                        $profile = $user->profile;
                        $user->status = StatusEnums::DISABLED;
                        $profile->status = StatusEnums::DISABLED;
                        $user->save();
                        $profile->save();
                        Mail::to($user->email)
                        ->queue(((new DesactiveInactiveAccountNotification($user))->onQueue('low')));
                        ModLogService::boot()
                            ->objectUid($profile->id)
                            ->objectId($profile->id)
                            ->objectType('App\Profile::class')
                            ->action('system.user.desactive')
                            ->message('Conta desativada por inatividade')
                            ->accessLevel('admin')
                            ->user($user)
                            ->save();
                    }
                }
            );

        User::whereNull('status')
            ->whereNull('deleted_at')
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '<', now()->subDays(180))
            ->chunk(
                1000,
                function ($users) {
                    foreach ($users as $user) {
                        info('Disactive inactive user ' . $user->username);
                            $profile = $user->profile;
                            $user->status = StatusEnums::DISABLED;
                            $profile->status = StatusEnums::DISABLED;
                            $user->save();
                            $profile->save();
                        Mail::to($user->email)
                        ->queue(((new DesactiveInactiveAccountNotification($user))->onQueue('low')));
                        ModLogService::boot()
                            ->objectUid($profile->id)
                            ->objectId($profile->id)
                            ->objectType('App\Profile::class')
                            ->action('system.user.desactive')
                            ->message('Conta desativada por inatividade')
                            ->accessLevel('admin')
                            ->user($user)
                            ->save();
                    }
                }
            );
    }
}

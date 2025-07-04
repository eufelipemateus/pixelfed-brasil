<?php

namespace App\Jobs\InternalPipeline;

use App\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\InactiveUser;
use Illuminate\Support\Facades\Cache;


class SendWeeklyInactiveUserJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 432000;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return date('W');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // Verificar se o job foi executado nos últimos 5 dias
        if (Cache::has('send_weekly_inactive_user_job_last_run')) {
            return;
        }

        // Marcar o job como executado
        Cache::put('send_weekly_inactive_user_job_last_run', now(), now()->addDays(5));

        User::whereNull('status')
            ->whereNull('deleted_at')
            ->whereNull('last_active_at')
            ->whereNull("email_verified_at")
            ->where('created_at', '<', now()->subDays(7))
            ->chunk(
                100,
                function ($users) {
                    foreach ($users as $user) {
                        info('Sending inactive user email to ' . $user->username);
                        Mail::to($user->email)
                            ->queue(((new InactiveUser($user))->onQueue('low')));
                    }
                }
            );
    }
}

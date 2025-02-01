<?php

namespace App\Jobs\InternalPipeline;

use App\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\InactiveUser;

class SendWeeklyInactiveUserJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
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
     * Execute the job.
     */
    public function handle(): void
    {
        //

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
                        Mail::to($user->email)->send(new InactiveUser($user));
                    }
                }
            );
    }
}

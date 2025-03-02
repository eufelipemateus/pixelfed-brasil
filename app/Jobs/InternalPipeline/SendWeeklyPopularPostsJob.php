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
use Illuminate\Support\Facades\Cache;
use App\Status;
use App\Mail\WeeklyPopularPostsMail;
use Carbon\Carbon;

class SendWeeklyPopularPostsJob implements ShouldQueue, ShouldBeUnique
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
        if (Cache::has('send_weekly_popupar_posts_job_last_run')) {
            return;
        }

        $popularPosts = Status::where('created_at', '>=', Carbon::now()->subWeek())
                    ->where('type', 'photo')
                    ->where('local', true)
                    ->where('visibility', 'public')
                    ->where('is_nsfw', false)
                    ->where('reply', false)
                    ->where('scope', 'public')
                    ->with('profile')
                    ->with('media')
                    ->orderByDesc('likes_count')
                    ->take(10)
                    ->get();

        if ($popularPosts->isEmpty()) {
            info('Nenhum post popular encontrado para enviar.');
            return;
        }

        User::whereNull('status')
            ->whereNull('deleted_at')
            ->whereNotNull('last_active_at')
            ->whereNotNull("email_verified_at")
            ->where('is_admin', true)
            ->chunk(
                1000,
                function ($users) use ($popularPosts) {
                    foreach ($users as $user) {
                        info('Sending popular posts email to ' . $user->username);
                        Mail::to($user->email)
                            ->queue((new WeeklyPopularPostsMail($popularPosts, $user))->onQueue('low'));
                    }
                }
            );

        User::whereNull('status')
            ->whereNull('deleted_at')
            ->whereNotNull('last_active_at')
            ->whereNotNull("email_verified_at")
            ->where('last_active_at', '<', now()->subDays(30))
            ->chunk(
                1000,
                function ($users) use ($popularPosts) {
                    foreach ($users as $user) {
                        info('Sending popular posts email to ' . $user->username);
                        Mail::to($user->email)
                            ->queue((new WeeklyPopularPostsMail($popularPosts, $user))->onQueue('low'));
                    }
                }
            );

        // Marcar o job como executado
        Cache::put('send_weekly_popupar_posts_job_last_run', now(), now()->addDays(5));
        info('Emails enviados com sucesso!');

    }
}

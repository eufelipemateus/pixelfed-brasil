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
use App\Profile;

class SendWeeklyPopularPostsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public bool $testing = false) {}

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
        return 'weekly-popular-' . now()->format('Y-W');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // Verificar se o job foi executado nos últimos 5 dias
        if (Cache::has('weekly_popular_posts_job_last_run') && !$this->testing) {
            return;
        }

        $popularPosts = $this->getPopularPosts();
        if ($popularPosts->isEmpty()) {
            info('Nenhum post popular encontrado para enviar.');
            return;
        }

        if (config('pixelfed.user_invites.enabled')) {
            $promotersIds = User::where('created_at', '>=', Carbon::now()->subWeek())
                ->whereNotNull('referred_by')
                ->pluck('referred_by')
                ->unique()
                ->values();
            $promoters = Profile::whereIn('user_id', $promotersIds)
                ->where('unlisted', false)
                ->get();
        } else {
            $promoters = collect();
        }

        #========== Send Emails ==========#
        $this->send($popularPosts, $promoters);

        // Marcar o job como executado
        if (!$this->testing) {
            Cache::put('weekly_popular_posts_job_last_run', now(), now()->addDays(5));
        }
        info('Emails enviados com sucesso!');
    }

    public function getPopularPosts()
    {

        return Status::where('created_at', '>=', Carbon::now()->subWeek())
            ->where('type', 'photo')
            ->where('local', true)
            ->where('visibility', 'public')
            ->where('is_nsfw', false)
            ->where('reply', false)
            ->where('scope', 'public')
            ->with(['profile', 'media'])
            ->whereHas('profile', function ($query) {
                $query->where('unlisted', false);
            })
            ->orderByDesc('likes_count')
            ->take(10)
            ->get();
    }


    public function send($popularPosts, $promoters)
    {
        if ($this->testing) {
            info('Running in test mode, skipping email sending.');
            User::whereNull('status')
                ->whereNull('deleted_at')
                ->whereNotNull('last_active_at')
                ->whereNotNull("email_verified_at")
                ->where('is_admin', true)
                ->chunk(
                    1000,
                    function ($users) use ($popularPosts, $promoters) {
                        foreach ($users as $user) {
                            info('Sending popular posts email to ' . $user->username);
                            Mail::to($user->email)
                                ->queue((new WeeklyPopularPostsMail($popularPosts, $user, $promoters))->onQueue('email'));
                        }
                    }
                );
            return;
        }

        User::whereNull('status')
            ->whereNull('deleted_at')
            ->whereNotNull('last_active_at')
            ->whereNotNull("email_verified_at")
            ->chunk(
                100,
                function ($users) use ($popularPosts, $promoters) {
                    foreach ($users as $user) {
                        info('Sending popular posts email to ' . $user->username);
                        Mail::to($user->email)
                            ->queue((new WeeklyPopularPostsMail($popularPosts, $user, $promoters))->onQueue('email'));
                    }
                }
            );
    }
}

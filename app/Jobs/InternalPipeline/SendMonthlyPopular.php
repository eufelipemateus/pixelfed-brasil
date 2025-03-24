<?php

namespace App\Jobs\InternalPipeline;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Status;
use App\User;
use Carbon\Carbon;
use App\Mail\MonthlyPopularPostsMail;
use App\Profile;

class SendMonthlyPopular implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $testing = false;


    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 2592000;

    /**
     * Create a new job instance.
     */
    public function __construct($testing = false)
    {
        //

        $this->testing = $testing;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return date('W-Y');
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        if (Cache::has('send_mounthly_popupar_posts_job_last_run')) {
            return;
        }

        $popularPosts = Status::whereBetween(
            'created_at',
            [
                Carbon::now()->startOfMonth()->subMonth()->startOfDay(),
                Carbon::now()->startOfMonth()->subMonth()->endOfMonth()
                    ->endOfDay()
            ]
        )->whereIn('type', ['photo', 'photo:album'])
            ->where('local', true)
            ->where('visibility', 'public')
            ->where('is_nsfw', false)
            ->where('reply', false)
            ->where('scope', 'public')
            ->with('profile')
            ->with('media')
            ->orderByDesc('likes_count')
            ->take(30)
            ->get();


        if ($popularPosts->isEmpty()) {
            info('Nenhum post popular encontrado para enviar.');
            return;
        }


        $profileIds = Profile::select('profiles.id')
            ->selectRaw('COUNT(likes.id) AS total_likes')
            ->leftJoin('users', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('statuses', 'statuses.profile_id', '=', 'profiles.id')
            ->leftJoin('likes', 'likes.status_id', '=', 'statuses.id')
            ->whereNotNull('profiles.user_id')
            ->where('profiles.is_private', false)
            ->where(
                function ($query) {
                    $query->where('users.is_admin', false)
                        ->orWhereNull('users.is_admin');
                }
            )->where('profiles.unlisted', false)
            ->where('profiles.cw', false)
            ->where('statuses.is_nsfw', false)
            ->where('statuses.scope', 'public')
            ->where('statuses.visibility', 'public')
            ->whereIn(
                'statuses.type', [
                'photo',
                'photo:album',
                'photo:video:album',
                'video',
                'video:album'
                ]
            )->whereBetween(
                'likes.created_at',
                [
                Carbon::now()
                    ->startOfMonth()
                    ->subMonth()
                    ->startOfDay(),
                Carbon::now()
                    ->startOfMonth()
                    ->subMonth()
                    ->endOfMonth()
                    ->endOfDay()
                ]
            )->groupBy('profiles.id')
            ->orderByDesc('total_likes')
            ->limit(50)
            ->get();

        $popularUsers = Profile::whereIn('id', $profileIds)->get();



        if ($this->testing) {
            User::whereNull('status')
                ->whereNull('deleted_at')
                ->where('users.is_admin', true)
                ->chunk(
                    10,
                    function ($users) use ($popularPosts, $popularUsers) {
                        foreach ($users as $user) {
                            info('Sending popular posts email to ' . $user->username);
                            Mail::to($user->email)
                                ->queue((new MonthlyPopularPostsMail($popularPosts, $user, $popularUsers))->onQueue('low'));
                        }
                    }
                );
        } else {
            User::whereNull('status')
                ->whereNull('deleted_at')
                ->chunk(
                    1000,
                    function ($users) use ($popularPosts, $popularUsers) {
                        foreach ($users as $user) {
                            info('Sending popular posts email to ' . $user->username);
                            Mail::to($user->email)
                                ->queue((new MonthlyPopularPostsMail($popularPosts, $user, $popularUsers))->onQueue('low'));
                        }
                    }
                );
        }

        Cache::put('send_mounthly_popupar_posts_job_last_run', now(), now()->addDays(30));
        info('Emails enviados com sucesso!');
    }
}

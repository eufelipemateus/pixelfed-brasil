<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Profile;
use App\Status;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{

    const CHUNK_SIZE = 40000;

    public function index()
    {

        $urls = [
            route('sitemap.site'),
            route('sitemap.popular'),
            route('sitemap.recents'),
            route('sitemap.common')
        ];

        return response()
            ->view('sitemap.part', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }


    public function site($frequency = 'weekly', $priority = '0.4')
    {

        $sitemap = Cache::remember('sitemap.site', 60 * 24 * 7, function () use ($frequency, $priority) {

            $urls = [];

            foreach (Route::getRoutes() as $route) {
                $uri = $route->uri();
                $name = $route->getName();

                if ($route->methods()[0] !== 'GET') {
                    continue;
                }

                if ($name && (
                    str_starts_with($name, 'site.') ||
                    str_starts_with($name, 'help.') ||
                    str_starts_with($name, 'newsroom.') ||
                    $name === 'legal-notice'
                )) {
                    $urls[] = url($uri);
                }
            }
            return view('sitemap.xml', compact('urls', 'frequency', 'priority'))->render();
        });


        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }

    public function popular($frequency = 'daily', $priority = '1.0')
    {
        $sitemaps = Cache::remember('sitemap.popular', 60 * 24 * 7, function () use ($frequency, $priority) {

            $urls = [];

            $profiles = Profile::query()
                ->select('profiles.*')
                ->leftJoin('users', 'profiles.user_id', '=', 'users.id')
                ->whereNull('profiles.status')
                ->where('profiles.is_private', false)
                ->where(function ($query) {
                    $query->where('profiles.is_popular', true)
                        ->orWhere('users.is_admin', true);
                })
                ->get();

            foreach ($profiles as $profile) {
                $urls[] = $profile->url();

                Status::whereProfileId($profile->id)
                    ->whereNull('deleted_at')
                    ->where('is_nsfw', false)
                    ->where('local', true)
                    ->where('scope', 'public')
                    ->where('visibility', 'public')
                    ->whereIn('type', ['text', 'image', 'video', 'photo:album', 'video:album'])
                    ->orderBy('likes_count', 'desc')
                    ->chunk(100, function ($chunkedStatuses) use (&$urls) {
                        foreach ($chunkedStatuses as $status) {
                            $urls[] = $status->url();
                        }
                    });
            }
            $urls = $this->filter404Urls($urls);

            return collect($urls)->chunk(self::CHUNK_SIZE)->map(function ($chunk, $index) use ($frequency, $priority) {
                $filename = "sitemap-popular-{$index}.xml";
                $path = "sitemaps/{$filename}";
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                Storage::disk('public')->put(
                    $path,
                    view('sitemap.xml', [
                        'urls' => $chunk,
                        'frequency' => $frequency,
                        'priority' => $priority
                    ])->render()
                );
                return Storage::disk('public')->url($path);
            })->toArray();
        });

        // agora gera o índice de sitemaps
        return response()->view('sitemap.part', ['urls' => $sitemaps])
            ->header('Content-Type', 'application/xml');
    }

    public function recents($frequency = 'yearly', $priority = '0.2')
    {
        $sitemaps = Cache::remember('sitemap.recents', 60 * 24 * 7, function ()  use ($frequency, $priority) {

            $urls = [];

            Profile::where('unlisted', false)
                ->where('is_private', false)
                ->whereNull('deleted_at')
                ->whereNull('profiles.status')
                ->whereNotNull('user_id')
                ->orderByDesc('created_at')
                ->where('is_popular', false)
                ->where('created_at', '>=', now()->subMonths(6))
                ->chunk(500, function ($profiles) use (&$urls) {
                    $urls = array_merge($urls, $profiles->map(fn($profile) => $profile->url())->toArray());
                    $profileIds = $profiles->pluck('id');

                    Status::whereIn('profile_id', $profileIds)
                        ->whereNull('deleted_at')
                        ->where('is_nsfw', false)
                        ->where('local', true)
                        ->where('scope', 'public')
                        ->where('visibility', 'public')
                        ->whereIn('type', ['text', 'image', 'video', 'photo:album', 'video:album'])
                        ->orderBy('likes_count', 'desc')
                        ->chunk(100, function ($statuses) use (&$urls) {
                            $urls = array_merge($urls, $statuses->map(fn($status) => $status->url())->toArray());
                        });
                });

            $urls = $this->filter404Urls($urls);

            return collect($urls)->chunk(self::CHUNK_SIZE)->map(function ($chunk, $index) use ($frequency, $priority) {
                $filename = "sitemap-recents-{$index}.xml";
                $path = "sitemaps/{$filename}";
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                Storage::disk('public')->put(
                    $path,
                    view('sitemap.xml', [
                        'urls' => $chunk,
                        'frequency' => $frequency,
                        'priority' => $priority
                    ])->render()
                );
                return Storage::disk('public')->url($path);
            })->toArray();
        });

        return response()->view('sitemap.part', ['urls' => $sitemaps])
            ->header('Content-Type', 'application/xml');
    }

    public function common($frequency = 'monthly', $priority = '0.6')
    {
        $sitemaps = Cache::remember('sitemap.common', 60 * 24 * 7, function ()  use ($frequency, $priority) {
            $urls = [];

            Profile::where('unlisted', false)
                ->where('is_private', false)
                ->whereNull('profiles.deleted_at')
                ->whereNotNull('user_id')
                ->orderByDesc('profiles.created_at')
                ->leftJoin('users', 'profiles.user_id', '=', 'users.id')
                ->where('is_popular', false)
                ->whereNull('profiles.status')
                ->where(function ($query) {
                    $query->whereNull('users.is_admin')->orWhere('users.is_admin', false);
                })
                ->where('profiles.created_at', '<=', now()->subMonths(6))
                ->select('profiles.*')
                ->chunk(500, function ($profiles) use (&$urls) {
                    $urls = array_merge($urls, $profiles->map(fn($profile) => $profile->url())->toArray());
                    $profileIds = $profiles->pluck('id');

                    Status::whereIn('profile_id', $profileIds)
                        ->whereNull('deleted_at')
                        ->where('is_nsfw', false)
                        ->where('local', true)
                        ->where('scope', 'public')
                        ->where('visibility', 'public')
                        ->whereIn('type', ['text', 'image', 'video', 'photo:album', 'video:album'])
                        ->orderBy('likes_count', 'desc')
                        ->chunk(100, function ($statuses) use (&$urls) {
                            $urls = array_merge($urls, $statuses->map(fn($status) => $status->url())->toArray());
                        });
                });

            $urls = $this->filter404Urls($urls);




            return collect($urls)->chunk(self::CHUNK_SIZE)->map(function ($chunk, $index) use ($frequency, $priority) {
                $filename = "sitemap-common-{$index}.xml";
                $path = "sitemaps/{$filename}";
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                Storage::disk('public')->put(
                    $path,
                    view('sitemap.xml', [
                        'urls' => $chunk,
                        'frequency' => $frequency,
                        'priority' => $priority
                    ])->render()
                );
                return Storage::disk('public')->url($path);
            })->toArray();
        });

        return response()->view('sitemap.part', ['urls' => $sitemaps])
            ->header('Content-Type', 'application/xml');
    }


    function filter404Urls(array $urls): array
    {
        return array_values(array_filter($urls, function ($url) {
            return !str_contains($url, '/404');
        }));
    }
}

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
                    ->orderBy('likes_count', 'desc')
                    ->chunk(100, function ($chunkedStatuses) use (&$urls) {
                        foreach ($chunkedStatuses as $status) {
                            $urls[] = $status->url();
                        }
                    });
            }

            return collect($urls)->chunk(self::CHUNK_SIZE)->map(function ($chunk, $index) use ($frequency, $priority) {
                $filename = "sitemap-popular-{$index}.xml";
                Storage::put(
                    "public/sitemaps/{$filename}",
                    view('sitemap.xml', [
                        'urls' => $chunk,
                        'frequency' => $frequency,
                        'priority' => $priority
                    ])->render()
                );
                return Storage::url("public/sitemaps/{$filename}");
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
                ->orderByDesc('created_at')
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
                        ->orderBy('likes_count', 'desc')
                        ->chunk(100, function ($statuses) use (&$urls) {
                            $urls = array_merge($urls, $statuses->map(fn($status) => $status->url())->toArray());
                        });
                });

            return collect($urls)->chunk(self::CHUNK_SIZE)->map(function ($chunk, $index) use ($frequency, $priority) {
                $filename = "sitemap-recents-{$index}.xml";
                Storage::put(
                    "public/sitemaps/{$filename}",
                    view('sitemap.xml', [
                        'urls' => $chunk,
                        'frequency' => $frequency,
                        'priority' => $priority
                    ])->render()
                );
                return Storage::url("public/sitemaps/{$filename}");
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
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->where('created_at', '<=', now()->subMonths(6))
                ->chunk(500, function ($profiles) use (&$urls) {
                    $urls = array_merge($urls, $profiles->map(fn($profile) => $profile->url())->toArray());
                    $profileIds = $profiles->pluck('id');

                    Status::whereIn('profile_id', $profileIds)
                        ->whereNull('deleted_at')
                        ->where('is_nsfw', false)
                        ->where('local', true)
                        ->where('scope', 'public')
                        ->where('visibility', 'public')
                        ->orderBy('likes_count', 'desc')
                        ->chunk(100, function ($statuses) use (&$urls) {
                            $urls = array_merge($urls, $statuses->map(fn($status) => $status->url())->toArray());
                        });
                });


            return collect($urls)->chunk(self::CHUNK_SIZE)->map(function ($chunk, $index) use ($frequency, $priority) {
                $filename = "sitemap-common-{$index}.xml";
                Storage::put(
                    "public/sitemaps/{$filename}",
                    view('sitemap.xml', [
                        'urls' => $chunk,
                        'frequency' => $frequency,
                        'priority' => $priority
                    ])->render()
                );
                return Storage::url("public/sitemaps/{$filename}");
            })->toArray();
        });

        return response()->view('sitemap.part', ['urls' => $sitemaps])
            ->header('Content-Type', 'application/xml');
    }
}

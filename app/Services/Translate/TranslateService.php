<?php

/**
 * TranslateService.php
 *
 * This file contains the TranslateService class for handling translation operations.
 *
 * @category Services
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/eufelipemateus/pixelfed
 */

namespace App\Services\Translate;

use App\Status;
use Illuminate\Support\Facades\Cache;
use App\Services\Translate\GoogleTranslate;
use App\Services\Translate\DeepLTranslate;
use App\Profile;

/**
 * Service class for handling translation operations using different providers.
 *
 * @category Services
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/eufelipemateus/pixelfed
 */
class TranslateService
{

    const CACHE_KEY_STATUS = 'pf:services:status:';

    const CACHE_KEY_BIO = 'pf:services:profile:';


    /**
     * Returns  the cache key for the given id and language.
     *
     * @param string $id       The id of the translation.
     * @param string $language The language code.
     * 
     * @return string The cache key.
     */
    public static function statusKey(string $id,  string $language)
    {
        return self::CACHE_KEY_STATUS . $id . ":" . $language;
    }


    /**
     * Returns the cache key for the given id and language.
     *
     * @param string $id       The id of the translation.
     * @param string $language The language code.
     *
     * @return string The cache key.
     */
    public static function bioKey(string $id,  string $language)
    {
        return self::CACHE_KEY_BIO . $id . ":bio:" . $language;
    }

    /**
     * Retrieves the translation for the given id and language.
     *
     * @param string $id       The id of the translation.
     * @param string $language The language code.
     *
     * @return array|null The translation data or null if not found.
     */
    public static function status(string $id, string $language)
    {
        return Cache::remember(
            self::statusKey($id, $language),
            21600,
            function () use ($id, $language) {
                $status =  Status::where('id', $id)
                    ->first();

                $config = self::config();
                $ranslate = new Translator($config['provider'], $config['config']);

                if ($status) {
                    $text = $ranslate->translate($status->caption, $language);
                    return [
                        'id' => $id,
                        'language' => $language,
                        'text' => $text['text'],
                        'provider' => $config['provider'],
                    ];
                } else {
                    return null;
                }
            }
        );
    }

    /**
     * Returns the configuration for the translation provider.
     *
     * @return array The configuration array.
     *
     * @throws \Exception If the translation provider is invalid.
     */
    public static function config()
    {

        $provider = config('pixelfed.translation.provider');
        $google_api_key = config('pixelfed.translation.google_api_key');
        $deepl_api_key = config('pixelfed.translation.deepl_api_key');

        return match ($provider) {
            'google' => [
                'provider' => GoogleTranslate::class,
                'config' => [
                    'google_api_key' => $google_api_key,
                ],
            ],
            'deepl' => [
                'provider' => DeepLTranslate::class,
                'config' => [
                    'deepl_api_key' => $deepl_api_key,
                ],
            ],
            default => throw new \Exception('Invalid translation provider'),
        };
    }



    /**
     * Retrieves the translated bio for the given profile ID and target language.
     *
     * @param string $pid            The profile ID.
     * @param string $targetLanguage The target language code.
     *
     * @return array|null The translated bio data or null if not found.
     */
    public static function bio(string $pid, string $targetLanguage)
    {
        return Cache::remember(
            self::bioKey($pid, $targetLanguage),
            21600,
            function () use ($pid, $targetLanguage) {
                $config = self::config();
                $ranslate = new Translator($config['provider'], $config['config']);
                $profile = Profile::where('id', $pid)->first();
                $text = $ranslate->translate($profile->bio, $targetLanguage);
                return [
                    'id' => $pid,
                    'language' => $targetLanguage,
                    'text' => $text['text'],
                    'provider' => $config['provider'],
                ];
            }
        );
    }
}

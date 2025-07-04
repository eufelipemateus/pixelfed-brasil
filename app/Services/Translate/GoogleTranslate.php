<?php

/**
 * GoogleTranslate.php
 *
 * This file contains the GoogleTranslate provider for translating text using the Google Translate API.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */

namespace App\Services\Translate;

use Illuminate\Support\Facades\Http;

/**
 * Google Translate provider for translating text using the Google Translate API.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */
class GoogleTranslate extends Provider implements TranslateInterface
{
    protected string $googleTranslateApi;

    protected string $googleProjectNumber;

    protected const URL  = "https://translation.googleapis.com/language/translate/v2";

    /**
     * GoogleTranslate constructor.
     *
     * @param Array $config Configuration array containing 'googleTranslateApi' and 'googlProjectNumber'.
     */
    public function __construct(array $config)
    {
        $this->googleTranslateApi = $config['google_api_key'];
        // $this->googleProjectNumber = $config['google_project_number'];
    }


    /**
     * Creates an HTTP client with the necessary headers and token.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private  function _client()
    {
        $client = Http::acceptJson()
            /* ->withHeaders(
                [
                'X-Goog-User-Project' => $this->googleProjectNumber,
                ]
            )*/;
        return $client;
    }


    /**
     * Translates the given text to the specified target language.
     *
     * @param string $text           The text to translate.
     * @param string $targetLanguage The language code to translate the text into.
     *
     * @return string The translated text.
     */
    public function translate(string $text, string $targetLanguage): string
    {
        $text = $this->sanitizeText($text);
        $response = $this->_client()->post(
            self::URL . '?key=' . $this->googleTranslateApi,
            [
                'q' => $text,
                'target' => $targetLanguage,
                'format' => 'text',
            ]
        );

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['data']['translations'][0]['translatedText'])) {
                return $data['data']['translations'][0]['translatedText'];
            }
        }

        return  $response->throw();

        return 'null';
    }


    /**
     * Detects the language of the given text.
     *
     * @param string $text The text whose language is to be detected.
     *
     * @return string The detected language code (e.g., 'en', 'pt').
     */
    public function detect(string $text): string
    {
        $text = $this->sanitizeText($text);

        $response = $this->_client()->post(
            self::URL . '/detect',
            [
                'q' => $text,
            ]
        );

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['data']['detections'][0][0]['language'])) {
                return $data['data']['detections'][0][0]['language'];
            }
        }

        $response->throw();
        return "";
    }
}

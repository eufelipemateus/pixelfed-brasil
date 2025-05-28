<?php

/**
 * DeepLTranslate Service
 *
 * Provides translation services using the DeepL API.
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
 * DeepLTranslate translation provider.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */
class DeepLTranslate extends Provider implements TranslateInterface
{
    protected $deeplApi;

    protected const URL = "https://api-free.deepl.com/v2/translate";

    /**
     * DeepLTranslate constructor.
     *
     * @param array $config The configuration array containing the DeepL API client instance.
     */
    public function __construct(array $config)
    {
        $this->deeplApi = $config['deepl_api_key'];
    }

    /**
     * Creates an HTTP client with the necessary headers and token.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function _client()
    {
        return  Http::acceptJson()
            /*->withHeaders(
                ['Authorization' => 'DeepL-Auth-Key ' . $this->deeplApi]
            );*/;
    }


    /**
     * Translates the given text to the specified target language using DeepL.
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
            self::URL,
            [
                'auth_key' => $this->deeplApi,
                'text' => $text,
                'target_lang' => strtoupper($targetLanguage),
            ]
        );

        if ($response->failed()) {
            throw new \Exception('Translation failed: ' . $response->body());
        }

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['translations'][0]['text'])) {
                return $data['translations'][0]['text'];
            }
        }

        $response->throw();
        return 'null';
    }

    /**
     * Detects the language of the given text using DeepL.
     *
     * @param string $text The text to detect the language of.
     *
     * @return string The detected language code.
     */
    public function detect(string $text): string
    {
        $text = $this->sanitizeText($text);

        $response = $this->_client()->asForm()->post(
            self::URL,
            [
                'auth_key'    =>   $this->deeplApi,
                'text'        => $text,
                'target_lang' => 'EN',
            ]
        );

        if ($response->successful()) {
            return $response->json()['translations'][0]['detected_source_language'] ?? null;
        }

        $response->throw();

        return "";
    }
}

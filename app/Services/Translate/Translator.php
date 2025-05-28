<?php
/**
 * Translator service for handling text translations.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */
namespace App\Services\Translate;


/**
 * Provides translation services using a specified translation provider.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */
class Translator
{
    protected TranslateInterface $provider;

    /**
     * Translator constructor.
     *
     * @param TranslateInterface $provider The translation provider implementation.
     * @param array              $config   Configuration array for the provider.
     */
    public function __construct(string $provider, array $config)
    {
        $this->provider = new $provider($config);
    }

    /**
     * Translates the given text to the specified target language.
     *
     * @param string $text           The text to translate.
     * @param string $targetLanguage The language code to translate the text into.
     *
     * @return rturn  The translated text.
     */
    public function translate(string $text, string $targetLanguage):  Array
    {
        $text =  $this->provider->translate($text, $targetLanguage);


        return [
            'text' => $text,
            'target' => $targetLanguage,
        ];
    }
}

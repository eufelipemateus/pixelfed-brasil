<?php
/**
 * TranslateInterface file.
 *
 * Provides the interface for translation services.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */
namespace App\Services\Translate;

/**
 * Interface for translation services.
 *
 * Provides a method to translate text into a specified target language.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */
interface TranslateInterface
{




    /**
     * Translates the given text to the specified target language.
     *
     * @param string $text           The text to translate.
     * @param string $targetLanguage The language code to translate the text into.
     *
     * @return string The translated text.
     */
    public function translate(string $text,string $targetLanguage): string;


    /**
     * Detects the language of the given text.
     *
     * @param string $text The text to detect the language of.
     *
     * @return string The detected language code.
     */
    public function detect(string $text): string;
}

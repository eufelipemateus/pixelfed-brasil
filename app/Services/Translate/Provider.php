<?php
/**
 * Provider.php
 *
 * Abstract base class for translation providers.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */
namespace App\Services\Translate;


/**
 * Abstract base class for translation providers.
 *
 * @category Translation
 * @package  App\Services\Translate
 * @author   Felipe Mateus <eu@felipeamteus.com>
 * @license  AGPL-3.0  https://opensource.org/licenses/AGPL-3.0
 * @link     https://github.com/felipeamteus/pixelfed
 */
abstract class Provider
{

    /**
     * Sanitize the given text by trimming whitespace.
     *
     * @param string $text The text to sanitize.
     *
     * @return string The sanitized text.
     */
    protected function sanitizeText(string $text): string
    {
        // TODO: Remove html, hashtags, mentions, urls, emojis
        return trim($text);
    }
}

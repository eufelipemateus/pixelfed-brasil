<?php

namespace App\Services\Translate;

use Illuminate\Support\Facades\Http;


class LibreTranslate extends Provider implements TranslateInterface
{

    protected string $libreTranslateApiKey;
    protected string $libreTranslateUrl;


    public function __construct(array $config)
    {
        $this->libreTranslateApiKey = $config['libre_translate_api_key'];
        $this->libreTranslateUrl = $config['libre_translate_url'] ?? 'https://libretranslate.de';
    }

    private  function _client()
    {
        $client = Http::acceptJson();

        return $client;
    }

    public function translate(string $text, string $targetLanguage): string
    {
        $text = $this->sanitizeText($text);
        $response = $this->_client()
            ->post(
                $this->url . '/translate',
                [
                    'q' => $text,
                    'target' => $targetLanguage,
                    'format' => 'text',
                    'api_key' => $this->libreTranslateApiKey,
                ]
            );
        if ($response->successful()) {
            $data = $response->json();
            return $data['translatedText'] ?? '';
        } else {
            // Handle error response
            return '';
        }
    }


    public function detect(string $text): string
    {
        $text = $this->sanitizeText($text);

        $response = $this->_client()
            ->post(
                $this->url . '/detect',
                [
                    'q' => $text,
                    'api_key' => $this->libreTranslateApiKey,
                ]
            );

        if ($response->successful()) {
            $data = $response->json();
            return $data[0]['language'] ?? '';
        } else {
            // Handle error response
            return '';
        }
    }
}

<?php

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class);

function renderStatusMetadata(string $type, bool $sensitive, array $media): string
{
    $template = <<<'BLADE'
@if(count($media) && ! $sensitive && in_array($type, ['photo', 'photo:album'], true))
<meta property="og:image" content="{{ $media[0]['url'] }}">
@elseif(count($media) && ! $sensitive && in_array($type, ['video', 'video:album'], true))
<meta property="og:video" content="{{ $media[0]['url'] }}">
@endif
BLADE;

    return Blade::render($template, compact('type', 'sensitive', 'media'));
}

it('only exposes metadata for non-sensitive media', function (
    string $type,
    bool $sensitive,
    array $media,
    ?string $property,
) {
    $html = renderStatusMetadata($type, $sensitive, $media);

    if ($property === null) {
        expect($html)->not->toContain('media.example/first');
    } else {
        expect($html)
            ->toContain('property="'.$property.'"')
            ->toContain('https://media.example/first');
    }
})->with([
    'photo' => ['photo', false, [['url' => 'https://media.example/first']], 'og:image'],
    'photo album' => ['photo:album', false, [['url' => 'https://media.example/first']], 'og:image'],
    'sensitive photo' => ['photo', true, [['url' => 'https://media.example/first']], null],
    'sensitive photo album' => ['photo:album', true, [['url' => 'https://media.example/first']], null],
    'video' => ['video', false, [['url' => 'https://media.example/first']], 'og:video'],
    'video album' => ['video:album', false, [['url' => 'https://media.example/first']], 'og:video'],
    'sensitive video' => ['video', true, [['url' => 'https://media.example/first']], null],
    'sensitive video album' => ['video:album', true, [['url' => 'https://media.example/first']], null],
    'album without attachments' => ['photo:album', false, [], null],
]);

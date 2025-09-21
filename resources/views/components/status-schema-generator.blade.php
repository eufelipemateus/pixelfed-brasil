@php
$commonPublisher = [
    "@type" => "Organization",
    "name" => $siteName ?? 'Pixelfed Brasil',
    "logo" => [
        "@type" => "ImageObject",
        "url" => $siteLogo ?? asset('logo.png'),
    ],
];

$contentLocation = !is_null($locationName)
    ? ['@type' => 'Place', 'name' => $locationName]
    : null;
@endphp

@if($type === 'image')
<script type="application/ld+json">
{!! json_encode([
    '@@context' => "https://schema.org/",
    '@type' => "ImageObject",
    'contentUrl' => $url,
    'license' => $license ?? null,
    'acquireLicensePage' => $acquireLicensePage ?? null,
    'creditText' => $creditText ?? null,
    'creator' => [
        '@type' => 'Person',
        'name' => $creator,
    ],
    'copyrightNotice' => $copyrightNotice ?? null,
    'publisher' => $commonPublisher,
    'datePublished' => $publishedAt,
    'caption' => $caption ?? null,
    'contentLocation' => $contentLocation
]) !!}
</script>
@endif

@if($type === 'video')
<script type="application/ld+json">
{!! json_encode([
    '@@context' => "https://schema.org",
    '@type' => "VideoObject",
    'name' => $name ?? 'Vídeo',
    'description' => $description ?? $caption,
    'thumbnailUrl' => $thumbnail ?? [],
    'uploadDate' => $publishedAt,
    'contentUrl' => $url,
    'embedUrl' => $embedUrl ?? $url,
    'interactionStatistic' => [
        '@type' => 'InteractionCounter',
        'interactionType' => ['@type' => 'WatchAction'],
        'userInteractionCount' => $views ?? 0
    ],
    'regionsAllowed' => $regionsAllowed ?? [],
    'creator' => [
        '@type' => 'Person',
        'name' => $creator,
        'url' => $creatorUrl ?? null,
    ],
    'publisher' => $commonPublisher,
    'contentLocation' => $contentLocation
], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
</script>
@endif

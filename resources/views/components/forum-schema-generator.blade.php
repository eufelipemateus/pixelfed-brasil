<script type="application/ld+json">
    {!!json_encode([
            "@@context" => "https://schema.org",
            "@type" => "SocialMediaPosting",
            "mainEntityOfPage" => $mainEntityOfPage,
            "headline" => $headline,
            "articleBody" => $articleBody,
            "url" => $url,
            "author" => [
                "@type" => "Person",
                "name" => $creator['name'],
                "url" => $creator['url'] ?? null,
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => config_cache('app.name'),
                "url" => "https://pixelfed.com.br",
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => url('/img/pixelfed-icon-color.png'),
                ],
            ],
            "datePublished" => $datePublished,
            "image" => $image ?? null,
            "video" => $video ?? null,
            "interactionStatistic" => [
                [
                    "@type" => "InteractionCounter",
                    "interactionType" => "https://schema.org/LikeAction",
                    "userInteractionCount" => $totalLikes,
                ],
                [
                    "@type" => "InteractionCounter",
                    "interactionType" => "https://schema.org/ShareAction",
                    "userInteractionCount" => $totalShares,
                ],

                [
                    "@type" => "InteractionCounter",
                    "interactionType" => "https://schema.org/CommentAction",
                    "userInteractionCount" => $totalComments
                ]
            ],
            "comment" => $comments ?? null,
        ], JSON_PRETTY_PRINT) !!}
</script>

<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Services\StatusService;
use DateTime;
use App\Status;

class ForumSchemaGenerator extends Component
{

    public string $datePublished;
    public string $mainEntityOfPage;
    public string $url;
    public ?string $headline = null;
    public ?string $articleBody = null;
    public ?array $creator = null;
    public ?array $video = null;
    public ?array $image = null;
    public int $totalLikes = 0;
    public int $totalComments = 0;
    public int $totalShares = 0;
    public array $comments = [];

    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $status
    ) {
        //

        $original = $status['in_reply_to_id'] ?  StatusService::get($status['in_reply_to_id'])  :  $status;

        $this->datePublished = ($status['created_at'] ?? (new \DateTime())->format(DATE_ATOM));
        $this->mainEntityOfPage = $original['url'];
        $this->url = $status['url'];

        $domain = '';
        if (isset($original['account']) && isset($original['account']['url'])) {
            $domain =  '@' . parse_url($original['account']['url'], PHP_URL_HOST);
        }

        $username = $original['account']['username'] ?? 'desconhecido';
        $this->headline = 'Imagem por @' . $username . $domain . ', via Pixelfed Brasil';
        $this->articleBody = $original['content_text'] ?? '';

        $this->creator = [
            'name' => $original['account']['display_name'] ?? $original['account']['username'] ?? 'desconhecido',
            'url' => $original['account']['url'] ?? null,
        ];

        $mediaCount = $original['media_attachments'] && count($original['media_attachments']) ? count($original['media_attachments']) : 0;

        if ($mediaCount && ($original['pf_type'] === "photo" || $original['pf_type'] === "photo:album")) {
            $this->getImage($original);
        } elseif ($mediaCount && ($original['pf_type'] === "video" || $original['pf_type'] === "video:album")) {
            $this->getVideo($original);
        }


        $this->totalShares = $original['reblogs_count'];
        $this->totalLikes = $original['favourites_count'];
        $this->totalComments = $original['reply_count'];

        $this->getComments($original['id'], $status['id']);
    }


    public function getVideo($status)
    {
        $this->video = count($status['media_attachments']) ?  [
            "@type" => "VideoObject",
            "contentUrl" => $status['media_attachments'][0]['url'],
            "name" => $this->headline,
            "uploadDate" => (new DateTime($status['created_at']))->format(DateTime::ATOM),
            "thumbnailUrl" => $status['media_attachments'][0]['preview_url'] ?? null,
            "description" => $this->articleBody,
        ] : null;
    }

    public function getImage($status)
    {
        $this->image = count($status['media_attachments']) ?  [
            "@type" => "ImageObject",
            "contentUrl" => $status['media_attachments'][0]['url'],
            "name" => $this->headline,
            "uploadDate" => (new DateTime($status['created_at']))->format(DateTime::ATOM),
            "thumbnailUrl" => $status['media_attachments'][0]['preview_url'] ?? null,
        ] : null;
    }

    public function getComments($originalID, $statusID)
    {
        $commentIds = Status::find($originalID)
            ->comments()
            ->limit(10)
            ->pluck('id');

        if ($commentIds->isEmpty()) {
            $this->comments = [];
            return;
        }

        if ($statusID !== null && $statusID != $originalID && !$commentIds->contains($statusID)) {
            $commentIds->push($statusID);
        }

        $commentsData = collect($commentIds)->map(function ($id) {
            return StatusService::get($id, false);
        });

        $this->comments = $commentsData->map(function ($comment) {
            return [
                "@type" => "Comment",
                "author" => [
                    "@type" => "Person",
                    "name" => $comment['account']['display_name']
                        ?? $comment['account']['username']
                        ?? 'desconhecido',
                    "url" => $comment['account']['url'] ?? null,
                ],
                "datePublished" => (new DateTime($comment['created_at']))->format(DateTime::ATOM),
                "url" => $comment['url'] ?? null,
                "text" => $comment['content_text'] ?? '',
                "interactionStatistic" => [
                    [
                        "@type" => "InteractionCounter",
                        "interactionType" => "https://schema.org/LikeAction",
                        "userInteractionCount" => $comment['favourites_count'] ?? 0,
                    ],
                    [
                        "@type" => "InteractionCounter",
                        "interactionType" => "https://schema.org/ShareAction",
                        "userInteractionCount" => $comment['reblogs_count'] ?? 0,
                    ],

                    [
                        "@type" => "InteractionCounter",
                        "interactionType" => "https://schema.org/CommentAction",
                        "userInteractionCount" => $comment['reply_count'] ?? 0,
                    ]
                ],
            ];
        })->toArray();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forum-schema-generator');
    }
}

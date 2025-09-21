<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusSchemaGenerator extends Component
{
    public $type;
    public $url;
    public $creator;
    public $creatorUrl;
    public $caption;
    public $publishedAt;
    public $siteName;
    public $siteLogo;

    // Campos adicionais
    public $license;
    public $acquireLicensePage;
    public $creditText;
    public $copyrightNotice;
    public $locationName;

    public $name;
    public $description;
    public $duration;
    public $thumbnail;
    public $embedUrl;
    public $views;
    public $regionsAllowed;

    public function __construct(
        $type,
        $url,
        $creator,
        $creatorUrl,
        $caption = "",
        $publishedAt,
        $siteName = "Pixelfed Brasil",
        $siteLogo = "/logo.png",
        // Imagem
        $license = null,
        $acquireLicensePage = null,
        $creditText = null,
        $copyrightNotice = null,
        $locationName = null,

        // Vídeo
        $name = null,
        $description = null,
        $thumbnail = null,
        $embedUrl = null,
        $views = 0,
        $regionsAllowed = null
    ) {
        $this->type = $type;
        $this->url = $url;
        $this->creator = $creator;
        $this->creatorUrl = $creatorUrl;
        $this->caption = $caption;
        $this->publishedAt = $publishedAt;
        $this->siteName = $siteName;
        $this->siteLogo = $siteLogo;

        $this->license = $license;
        $this->acquireLicensePage = $acquireLicensePage;
        $this->creditText = $creditText;
        $this->copyrightNotice = $copyrightNotice;
        $this->locationName = $locationName;

        $this->name = $name;
        $this->description = $description;
        $this->thumbnail = $thumbnail;
        $this->embedUrl = $embedUrl;
        $this->views = $views;
        $this->regionsAllowed = $regionsAllowed;
    }

    public function render(): View|Closure|string
    {
        return view('components.status-schema-generator');
    }
}

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>

    <title>{{ __('site.app_title') }}</title>
    <meta name="description" content="{{ __('site.app_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://pixelfed.com.br/app">
    <meta property="og:title" content="{{ __('site.app_og_title') }}">
    <meta property="og:description" content="{{ __('site.app_og_description') }}">
    <meta property="og:image" content="https://pixelfed.com.br/img/pixelfed.png">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://pixelfed.com.br/app">
    <meta property="twitter:title" content="Pixelfed Brasil">
    <meta property="twitter:description" content="{{ __('site.app_description') }}">
    <meta property="twitter:image" content="https://pixelfed.com.br/img/pixelfed.png">

    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "SoftwareApplication",
            "@@id": "https://pixelfed.app.br/#software",
            "name": "Aplicativo Pixelfed Brasil",
            "operatingSystem": ["Web", "Linux", "Windows", "Android"],
            "applicationCategory": "SocialNetworkingApplication",
            "offers": {
                "@@type": "Offer",
                "price": "0",
                "priceCurrency": "BRL"
            },
            "downloadUrl": [
                "https://pixelfed.app.br",
                "https://snapcraft.io/pixelfed-brasil",
                "https://www.microsoft.com/store/apps/9ntgldsdchpx",
                "https://play.google.com/store/apps/details?id=br.com.pixefed.app"
            ],
            "aggregateRating": {
                "@@type": "AggregateRating",
                "ratingValue": "5",
                "reviewCount": "100"
            },
            "publisher": {
                "@@id": "https://pixelfed.com.br/#organization"
            }
        }
    </script>
    <script type="application/ld+json">
        {
            "@@ontext": "https://schema.org",
            "@@type": "WebSite",
            "@@id": "https://pixelfed.com.br/#website",
            "url": "https://pixelfed.com.br",
            "name": "Pixelfed Brasil",
            "publisher": {
                "@id": "https://pixelfed.com.br/#organization"
            }
        }
    </script>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <main id="content">
        <div class="container">
            <p class="text-right mt-3">
                <a href="/" class="font-weight-bold text-dark">{{ __('site.home') }}</a>
                <a href="{{route('newsroom.index')}}" class="ml-4 font-weight-bold text-dark">{{ __('site.newsroom') }}</a>
            </p>
            <h1 class="mb-4 text-center">{{ __('site.app_title') }}</h1>
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8">
                    <div class="card shadow p-4 mb-4">
                        <h2 class="h4 font-weight-bold mb-3 text-center">{{ __('site.app_features_title') }}</h2>
                        <ul class="list-unstyled mb-0" style="font-size:1.15rem;">
                            <li class="mb-2"><span style="font-size:1.5em;">🌍</span> {{ __('site.app_feature_fediverse') }}</li>
                            <li class="mb-2"><span style="font-size:1.5em;">🇧🇷</span> {{ __('site.app_feature_portuguese') }}</li>
                            <li class="mb-2"><span style="font-size:1.5em;">🔒</span> {{ __('site.app_feature_privacy') }}</li>
                            <li class="mb-2"><span style="font-size:1.5em;">✨</span> {{ __('site.app_feature_photography') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-3 mb-4">
                    <a href="https://pixelfed.app.br" class="text-decoration-none" target="_blank">
                        <div class="card h-100 text-center shadow-sm p-4">
                            <div style="font-size:2.5em;">🌐</div>
                            <h3 class="h5 mt-3 mb-2">{{ __('site.app_download_web') }}</h3>
                            <p class="mb-0 text-muted">{{ __('site.app_download_web_desc') }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <a href="https://snapcraft.io/pixelfed-brasil" class="text-decoration-none" target="_blank">
                        <div class="card h-100 text-center shadow-sm p-4">
                            <div style="font-size:2.5em;">🐧</div>
                            <h3 class="h5 mt-3 mb-2">{{ __('site.app_download_linux') }}</h3>
                            <p class="mb-0 text-muted">{{ __('site.app_download_linux_desc') }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <a href="https://apps.microsoft.com/detail/9ntgldsdchpx" class="text-decoration-none" target="_blank">
                        <div class="card h-100 text-center shadow-sm p-4">
                            <div style="font-size:2.5em;">🪟</div>
                            <h3 class="h5 mt-3 mb-2">{{ __('site.app_download_windows') }}</h3>
                            <p class="mb-0 text-muted">{{ __('site.app_download_windows_desc') }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <a href="https://play.google.com/store/apps/details?id=br.com.pixelfed.app" class="text-decoration-none" target="_blank">
                        <div class="card h-100 text-center shadow-sm p-4">
                            <div style="font-size:2.5em;">🤳</div>
                            <h3 class="h5 mt-3 mb-2">{{ __('site.app_download_android') }}</h3>
                            <p class="mb-0 text-muted">{{ __('site.app_download_android_desc') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>
    @include('layouts.partial.footer')
</body>

</html>

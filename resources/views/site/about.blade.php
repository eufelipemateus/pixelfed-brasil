<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">

    <title>Nossa História | Pixelfed Brasil</title>
    <meta name="description" content="Conheça a jornada do Pixelfed Brasil: Iniciada em 2022 e reconstruída em 2023. Soberania digital, LGPD e fotografia ética.">

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebPage",
      "name": "Sobre o Pixelfed Brasil",
      "description": "História e compromisso com a privacidade da instância Pixelfed Brasil.",
      "publisher": {
        "@@type": "Organization",
        "name": "Felipe Mateus",
        "location": "Brasil"
      }
    }
    </script>

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <style type="text/css">
        .section-spacer { height: 10vh; }
        .history-card { border-radius: 1.5rem; border: none; transition: 0.3s; }
        .timeline-number { font-size: 3rem; font-weight: 800; color: #10c5f8; opacity: 0.3; position: absolute; right: 20px; top: 10px; }
        .law-gradient { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 8px solid #28a745; border-radius: 1rem; }
        .local-badge { background: #10c5f8; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>
    <main id="content">
        <div class="container">
            <p class="text-right mt-3">
                <a href="/" class="font-weight-bold text-dark">Home</a>
                <a href="{{route('newsroom.index')}}" class="ml-4 font-weight-bold text-dark">Newsroom</a>
            </p>
        </div>

        <div class="px-4 py-5 my-5 text-center">
            <a href="/">
                <img class="d-block mx-auto mb-4" src="/img/pixelfed-icon-color.svg" alt="Logo" width="72" height="57">
            </a>
            <div class="local-badge">{{ __('site.manifest_history_badge') }}</div>
            <h1 class="display-4 font-weight-bold py-3">{{ __('site.about_title') }}</h1>
            <div class="col-lg-6 mx-auto">
                <p class="lead mb-4 font-weight-light" style="font-size: 24px; line-height: 1.5;">
                    {{ __('site.about_journey') }}
                </p>
            </div>
        </div>

        <div class="container">
            <div class="row align-items-stretch">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4">
                        <span class="timeline-number">2022</span>
                        <h3 class="font-weight-bold mt-4 text-primary">{{ __('site.history_2022_title') }}</h3>
                        <p class="h5 font-weight-light text-justify mt-3">
                            {{ __('site.history_2022_text') }}
                        </p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4 border-primary" style="border-width: 2px !important;">
                        <span class="timeline-number">2023</span>
                        <h3 class="font-weight-bold mt-4 text-primary">{{ __('site.history_2023_title') }}</h3>
                        <p class="h5 font-weight-light text-justify mt-3">
                            {{ __('site.history_2023_text') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-center">
                    <div class="card shadow-lg history-card p-5 bg-dark text-white">
                        <h2 class="font-weight-bold mb-3">{{ __('site.innovation_forks_title') }}</h2>
                        <p class="h4 font-weight-light">
                            {!! __('site.innovation_forks_text') !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-spacer"></div>

        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="law-gradient p-5 shadow">
                        <h2 class="display-5 font-weight-bold mb-4">{{ __('site.commitment_lgpd_title') }}</h2>
                        <p class="h4 font-weight-light mb-4 text-justify">
                            {!! __('site.commitment_lgpd_text') !!}
                        </p>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-gavel fa-2x text-success mr-3"></i>
                            <span class="h5 mb-0">{{ __('site.marco_civil_text') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 p-5 text-center">
                    <h2 class="display-4 font-weight-bold text-primary">0%</h2>
                    <p class="h4 font-weight-light">{{ __('site.tracking_algorithms') }}</p>
                    <hr>
                    <h2 class="display-4 font-weight-bold text-primary">100%</h2>
                    <p class="h4 font-weight-light">{{ __('site.chronological_order') }}</p>
                </div>
            </div>
        </div>

        <div class="section-spacer"></div>

        <div id="stats" class="container py-5">
            <div class="row text-center">
                <div class="col">
                    <p class="display-4 font-weight-bold">
                        {!! str_replace(':count', '<span class="text-primary">'.$user_count.'</span>', __('site.join_members')) !!}
                    </p>
                    <div class="mt-5">
                        <a href="/register" class="btn btn-primary btn-lg px-5 py-3 shadow-lg font-weight-bold" style="border-radius: 50px;">{{ __('site.create_account_terms') }}</a>
                    </div>
                    <p class="mt-4 text-muted">
                        {!! str_replace(':terms_url', route('site.terms'), __('site.accept_terms_text')) !!}
                    </p>
                </div>
            </div>
        </div>

        <div class="section-spacer"></div>
    </main>
    @include('layouts.partial.footer')
</body>
</html>

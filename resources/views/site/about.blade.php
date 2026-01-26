<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">

    <title>{{ __('site.about_title') }}</title>
    <meta name="description" content="{{ __('site.about_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://pixelfed.com.br/about">
    <meta property="og:title" content="Pixelfed Brasil - A Maior Instância Brasileira do Fediverso">
    <meta property="og:description" content="Rede social de fotos ética, federada e sem algoritmos. Descubra a soberania digital na maior instância Pixelfed do Brasil.">
    <meta property="og:image" content="https://pixelfed.com.br/img/pixelfed.png">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://pixelfed.com.br/about">
    <meta property="twitter:title" content="Pixelfed Brasil">
    <meta property="twitter:description" content="A maior instância brasileira do Pixelfed. Fotografia ética e federada.">
    <meta property="twitter:image" content="https://pixelfed.com.br/img/pixelfed.png">

    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "Organization",
            "@@id": "https://pixelfed.com.br/#organization",
            "name": "Pixelfed Brasil",
            "alternateName": ["Pixelfed.com.br", "Pixelfed BR"],
            "url": "https://pixelfed.com.br",
            "logo": "https://pixelfed.com.br/img/pixelfed.png",
            "description": "Pixelfed Brasil é uma instância brasileira independente do Pixelfed, uma rede social federada de compartilhamento de fotos e vídeos, sem anúncios e sem algoritmos de manipulação, parte do Fediverso.",
            "foundingDate": "2022",
            "founder": {
                "@@type": "Person",
                "name": "Felipe Mateus",
                "url": "https://pixelfed.com.br/eufelipemateus",
                "sameAs": [
                    "https://github.com/eufelipemateus",
                    "https://felipemateus.com"
                ]
            },
            "address": {
                "@@type": "PostalAddress",
                "addressCountry": "BR"
            },
            "contactPoint": {
                "@@type": "ContactPoint",
                "contactType": "customer support",
                "email": "suporte@felipemateus.com",
                "url": "https://pixelfed.com.br/contact"
            },
            "sameAs": [
                "https://apoia.se/pixelfedbrasil",
                "https://pixelfed.com.br/PixelfedBrasil",
                "https://github.com/eufelipemateus/pixelfed-brasil"
            ],
            "mainEntityOfPage": {
                "@@type": "WebPage",
                "@@id": "https://pixelfed.com.br/about"
            },
            "knowsAbout": [
                "Fediverso",
                "ActivityPub",
                "Pixelfed",
                "Redes sociais descentralizadas",
                "LGPD"
            ]
        }
    </script>
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "WebSite",
            "@@id": "https://pixelfed.com.br/#website",
            "url": "https://pixelfed.com.br",
            "name": "Pixelfed Brasil",
            "publisher": {
                "@@id": "https://pixelfed.com.br/#organization"
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
                <a href="/" class="font-weight-bold text-dark">{{ __('site.home') }}</a>
                <a href="{{route('newsroom.index')}}" class="ml-4 font-weight-bold text-dark">{{ __('site.newsroom') }}</a>
            </p>
        </div>

        <section class="px-4 py-5 my-5 text-center">
            <a href="/">
                <img class="d-block mx-auto mb-4" src="/img/pixelfed_transparent.png" alt="Logo Pixelfed Brasil"  width="120"  style="filter: drop-shadow(0 2px 8px #10c5f855);">
            </a>
            <div class="local-badge">Pixelfed Brasil</div>
            <h1 class="display-4 font-weight-bold py-3">{{ __('site.about_title') }}</h1>
            <div class="col-lg-7 mx-auto">
                <p class="lead mb-4 font-weight-light" style="font-size: 22px; line-height: 1.6;">
                    {!! __('site.about_intro') !!}
                </p>
            </div>
        </section>

        <section class="container mb-5">
            <div class="row align-items-stretch">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4">
                        <h2 class="h4 font-weight-bold mb-3">{{ __('site.about_section_pixelfed_title') }}</h2>
                        <p class="mb-0" style="font-size:1.08rem;">
                            {{ __('site.about_section_pixelfed_text') }}
                        </p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4 border-primary" style="border-width: 2px !important;">
                        <h2 class="h4 font-weight-bold mb-3">{{ __('site.about_section_pixelfedbr_title') }}</h2>
                        <p class="mb-0" style="font-size:1.08rem;">
                            {!! __('site.about_section_pixelfedbr_text') !!}
                        </p>
                    </div>
                </div>
            </div>

            <div class="row align-items-stretch">
                <div class="col-md-12 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4">
                        <h2 class="font-weight-bold mb-3"><i class="fas fa-bullseye icon-bullet"></i> {{ __('site.our_mission') }}</h2>
                        <p class="mb-0" style="font-size:1.08rem;">
                            {!! __('site.our_mission_text') !!}
                        </p>
                    </div>
                </div>
                <div class="col-md-12 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4 border-primary" style="border-width: 2px !important;">
                        <h2 class="font-weight-bold mb-3"><i class="fas fa-star icon-bullet"></i> {{ __('site.why_pixelfed_brasil') }}</h2>
                        <ul class="mb-0" style="font-size:1.08rem;">
                            <li><b>{{ __('site.benefit_ethics_title') }}:</b> {{ __('site.benefit_ethics_text') }}</li>
                            <li><b>{{ __('site.benefit_ads_title') }}:</b> {{ __('site.benefit_ads_text') }}</li>
                            <li><b>{{ __('site.benefit_data_title') }}:</b> {{ __('site.benefit_data_text') }}</li>
                            <li><b>{{ __('site.benefit_local_title') }}:</b> {{ __('site.benefit_local_text') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row align-items-stretch">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4">
                        <h2 class="font-weight-bold mb-3"><i class="fas fa-user-astronaut icon-bullet"></i> {{ __('site.who_makes_pixelfed_brasil') }}</h2>
                        <p class="mb-0" style="font-size:1.08rem;">
                            {{ __('site.founder_intro') }}

                        <ul class="list-unstyled mt-3">
                            <li>{!! __('site.founder_name') !!} (<a href="https://pixelfed.com.br/eufelipemateus" target="_blank">Perfil</a>)</li>
                            <li><b>Links:</b> <a href="https://github.com/eufelipemateus" target="_blank">GitHub</a> | <a href="https://felipemateus.com" target="_blank">Site pessoal</a></li>
                        </ul>
                        </p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4 border-success" style="border-width: 2px !important;">
                        <h2 class="font-weight-bold mb-3"><i class="fas fa-heart icon-bullet"></i> {{ __('site.support_our_instance') }}</h2>
                        <p class="mb-2" style="font-size:1.08rem;">
                            {{ __('site.support_our_instance_text') }}
                        </p>
                        <p class="mb-0" style="font-size:1.08rem;">
                            {{ __('site.apoie_cta') }}
                            <a href="{{ route('site.donate') }}" class="btn  btn-sm ml-2" style="background: #ff6600; color: #fff;" target="_blank"><i class="fas fa-hand-holding-heart mr-2"></i> {{ __('site.donate') }}</a>
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li><b>{{ __('site.apoie_links') }}:</b> <a href="https://apoia.se/pixelfedbrasil" target="_blank">{{ __('site.apoie_apoia') }}</a> | <a href="https://pixelfed.com.br/PixelfedBrasil" target="_blank">{{ __('site.apoie_perfil') }}</a> | <a href="https://github.com/eufelipemateus/pixelfed-brasil" target="_blank">{{ __('site.apoie_github') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>


            <div class="row align-items-stretch">
                <div class="col-md-12 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4">
                        <h2 class="font-weight-bold mb-3"><i class="fas fa-bullseye icon-bullet"></i> {{ __('site.not_what_we_are_title') }}</h2>
                        <p class="mb-0" style="font-size:1.08rem;">
                            {!! __('site.not_what_we_are_text') !!}
                        </p>
                    </div>
                </div>
            </div>
        </section>

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

        <div class="container ">
            <div class="row align-items-stretch">
                <div class="col-md-12 mb-4">
                    <div class="card h-100 shadow-lg history-card p-4 border-info" style="border-width: 2px !important;">
                        <h2 class="font-weight-bold mb-3"><i class="fas fa-envelope-open-text icon-bullet"></i> {{ __('site.contact_admins') }}</h2>
                        <p class="mb-2" style="font-size:1.08rem;">{{ __('site.you_can_contact_the_admins') }}</p>
                        <ul class="mb-2" style="font-size:1.08rem;">
                            <li><b>{{ __('site.contact-us') }}:</b> <a href="https://pixelfed.com.br/contact" target="_blank">pixelfed.com.br/contact</a></li>
                            <li><b>{{ __('site.contact_by_email') }}</b> <a href="mailto:suporte@felipemateus.com">suporte@felipemateus.com</a></li>
                            <li><b>{{ __('site.localization') }}:</b> Brasil</li>
                        </ul>
                        <p class="mb-0" style="font-size:1.08rem;"><b>{{ __('site.contact_type')  }}</b> Suporte ao usuário</p>
                    </div>
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

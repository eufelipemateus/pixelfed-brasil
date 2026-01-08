@extends('layouts.app')

@section('title', __('site.donate_title') ?? 'Doação')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 text-center">
            <h1 class="display-4 font-weight-bold mb-4">{{ __('site.donate_title') }}</h1>
            <p class="lead text-secondary mb-5">
                {!! __('site.donate_intro') !!}
            </p>

            <div class="card shadow-lg donate-card p-5 mb-5 border-0">
                <div class="row align-items-center text-left text-md-center">
                    <div class="col-12">
                        <img src="/img/pixelfed-icon-color.svg" width="80" class="mb-4" alt="Pixelfed Logo">
                        <h2 class="font-weight-bold">Apoia.se/pixelfedbrasil</h2>
                        <p class="h5 font-weight-light mb-4">{{ __('site.donate_monthly_help') }}</p>

                        <div class="py-3">
                            <a href="https://apoia.se/pixelfedbrasil" target="_blank" rel="noopener" class="apoia-btn shadow-lg" style="background: #ff6600; color: #fff; border-radius: 30px; padding: 18px 36px; font-size: 1.2rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; display: inline-block; transition: background 0.2s;">
                                {{ __('site.donate_button') }}
                            </a>
                        </div>

                        <p class="small text-muted mt-3">
                            {{ __('site.donate_redirect_notice') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="row mt-5 text-left">
                <div class="col-md-4">
                    <div class="p-3">
                        <h5 class="font-weight-bold">{{ __('site.donate_server_title') }}</h5>
                        <p class="small text-muted">{{ __('site.donate_server_desc') }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3">
                        <h5 class="font-weight-bold">{{ __('site.donate_storage_title') }}</h5>
                        <p class="small text-muted">{{ __('site.donate_storage_desc') }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3">
                        <h5 class="font-weight-bold">{{ __('site.donate_maintenance_title') }}</h5>
                        <p class="small text-muted">{!! __('site.donate_maintenance_desc') !!}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

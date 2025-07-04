@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="col-12 col-md-8 offset-md-2">
        @if (session('status'))
        <div class="alert alert-success">
            <p class="font-weight-bold mb-0">{{ session('status') }}</p>
        </div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger">
            <p class="font-weight-bold mb-0">{{ session('error') }}</p>
        </div>
        @endif

        @if($isInvalidEmail)
        <div class="card shadow-none border">
            <div class="card-header font-weight-bold bg-white">Email Inválido</div>
            <div class="card-body">
                Seu email esta inválido não pode ser verificado. Você pode alterar seu email clicando no botão abaixo.
                <p class="mt-3 mb-0 small text-muted"><a href="/settings/email" class="font-weight-bold">Click Aqui</a> para alerar seu email.</p>
            </div>
        </div>
        @elseif(Auth::user()->email_verified_at)
        <p class="lead text-center mt-5">Seu email já foi verificado. <a href="/" class="font-weight-bold">Clique aqui</a> para ir pro Feed.</p>
        @else
        <div class="card shadow-none border">
            <div class="card-header font-weight-bold bg-white">Confirme Endereço de Email</div>
            <div class="card-body">
                <p class="lead text-break">Você precisa confirmar seu endereço de email <span class="font-weight-bold">{{Auth::user()->email}}</span> antes de prosseguir.</p>
                @if(!$recentSent)
                <form method="post">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block py-1 font-weight-bold">Enviar email de confirmação</button>
                </form>
                @else
                <button class="btn btn-primary btn-block py-1 font-weight-bold" disabled>Confirme email enviado</button>
                @endif
                <p class="mt-3 mb-0 small text-muted"><a href="/settings/email" class="font-weight-bold">Click Aqui</a> para alerar seu email.</p>
            </div>
        </div>

        @if($recentSent)
        <div class="card mt-3 border shadow-none">
            <div class="card-body">
                <p class="mb-0 text-muted">Se voce esta enfrentando probelmas pra verificar seu email  <a href="/i/verify-email/request" class="font-weight-bold">Solicite uma verificação manual.</a>.</p>
            </div>
        </div>
        @endif

        @endif
    </div>
</div>
@endsection
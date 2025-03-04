@component('mail::message')
<div class="otcontainer">

## Verifique seu endereço de e-mail

<p class="ottext">
    Olá,
</p>

<p class="ottext">
    Obrigado por se cadastrar no {{config('app.name')}}!
</p>

<p class="ottext">
    Para concluir seu registro, insira o seguinte código de verificação:
</p>

<div class="otcode">
    {{ $code }}
</div>

<p class="ottext">
    Este código expirará em 4 horas. Se você não solicitou esta verificação, ignore este e-mail.
</p>

<div class="otfooter">
<p>Se estiver com problemas para usar o código de verificação, entre em contato com nossa <a href="{{route('site.contact')}}">equipe de suporte</a>.</p>
</div>

</div>
@endcomponent

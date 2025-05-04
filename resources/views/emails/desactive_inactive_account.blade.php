@component('mail::message')
# Sua conta foi Desativada

Olá,

Informamos que sua conta foi marcada como **Desativada** devido à ausência de atividade.

Caso deseje reativá-la, basta acessar a plataforma com seu login normalmente.

<x-mail::button :url="config('app.url')">
Reativar minha conta
</x-mail::button>

Se você tiver qualquer dúvida ou precisar de suporte, entre em contato conosco.

Obrigado,<br>
{{ config('app.name') }}
@endcomponent

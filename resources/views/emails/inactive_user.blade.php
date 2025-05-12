@component('mail::message')
# Você ainda está aí?

Olá {{ $user->name }},

Notamos que você se cadastrou em nosso sistema, mas nunca mais voltou. Gostaríamos de saber o que deseja fazer com sua conta.

Você pode:

- **Ativar sua conta e continuar aproveitando o servidor Pixelfed Brasil.**
- **Apagar sua conta permanentemente.**

Escolha uma das opções clicando nos botões abaixo:

@component('mail::button', ['url' => route('login')])
Voltar a Plataforma
@endcomponent

@component('mail::button', ['url' => route('settings.remove.permanent'), 'color' => 'red'])
Apagar Conta
@endcomponent

Caso não tome nenhuma ação, sua conta permanecerá inativa. Se precisar de ajuda, estamos à disposição.

Obrigado,
{{ config('app.name') }}
@endcomponent

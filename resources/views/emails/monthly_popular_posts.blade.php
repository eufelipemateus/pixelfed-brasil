<center>
    <h1>Olá, {{ $user->name }}</strong>!</h1>

    <h2>📷🔥 Destaques do Mês de <span style="text-transform: capitalize;">{{ $mes }}</span> no {{ config('app.name') }}!</h2>

    <h3>Posts populares em {{$mes}}:</h3>
    <x-post-photo-grid-component :posts="$posts" />

    <h3>Usuários Poulares em {{$mes}}:</h3>
    <x-users-popular-component :users="$popularUsers" />

    <p>
        <a href="https://pixelfed.com.br" style="display: inline-block; padding: 10px 15px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">
            Ver mais no {{ config('app.name') }}
        </a>
    </p>

    <p>Obrigado, <br> {{ config('app.name') }}</p>

</center>


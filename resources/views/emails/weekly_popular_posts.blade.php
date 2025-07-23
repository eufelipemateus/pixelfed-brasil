    <center>
        <h2>Olá, {{ $user->name }}</strong>!</h2>

        <h3>📷🔥 Publicações populares da Semana no {{ config('app.name') }}</h3>

        <p>Aqui estão os publicações mais curtidos da semana:</p>

        @foreach ($posts as $post)
        <x-post :post="$post" />
        @endforeach

        @if(config('pixelfed.user_invites.enabled'))
        <h3> Promotores Destaque da Semana</h3>
        <p>Veja quem são os usuários que mais promoveram o {{ config('app.name') }} esta semana:</p>
        @if($promoters->isNotEmpty())
        <x-users-popular-component :users="$promoters" />
        @else
        <p style="margin-top: 2%;"><strong>Nenhum promotor esta semana. Que tal convidar amigos para se juntar ao {{ config('app.name') }}?</strong></p>
        @endif

        <p>
            Torne-se um promotor! Compartilhe seu link de convite:<br>
            <a href="{{ $user->inviteLink() }}">{{ $user->inviteLink() }}</a>
        </p>
        @endif
        <p>
            <a href="https://pixelfed.com.br" style="display: inline-block; padding: 10px 15px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">
                Ver mais no {{ config('app.name') }}
            </a>
        </p>

        <p>Obrigado, <br> {{ config('app.name') }}</p>

    </center>
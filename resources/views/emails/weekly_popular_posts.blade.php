    <center>
        <h2>Olá, {{ $user->name }}</strong>!</h2>

        <h3>📷🔥 Top Posts da Semana no Pixelfed</h3>

        <p>Aqui estão os posts mais curtidos da semana:</p>

        @foreach ($posts as $post)
            <x-post :post="$post" />
        @endforeach

        <p>
            <a href="https://pixelfed.com.br" style="display: inline-block; padding: 10px 15px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">
                Ver mais no Pixelfed
            </a>
        </p>

        <p>Obrigado, <br> {{ config('app.name') }}</p>

    </center>


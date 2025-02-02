<?php

return [

    'remove.permanent.title' => 'Excluir sua conta',
    'remove.permanent.body' => '<p>Olá <span class="font-weight-bold">'.Auth::user()->username.'</span>,</p>

<p>Lamentamos saber que você deseja excluir sua conta.</p>

<p class="pb-1">Se você só precisa de uma pausa, pode sempre <a href="'.route('settings.remove.temporary').'">desativar temporariamente</a> sua conta.</p>

<p class="">Ao pressionar o botão abaixo, suas fotos, comentários, curtidas, amizades e todos os outros dados serão removidos permanentemente e não poderão ser recuperados. Se decidir criar outra conta no Pixelfed no futuro, não poderá se cadastrar novamente com o mesmo nome de usuário nesta instância.</p>

<div class="alert alert-danger my-5">
  <span class="font-weight-bold">Atenção:</span> Alguns servidores remotos podem conter seus dados públicos (status, avatares, etc.), e esses dados não serão excluídos até que o suporte à federação seja implementado.
</div>',


    'remove.permanent.confirm_check' => 'Confirmo que esta ação não é reversível e resultará na exclusão permanente da minha conta.',
    'remove.permanent.confirm_button' => 'Excluir permanentemente minha conta',

    'remove.temporary.title' => 'Desativar Temporariamente Sua Conta',
    'remove.temporary.body' => '<p>Olá <span class="font-weight-bold">'.Auth::user()->username.'</span>,</p>

<p>Você pode desativar sua conta em vez de excluí-la. Isso significa que sua conta ficará oculta até que você a reative fazendo login novamente.</p>

<p class="pb-1">Você só pode desativar sua conta uma vez por semana.</p>

<p class="font-weight-bold">Mantendo Seus Dados Seguros</p>
<p class="pb-3">Nada é mais importante para nós do que a segurança desta comunidade. As pessoas confiam em nós ao compartilhar momentos de suas vidas no Pixelfed. Por isso, nunca faremos concessões quando se trata de proteger seus dados.</p>

<p class="pb-2">Ao pressionar o botão abaixo, suas fotos, comentários e curtidas ficarão ocultos até que você reative sua conta fazendo login novamente.</p>
',

    'remove.temporary.button' => 'Desativar Conta Temporariamente',
];

<?php

return [

	'helpcenter' => 'Centro de Ajuda',
	'whatsnew' => 'O que há de novo',

	'gettingStarted' => 'Como Começar',
	'sharingMedia' => 'Compartilhar Mídia',
	'profile' => 'Perfil',
	'stories' => 'Stories',
	'hashtags' => 'Hashtags',
	'discover' => 'Descobrir',
	'directMessages' => 'Mensagens Diretas',
	'timelines' => 'Linha do Tempo',
	'embed'	=> 'Embed',

	'communityGuidelines' => 'Directrizes da Comunidade',
	'whatIsTheFediverse' => 'O que é o fediverse?',
	'controllingVisibility' => 'Controlar a Visibilidade',
	'blockingAccounts' => 'Bloqueio de contas',
	'safetyTips' => 'Dicas de Segurança',
	'reportSomething' => 'Reportar Algo',
	'dataPolicy' => 'Política de Dados',

	'taggingPeople' => 'Marcação de Pessoas',


	'howAccount' =>	'Como criar uma conta?',
	'howBio' => 'Como eu mudo minha bio?',

	'whatHahstag' =>'O que são hashtags?',
	'hashtagsTips' => 'Dicas de hashtags',

	'howPost' => 'Como eu criar um Post?',
	'howFilter' => 'Como adicionar um filtro?',

	'whatDiscover' =>	'O que é Descobrir?',
	'whatDiscoverCat' => 'O que é Descobrir Categorias?',

	'howPrivate' => 'Como privar minha conta?',
	'howSecure' =>'Como torna minha conta segura?',

	'howDirect' => 'Como Eu uso o Pixelfed Direct?',
	'hoUnsend' => 'Como eu cancelo o envio da menssagem?',

	'personal' => 'Linha do tempo pessoal',
	'public' => 'Linha do tempo publica',

	'contentRemoved' => 'Conteúdo que será removido',
	'contentExplicitly' => 'Conteúdo explicitamente proibido',

	'knowRules' =>'Conheça as regras',
	'make3Post' => 'Torne sua conta ou postagens privadas',

	'welcomePiexelfed' => ' Bem-Vindo ao '.config_cache('app.name').'!!',


	'howCreateAccountAask' =>'Como criar uma conta Pixelfed?',
	'howCreateAccountAnswer' => 'Para criar uma conta usando um navegador:'.
								'<ol>'.
								'<li>Acesse <a href=\''.config('app.url').'\'>'.config('app.url').'</a>.</li>'.
								'<li>Clique no link de registro no topo da página.</li>'.
								'<li>Digite seu nome, endereço de e-mail, nome de usuário e senha.</li>'.
								((config_cache('pixelfed.enforce_email_verification') != true) ?
									'<li>Aguarde um e-mail de verificação de conta, pode demorar alguns minutos.</li>'
								: '').
								'</ol>',

	'howUpdateProfileAsk' => 'Como atualizar as informações do perfil, como nome, bio, e-mail?',
	'howUpdateProfileAnswer' => 'Você pode atualizar sua conta visitando a página de  <a href=\''.route('settings').'\'>configurações da conta</a>.',

	'howInactiveUserAsk' => 'O que posso fazer se um nome de usuário que eu quero for usado, mas parecer inativo?',
	'howInactiveUserAnswer' => 'Se o nome de usuário desejado for usado, você poderá adicionar sublinhados, traços ou números para torná-lo único.',

	'whyChantUserAsk' => 'Por que não consigo alterar meu nome de usuário?',
	'whyChantUserAnswer' => 'Pixelfed é um aplicativo federado, alterar seu nome de usuário não é suportado em todos os <a href=\'https://en.wikipedia.org/wiki/ActivityPub\'>softwares federados</a>, portanto, não podemos permitir alterações de nome de usuário. Sua melhor opção é criar uma nova conta com o nome de usuário desejado.',

	'whyReceiveEmaillAsk' =>	'Recebi um e-mail informando que criei uma conta, mas nunca me inscrevi.',
	'whyReceiveEmaillAnswer' =>	'Alguém pode ter registrado seu e-mail por engano. Se você deseja que seu e-mail seja removido da conta, entre em contato com um administrador desta instância.',

	'whyExistsEmailAsk' => 'Não consigo criar uma nova conta porque já existe uma conta com este e-mail',
	'whyExistsEmailAnswer' => 'Você pode ter se registrado antes, ou alguém pode ter usado seu e-mail por engano. Entre em contato com um administrador desta instância.',

	'hashtagLead' => 'Uma hashtag — escrita com um símbolo # — é usada para indexar palavras-chave ou tópicos.',
	'hashtagInfo' => '<p class="font-weight-bold h5 pb-3">Usando hashtags para categorizar postagens por palavra-chave</p>'.
					'<ul>'.
					'<li class="mb-3 ">As pessoas usam o símbolo de hashtag (#) antes de uma frase ou palavra-chave relevante em suas postagens para categorizar essas postagens e torná-las mais detectáveis.</li>'.
					'<li class="mb-3 ">Quaisquer hashtags serão vinculadas a uma página de hashtag com outras postagens contendo a mesma hashtag.</li>'.
					'<li class="mb-3">Hashtags podem ser usadas em qualquer lugar em um post.</li>'.
					'<li class="">Você pode adicionar até 30 hashtags à sua postagem ou comentário.</li>'.
					'</ul>',

	'howFollowHashtagAsk' => 'Como faço para seguir uma hashtag?',
	'howFollowHashtagAnswer' => '<p>Você pode seguir hashtags no Pixelfed para se manter conectado com os interesses que lhe interessam.</p>'.
								'<p class=\'mb-0\'>Para seguir uma hashtag:</p>'.
								'<ol>'.
									'<li>Toque em qualquer hashtag (exemplo: #arte) que você vê no Pixelfed.</li>'.
									'<li>Toque em  <span class=\'font-weight-bold\'>Seguir</span>. Depois de seguir uma hashtag, você verá suas fotos e vídeos aparecerem no feed.</li>'.
								'</ol>'.
								'<p>Para deixar de seguir uma hashtag, toque na hashtag e toque em Deixar de seguir para confirmar.</p>'.
								'<p class=\'mb-0\'>'.
									'Você pode seguir até 20 hashtags por hora ou 100 por dia.'.
								'</p>',
	'hashtagTipsTitle' => 'Dicas de hashtag',
	'hashtagTips' => '<ul class=\'pt-3\'>'.
					'<li class=\'lead  mb-4\'>Você não pode adicionar espaços ou pontuação em uma hashtag, ou ela não funcionará corretamente.</li>'.
					'<li class=\'lead  mb-4\'>Quaisquer postagens públicas que contenham uma hashtag podem ser incluídas nos resultados de pesquisa ou nas páginas de descoberta.</li>'.
					'<li class=\'lead \'>Você pode pesquisar hashtags digitando uma hashtag na barra de pesquisa.</li>'.
					'</ul>'

];

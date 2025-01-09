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
					'</ul>',
   'sharingMediaTitle' => 'Compartilhando Fotos & Vídeos',
   'howCreatePostAask' => 'Como criar um post?',
   'howCreatePostAnswer' => '<div>
            Para criar uma postagem usando um navegador web no computador:
            <ol>
                <li>Acesse <a href="'.config('app.url').'">'.config('app.url').'</a>.</li>
                <li>Clique no link <i class="fas fa-camera-retro text-primary"></i> no topo da página.</li>
                <li>Envie sua(s) foto(s) ou vídeo(s), adicione uma legenda opcional e configure outras opções.</li>
                <li>Clique no botão <span class="font-weight-bold">Criar Postagem</span>.</li>
            </ol>
        </div>
        <div class="pt-3">
            Para criar uma postagem usando um navegador web no celular:
            <ol>
                <li>Acesse <a href="'.config('app.url').'">'.config('app.url').'</a>.</li>
                <li>Clique no botão <i class="far fa-plus-square fa-lg"></i> na parte inferior da página.</li>
                <li>Envie sua(s) foto(s) ou vídeo(s), adicione uma legenda opcional e configure outras opções.</li>
                <li>Clique no botão <span class="font-weight-bold">Criar Postagem</span>.</li>
            </ol>
        </div>',
            'howAddMultiplePhotosAsk' =>'Como adicionar várias fotos a uma postagem?',
            'howAddMultiplePhotosAnswer' => 'Durante o processo de composição, você pode selecionar vários arquivos de uma só vez ou adicionar cada foto/vídeo individualmente.',
            'howCaptionBeforeSharePhotoAsk' => 'Como adiciono uma legenda antes de compartilhar minhas fotos ou vídeos no Pixelfed?',
            'howCaptionBeforeSharePhotoAnswer' => '<div>
            Durante o processo de composição, você verá o campo <span class="font-weight-bold">Legenda</span>. As legendas são opcionais e limitadas a <span class="font-weight-bold">'.config_cache('pixelfed.max_caption_length').'</span> caracteres.
        </div>',
            'howAddFilterAsk' => 'Como adiciono um filtro às minhas fotos?',
            'howAddFilterAnswer' => '<div>
            <p class="text-center">
                <span class="alert alert-warning py-2 font-weight-bold">Este é um recurso experimental, filtros ainda não são federados!</span>
            </p>
            Para adicionar um filtro à mídia durante o processo de composição de uma postagem:
            <ol>
                <li>
                    Clique no botão <span class="btn btn-sm btn-outline-primary py-0">Opções <i class="fas fa-chevron-down fa-sm"></i></span> se a pré-visualização da mídia não estiver exibida.
                </li>
                <li>Selecione um filtro no menu suspenso <span class="font-weight-bold small text-muted">Selecionar Filtro</span>.</li>
            </ol>
        </div>
    ',
    'howAddDescriptionPhotoAsk' => 'Como adiciono uma descrição a cada foto ou vídeo para deficientes visuais?',
    'howAddDescriptionPhotoAnswer' => '<div>
        <p class="text-center">
            <span class="alert alert-warning py-2 font-weight-bold">Este é um recurso experimental!</span>
        </p>
        <p>
            Você precisa usar a interface de composição experimental encontrada <a href="/i/compose">aqui</a>.
        </p>
        <ol>
            <li>Adicione mídia clicando no botão <span class="btn btn-outline-secondary btn-sm py-0">Adicionar Foto/Vídeo</span>.</li>
            <li>Defina uma descrição da imagem clicando no botão <span class="btn btn-outline-secondary btn-sm py-0">Descrição da Mídia</span>.</li>
        </ol>
        <p class="small text-muted"><i class="fas fa-info-circle mr-1"></i> As descrições das imagens são federadas para instâncias onde são suportadas.</p>
    </div>
    ',
    'howMediaTypesCanUploadAsk' => 'Que tipos de fotos ou vídeos posso enviar?',
    'howMediaTypesCanUploadAnswer' => 'Você pode carregar os seguintes tipos de mídia:',
    'howDisablecommentsAsk' =>'Como posso desabilitar comentários/respostas na minha postagem?',
    'howDisablecommentsAnswer' => '<div>
        Para ativar ou desativar comentários/respostas usando um navegador no computador ou no celular:
        <ul>
            <li>Abra o menu e clique no botão <i class="fas fa-ellipsis-v text-muted mx-2 cursor-pointer"></i></li>
            <li>Clique em <span class="small font-weight-bold cursor-pointer">Ativar Comentários</span> ou <span class="small font-weight-bold cursor-pointer">Desativar Comentários</span></li>
        </ul>
    </div>
    ',
    'howManyTagMentionAsk'=> 'Quantas pessoas posso marcar ou mencionar em meus comentários ou postagens?',
    'howManyTagMentionAnswer' => 'Você pode marcar ou mencionar até 5 perfis por comentário ou postagem.',
    'whatArchiveMeanAsk' => 'O que significa arquivar posts?',
    'whatArchiveMeanAnswer' => '<div>
            Você pode arquivar suas postagens, o que impede qualquer pessoa de interagir ou visualizá-las.
            <br />
            <strong class="text-danger">Postagens arquivadas não podem ser excluídas ou ter qualquer outro tipo de interação. Você não receberá interações (comentários, curtidas, compartilhamentos) de outros servidores enquanto a postagem estiver arquivada.</strong>
            <br />
        </div>
        ',

    'howArchivePostAsk' => 'Como arquivar meus posts?',
    'howArchivePostAnswer' => '<div>
        Para arquivar suas postagens:
        <ul>
            <li>Navegue até a postagem</li>
            <li>Abra o menu e clique no botão <i class="fas fa-ellipsis-v text-muted mx-2 cursor-pointer"></i> ou <i class="fas fa-ellipsis-h text-muted mx-2 cursor-pointer"></i></li>
            <li>Clique em <span class="small font-weight-bold cursor-pointer">Arquivar</span></li>
        </ul>
    </div>
    ',
    'howUnarchivePostAsk' => 'Como desarquivar meus posts?',
    'howUnarchivePostAnswer' => '<div>
        Para desarquivar suas postagens:
        <ul>
            <li>Navegue até o seu perfil</li>
            <li>Clique na aba <strong>ARQUIVOS</strong></li>
            <li>Role até a postagem que deseja desarquivar</li>
            <li>Abra o menu e clique no botão <i class="fas fa-ellipsis-v text-muted mx-2 cursor-pointer"></i> ou <i class="fas fa-ellipsis-h text-muted mx-2 cursor-pointer"></i></li>
            <li>Clique em <span class="small font-weight-bold cursor-pointer">Desarquivar</span></li>
        </ul>
    </div>
    ',
    'discorverTitle' => 'Descobrir',
    'discoversubTitle' => 'Descubra novas postagens, pessoas e tópicos.',
    'howUseDiscover' => '<p class="font-weight-bold h5 pb-3">Como usar o Descobrir</p>
        <ul>
            <li class="mb-3">Clique no ícone <i class="far fa-compass fa-sm"></i>.</li>
            <li class="mb-3">Veja as postagens mais recentes.</li>
        </ul>',
    'discoverCategories' => '<p class="font-weight-bold h5 pb-3">Categorias do Descobrir <span class="badge badge-success">NOVO</span></p>
        <p>As Categorias do Descobrir são um recurso novo que pode não ser suportado em todas as instâncias do Pixelfed.</p>
        <ul>
            <li class="mb-3">Clique no ícone <i class="far fa-compass fa-sm"></i>.</li>
            <li class="mb-3">Na página Descobrir, você verá uma lista de cartões de Categoria que levam a cada Categoria do Descobrir.</li>
        </ul>',
    'discoverTips' => '<div class="card-header text-light font-weight-bold h4 p-4 bg-primary">Dicas do Descobrir</div>
        <div class="card-body bg-white p-3">
            <ul class="pt-3">
                <li class="lead mb-4">Para tornar suas postagens mais visíveis, adicione hashtags às suas postagens.</li>
                <li class="lead mb-4">Qualquer postagem pública que contenha uma hashtag pode ser incluída nas páginas de descobrir.</li>
            </ul>
        </div>',

    'dmSubTitle'=> 'Envie e receba mensagens diretas de outros perfis.',
    'howUseDirectMessagesAsk'=> 'Como usar o Pixelfed Direct?',
    'howUseDirectMessagesAnswer'=> '<div>
            <p>O Pixelfed Direct permite que você envie mensagens para outra conta. Você pode enviar as seguintes coisas como mensagem no Pixelfed Direct:</p>
            <ul>
                <li>Fotos ou vídeos que você tira ou carrega da sua biblioteca</li>
                <li>Postagens que você vê no feed</li>
                <li>Perfis</li>
                <li>Texto</li>
                <li>Hashtags</li>
                <li>Localizações</li>
            </ul>
            <p>Para ver as mensagens que você enviou com o Pixelfed Direct, toque em <i class="far fa-comment-dots"></i> no canto superior direito do feed. A partir daí, você pode gerenciar as mensagens enviadas e recebidas.</p>
            <p>Fotos ou vídeos enviados pelo Pixelfed Direct não podem ser compartilhados no Pixelfed para outros sites como Mastodon ou Twitter e não aparecerão nas páginas de hashtags e localizações.</p>
        </div>',

    'howUnsedDirectMessageAsk' => 'Como faço para cancelar o envio de uma mensagem que enviei usando o Pixelfed Direct?',
    'howUnsedDirectMessageAnswer' => 'Você pode clicar na mensagem e selecionar a opção <strong>Excluir</strong>.',
    'canSendDirectMessageAsk' => 'Posso usar o Pixelfed Direct para enviar mensagens para pessoas que não estou seguindo?',
    'canSendDirectMessageAnswer' => 'Você pode enviar uma mensagem para alguém que não segue, embora ela possa ser enviada para a caixa de entrada filtrada dessa pessoa e não ser facilmente vista.',
    'howReportDirectMessageAsk' => 'Como faço para denunciar conteúdo que recebi em uma mensagem do Pixelfed Direct?',
    'howReportDirectMessageAnswer' => 'Você pode clicar na mensagem e, em seguida, selecionar a opção <strong>Denunciar</strong> e seguir as instruções na página de Denúncia.',

    'timelineSubTitle' => 'Linhas do tempo são feeds cronológicos de postagens.',
    'timelineHome' => 'Linha do tempo com conteúdo de contas que você segue',
    'timelinePublic' => 'Linha do tempo com conteúdo de outros usuários neste servidor',
    'timelineNetwork' => 'Linha do tempo com conteúdo não moderado de outros servidores',
    'timelineTips' => 'Dicas de linha do tempo',
    'timelineTipsContent' =>  '<ul class="pt-3">
        <li class="lead mb-4">Você pode silenciar ou bloquear contas para impedir que elas apareçam na linha do tempo inicial e nas linhas do tempo públicas.</li>
        <li class="lead mb-4">Você pode criar postagens <span class="font-weight-bold">Não Listadas</span> que não aparecem nas linhas do tempo públicas.</li>
        </ul>',

    'safetyTipsSubTitle' =>'Estamos comprometidos em construir uma plataforma de compartilhamento de fotos divertida e fácil de usar, segura e protegida para todos.',
    'safetyTipsKnowRules' => 'Conheça as regras',
    'safetyTipsKnowRulesContent' => 'Para se manter seguro, é importante conhecer as regras dos <a href="'.route('site.terms').'">termos de serviço</a>.',
    'safetyTipsAage' => 'Conheça a diretriz etária',
    'safetyTipsAageContent' => 'Você deve ter pelo menos 16 anos de idade para usar o Pixelfed. Se você é menor de 18 anos, você deve ter permissão dos pais ou responsável legal.',
    'safetyTipsRport' => 'Denunciar conteúdo problemático',
    'safetyTipsRportContent' => 'Você pode denunciar conteúdos que acredita violarem nossas políticas.',
    'safetyTipsVisility' => 'Entendendo a visibilidade do conteúdo',
    'safetyTipsVisilityContent' => 'Você pode limitar a visibilidade do seu conteúdo para pessoas específicas, seguidores, público e mais.',
    'safetyTipsPostsPrivacy' => 'Torne sua conta ou postagens privadas',
    'safetyTipsPostsPrivacyContent' => 'Você pode tornar sua conta privada e verificar novas solicitações de seguidores para controlar com quem suas postagens são compartilhadas.',


];

ArtDraw
Plataforma web para organizar, descobrir e compartilhar conteúdos de aprendizagem em desenho. O ArtDraw reúne aulas, dicas e trabalhos em um feed categorizado por assunto e nível, com perfis de alunos e professores, interações e uma área administrativa para manutenção e moderação.
Estado deste README: documentação baseada na versão do projeto enviada no arquivo artdraw(2).zip. As observações de funcionalidades e limitações descrevem o conteúdo efetivamente encontrado nessa versão.

Sumário
- Visão geral
- Perfis e permissões
- Funcionalidades
- Estrutura do projeto
- Tecnologias e dependências
- Como executar localmente
- Banco de dados
- Fluxos principais
- Segurança e regras de negócio
- Limitações conhecidas desta versão
- Manutenção e backup
Visão geral
O ArtDraw é uma aplicação PHP com banco de dados MySQL. A aplicação é servida pelo Apache dentro de um contêiner Docker. O banco também é executado em contêiner, e o phpMyAdmin é disponibilizado para administração durante o desenvolvimento.

O conteúdo publicado é classificado por:
- Tipo: Aula, Dica ou Trabalho.
- Categoria: por exemplo, Desenho técnico, Perspectiva, Retrato e Desenho digital.
- Nível: Iniciante, Intermediário ou Avançado.
- Mídia principal opcional: uma imagem enviada ao servidor ou um vídeo do YouTube. A publicação não utiliza as duas opções simultaneamente.
- Links complementares: até 10 endereços HTTP/HTTPS por publicação.
O visitante pode explorar conteúdos de nível Iniciante. Alunos e professores autenticados têm acesso aos demais níveis e podem curtir e salvar publicações. Professores também podem publicar e administrar os próprios conteúdos. Administradores gerenciam categorias, níveis, usuários e publicações.
Perfis e permissões
Perfil	Acesso e permissões
Visitante	Acessa o feed e os detalhes de conteúdos de nível Iniciante; pesquisa por texto e filtra por categoria ativa. Não pode curtir ou salvar.
Aluno	Pode explorar conteúdos de todos os níveis, curtir e salvar publicações, manter um perfil público e editar os próprios dados de perfil.
Professor	Possui as permissões de interação de aluno, perfil público e painel próprio. Pode criar, editar e excluir as próprias aulas, dicas e trabalhos.
Administrador	Acessa o painel administrativo, gerencia categorias e níveis, consulta/bloqueia/desbloqueia/exclui logicamente alunos e professores e modera publicações. A conta administrativa não é oferecida no cadastro público.


As permissões são verificadas no servidor. Ocultar um link na interface não é considerado controle de acesso: os arquivos protegidos também verificam a sessão e o tipo de usuário.
Funcionalidades
Explorar e pesquisar
A página inicial (www/index.php) apresenta o feed de publicações. A pesquisa considera título, descrição e apelido do autor. O filtro de categoria utiliza apenas categorias ativas. O feed limita a listagem a 24 publicações por carregamento e ordena da mais recente para a mais antiga.

Visitantes recebem uma restrição aplicada na consulta ao banco: só podem abrir conteúdos associados ao nível Iniciante. A regra também é aplicada na página de detalhes, não apenas na interface.
Detalhes da publicação
A página www/conteudo/index.php?id=ID mostra o título, a descrição, o texto completo, o autor, a categoria, o nível, a data, as interações, a mídia principal e os links complementares.

Vídeos são incorporados por meio do domínio youtube-nocookie.com. O endereço do YouTube passa por validação antes da geração do endereço de incorporação. A página também contém um modo de pré-visualização administrativa, identificado por admin_preview=1, que só é habilitado quando a sessão pertence a um administrador. Nesse modo, o administrador pode analisar a publicação sem receber permissões de curtir ou salvar.
Cadastro, login e sessão
O cadastro público permite escolher somente Aluno ou Professor. O processamento valida os campos, verifica duplicidade de e-mail e armazena a senha com password_hash().

O login consulta o usuário pelo e-mail, verifica o hash com password_verify() e aceita somente contas com status ativo. Após autenticar, o identificador da sessão é regenerado. O arquivo logout.php limpa os dados e o cookie da sessão.
Perfis
Os perfis de alunos e professores são públicos. A página de perfil apresenta apelido, nome, tipo de usuário, foto e biografia. O perfil de professor também lista suas publicações. O titular pode editar nome, apelido, biografia e foto de perfil.

O envio de foto de perfil aceita imagens JPEG/JPG, PNG e WebP, incluindo extensão JFIF reconhecida como JPEG, com limite de 2 MB. O servidor gera um nome aleatório para o arquivo e armazena o caminho no banco.
Área do professor
O painel www/professor/index.php apresenta totais de publicações e separa a quantidade de aulas, dicas e trabalhos. O professor pode:
- Criar uma publicação do tipo Aula, Dica ou Trabalho.
- Escolher categoria e nível ativos.
- Adicionar texto e uma mídia principal opcional (imagem ou YouTube).
- Adicionar até 10 links complementares.
- Editar e excluir somente publicações de sua própria autoria.
Imagens das publicações são armazenadas em www/uploads/conteudos/, com nomes gerados pelo sistema. O limite de envio definido no formulário/processamento de publicação é de 5 MB.
Painel administrativo
O painel está em www/admin/index.php e é exclusivo para o perfil admin.
Módulo	Arquivo de entrada	Recursos
Categorias	www/admin/categorias/index.php	Cadastrar, editar, desativar e reativar categorias. Categorias não são apagadas pelo painel, preservando vínculos existentes.
Níveis	www/admin/niveis/index.php	Cadastrar, editar nome/ordem, desativar e reativar níveis. A ordem é única; para trocar posições é necessário utilizar temporariamente uma posição livre.
Usuários	www/admin/usuarios/index.php	Pesquisar e filtrar alunos e professores por tipo e situação; bloquear, desbloquear e realizar exclusão lógica. As contas admin não aparecem nesse módulo.
Publicações	www/admin/publicacoes/index.php	Pesquisar, filtrar por tipo/categoria/nível, paginar, abrir pré-visualização e remover definitivamente publicações.


A remoção administrativa de uma publicação é permanente. As chaves estrangeiras do banco removem em cascata os links complementares, curtidas e salvamentos da publicação. Quando a publicação possui uma imagem local com nome no padrão gerado pela aplicação, o arquivo também é removido.

A exclusão de um usuário é diferente: o sistema atualiza seu status para excluido e registra a data, mantendo a linha do usuário e suas publicações. O código atual não anonimiza os dados pessoais da conta; consulte Limitações conhecidas desta versão.
Estrutura do projeto
artdraw/
├── Dockerfile
├── docker-compose.yml
├── banco/
│   └── artdraw.sql
└── www/
    ├── index.php
    ├── logout.php
    ├── assets/
    │   └── css/
    │       └── style.css
    ├── cadastro/
    │   ├── index.php
    │   └── salvar.php
    ├── conteudo/
    │   └── index.php
    ├── includes/
    │   ├── auth.php
    │   ├── card-conteudo.php
    │   ├── conexao.php
    │   ├── footer.php
    │   ├── header.php
    │   ├── interacoes.php
    │   ├── menu.php
    │   ├── permissoes.php
    │   ├── sessao.php
    │   └── youtube.php
    ├── login/
    │   ├── index.php
    │   └── autenticar.php
    ├── perfil/
    │   ├── index.php
    │   └── editar.php
    ├── professor/
    │   ├── index.php
    │   ├── nova-publicacao.php
    │   ├── editar-publicacao.php
    │   └── excluir-publicacao.php
    ├── admin/
    │   ├── index.php
    │   ├── categorias/
    │   │   ├── index.php
    │   │   ├── salvar.php
    │   │   └── alterar-status.php
    │   ├── niveis/
    │   │   ├── index.php
    │   │   ├── salvar.php
    │   │   └── alterar-status.php
    │   ├── usuarios/
    │   │   ├── index.php
    │   │   ├── alterar-status.php
    │   │   └── excluir.php
    │   └── publicacoes/
    │       ├── index.php
    │       └── excluir.php
    └── uploads/
        ├── perfis/
        └── conteudos/
Responsabilidade dos arquivos compartilhados
- includes/sessao.php: inicia a sessão PHP quando ainda não existe uma sessão ativa.
- includes/conexao.php: cria a conexão MySQLi e define utf8mb4.
- includes/auth.php: consulta o usuário da sessão no banco e invalida sessões de contas inexistentes ou não ativas.
- includes/permissoes.php: fornece exigirPerfil() para restringir páginas por tipo de usuário.
- includes/header.php e includes/footer.php: estrutura compartilhada, metadados, fontes, Bootstrap, folha de estilos e rodapé.
- includes/menu.php: menu de navegação que muda de acordo com o perfil e o estado de autenticação.
- includes/card-conteudo.php: componente reutilizado para exibir cartões no feed.
- includes/interacoes.php: processa as ações POST de curtir e salvar, com verificação de autenticação, perfil permitido e token CSRF.
- includes/youtube.php: valida endereços compatíveis do YouTube e extrai o identificador do vídeo.
Tecnologias e dependências
- PHP 8.3 com Apache.
- MySQL 8.3 com InnoDB e utf8mb4.
- MySQLi para as consultas ao banco; a imagem Docker instala também PDO e pdo_mysql.
- Docker Compose para orquestrar PHP/Apache, MySQL e phpMyAdmin.
- Bootstrap 5.3.8 carregado por CDN em páginas que usam seus componentes.
- Google Fonts: DM Sans e Caveat, carregadas pelo cabeçalho compartilhado.
- YouTube para vídeos incorporados.
O repositório não contém composer.json nem dependências PHP instaladas via Composer na versão analisada.
Como executar localmente
Pré-requisitos
- Docker Desktop com Docker Compose disponível.
- Git, caso deseje clonar o repositório.
- Navegador atualizado.
1. Iniciar os contêineres
Na pasta raiz do projeto, execute:
docker compose up -d --build
Confira os contêineres:
docker compose ps
O Compose define estes serviços e endereços locais:
Serviço	Contêiner	Endereço/porta
Aplicação PHP/Apache	sistema_artdraw	http://localhost:8080
phpMyAdmin	sistema_artdraw_phpmyadmin	http://localhost:8081
MySQL	sistema_artdraw_mysql	Porta 3306 do host; dentro da rede Docker, o host usado pela aplicação é mysql


A aplicação é montada no contêiner Apache por meio do volume ./www:/var/www/html. Por isso, alterações nos arquivos PHP e CSS do host são refletidas no contêiner sem rebuild da imagem.
2. Criar o banco de dados
O arquivo banco/artdraw.sql cria o banco sistema_artdraw, as oito tabelas e as categorias e níveis iniciais.
1. Abra http://localhost:8081.
2. Entre no phpMyAdmin com o usuário root e a senha atualmente definida no docker-compose.yml.
3. Selecione a opção Importar e envie banco/artdraw.sql.
4. Execute a importação e confirme que as tabelas foram criadas.
Importante: o SQL foi escrito para uma instalação limpa. Ele não é um script de migração e não deve ser importado repetidamente sobre um banco já populado. O volume Docker db_data persiste os dados entre reinicializações. Não execute docker compose down -v para uma simples reinicialização, pois -v remove os volumes e pode apagar o banco persistido.
3. Criar a primeira conta administrativa
A conta admin não é inserida pelo cadastro público e não há rotina automática de provisionamento no projeto analisado. Ela precisa ser criada por procedimento privado no banco.

Gere o hash da senha usando o PHP do contêiner (substitua o texto de exemplo por uma senha forte):
docker exec sistema_artdraw php -r "echo password_hash('SENHA_FORTE_AQUI', PASSWORD_DEFAULT), PHP_EOL;"
Copie o hash gerado e execute no SQL do phpMyAdmin, substituindo o e-mail por um endereço único e o hash pelo valor gerado:
INSERT INTO usuarios (
    nome,
    apelido,
    email,
    senha,
    tipo_usuario,
    status
) VALUES (
    'Administrador ArtDraw',
    'admin',
    'admin@artdraw.local',
    'COLE_AQUI_O_HASH_GERADO',
    'admin',
    'ativo'
);
Não grave a senha em texto puro no banco, não publique o hash no repositório e não use credenciais reais em exemplos versionados. Depois, acesse http://localhost:8080/login/index.php e entre com o e-mail e a senha escolhidos.
4. Acessar a aplicação
- Aplicação: http://localhost:8080
- Cadastro: http://localhost:8080/cadastro/index.php
- Login: http://localhost:8080/login/index.php
- Painel administrativo: http://localhost:8080/admin/index.php (conta admin necessária)
Banco de dados
O script banco/artdraw.sql cria oito tabelas:
Tabela	Finalidade
usuarios	Dados de acesso, perfil, tipo de conta e status.
categorias	Categorias disponíveis para classificar publicações.
niveis	Níveis de aprendizado e ordem de apresentação.
conteudos	Aulas, dicas e trabalhos, com autor, categoria, nível e mídia opcional.
links_complementares	Links adicionais associados a uma publicação.
curtidas	Relação entre usuário e conteúdo curtido.
salvamentos	Relação entre usuário e conteúdo salvo.
recuperacoes_senha	Estrutura preparada para tokens de recuperação, armazenando hash, validade e utilização.


Relacionamentos principais
- Um usuário (aluno ou professor) pode ter várias publicações.
- Cada publicação pertence a um autor, uma categoria e um nível.
- Uma publicação pode possuir vários links complementares.
- Curtidas e salvamentos relacionam usuários e publicações. As combinações usuário/publicação são únicas.
- Os links complementares, curtidas e salvamentos são removidos em cascata quando uma publicação é removida fisicamente.
- A exclusão física de autores, categorias e níveis é restringida quando existem publicações associadas; a aplicação utiliza bloqueio/desativação/exclusão lógica para preservar esses vínculos.
Dados iniciais
O SQL insere dez categorias iniciais:
1. Desenho técnico
2. Desenho à mão livre
3. Anatomia e figura humana
4. Perspectiva
5. Luz e sombra
6. Teoria das cores
7. Retrato
8. Paisagem e natureza
9. Ilustração
10. Desenho digital
Também insere os níveis Iniciante (ordem 1), Intermediário (ordem 2) e Avançado (ordem 3).
Fluxos principais
Publicar conteúdo como professor
1. O professor entra na aplicação.
2. Acessa a Área do professor.
3. Escolhe cadastrar uma Aula, Dica ou Trabalho.
4. Preenche título, descrição, texto, categoria e nível.
5. Opcionalmente envia uma imagem ou informa um vídeo do YouTube.
6. Opcionalmente adiciona até 10 links complementares.
7. O servidor valida os dados, grava a publicação e os links em uma transação e direciona para a página de detalhes.
Interagir com conteúdo
1. Um aluno ou professor autenticado acessa uma publicação.
2. Pode curtir ou salvar por formulários POST.
3. includes/interacoes.php valida o token CSRF, o tipo de usuário, a publicação e o estado do autor antes de alternar a relação no banco.
4. Visitantes e administradores não recebem os formulários de interação.
Moderar publicação como administrador
1. O administrador abre Moderar publicações.
2. Pesquisa ou filtra a listagem.
3. Abre Visualizar para analisar a publicação em modo de pré-visualização administrativa.
4. Se necessário, solicita a remoção permanente.
5. O servidor remove o registro e seus relacionamentos em cascata; se houver imagem local gerada pelo sistema, tenta remover o arquivo correspondente.
Segurança e regras de negócio
- As senhas são armazenadas com password_hash() e verificadas com password_verify().
- As consultas que recebem dados do usuário usam prepared statements/MySQLi.
- As ações de interação, edição de perfil, publicações e módulos administrativos incluem tokens CSRF.
- As páginas administrativas usam exigirPerfil(['admin']).
- O cadastro público valida apenas os perfis aluno e professor.
- Contas com status diferente de ativo não autenticam. O bloqueio impede o acesso; publicações de autores bloqueados são ocultadas do feed e das páginas normais de detalhes. A pré-visualização administrativa permite que um administrador as examine.
- Categorias e níveis devem ser desativados em vez de apagados quando já utilizados.
- A exclusão lógica de usuário preserva suas publicações; a exclusão de publicação é física e remove as relações dependentes.
- A mídia principal é opcional e única: imagem ou youtube, nunca as duas no mesmo registro.
Limitações conhecidas desta versão
Os pontos abaixo foram identificados na leitura do código enviado. São importantes para que o README não apresente como pronta uma funcionalidade que ainda não está implementada:
1. Recuperação de senha: a tabela recuperacoes_senha existe no banco e a tela de login contém um link para /recuperacao/index.php, mas a pasta/rotina de recuperação e o envio de e-mail não estão presentes no ZIP analisado. O link, portanto, não tem destino implementado nesta versão.
2. Lista de conteúdos salvos: os usuários podem salvar publicações e as relações são armazenadas, mas não foi encontrada uma página para consultar uma coleção/lista pessoal de conteúdos salvos.
3. Provisionamento administrativo: não existe script automático para criar ou recuperar a conta admin. O procedimento é manual, como explicado acima.
4. Anonimização na exclusão de usuário: o comentário final do SQL descreve que dados pessoais deveriam ser anonimizados na exclusão lógica, mas admin/usuarios/excluir.php atualmente apenas altera status, data_exclusao e data_atualizacao. Nome, apelido, e-mail e foto não são anonimizados pelo código atual. Isso deve ser resolvido antes de utilizar a exclusão de contas em produção.
5. Marcação HTML do perfil público: em www/perfil/index.php, o fechamento da div.ad-profile-information está dentro da condição que exibe o link de edição do próprio perfil. Quando outro usuário visita o perfil, esse fechamento não é emitido. O navegador pode tentar corrigir a estrutura, mas o HTML deve ser ajustado.
6. Proteção CSRF em cadastro/login: os formulários de cadastro e autenticação não apresentam token CSRF nesta versão, ao contrário dos formulários de interação, edição, publicação e administração.
7. Configuração de produção: as credenciais do banco estão definidas diretamente em docker-compose.yml e www/includes/conexao.php; a senha local é fraca (1234). O Compose expõe a porta MySQL e utiliza phpmyadmin:latest. O Dockerfile habilita Options Indexes, que permite listagem de diretórios quando não há arquivo índice. Essa configuração é para desenvolvimento local e precisa ser endurecida antes de qualquer implantação pública.
8. Verificação de ponta a ponta: a revisão desta entrega incluiu leitura estática dos arquivos, análise de classes CSS e verificação de sintaxe PHP. Isso não substitui testes de integração no Docker, testes de banco e testes manuais no navegador.
Manutenção e backup
- Os dados do MySQL ficam no volume Docker nomeado db_data.
- Faça backup antes de alterar o esquema, importar SQL ou remover volumes.
- Para exportar o banco, use a função Exportar do phpMyAdmin ou um dump MySQL equivalente.
- Para restaurar, importe o arquivo de backup em uma instância compatível e confirme as tabelas e os vínculos antes de voltar a disponibilizar a aplicação.
- Faça backup também dos arquivos enviados pelos usuários em www/uploads/; um dump SQL não inclui as imagens armazenadas no sistema de arquivos.
- Não versione senhas, hashes de contas reais, backups de produção ou imagens privadas sem autorização.
ArtDraw — aprender, compartilhar e evoluir no desenho.

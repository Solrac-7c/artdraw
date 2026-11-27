<?php

require_once __DIR__ . "/../includes/permissoes.php";

// Somente administradores autenticados podem acessar o painel.
$usuario = exigirPerfil(["admin"]);

// Protege os valores exibidos no HTML.
$escapar = static function ($valor) {
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
};

/*
|--------------------------------------------------------------------------
| 1. ESTATÍSTICAS DE USUÁRIOS
|--------------------------------------------------------------------------
*/

$sqlUsuarios = "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'ativo'), 0) AS ativos,
        COALESCE(SUM(status = 'bloqueado'), 0) AS bloqueados,
        COALESCE(SUM(status = 'excluido'), 0) AS excluidos,

        COALESCE(SUM(
            tipo_usuario = 'aluno'
            AND status <> 'excluido'
        ), 0) AS alunos,

        COALESCE(SUM(
            tipo_usuario = 'professor'
            AND status <> 'excluido'
        ), 0) AS professores

    FROM usuarios
";

$resultado = $conn->query($sqlUsuarios);
$estatisticasUsuarios = $resultado->fetch_assoc();

/*
|--------------------------------------------------------------------------
| 2. ESTATÍSTICAS DE PUBLICAÇÕES
|--------------------------------------------------------------------------
*/

$sqlPublicacoes = "
    SELECT
        COUNT(*) AS total,

        COALESCE(SUM(tipo_conteudo = 'aula'), 0) AS aulas,

        COALESCE(SUM(tipo_conteudo = 'dica'), 0) AS dicas,

        COALESCE(SUM(tipo_conteudo = 'trabalho'), 0) AS trabalhos

    FROM conteudos
";

$resultado = $conn->query($sqlPublicacoes);
$estatisticasPublicacoes = $resultado->fetch_assoc();

/*
|--------------------------------------------------------------------------
| 3. ESTATÍSTICAS DE CATEGORIAS
|--------------------------------------------------------------------------
*/

$sqlCategorias = "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(ativo = 1), 0) AS ativos
    FROM categorias
";

$resultado = $conn->query($sqlCategorias);
$estatisticasCategorias = $resultado->fetch_assoc();

/*
|--------------------------------------------------------------------------
| 4. ESTATÍSTICAS DE NÍVEIS
|--------------------------------------------------------------------------
*/

$sqlNiveis = "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(ativo = 1), 0) AS ativos
    FROM niveis
";

$resultado = $conn->query($sqlNiveis);
$estatisticasNiveis = $resultado->fetch_assoc();

/*
|--------------------------------------------------------------------------
| 5. CONFIGURAÇÕES DA PÁGINA
|--------------------------------------------------------------------------
*/

$tituloPagina = "Painel administrativo";
$paginaAtual = "admin";

require_once __DIR__ . "/../includes/header.php";

?>

<div class="ad-admin-page">

    <!-- APRESENTAÇÃO -->

    <section class="ad-admin-welcome">

        <div>

            <span class="ad-admin-eyebrow">
                ADMINISTRAÇÃO ARTDRAW
            </span>

            <h1>
                Olá, <?= $escapar($usuario["apelido"]) ?>!
            </h1>

            <p>
                Gerencie usuários, conteúdos e configurações
                da comunidade ArtDraw.
            </p>

        </div>

        <span class="ad-admin-role">
            Administrador
        </span>

    </section>


    <!-- ESTATÍSTICAS -->

    <section class="ad-admin-statistics">

        <div class="ad-admin-section-heading">

            <h2>Visão geral</h2>

            <p>
                Um resumo das informações cadastradas na plataforma.
            </p>

        </div>


        <div class="ad-admin-statistics-grid">


            <article class="ad-admin-stat-card">

                <span>Contas ativas</span>

                <strong>
                    <?= (int) $estatisticasUsuarios["ativos"] ?>
                </strong>

            </article>


            <article class="ad-admin-stat-card">

                <span>Alunos</span>

                <strong>
                    <?= (int) $estatisticasUsuarios["alunos"] ?>
                </strong>

            </article>


            <article class="ad-admin-stat-card">

                <span>Professores</span>

                <strong>
                    <?= (int) $estatisticasUsuarios["professores"] ?>
                </strong>

            </article>


            <article class="ad-admin-stat-card">

                <span>Contas bloqueadas</span>

                <strong>
                    <?= (int) $estatisticasUsuarios["bloqueados"] ?>
                </strong>

            </article>


            <article class="ad-admin-stat-card">

                <span>Contas excluídas</span>

                <strong>
                    <?= (int) $estatisticasUsuarios["excluidos"] ?>
                </strong>

            </article>


            <article class="ad-admin-stat-card">

                <span>Publicações</span>

                <strong>
                    <?= (int) $estatisticasPublicacoes["total"] ?>
                </strong>

                <small>
                    <?= (int) $estatisticasPublicacoes["aulas"] ?> aulas,
                    <?= (int) $estatisticasPublicacoes["dicas"] ?> dicas e
                    <?= (int) $estatisticasPublicacoes["trabalhos"] ?> trabalhos
                </small>

            </article>


            <article class="ad-admin-stat-card">

                <span>Categorias ativas</span>

                <strong>
                    <?= (int) $estatisticasCategorias["ativos"] ?>
                </strong>

                <small>
                    De <?= (int) $estatisticasCategorias["total"] ?> cadastradas
                </small>

            </article>


            <article class="ad-admin-stat-card">

                <span>Níveis ativos</span>

                <strong>
                    <?= (int) $estatisticasNiveis["ativos"] ?>
                </strong>

                <small>
                    De <?= (int) $estatisticasNiveis["total"] ?> cadastrados
                </small>

            </article>


        </div>

    </section>


    <!-- MÓDULOS ADMINISTRATIVOS -->

    <section class="ad-admin-modules">

        <div class="ad-admin-section-heading">

            <h2>Gerenciamento da plataforma</h2>

            <p>
                Acesse as ferramentas administrativas do ArtDraw.
            </p>

        </div>


        <div class="ad-admin-module-grid">


            <article class="ad-admin-module-card">

                <span class="ad-admin-module-icon">▦</span>

                <h3>Categorias</h3>

                <p>
                    Gerencie as categorias utilizadas para classificar
                    as publicações.
                </p>

                <span class="ad-admin-module-status">
                    <a
                        href="/admin/categorias/index.php"
                        class="ad-admin-action-link">
                        Gerenciar categorias
                    </a>
                </span>

            </article>


            <article class="ad-admin-module-card">

                <span class="ad-admin-module-icon">▤</span>

                <h3>Níveis de aprendizagem</h3>

                <p>
                    Organize os níveis Iniciante, Intermediário
                    e Avançado.
                </p>

                <span class="ad-admin-module-status">
                    <a
                        href="/admin/niveis/index.php"
                        class="ad-admin-action-link">
                        Gerenciar níveis
                    </a>
                </span>

            </article>


            <article class="ad-admin-module-card">

                <span class="ad-admin-module-icon">♙</span>

                <h3>Usuários</h3>

                <p>
                    Consulte contas, bloqueie usuários e administre
                    os acessos à plataforma.
                </p>

                <span class="ad-admin-module-status">
                    <a
                        href="/admin/usuarios/index.php"
                        class="ad-admin-action-link">
                        Gerenciar usuários
                    </a>
                </span>

            </article>


            <article class="ad-admin-module-card">

                <span class="ad-admin-module-icon">▧</span>

                <h3>Publicações</h3>

                <p>
                    Consulte os conteúdos e modere publicações
                    da comunidade.
                </p>

                <span class="ad-admin-module-status">
                    Próxima implementação
                </span>

            </article>


        </div>

    </section>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
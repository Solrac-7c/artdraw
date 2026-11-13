<?php

/*
|--------------------------------------------------------------------------
| 1. AUTENTICAÇÃO E CONEXÃO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/auth.php";

$usuario = usuarioAtual();

require_once __DIR__ . "/../includes/youtube.php";

/*
|--------------------------------------------------------------------------
| 2. IDENTIFICAÇÃO DA PUBLICAÇÃO
|--------------------------------------------------------------------------
*/

$conteudoId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

$conteudo = null;
$linksComplementares = [];
$youtubeEmbedUrl = null;


// Só consulta se recebermos um ID inteiro positivo.

if ($conteudoId && $conteudoId > 0) {


    /*
    |--------------------------------------------------------------------------
    | 3. CONSULTA DA PUBLICAÇÃO
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT

            c.id,
            c.titulo,
            c.descricao,
            c.conteudo,
            c.tipo_conteudo,
            c.tipo_midia,
            c.midia_url,
            c.data_publicacao,

            u.id AS autor_id,
            u.apelido AS autor_apelido,
            u.foto_perfil AS autor_foto,
            u.tipo_usuario AS autor_tipo,

            cat.nome AS categoria_nome,

            n.nome AS nivel_nome

        FROM conteudos c

        INNER JOIN usuarios u
            ON c.autor_id = u.id

        INNER JOIN categorias cat
            ON c.categoria_id = cat.id

        INNER JOIN niveis n
            ON c.nivel_id = n.id

        WHERE c.id = ?

        AND u.status <> 'bloqueado'
    ";


    // Visitantes só podem acessar publicações iniciantes.

    if (!$usuario) {

        $sql .= " AND n.nome = 'Iniciante'";

    }


    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $conteudoId);

    $stmt->execute();

    $resultado = $stmt->get_result();

    $conteudo = $resultado->fetch_assoc();

    $stmt->close();

    if (
        $conteudo &&
        $conteudo["tipo_midia"] === "youtube"
    ) {

        $youtubeId = artdrawYoutubeVideoId(
            $conteudo["midia_url"]
        );

        if ($youtubeId !== null) {

            $youtubeEmbedUrl =
                "https://www.youtube-nocookie.com/embed/" .
                $youtubeId;

        }

}

    /*
    |--------------------------------------------------------------------------
    | 4. LINKS COMPLEMENTARES
    |--------------------------------------------------------------------------
    */

    if ($conteudo) {

        $sqlLinks = "
            SELECT
                titulo,
                url

            FROM links_complementares

            WHERE conteudo_id = ?

            ORDER BY ordem ASC, id ASC
        ";

        $stmtLinks = $conn->prepare($sqlLinks);

        $stmtLinks->bind_param("i", $conteudoId);

        $stmtLinks->execute();

        $resultadoLinks = $stmtLinks->get_result();

        $linksComplementares = $resultadoLinks->fetch_all(
            MYSQLI_ASSOC
        );

        $stmtLinks->close();

    }

}


/*
|--------------------------------------------------------------------------
| 5. TRATAMENTO DE CONTEÚDO INDISPONÍVEL
|--------------------------------------------------------------------------
*/

// A mesma mensagem é utilizada para ID inexistente,
// conteúdo bloqueado e conteúdo restrito ao visitante.

if (!$conteudo) {

    http_response_code(404);

}


/*
|--------------------------------------------------------------------------
| 6. CONFIGURAÇÕES DA PÁGINA
|--------------------------------------------------------------------------
*/

$tituloPagina = $conteudo
    ? $conteudo["titulo"]
    : "Conteúdo indisponível";

$descricaoPagina = $conteudo
    ? $conteudo["descricao"]
    : "Publicação não encontrada.";

$paginaAtual = "conteudo";


/*
|--------------------------------------------------------------------------
| 7. PREPARAÇÃO DOS TEXTOS
|--------------------------------------------------------------------------
*/

if ($conteudo) {

    $titulo = htmlspecialchars(
        $conteudo["titulo"],
        ENT_QUOTES,
        "UTF-8"
    );

    $descricao = htmlspecialchars(
        $conteudo["descricao"],
        ENT_QUOTES,
        "UTF-8"
    );

    $textoConteudo = nl2br(
        htmlspecialchars(
            $conteudo["conteudo"],
            ENT_QUOTES,
            "UTF-8"
        )
    );

    $autor = htmlspecialchars(
        $conteudo["autor_apelido"],
        ENT_QUOTES,
        "UTF-8"
    );

    $categoria = htmlspecialchars(
        $conteudo["categoria_nome"],
        ENT_QUOTES,
        "UTF-8"
    );

    $nivel = htmlspecialchars(
        $conteudo["nivel_nome"],
        ENT_QUOTES,
        "UTF-8"
    );


    // Tipo de publicação.

    $tipos = [
        "aula" => [
            "nome" => "Aula",
            "classe" => "ad-badge-aula"
        ],

        "dica" => [
            "nome" => "Dica",
            "classe" => "ad-badge-dica"
        ],

        "trabalho" => [
            "nome" => "Trabalho",
            "classe" => "ad-badge-trabalho"
        ]
    ];

    $tipo = $tipos[$conteudo["tipo_conteudo"]]
        ?? [
            "nome" => "Conteúdo",
            "classe" => "ad-badge-nivel"
        ];


    // Data de publicação.

    $dataPublicacao = date(
        "d/m/Y",
        strtotime($conteudo["data_publicacao"])
    );

}


/*
|--------------------------------------------------------------------------
| 8. CABEÇALHO COMPARTILHADO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/header.php";

?>


<!-- =====================================================
     VISUALIZAÇÃO INDIVIDUAL DA PUBLICAÇÃO
     ===================================================== -->

<div class="ad-container ad-detail">


    <?php if (!$conteudo) { ?>


        <!-- CONTEÚDO INDISPONÍVEL -->

        <section class="ad-empty-state">

            <h3>

                Conteúdo não encontrado.

            </h3>

            <p>

                Esta publicação não está disponível ou você não
                possui permissão para visualizá-la.

            </p>

            <a
                href="/index.php"
                class="ad-btn ad-btn-primary mt-3"
            >

                Voltar para explorar

            </a>

        </section>


    <?php } else { ?>


        <!-- INFORMAÇÕES PRINCIPAIS -->

        <div class="ad-detail-heading">

            <div class="ad-content-card-tags">

                <span class="ad-badge <?= $tipo["classe"] ?>">

                    <?= $tipo["nome"] ?>

                </span>

                <span class="ad-badge ad-badge-nivel">

                    <?= $nivel ?>

                </span>

            </div>


            <h1 class="ad-detail-title">

                <?= $titulo ?>

            </h1>


            <p class="ad-detail-description">

                <?= $descricao ?>

            </p>

        </div>


        <!-- ÁREA PRINCIPAL E INFORMAÇÕES LATERAIS -->

        <div class="ad-detail-layout">


            <!-- CONTEÚDO PRINCIPAL -->

            <main class="ad-detail-main">


                <!-- MÍDIA -->

                <?php if (
                    $conteudo["tipo_midia"] === "imagem"
                    && !empty($conteudo["midia_url"])
                ) { ?>


                    <div class="ad-detail-media">

                        <img
                            src="<?= htmlspecialchars(
                                $conteudo["midia_url"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            alt="<?= $titulo ?>"
                        >

                    </div>


                
                <?php } elseif (
                    $conteudo["tipo_midia"] === "youtube"
                ) { ?>


                <?php if ($youtubeEmbedUrl !== null) { ?>         

                    <div class="ad-detail-media ad-detail-media-video">

                    <iframe
                        src="<?= htmlspecialchars(
                            $youtubeEmbedUrl,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"

                        title="Vídeo: <?= $titulo ?>"
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen

                    ></iframe>

        </div>


                <?php } else { ?>


                    <div class="ad-detail-media">

                        <div class="ad-video-placeholder">

                            Não foi possível carregar este vídeo.

                            <p>
                                O endereço informado não é um link válido
                                ou compatível com o YouTube.
                            </p>

                        </div>

                    </div>


                <?php } ?>
            <?php } ?>       


                <!-- TEXTO COMPLETO -->

                <section class="ad-detail-text">

                    <h2>

                        Sobre este conteúdo

                    </h2>

                    <div class="ad-content-text">

                        <?= $textoConteudo ?>

                    </div>

                </section>


                <!-- LINKS COMPLEMENTARES -->

                <?php if (!empty($linksComplementares)) { ?>


                    <section class="ad-detail-links">

                        <h2>

                            Materiais complementares

                        </h2>


                        <?php foreach (
                            $linksComplementares as $link
                        ) {

                            $partesUrl = parse_url($link["url"]);

                            $esquema = strtolower(
                                $partesUrl["scheme"] ?? ""
                            );

                            $urlValida = (
                                filter_var(
                                    $link["url"],
                                    FILTER_VALIDATE_URL
                                )
                                && in_array(
                                    $esquema,
                                    ["http", "https"],
                                    true
                                )
                            );

                            if (!$urlValida) {
                                continue;
                            }

                            $tituloLink = !empty($link["titulo"])
                                ? $link["titulo"]
                                : $link["url"];

                        ?>


                            <a
                                href="<?= htmlspecialchars(
                                    $link["url"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="ad-detail-link"
                            >

                                <?= htmlspecialchars(
                                    $tituloLink,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                                <span>↗</span>

                            </a>


                        <?php } ?>


                    </section>


                <?php } ?>


            </main>


            <!-- INFORMAÇÕES LATERAIS -->

            <aside class="ad-detail-sidebar">


                <!-- AUTOR -->

                <section class="ad-detail-info-card">

                    <h2>

                        Publicado por

                    </h2>


                    <div class="ad-detail-author">


                        <?php if (!empty($conteudo["autor_foto"])) { ?>


                            <img
                                src="<?= htmlspecialchars(
                                    $conteudo["autor_foto"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                alt="Foto de <?= $autor ?>"
                            >


                        <?php } else { ?>


                            <div class="ad-detail-avatar">

                                <?= strtoupper(
                                    mb_substr(
                                        $conteudo["autor_apelido"],
                                        0,
                                        1,
                                        "UTF-8"
                                    )
                                ) ?>

                            </div>


                        <?php } ?>


                        <div>

                            <strong>

                                <?= $autor ?>

                            </strong>

                            <span>

                                <?= ucfirst(
                                    htmlspecialchars(
                                        $conteudo["autor_tipo"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                ) ?>

                            </span>

                        </div>


                    </div>

                </section>


                <!-- CLASSIFICAÇÃO -->

                <section class="ad-detail-info-card">

                    <h2>

                        Informações

                    </h2>


                    <div class="ad-detail-info-item">

                        <span>Categoria</span>

                        <strong><?= $categoria ?></strong>

                    </div>


                    <div class="ad-detail-info-item">

                        <span>Nível</span>

                        <strong><?= $nivel ?></strong>

                    </div>


                    <div class="ad-detail-info-item">

                        <span>Tipo</span>

                        <strong><?= $tipo["nome"] ?></strong>

                    </div>


                    <div class="ad-detail-info-item">

                        <span>Publicação</span>

                        <strong><?= $dataPublicacao ?></strong>

                    </div>


                </section>


                <a
                    href="/index.php"
                    class="ad-btn ad-btn-secondary ad-detail-back"
                >

                    Voltar para explorar

                </a>


            </aside>


        </div>


    <?php } ?>


</div>


<?php

/*
|--------------------------------------------------------------------------
| RODAPÉ COMPARTILHADO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/footer.php";

?>
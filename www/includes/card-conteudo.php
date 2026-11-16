<?php

require_once __DIR__ . "/youtube.php";


/*
|--------------------------------------------------------------------------
| 1. PREPARAÇÃO DOS DADOS
|--------------------------------------------------------------------------
*/

$tiposCartao = [

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

$tipoCartao = $tiposCartao[$conteudo["tipo_conteudo"]]
    ?? [
        "nome" => "Conteúdo",
        "classe" => "ad-badge-nivel"
    ];


/*
|--------------------------------------------------------------------------
| 2. TEXTOS
|--------------------------------------------------------------------------
*/

$tituloCartao = htmlspecialchars(
    $conteudo["titulo"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);

$descricaoCartao = htmlspecialchars(
    $conteudo["descricao"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);

$nivelCartao = htmlspecialchars(
    $conteudo["nivel_nome"] ?? "",
    ENT_QUOTES,
    "UTF-8"
);

$autorCartao = htmlspecialchars(
    $conteudo["autor_apelido"] ?? "Artista",
    ENT_QUOTES,
    "UTF-8"
);


/*
|--------------------------------------------------------------------------
| 3. DATA
|--------------------------------------------------------------------------
*/

$dataCartao = "";

if (!empty($conteudo["data_publicacao"])) {

    $dataCartao = date(
        "d/m/Y",
        strtotime($conteudo["data_publicacao"])
    );

}


/*
|--------------------------------------------------------------------------
| 4. MÍDIA
|--------------------------------------------------------------------------
*/

$temImagem = (
    ($conteudo["tipo_midia"] ?? null) === "imagem"
    && !empty($conteudo["midia_url"])
);

$temYoutube = (
    ($conteudo["tipo_midia"] ?? null) === "youtube"
    && !empty($conteudo["midia_url"])
);

$youtubeIdCartao = $temYoutube
    ? artdrawYoutubeVideoId($conteudo["midia_url"])
    : null;


/*
|--------------------------------------------------------------------------
| 5. INTERAÇÕES
|--------------------------------------------------------------------------
*/

$totalCurtidas = (int) ($conteudo["total_curtidas"] ?? 0);

$totalSalvamentos = (int) ($conteudo["total_salvamentos"] ?? 0);

$usuarioCurtiu = !empty($conteudo["usuario_curtiu"]);

$usuarioSalvou = !empty($conteudo["usuario_salvou"]);

$podeInteragir = (
    isset($usuario["tipo_usuario"]) &&
    in_array(
        $usuario["tipo_usuario"],
        ["aluno", "professor"],
        true
    )
);

?>


<article class="ad-content-card">


    <!-- =================================================
         ÁREA CLICÁVEL DO CARTÃO
         ================================================= -->

    <a
        href="/conteudo/index.php?id=<?= (int) $conteudo["id"] ?>"
        class="ad-content-card-link"
        aria-label="Abrir publicação: <?= $tituloCartao ?>"
    >


        <!-- MÍDIA -->

        <?php if ($temImagem) { ?>

            <img
                src="<?= htmlspecialchars(
                    $conteudo["midia_url"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                alt="<?= $tituloCartao ?>"
                class="ad-content-card-image"
                loading="lazy"
            >

        <?php } elseif (
            $temYoutube && $youtubeIdCartao !== null
        ) { ?>

            <img
                src="https://img.youtube.com/vi/<?= $youtubeIdCartao ?>/mqdefault.jpg"
                alt="Miniatura do vídeo: <?= $tituloCartao ?>"
                class="ad-content-card-image ad-content-card-youtube-image"
                loading="lazy"
            >

        <?php } elseif ($temYoutube) { ?>

            <div class="ad-content-card-placeholder ad-placeholder-youtube">

                <span class="ad-placeholder-icon">▶</span>

                <span>Endereço de vídeo inválido</span>

            </div>

        <?php } else { ?>

            <div class="ad-content-card-placeholder">

                <span class="ad-placeholder-icon">✎</span>

                <span>Conteúdo do ArtDraw</span>

            </div>

        <?php } ?>


        <!-- INFORMAÇÕES -->

        <div class="ad-content-card-body">
            <div class="ad-content-card-tags">

                <span class="ad-badge <?= $tipoCartao["classe"] ?>">

                    <?= $tipoCartao["nome"] ?>

                </span>

                <span class="ad-badge ad-badge-nivel">

                    <?= $nivelCartao ?>

                </span>

            </div>
            <h3 class="ad-content-card-title">

                <?= $tituloCartao ?>

            </h3>
            <p class="ad-content-card-description">

                <?= $descricaoCartao ?>

            </p>
            
            <div class="ad-content-card-footer">

                <span>

                    Por <?= $autorCartao ?>

                </span>

                <?php if ($dataCartao !== "") { ?>

                    <time>

                        <?= $dataCartao ?>

                    </time>

                <?php } ?>

            </div>


        </div>


    </a>


    <!-- =================================================
         INTERAÇÕES DO CARTÃO
         ================================================= -->

    <div class="ad-card-interactions">


        <?php if ($podeInteragir) { ?>


            <!-- BOTÃO CURTIR -->

            <form
                action="/includes/interacoes.php"
                method="POST"
                class="ad-interaction-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="conteudo_id"
                    value="<?= (int) $conteudo["id"] ?>"
                >

                <input
                    type="hidden"
                    name="acao"
                    value="curtir"
                >

                <input
                    type="hidden"
                    name="retorno"
                    value="/index.php"
                >

                <button
                    type="submit"
                    class="ad-interaction-button <?= $usuarioCurtiu ? 'is-liked' : '' ?>"
                    aria-pressed="<?= $usuarioCurtiu ? 'true' : 'false' ?>"
                >

                    <span aria-hidden="true">

                        <?= $usuarioCurtiu ? "♥" : "♡" ?>

                    </span>

                    <span>

                        <?= $totalCurtidas ?>

                    </span>

                    <span class="ad-interaction-label">

                        <?= $usuarioCurtiu ? "Curtido" : "Curtir" ?>

                    </span>

                </button>

            </form>


            <!-- BOTÃO SALVAR -->

            <form
                action="/includes/interacoes.php"
                method="POST"
                class="ad-interaction-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="conteudo_id"
                    value="<?= (int) $conteudo["id"] ?>"
                >

                <input
                    type="hidden"
                    name="acao"
                    value="salvar"
                >

                <input
                    type="hidden"
                    name="retorno"
                    value="/index.php"
                >

                <button
                    type="submit"
                    class="ad-interaction-button <?= $usuarioSalvou ? 'is-saved' : '' ?>"
                    aria-pressed="<?= $usuarioSalvou ? 'true' : 'false' ?>"
                >

                    <span aria-hidden="true">

                        <?= $usuarioSalvou ? "▰" : "▱" ?>

                    </span>

                    <span>

                        <?= $totalSalvamentos ?>

                    </span>

                    <span class="ad-interaction-label">

                        <?= $usuarioSalvou ? "Salvo" : "Salvar" ?>

                    </span>

                </button>

            </form>


        <?php } else { ?>


            <!-- VISITANTES VISUALIZAM SOMENTE AS QUANTIDADES -->

            <span class="ad-interaction-counter">

                ♡ <?= $totalCurtidas ?>

            </span>

            <span class="ad-interaction-counter">

                ▱ <?= $totalSalvamentos ?>

            </span>


        <?php } ?>


    </div>


</article>
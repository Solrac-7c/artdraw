<?php

require_once __DIR__ . "/youtube.php";

/*
|--------------------------------------------------------------------------
| PREPARAÇÃO DOS DADOS DO CARTÃO
|--------------------------------------------------------------------------
*/

// Tipos de publicação e suas respectivas classes visuais.

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


// Identifica o tipo de conteúdo.

$tipoCartao = $tiposCartao[$conteudo["tipo_conteudo"]]
    ?? [
        "nome" => "Conteúdo",
        "classe" => "ad-badge-nivel"
    ];


// Proteção dos textos antes de exibi-los no HTML.

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

$categoriaCartao = htmlspecialchars(
    $conteudo["categoria_nome"] ?? "",
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


// Formatação da data.

$dataCartao = "";

if (!empty($conteudo["data_publicacao"])) {

    $dataCartao = date(
        "d/m/Y",
        strtotime($conteudo["data_publicacao"])
    );

}


// Identificação da mídia.

$temImagem = (
    ($conteudo["tipo_midia"] ?? null) === "imagem"
    && !empty($conteudo["midia_url"])
);

$temYoutube = (
    ($conteudo["tipo_midia"] ?? null) === "youtube"
    && !empty($conteudo["midia_url"])
);

$youtubeIdCartao = $temYoutube ? artdrawYoutubeVideoId($conteudo["midia_url"]) : null;

?>
<a
    href="/conteudo/index.php?id=<?= (int) $conteudo["id"] ?>"
    class="ad-content-card-link"
    aria-label="Abrir publicação: <?= $tituloCartao ?>"
>
<article class="ad-content-card">


    <!-- MÍDIA PRINCIPAL -->

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


    
    <?php } elseif ($temYoutube && $youtubeIdCartao !== null) { ?>

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


    <!-- INFORMAÇÕES DA PUBLICAÇÃO -->

    <div class="ad-content-card-body">


        <!-- TIPO E NÍVEL -->

        <div class="ad-content-card-tags">

            <span class="ad-badge <?= $tipoCartao["classe"] ?>">

                <?= $tipoCartao["nome"] ?>

            </span>

            <span class="ad-badge ad-badge-nivel">

                <?= $nivelCartao ?>

            </span>

        </div>


        <!-- TÍTULO -->

        <h3 class="ad-content-card-title">

            <?= $tituloCartao ?>

        </h3>


        <!-- DESCRIÇÃO -->

        <p class="ad-content-card-description">

            <?= $descricaoCartao ?>

        </p>


        <!-- CATEGORIA -->

        <div class="ad-content-card-category">

            <?= $categoriaCartao ?>

        </div>


        <!-- AUTOR E DATA -->

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

</article>
</a>

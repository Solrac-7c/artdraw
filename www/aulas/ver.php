<?php

// Exibição do conteúdo (Telas.pdf): mídia principal, demais mídias,
// título e descrição da aula. Página pública.

require_once(__DIR__ . "/../includes/dados_exemplo.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$aula = $id ? buscarAula($id) : null;

if ($aula === null) {

    $_SESSION["msg"] = "Aula não encontrada.";
    $_SESSION["class"] = "alert alert-danger";

    header("Location: /index.php");
    exit;
}

$tituloPagina = $aula["titulo"];

require_once(__DIR__ . "/../includes/header.php");

?>

<div class="lesson-layout">

    <section class="lesson-media">

        <div class="media-box media-box-large">
            Foto/vídeo do conteúdo
        </div>

        <div class="media-thumbs">

            <div class="media-box">Mídia 2</div>
            <div class="media-box">Mídia 3</div>
            <div class="media-box">Mídia 4</div>

        </div>

        <div class="lesson-actions">

            <button type="button" class="btn">
                Curtir
            </button>

            <button type="button" class="btn btn-primary">
                Salvar
            </button>

        </div>

    </section>

    <section class="lesson-info">

        <div class="badge-list">

            <span class="badge">
                <?= htmlspecialchars(nomeCategoria($aula["categoria_id"])) ?>
            </span>

            <span class="badge">
                <?= htmlspecialchars(nomeNivel($aula["nivel"])) ?>
            </span>

        </div>

        <h1 class="page-title">
            <?= htmlspecialchars($aula["titulo"]) ?>
        </h1>

        <p class="lesson-text">
            <?= nl2br(htmlspecialchars($aula["descricao"])) ?>
        </p>

    </section>

</div>

<?php

require_once(__DIR__ . "/../includes/footer.php");

?>
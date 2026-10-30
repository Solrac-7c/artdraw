<?php

// Card de aula reutilizado na tela principal, nas aulas salvas e nas aulas
// criadas. Espera a variável $aula com as chaves: id, titulo, descricao,
// categoria_id e nivel.
//
// PROVISÓRIO: "Curtir" e "Salvar" ainda não têm ação; viram formulários POST
// quando houver sessão e banco.

require_once(__DIR__ . "/exemplo.php");

?>

<article class="card">

    <a
        class="media-box"
        href="/aulas/ver.php?id=<?= (int) $aula["id"] ?>"
        aria-label="Abrir <?= htmlspecialchars($aula["titulo"]) ?>"
    >
        Foto/vídeo do conteúdo
    </a>

    <div class="card-body">

        <div class="badge-list">

            <span class="badge">
                <?= htmlspecialchars(nomeCategoria($aula["categoria_id"])) ?>
            </span>

            <span class="badge">
                <?= htmlspecialchars(nomeNivel($aula["nivel"])) ?>
            </span>

        </div>

        <h2 class="card-title">
            <?= htmlspecialchars($aula["titulo"]) ?>
        </h2>

        <p class="card-text">
            <?= htmlspecialchars($aula["descricao"]) ?>
        </p>

        <div class="card-actions">

            <button type="button" class="btn">
                Curtir
            </button>

            <button type="button" class="btn">
                Salvar
            </button>

        </div>

    </div>

</article>
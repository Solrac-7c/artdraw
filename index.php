<?php

// Tela principal: pública (anônimo, aluno logado ou instrutor).
// Lista as aulas e aplica busca, categoria e nível recebidos por GET.

require_once(__DIR__ . "/www/includes/filtros.php");

$tituloPagina = "Início";
$mostrarBusca = true;

$filtros = lerFiltros();

$aulas = filtrarAulas(
    $filtros["busca"],
    $filtros["categoria"],
    $filtros["nivel"]
);

$total = count($aulas);

$temFiltro = $filtros["busca"] !== ""
    || $filtros["categoria"] > 0
    || $filtros["nivel"] !== "";

require_once(__DIR__ . "/includes/header.php");

?>

<h1 class="sr-only">Conteúdos disponíveis</h1>

<div class="result-bar">

    <p class="result-count">
        <?= $total ?> <?= $total === 1 ? "conteúdo" : "conteúdos" ?>
    </p>

    <?php if ($temFiltro) { ?>

        <a class="text-link" href="/index.php">
            Limpar busca
        </a>

    <?php } ?>

</div>

<?php if ($total > 0) { ?>

    <div class="content-grid">

        <?php

        foreach ($aulas as $aula) {
            require(__DIR__ . "/includes/card_aula.php");
        }

        ?>

    </div>

<?php } else { ?>

    <div class="empty-state">

        <p>Nenhum conteúdo encontrado.</p>

        <a href="/index.php" class="btn">
            Ver todos os conteúdos
        </a>

    </div>

<?php } ?>

<?php

require_once(__DIR__ . "/includes/footer.php");

?>
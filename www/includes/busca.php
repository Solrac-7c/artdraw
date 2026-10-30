<?php

// Barra de pesquisa com o botão "Filtragem" (Telas.pdf, tela principal).
// Funciona sem JavaScript: o painel de filtros usa <details>.
// As categorias e os níveis vêm de dados_exemplo.php.

require_once __DIR__ . "/filtros.php";

$aulas = listarAulas();

foreach ($aulas as $aula) {
    require __DIR__ . "/card_aula.php";
}

$filtros = lerFiltros();

$categorias = listarCategorias();
$niveis = listarNiveis();

?>

<form
    class="search-bar"
    method="GET"
    action="/index.php"
    role="search"
>

    <label class="sr-only" for="busca">
        Pesquisar conteúdos
    </label>

    <input
        type="search"
        id="busca"
        name="busca"
        maxlength="100"
        placeholder="Pesquisar aulas"
        autocomplete="off"
        value="<?= htmlspecialchars($filtros["busca"]) ?>"
    >

    <details class="filter-menu">

        <summary class="btn">
            Filtragem
        </summary>

        <div class="filter-panel">

            <fieldset class="filter-group">

                <legend>Categoria</legend>

                <div class="chip-list">

                    <label class="chip">
                        <input
                            type="radio"
                            name="categoria"
                            value="0"
                            <?= $filtros["categoria"] === 0 ? "checked" : "" ?>
                        >
                        Todas
                    </label>

                    <?php foreach ($categorias as $categoria) { ?>

                        <label class="chip">
                            <input
                                type="radio"
                                name="categoria"
                                value="<?= $categoria["id"] ?>"
                                <?= $filtros["categoria"] === $categoria["id"] ? "checked" : "" ?>
                            >
                            <?= htmlspecialchars($categoria["nome"]) ?>
                        </label>

                    <?php } ?>

                </div>

            </fieldset>

            <fieldset class="filter-group">

                <legend>Nível</legend>

                <div class="chip-list">

                    <label class="chip">
                        <input
                            type="radio"
                            name="nivel"
                            value=""
                            <?= $filtros["nivel"] === "" ? "checked" : "" ?>
                        >
                        Todos
                    </label>

                    <?php foreach ($niveis as $valor => $rotulo) { ?>

                        <label class="chip">
                            <input
                                type="radio"
                                name="nivel"
                                value="<?= $valor ?>"
                                <?= $filtros["nivel"] === $valor ? "checked" : "" ?>
                            >
                            <?= $rotulo ?>
                        </label>

                    <?php } ?>

                </div>

            </fieldset>

            <button type="submit" class="btn btn-primary">
                Aplicar filtros
            </button>

        </div>

    </details>

</form>
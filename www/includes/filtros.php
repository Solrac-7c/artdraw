<?php

// Lê e valida os filtros da tela principal (busca, categoria e nível).
// Usado por busca.php (para marcar o que está selecionado) e por index.php
// (para filtrar a lista). Esta lógica continua igual quando houver banco.

require_once __DIR__ . "/exemplo.php";

function lerFiltros(): array
{
    $busca = trim((string) filter_input(INPUT_GET, "busca"));

    $categoria = filter_input(INPUT_GET, "categoria", FILTER_VALIDATE_INT);
    $categoria = $categoria > 0 ? $categoria : 0;

    $nivel = trim((string) filter_input(INPUT_GET, "nivel"));

    if (!array_key_exists($nivel, listarNiveis())) {
        $nivel = "";
    }

    return [
        "busca"     => $busca,
        "categoria" => $categoria,
        "nivel"     => $nivel
    ];
}

?>
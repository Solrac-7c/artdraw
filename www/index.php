<?php

// Tela principal: pública (anônimo, aluno logado ou instrutor).

$tituloPagina = "Início";
$mostrarBusca = true;

require_once(__DIR__ . "/includes/header.php");

?>

<h1 class="sr-only">Conteúdos disponíveis</h1>

<div class="content-grid">

    <?php

    // PROVISÓRIO (Sprint 1): 8 cards de exemplo, como no Telas.pdf.
    // O Sprint 2 troca este laço por uma lista de aulas com busca e filtros.

    for ($i = 1; $i <= 8; $i++) {

        $aula = [
            "id"        => $i,
            "titulo"    => "Conteúdo " . str_pad((string) $i, 2, "0", STR_PAD_LEFT),
            "descricao" => "Título ou breve descrição do conteúdo."
        ];

        require(__DIR__ . "/includes/card_aula.php");

    }

    ?>

</div>

<?php

require_once(__DIR__ . "/includes/footer.php");

?>

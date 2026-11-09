
<?php

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÕES DA PÁGINA
|--------------------------------------------------------------------------
| Cada página poderá definir seu próprio título e usuário antes
| de incluir este arquivo.
*/

$tituloPagina = $tituloPagina ?? "ArtDraw";

$descricaoPagina = $descricaoPagina
    ?? "Aprenda, compartilhe e desenvolva suas habilidades em desenho.";

$paginaAtual = $paginaAtual ?? "";

$usuario = $usuario ?? null;


/*
|--------------------------------------------------------------------------
| TÍTULO DO DOCUMENTO
|--------------------------------------------------------------------------
*/

$tituloDocumento = $tituloPagina === "ArtDraw"
    ? "ArtDraw"
    : $tituloPagina . " | ArtDraw";

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <!-- Configurações básicas -->

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#F5F1E8">

    <meta
        name="description"
        content="<?= htmlspecialchars(
            $descricaoPagina,
            ENT_QUOTES,
            "UTF-8"
        ) ?>"
    >

    <title><?= htmlspecialchars(
        $tituloDocumento,
        ENT_QUOTES,
        "UTF-8"
    ) ?></title>


    <!-- Preparação para carregar as fontes do Google -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >


    <!-- Fontes do ArtDraw: DM Sans e Caveat -->

    <link
        href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- Bootstrap 5.3.8 -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- CSS principal do ArtDraw -->

    <link
        rel="stylesheet"
        href="/assets/css/style.css"
    >

</head>


<body>


    <!-- Link de acessibilidade para pular a navegação -->

    <a
        href="#conteudo-principal"
        class="visually-hidden-focusable"
    >
        Pular para o conteúdo
    </a>


    <!-- Menu compartilhado -->

    <?php

    require_once __DIR__ . "/menu.php";

    ?>


    <!-- Início do conteúdo específico da página -->

    <main class="ad-main" id="conteudo-principal">

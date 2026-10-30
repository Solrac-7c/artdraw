<?php

// Cabeçalho comum a todas as páginas: abre o HTML, carrega o CSS,
// monta a barra superior (menu.php) e abre o <main>.
// O arquivo footer.php fecha o que é aberto aqui.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tituloPagina = $tituloPagina ?? "ArtDraw";
$mostrarBusca = $mostrarBusca ?? false;

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($tituloPagina) ?> | ArtDraw</title>

    <link rel="stylesheet" href="/assets/css/style.css">

</head>

<body>

<?php require_once(__DIR__ . "/menu.php"); ?>

<main class="container">

<?php if (isset($_SESSION["msg"])) { ?>

    <div
        class="<?= htmlspecialchars($_SESSION["class"] ?? "alert") ?>"
        role="alert"
    >
        <?= htmlspecialchars($_SESSION["msg"]) ?>
    </div>

<?php

    unset($_SESSION["msg"], $_SESSION["class"]);

}

?>

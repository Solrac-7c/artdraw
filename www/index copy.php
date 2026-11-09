<?php

require_once("includes/auth.php");

$usuario = usuarioAtual();

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ArtDraw</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <h1>ArtDraw</h1>

    <?php if ($usuario) { ?>

        <h3>
            Bem-vindo, <?= htmlspecialchars($usuario["apelido"]) ?>!
        </h3>

        <p>
            Você está conectado como:

            <strong>
                <?= htmlspecialchars($usuario["tipo_usuario"]) ?>
            </strong>
        </p>

        <a href="logout.php" class="btn btn-danger">
            Sair
        </a>

    <?php } else { ?>

        <p>
            Bem-vindo à comunidade ArtDraw.
        </p>

        <a href="login/index.php" class="btn btn-primary">
            Entrar
        </a>

        <a href="cadastro/index.php" class="btn btn-outline-primary">
            Criar conta
        </a>

    <?php } ?>

    <hr>

    <p>
        Página inicial provisória. Em breve, aqui estará nosso
        ambiente de descoberta de conteúdos.
    </p>

</div>

</body>
</html>
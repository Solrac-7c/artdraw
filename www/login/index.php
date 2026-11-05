<?php

require_once("../includes/sessao.php");

// Se já estiver autenticado, vai para a Home
if (isset($_SESSION["usuario_id"])) {
    header("Location: /index.php");
    exit;
}

$erro = $_GET["erro"] ?? "";

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Entrar - ArtDraw</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/assets/css/style.css">

</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center align-items-center min-vh-100">

        <div class="col-md-5 col-lg-4">

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">

                    <h1 class="text-center mb-2">
                        ArtDraw
                    </h1>

                    <p class="text-center text-muted mb-4">
                        Seu espaço para aprender e compartilhar desenho.
                    </p>

                    <?php if ($erro === "credenciais") { ?>

                        <div class="alert alert-danger">
                            E-mail ou senha incorretos.
                        </div>

                    <?php } ?>

                    <?php if ($erro === "bloqueada") { ?>

                        <div class="alert alert-danger">
                            Sua conta está bloqueada ou indisponível.
                            Entre em contato com a administração.
                        </div>

                    <?php } ?>

                    <?php if ($erro === "campos") { ?>

                        <div class="alert alert-warning">
                            Preencha todos os campos.
                        </div>

                    <?php } ?>

                    <form action="autenticar.php" method="POST">

                        <div class="mb-3">

                            <label for="email" class="form-label">
                                E-mail
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                maxlength="150"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label for="senha" class="form-label">
                                Senha
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="senha"
                                name="senha"
                                required
                            >

                        </div>

                        <div class="text-end mb-3">

                            <a href="/recuperacao/index.php">
                                Esqueci minha senha
                            </a>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Entrar
                        </button>

                    </form>

                    <hr>

                    <div class="text-center">

                        <p class="mb-1">
                            Ainda não possui uma conta?
                        </p>

                        <a href="/cadastro/index.php">
                            Cadastre-se gratuitamente
                        </a>

                    </div>

                    <div class="text-center mt-3">

                        <a href="/" class="text-muted">
                            Voltar para a página inicial
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
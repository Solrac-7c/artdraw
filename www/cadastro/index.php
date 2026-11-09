<?php

require_once("../includes/sessao.php");

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

    <title>Criar conta - ArtDraw</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/assets/css/style.css">

</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center align-items-center min-vh-100">

        <div class="col-md-6 col-lg-5">

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">

                    <h1 class="text-center mb-2">
                        Criar conta
                    </h1>

                    <p class="text-center text-muted mb-4">
                        Faça parte da comunidade ArtDraw.
                    </p>


                    <?php if ($erro === "email_existente") { ?>

                        <div class="alert alert-danger">
                            Este e-mail já está cadastrado.
                        </div>

                    <?php } ?>

                    <?php if ($erro === "senha_diferente") { ?>

                        <div class="alert alert-danger">
                            As senhas não coincidem.
                        </div>

                    <?php } ?>

                    <?php if ($erro === "email_invalido") { ?>

                        <div class="alert alert-danger">
                            Informe um e-mail válido.
                        </div>

                    <?php } ?>

                    <?php if ($erro === "dados_invalidos") { ?>

                        <div class="alert alert-danger">
                            Verifique os dados preenchidos.
                        </div>

                    <?php } ?>


                    <form action="salvar.php" method="POST">

                        <div class="mb-3">

                            <label for="nome" class="form-label">
                                Nome completo
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="nome"
                                name="nome"
                                maxlength="100"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label for="apelido" class="form-label">
                                Apelido
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="apelido"
                                name="apelido"
                                maxlength="50"
                                placeholder="Como deseja ser identificado?"
                                required
                            >

                        </div>


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

                            <label for="tipo_usuario" class="form-label">
                                Você é um(a):
                            </label>

                            <select
                                class="form-select"
                                id="tipo_usuario"
                                name="tipo_usuario"
                                required
                            >

                                <option value="">
                                    Selecione uma opção
                                </option>

                                <option value="aluno">
                                    Aluno(a) de Desenho
                                </option>

                                <option value="professor">
                                    Professor(a) de Desenho
                                </option>

                            </select>

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
                                minlength="8"
                                maxlength="72"
                                required
                            >

                            <div class="form-text">
                                Utilize pelo menos 8 caracteres.
                            </div>

                        </div>


                        <div class="mb-4">

                            <label for="confirmar_senha" class="form-label">
                                Confirmar senha
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                minlength="8"
                                maxlength="72"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Criar minha conta
                        </button>

                    </form>


                    <hr>

                    <div class="text-center">

                        <p class="mb-1">
                            Já possui uma conta?
                        </p>

                        <a href="/login/index.php" class="text-decoration-underline">
                            Fazer login
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
<div
    class="modal fade"
    id="perfilInfoModal"
    tabindex="-1"
    aria-labelledby="perfilInfoTitulo"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="perfilInfoTitulo"
                >
                    Conheça seu perfil
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar"
                ></button>

            </div>

            <div class="modal-body">

                <p id="perfilInfoDescricao"></p>

                <h6 class="mt-4 mb-3">
                    O que você poderá fazer:
                </h6>

                <ul
                    id="perfilInfoRecursos"
                    class="list-group list-group-flush"
                ></ul>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-dismiss="modal"
                >
                    Entendi, continuar
                </button>

            </div>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
></script>
<script>

document.addEventListener("DOMContentLoaded", function() {

    const campoPerfil = document.getElementById("tipo_usuario");

    const modalElemento = document.getElementById("perfilInfoModal");

    const titulo = document.getElementById("perfilInfoTitulo");

    const descricao = document.getElementById("perfilInfoDescricao");

    const listaRecursos = document.getElementById("perfilInfoRecursos");

    const modal = new bootstrap.Modal(modalElemento);


    // Informações de cada perfil

    const perfis = {

        aluno: {

            titulo: "Você quer ser um Aluno!",

            descricao:
                "O ArtDraw será seu espaço para descobrir, aprender e organizar seus estudos de desenho.",

            recursos: [

                "Acessar aulas, dicas e trabalhos de todos os níveis.",

                "Explorar conteúdos de todas as categorias.",

                "Curtir publicações de outros usuários.",

                "Salvar conteúdos para consultar posteriormente.",

                "Personalizar seu perfil."

            ]

        },


        professor: {

            titulo: "Você quer ser um Professor!",

            descricao:
                "Compartilhe seu conhecimento, publique materiais e ajude outras pessoas a desenvolver suas habilidades artísticas.",

            recursos: [

                "Publicar aulas, dicas e trabalhos.",

                "Classificar conteúdos por categoria e nível.",

                "Editar e gerenciar suas próprias publicações.",

                "Curtir e salvar conteúdos de outros professores.",

                "Possuir um perfil público identificado como professor."

            ]

        }

    };


    // Detecta a alteração do perfil

    campoPerfil.addEventListener("change", function() {

        const perfilSelecionado = campoPerfil.value;

        const informacoes = perfis[perfilSelecionado];


        if (!informacoes) {
            return;
        }


        // Atualiza o título e a descrição

        titulo.textContent = informacoes.titulo;

        descricao.textContent = informacoes.descricao;


        // Limpa a lista anterior

        listaRecursos.replaceChildren();


        // Adiciona as funcionalidades do perfil

        informacoes.recursos.forEach(function(recurso) {

            const item = document.createElement("li");

            item.className =
                "list-group-item border-0 px-0";

            item.textContent = "✓ " + recurso;

            listaRecursos.appendChild(item);

        });


        // Abre o modal

        modal.show();

    });

});

</script>

</body>
</html>
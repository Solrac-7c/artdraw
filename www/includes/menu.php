
<?php

// O menu recebe os dados do usuário definidos pela página
// que inclui o header.

$usuarioMenu = isset($usuario) && is_array($usuario) ? $usuario : null;


// Protege o apelido antes de exibi-lo no HTML.

$apelidoMenu = $usuarioMenu
    ? htmlspecialchars(
        $usuarioMenu["apelido"] ?? "Usuário",
        ENT_QUOTES,
        "UTF-8"
    )
    : "";


// Traduz os tipos de usuário para nomes exibidos na interface.

$tiposUsuario = [
    "aluno" => "Aluno",
    "professor" => "Professor",
    "admin" => "Administrador"
];

$tipoUsuarioMenu = $usuarioMenu["tipo_usuario"] ?? "";

$tipoUsuarioTexto = $tiposUsuario[$tipoUsuarioMenu] ?? "Usuário";

$tipoUsuarioTexto = htmlspecialchars(
    $tipoUsuarioTexto,
    ENT_QUOTES,
    "UTF-8"
);


// Identifica a página atual para destacar o item correspondente.

$paginaAtual = $paginaAtual ?? "";

$classeExplorar = $paginaAtual === "inicio" ? " active" : "";

?>


<header class="ad-header">

    <nav class="navbar navbar-expand-lg ad-navbar" data-bs-theme="light">

        <div class="container-fluid ad-container">


            <!-- LOGOTIPO -->

            <a
                class="ad-brand navbar-brand"
                href="/index.php"
                aria-label="ArtDraw - página inicial"
            >

                ArtDraw<span class="ad-brand-dot">.</span>

            </a>


            <!-- BOTÃO DO MENU PARA CELULARES -->

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#adMenuNavegacao"
                aria-controls="adMenuNavegacao"
                aria-expanded="false"
                aria-label="Abrir ou fechar o menu"
            >

                <span class="navbar-toggler-icon"></span>

            </button>


            <!-- CONTEÚDO DA NAVEGAÇÃO -->

            <div class="collapse navbar-collapse" id="adMenuNavegacao">


                <!-- LINKS PRINCIPAIS -->

                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <li class="nav-item">

                        <a
                            class="ad-nav-link<?= $classeExplorar ?>"
                            href="/index.php"
                        >

                            Explorar

                        </a>

                    </li>

                </ul>


                <!-- AÇÕES DEPENDENDO DA AUTENTICAÇÃO -->

                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mt-3 mt-lg-0">


                    <?php if ($usuarioMenu) { ?>


                        <!-- USUÁRIO AUTENTICADO -->

                        <div class="ad-header-user">

                            <span>

                                Olá, <strong><?= $apelidoMenu ?></strong>

                            </span>

                            <span class="ad-badge ad-badge-nivel">

                                <?= $tipoUsuarioTexto ?>

                            </span>

                        </div>


                        <a
                            href="/logout.php"
                            class="ad-btn ad-btn-secondary"
                        >

                            Sair

                        </a>


                    <?php } else { ?>


                        <!-- VISITANTE NÃO AUTENTICADO -->

                        <a
                            href="/login/index.php"
                            class="ad-nav-link"
                        >

                            Entrar

                        </a>


                        <a
                            href="/cadastro/index.php"
                            class="ad-btn ad-btn-primary"
                        >

                            Criar conta

                        </a>


                    <?php } ?>


                </div>

            </div>

        </div>

    </nav>

</header>

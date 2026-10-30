<?php

// Barra superior conforme o Telas.pdf:
// [logo]  [barra de pesquisa + Filtragem - só na tela principal]  [perfil]
// O círculo de perfil sempre aponta para /perfil/index.php; quem não está
// logado é enviado ao login por includes/auth.php (a ser ligado no Sprint 4).

$logado = isset($_SESSION["usuario_id"]);

$inicial = $logado
    ? mb_strtoupper(mb_substr($_SESSION["usuario_nome"] ?? "?", 0, 1))
    : "";

?>

<header class="site-header">

    <div class="site-header-inner">

        <a
            class="brand"
            href="/index.php"
            aria-label="ArtDraw - página principal"
        >
            <span class="brand-mark">AD</span>
            <span class="brand-name">ArtDraw</span>
        </a>

        <?php

        if ($mostrarBusca) {
            require_once(__DIR__ . "/busca.php");
        }

        ?>

        <a
            class="profile-circle"
            href="/perfil/index.php"
            aria-label="Perfil"
        >

            <?php if ($logado) { ?>

                <?= htmlspecialchars($inicial) ?>

            <?php } else { ?>

                <svg
                    viewBox="0 0 24 24"
                    width="22"
                    height="22"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"></path>
                </svg>

            <?php } ?>

        </a>

    </div>

</header>

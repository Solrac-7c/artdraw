<?php

/*
|--------------------------------------------------------------------------
| AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/auth.php";

$usuario = usuarioAtual();


/*
|--------------------------------------------------------------------------
| CONFIGURAÇÕES DA PÁGINA
|--------------------------------------------------------------------------
*/

$tituloPagina = "Explorar";

$descricaoPagina =
    "Aprenda, compartilhe e desenvolva suas habilidades em desenho.";

$paginaAtual = "inicio";


/*
|--------------------------------------------------------------------------
| CABEÇALHO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/header.php";

?>


<!-- CONTEÚDO DA PÁGINA INICIAL -->

<div class="ad-home">


    <!-- APRESENTAÇÃO PRINCIPAL -->

    <section class="ad-home-hero">

        <span class="ad-home-kicker">

            COMUNIDADE ARTDRAW

        </span>


        <h1>

            Inspire-se. Aprenda. Desenhe.

        </h1>


        <p>

            Descubra novas técnicas, encontre referências e desenvolva
            suas habilidades com conteúdos de desenho organizados
            em um só lugar.

        </p>


        <?php if ($usuario) { ?>

            <div class="ad-home-welcome">

                Olá,

                <strong>
                    <?= htmlspecialchars(
                        $usuario["apelido"] ?? "artista",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>!
                </strong>

                Explore novas ideias e acompanhe a comunidade.

            </div>

        <?php } ?>


    </section>


    <!-- ÁREA PRINCIPAL DE EXPLORAÇÃO -->

    <section class="ad-home-feed">


        <div class="ad-home-feed-heading">

            <h2>

                Explore conteúdos

            </h2>

            <p>

                Aulas, dicas e trabalhos para inspirar sua evolução.

            </p>

        </div>


        <!-- ESTADO TEMPORÁRIO ATÉ A IMPLEMENTAÇÃO DO FEED -->

        <div class="ad-home-empty">

            <span class="ad-home-empty-label">

                EM PREPARAÇÃO

            </span>


            <h3>

                Seu espaço de descobertas está sendo preparado.

            </h3>


            <p>

                Em breve, você poderá explorar publicações da comunidade,
                descobrir novas técnicas e encontrar referências
                adequadas ao seu nível de conhecimento.

            </p>

        </div>


    </section>


</div>


<?php

/*
|--------------------------------------------------------------------------
| RODAPÉ
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/footer.php";

?>

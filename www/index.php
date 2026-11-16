<?php

/*
|--------------------------------------------------------------------------
| 1. AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/auth.php";

$usuario = usuarioAtual();

// Gera um token de segurança para os formulários de interação.

if (empty($_SESSION["csrf_interacao"])) {

    $_SESSION["csrf_interacao"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_interacao"];


/*
|--------------------------------------------------------------------------
| 2. CONFIGURAÇÕES DA PÁGINA
|--------------------------------------------------------------------------
*/

$tituloPagina = "Explorar";

$descricaoPagina =
    "Aprenda, compartilhe e desenvolva suas habilidades em desenho.";

$paginaAtual = "inicio";


/*
|--------------------------------------------------------------------------
| 3. CONSULTA DOS CONTEÚDOS
|--------------------------------------------------------------------------
*/

// Consulta a conexão criada pelo arquivo conexao.php.

global $conn;

// Define os campos que identificam as interações do usuário.

if ($usuario) {

    $camposInteracao = "

        EXISTS (
            SELECT 1
            FROM curtidas cu
            WHERE cu.conteudo_id = c.id
            AND cu.usuario_id = ?
        ) AS usuario_curtiu,

        EXISTS (
            SELECT 1
            FROM salvamentos sa
            WHERE sa.conteudo_id = c.id
            AND sa.usuario_id = ?
        ) AS usuario_salvou

    ";

} else {

    $camposInteracao = "

        0 AS usuario_curtiu,
        0 AS usuario_salvou

    ";

}


// Consulta principal.

$sql = "

    SELECT

        c.id,
        c.titulo,
        c.descricao,
        c.tipo_conteudo,
        c.tipo_midia,
        c.midia_url,
        c.data_publicacao,

        u.id AS autor_id,
        u.apelido AS autor_apelido,

        cat.nome AS categoria_nome,

        n.nome AS nivel_nome,

        (
            SELECT COUNT(*)
            FROM curtidas cu
            WHERE cu.conteudo_id = c.id
        ) AS total_curtidas,

        (
            SELECT COUNT(*)
            FROM salvamentos sa
            WHERE sa.conteudo_id = c.id
        ) AS total_salvamentos, $camposInteracao

    FROM conteudos c

    INNER JOIN usuarios u
        ON c.autor_id = u.id

    INNER JOIN categorias cat
        ON c.categoria_id = cat.id

    INNER JOIN niveis n
        ON c.nivel_id = n.id

    WHERE u.status <> 'bloqueado'

";

// Visitantes podem visualizar somente conteúdos iniciantes.

if (!$usuario) {
    $sql .= " AND n.nome = ?";
}


// Ordenação das publicações.

$sql .= "

    ORDER BY c.data_publicacao DESC, c.id DESC
    LIMIT 24

";


// Prepara a consulta.

$stmt = $conn->prepare($sql);


// Configura os parâmetros conforme o tipo de usuário.

if ($usuario) {

    $idUsuario = (int) $usuario["id"];

    $stmt->bind_param(
        "ii",
        $idUsuario,
        $idUsuario
    );

} else {

    $nivelPermitido = "Iniciante";

    $stmt->bind_param(
        "s",
        $nivelPermitido
    );

}


// Executa.

$stmt->execute();

$resultado = $stmt->get_result();

$conteudos = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();



/*
|--------------------------------------------------------------------------
| 4. CABEÇALHO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/header.php";

?>


<!-- =====================================================
     PÁGINA INICIAL
     ===================================================== -->

<div class="ad-home">


    <?php if (!$usuario) { ?>


        <!-- APRESENTAÇÃO EXIBIDA SOMENTE PARA VISITANTES -->

        <section class="ad-home-hero">

            <h1>

                Inspire-se. Aprenda. Desenhe.

            </h1>


            <p>

                Descubra novas técnicas, encontre referências e desenvolva
                suas habilidades com conteúdos de desenho organizados
                em um só lugar.

            </p>


            <div class="d-flex flex-wrap gap-2 mt-4">

                <a
                    href="/cadastro/index.php"
                    class="ad-btn ad-btn-primary"
                >

                    Criar minha conta

                </a>


                <a
                    href="/login/index.php"
                    class="ad-btn ad-btn-secondary"
                >

                    Já tenho uma conta

                </a>

            </div>

        </section>


    <?php } ?>


    <!-- =================================================
         FEED DE PUBLICAÇÕES
         ================================================= -->

    <section class="ad-home-feed">


        <?php if (!$usuario) { ?>

            <div class="ad-home-feed-heading">

                <h2>

                    Explore conteúdos

                </h2>

                <p>

                    Conheça aulas, dicas e trabalhos para começar
                    sua jornada no desenho.

                </p>

            </div>

        <?php } ?>


        <?php if (!empty($conteudos)) { ?>


            <!-- GRADE DE CARTÕES ESTILO PINTEREST -->

            <div class="ad-feed-grid">


                <?php foreach ($conteudos as $conteudo) { ?>

                    <div class="ad-feed-item">

                        <?php
                        require __DIR__ . "/includes/card-conteudo.php";
                        ?>

                    </div>

                <?php } ?>


            </div>


        <?php } else { ?>


            <!-- MENSAGEM QUANDO NÃO EXISTEM PUBLICAÇÕES -->

            <div class="ad-empty-state">

                <h3>

                    Ainda não existem publicações por aqui.

                </h3>

                <p>

                    Em breve, novos conteúdos estarão disponíveis
                    para você explorar.

                </p>

            </div>


        <?php } ?>


    </section>


</div>


<?php

/*
|--------------------------------------------------------------------------
| 5. RODAPÉ
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/footer.php";

?>

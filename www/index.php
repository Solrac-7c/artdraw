<?php

/*
|--------------------------------------------------------------------------
| 1. AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/auth.php";

$usuario = usuarioAtual();


// Token de segurança das interações.

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
| 3. RECEBIMENTO DA PESQUISA E CATEGORIA
|--------------------------------------------------------------------------
*/

// Pesquisa.

$busca = $_GET["busca"] ?? "";

if (!is_string($busca)) {
    $busca = "";
}

$busca = trim($busca);

if (function_exists("mb_substr")) {

    $busca = mb_substr($busca, 0, 100, "UTF-8");

} else {

    $busca = substr($busca, 0, 300);

}


// Categoria.

$categoriaSelecionada = 0;

if (
    isset($_GET["categoria"]) &&
    is_scalar($_GET["categoria"])
) {

    $valorCategoria = filter_var(
        $_GET["categoria"],
        FILTER_VALIDATE_INT
    );

    if ($valorCategoria !== false && $valorCategoria > 0) {

        $categoriaSelecionada = $valorCategoria;

    }

}


/*
|--------------------------------------------------------------------------
| 4. BUSCAR CATEGORIAS ATIVAS
|--------------------------------------------------------------------------
*/

global $conn;


$resultadoCategorias = $conn->query("

    SELECT id, nome

    FROM categorias

    WHERE ativo = 1

    ORDER BY nome ASC

");

$categorias = $resultadoCategorias->fetch_all(MYSQLI_ASSOC);


// Verifica se a categoria selecionada realmente está ativa.

$idsCategoriasAtivas = array_map(
    "intval",
    array_column($categorias, "id")
);

if (
    $categoriaSelecionada > 0 &&
    !in_array($categoriaSelecionada, $idsCategoriasAtivas, true)
) {

    $categoriaSelecionada = 0;

}


/*
|--------------------------------------------------------------------------
| 5. PREPARAÇÃO DAS INTERAÇÕES
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| 6. CONSTRUIR AS CONDIÇÕES DA CONSULTA
|--------------------------------------------------------------------------
*/

$condicoes = [
    "u.status <> 'bloqueado'"
];

$parametros = [];

$tiposParametros = "";


// Identificação das interações do usuário.

if ($usuario) {

    $idUsuario = (int) $usuario["id"];

    $parametros[] = $idUsuario;
    $parametros[] = $idUsuario;

    $tiposParametros .= "ii";

}


// Visitantes visualizam exclusivamente conteúdos iniciantes.

if (!$usuario) {

    $condicoes[] = "n.nome = ?";

    $parametros[] = "Iniciante";

    $tiposParametros .= "s";

}


// Pesquisa por título, descrição ou apelido do autor.

if ($busca !== "") {

    $condicoes[] = "

        (
            c.titulo LIKE ?
            OR c.descricao LIKE ?
            OR u.apelido LIKE ?
        )

    ";

    $termoPesquisa = "%" . $busca . "%";

    $parametros[] = $termoPesquisa;
    $parametros[] = $termoPesquisa;
    $parametros[] = $termoPesquisa;

    $tiposParametros .= "sss";

}


// Filtro por categoria.

if ($categoriaSelecionada > 0) {

    $condicoes[] = "c.categoria_id = ?";

    $parametros[] = $categoriaSelecionada;

    $tiposParametros .= "i";

}


/*
|--------------------------------------------------------------------------
| 7. CONSULTA PRINCIPAL DO FEED
|--------------------------------------------------------------------------
*/

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
        ) AS total_salvamentos,

        $camposInteracao

    FROM conteudos c

    INNER JOIN usuarios u
        ON c.autor_id = u.id

    INNER JOIN categorias cat
        ON c.categoria_id = cat.id

    INNER JOIN niveis n
        ON c.nivel_id = n.id

    WHERE " . implode(" AND ", $condicoes) . "

    ORDER BY c.data_publicacao DESC, c.id DESC

    LIMIT 24

";


$stmt = $conn->prepare($sql);


// Vinculação dinâmica dos parâmetros.

if (!empty($parametros)) {

    $referencias = [];

    foreach ($parametros as $indice => &$valor) {

        $referencias[$indice] = &$valor;

    }

    unset($valor);

    $stmt->bind_param(
        $tiposParametros,
        ...$referencias
    );

}


// Executa a consulta.

$stmt->execute();

$resultado = $stmt->get_result();

$conteudos = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| 8. CABEÇALHO COMPARTILHADO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/header.php";

?>


<!-- =====================================================
     HOME - PESQUISA E FEED
     ===================================================== -->

<div class="ad-home">

    <section class="ad-home-feed">


        <!-- BARRA DE PESQUISA E CATEGORIAS -->

        <form
            action="/index.php"
            method="GET"
            class="ad-feed-toolbar"
            id="ad-feed-form"
        >


            <!-- CAMPO DE PESQUISA -->

            <div class="ad-feed-search">

                <label
                    for="busca-conteudo"
                    class="visually-hidden"
                >
                    Pesquisar conteúdos
                </label>


                <input
                    type="search"
                    name="busca"
                    id="busca-conteudo"
                    placeholder="Pesquisar desenhos, técnicas, autores..."
                    value="<?= htmlspecialchars(
                        $busca,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >


                <input
                    type="hidden"
                    name="categoria"
                    id="ad-categoria-selecionada"
                    value="<?= (int) $categoriaSelecionada ?>"
                >


                <!-- BOTÃO DE PESQUISA -->

                <button
                    type="submit"
                    class="ad-feed-search-button"
                    aria-label="Pesquisar"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >

                        <circle cx="11" cy="11" r="7"/>

                        <path d="m20 20-4-4"/>

                    </svg>

                </button>

            </div>


            <!-- FILTRO DE CATEGORIAS -->

            <div class="dropdown ad-category-dropdown">

                <button
                    type="button"
                    class="ad-category-toggle"
                    id="ad-category-toggle"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >


                    <!-- ÍCONE DE FILTROS -->

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >

                        <path d="M4 6h16"/>

                        <path d="M4 12h16"/>

                        <path d="M4 18h16"/>

                        <circle cx="8" cy="6" r="2" fill="#F5F1E8"/>

                        <circle cx="15" cy="12" r="2" fill="#F5F1E8"/>

                        <circle cx="10" cy="18" r="2" fill="#F5F1E8"/>

                    </svg>


                    <span>Categorias</span>


                    <!-- SETA -->

                    <svg
                        class="ad-category-chevron"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >

                        <path d="m6 9 6 6 6-6"/>

                    </svg>


                </button>


                <!-- OPÇÕES DO MENU -->

                <div
                    class="dropdown-menu dropdown-menu-end ad-category-menu"
                    aria-labelledby="ad-category-toggle"
                >


                    <button
                        type="button"
                        class="dropdown-item <?= $categoriaSelecionada === 0 ? 'selected' : '' ?>"
                        data-categoria="0"
                    >

                        Todas as categorias

                    </button>


                    <?php foreach ($categorias as $categoria) { ?>


                        <button
                            type="button"
                            class="dropdown-item <?= $categoriaSelecionada == $categoria["id"] ? 'selected' : '' ?>"
                            data-categoria="<?= (int) $categoria["id"] ?>"
                        >

                            <?= htmlspecialchars(
                                $categoria["nome"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </button>


                    <?php } ?>


                </div>

            </div>


        </form>


        <!-- =================================================
             CARTÕES DO FEED
             ================================================= -->

        <?php if (!empty($conteudos)) { ?>


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


            <!-- ESTADO VAZIO -->

            <div class="ad-empty-state">

                <h3>

                    Nenhum conteúdo encontrado.

                </h3>

                <p>

                    Experimente outra palavra-chave ou selecione
                    uma categoria diferente.

                </p>

            </div>


        <?php } ?>


    </section>

</div>


<!-- =====================================================
     COMPORTAMENTO DO FILTRO DE CATEGORIAS
     ===================================================== -->

<script>

document.addEventListener("click", function(event) {

    const opcao = event.target.closest("[data-categoria]");

    if (!opcao) {
        return;
    }

    const formulario = document.getElementById("ad-feed-form");

    const campoCategoria = document.getElementById(
        "ad-categoria-selecionada"
    );

    // Atualiza a categoria escolhida.

    campoCategoria.value = opcao.dataset.categoria;

    // Envia a pesquisa e o filtro juntos.

    formulario.requestSubmit();

});

</script>


<?php

/*
|--------------------------------------------------------------------------
| 9. RODAPÉ COMPARTILHADO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/footer.php";

?>

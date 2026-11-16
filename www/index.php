<?php

/*
|--------------------------------------------------------------------------
| 1. AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/auth.php";

$usuario = usuarioAtual();


// Token de segurança para curtidas e salvamentos.

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
| 3. RECEBIMENTO DOS FILTROS
|--------------------------------------------------------------------------
*/

// Pesquisa por palavras-chave.

$busca = $_GET["busca"] ?? "";

if (!is_string($busca)) {
    $busca = "";
}

$busca = trim($busca);


// Limita o tamanho da pesquisa.

if (function_exists("mb_substr")) {

    $busca = mb_substr($busca, 0, 100, "UTF-8");

} else {

    $busca = substr($busca, 0, 300);

}


// Categoria selecionada.

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


// Nível selecionado.

$nivelSelecionado = 0;

if (
    isset($_GET["nivel"]) &&
    is_scalar($_GET["nivel"])
) {

    $valorNivel = filter_var(
        $_GET["nivel"],
        FILTER_VALIDATE_INT
    );

    if ($valorNivel !== false && $valorNivel > 0) {
        $nivelSelecionado = $valorNivel;
    }

}


// Tipo de publicação selecionado.

$tipoSelecionado = $_GET["tipo"] ?? "";

$tiposPermitidos = [
    "aula",
    "dica",
    "trabalho"
];

if (
    !is_string($tipoSelecionado) ||
    !in_array($tipoSelecionado, $tiposPermitidos, true)
) {

    $tipoSelecionado = "";

}


/*
|--------------------------------------------------------------------------
| 4. CONSULTAR CATEGORIAS E NÍVEIS ATIVOS
|--------------------------------------------------------------------------
*/

global $conn;


// Categorias disponíveis.

$resultadoCategorias = $conn->query("

    SELECT id, nome

    FROM categorias

    WHERE ativo = 1

    ORDER BY nome ASC

");

$categorias = $resultadoCategorias->fetch_all(MYSQLI_ASSOC);


// Níveis disponíveis.

$resultadoNiveis = $conn->query("

    SELECT id, nome, ordem

    FROM niveis

    WHERE ativo = 1

    ORDER BY ordem ASC

");

$niveis = $resultadoNiveis->fetch_all(MYSQLI_ASSOC);


// Verifica se a categoria escolhida está ativa.

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


// Visitantes não podem escolher níveis superiores.

if (!$usuario) {

    $nivelSelecionado = 0;

} else {

    // Verifica se o nível escolhido está ativo.

    $idsNiveisAtivos = array_map(
        "intval",
        array_column($niveis, "id")
    );

    if (
        $nivelSelecionado > 0 &&
        !in_array($nivelSelecionado, $idsNiveisAtivos, true)
    ) {

        $nivelSelecionado = 0;

    }

}


/*
|--------------------------------------------------------------------------
| 5. CONSTRUIR A CONSULTA DO FEED
|--------------------------------------------------------------------------
*/

// Campos que identificam as interações do usuário.

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


// Parâmetros da consulta preparada.

$parametros = [];

$tiposParametros = "";


// Os dois primeiros parâmetros pertencem aos campos
// de curtida e salvamento do usuário autenticado.

if ($usuario) {

    $idUsuario = (int) $usuario["id"];

    $parametros[] = $idUsuario;
    $parametros[] = $idUsuario;

    $tiposParametros .= "ii";

}


// Condições iniciais.

$condicoes = [

    "u.status <> 'bloqueado'"

];


// Visitantes veem exclusivamente conteúdos iniciantes.

if (!$usuario) {

    $condicoes[] = "n.nome = ?";

    $parametros[] = "Iniciante";

    $tiposParametros .= "s";

}


// Pesquisa por título ou descrição.

if ($busca !== "") {

    $condicoes[] = "

        (
            c.titulo LIKE ?
            OR c.descricao LIKE ?
        )

    ";

    $termoPesquisa = "%" . $busca . "%";

    $parametros[] = $termoPesquisa;
    $parametros[] = $termoPesquisa;

    $tiposParametros .= "ss";

}


// Filtro por categoria.

if ($categoriaSelecionada > 0) {

    $condicoes[] = "c.categoria_id = ?";

    $parametros[] = $categoriaSelecionada;

    $tiposParametros .= "i";

}


// Filtro por nível.
// Aplicado somente para usuários autenticados.

if ($usuario && $nivelSelecionado > 0) {

    $condicoes[] = "c.nivel_id = ?";

    $parametros[] = $nivelSelecionado;

    $tiposParametros .= "i";

}


// Filtro por tipo de publicação.

if ($tipoSelecionado !== "") {

    $condicoes[] = "c.tipo_conteudo = ?";

    $parametros[] = $tipoSelecionado;

    $tiposParametros .= "s";

}


/*
|--------------------------------------------------------------------------
| 6. CONSULTA SQL PRINCIPAL
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


// Prepara a consulta.

$stmt = $conn->prepare($sql);


// Vincula os parâmetros dinamicamente.

if (!empty($parametros)) {

    $referencias = [];

    foreach ($parametros as $indice => &$valor) {

        $referencias[$indice] = &$valor;

    }

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


// Verifica se existem filtros ativos.

$temFiltros = (
    $busca !== "" ||
    $categoriaSelecionada > 0 ||
    $nivelSelecionado > 0 ||
    $tipoSelecionado !== ""
);


/*
|--------------------------------------------------------------------------
| 7. CABEÇALHO
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


        <div class="ad-home-feed-heading">

            <h2>

                Explore conteúdos

            </h2>

            <p>

                Encontre aulas, dicas e trabalhos para inspirar
                sua evolução no desenho.

            </p>

        </div>


        <!-- =================================================
             PESQUISA E FILTROS
             ================================================= -->

        <form
            action="/index.php"
            method="GET"
            class="ad-feed-filters"
        >


            <!-- PESQUISA -->

            <div class="ad-filter-search">

                <label for="busca-conteudo">

                    Pesquisar conteúdos

                </label>

                <input
                    type="search"
                    name="busca"
                    id="busca-conteudo"
                    class="form-control"
                    placeholder="Digite o título ou uma palavra-chave..."
                    value="<?= htmlspecialchars(
                        $busca,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

            </div>


            <!-- CATEGORIA -->

            <div class="ad-filter-field">

                <label for="filtro-categoria">

                    Categoria

                </label>

                <select
                    name="categoria"
                    id="filtro-categoria"
                    class="form-select"
                >

                    <option value="0">

                        Todas as categorias

                    </option>


                    <?php foreach ($categorias as $categoria) { ?>

                        <option
                            value="<?= (int) $categoria["id"] ?>"
                            <?= $categoriaSelecionada == $categoria["id"] ? "selected" : "" ?>
                        >

                            <?= htmlspecialchars(
                                $categoria["nome"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </option>

                    <?php } ?>


                </select>

            </div>


            <!-- NÍVEL -->

            <div class="ad-filter-field">

                <label for="filtro-nivel">

                    Nível

                </label>


                <?php if ($usuario) { ?>


                    <select
                        name="nivel"
                        id="filtro-nivel"
                        class="form-select"
                    >

                        <option value="0">

                            Todos os níveis

                        </option>


                        <?php foreach ($niveis as $nivel) { ?>

                            <option
                                value="<?= (int) $nivel["id"] ?>"
                                <?= $nivelSelecionado == $nivel["id"] ? "selected" : "" ?>
                            >

                                <?= htmlspecialchars(
                                    $nivel["nome"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </option>

                        <?php } ?>


                    </select>


                <?php } else { ?>


                    <select
                        id="filtro-nivel"
                        class="form-select"
                        disabled
                    >

                        <option selected>

                            Iniciante

                        </option>

                    </select>


                <?php } ?>


            </div>


            <!-- TIPO DE CONTEÚDO -->

            <div class="ad-filter-field">

                <label for="filtro-tipo">

                    Tipo de conteúdo

                </label>

                <select
                    name="tipo"
                    id="filtro-tipo"
                    class="form-select"
                >

                    <option value="">

                        Todos os tipos

                    </option>

                    <option
                        value="aula"
                        <?= $tipoSelecionado === "aula" ? "selected" : "" ?>
                    >

                        Aulas

                    </option>

                    <option
                        value="dica"
                        <?= $tipoSelecionado === "dica" ? "selected" : "" ?>
                    >

                        Dicas

                    </option>

                    <option
                        value="trabalho"
                        <?= $tipoSelecionado === "trabalho" ? "selected" : "" ?>
                    >

                        Trabalhos

                    </option>

                </select>

            </div>


            <!-- BOTÕES -->

            <div class="ad-filter-actions">

                <button
                    type="submit"
                    class="ad-btn ad-btn-primary"
                >

                    Pesquisar e filtrar

                </button>


                <a
                    href="/index.php"
                    class="ad-btn ad-btn-secondary"
                >

                    Limpar filtros

                </a>

            </div>


        </form>


        <!-- =================================================
             RESULTADOS
             ================================================= -->

        <?php if (!empty($conteudos)) { ?>


            <div class="ad-feed-results-info">

                <?= count($conteudos) ?>

                <?= count($conteudos) === 1 ? "publicação encontrada" : "publicações encontradas" ?>

            </div>


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


            <!-- NENHUM RESULTADO -->

            <div class="ad-empty-state">

                <?php if ($temFiltros) { ?>


                    <h3>

                        Nenhum conteúdo encontrado.

                    </h3>

                    <p>

                        Não encontramos publicações com os critérios
                        selecionados. Experimente alterar sua pesquisa
                        ou limpar os filtros.

                    </p>


                <?php } else { ?>


                    <h3>

                        Ainda não existem publicações por aqui.

                    </h3>

                    <p>

                        Em breve, novos conteúdos estarão disponíveis
                        para você explorar.

                    </p>


                <?php } ?>

            </div>


        <?php } ?>


        <?php if (count($conteudos) === 24) { ?>

            <p class="ad-feed-limit-note">

                Exibindo as primeiras 24 publicações.
                Em breve, teremos carregamento de mais resultados.

            </p>

        <?php } ?>


    </section>


</div>


<?php

/*
|--------------------------------------------------------------------------
| 8. RODAPÉ
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/footer.php";

?>

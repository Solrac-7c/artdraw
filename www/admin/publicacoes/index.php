<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

$tituloPagina = "Moderar publicações";
$paginaAtual = "admin";

// Token CSRF
if (empty($_SESSION["csrf_admin_publicacoes"])) {
    $_SESSION["csrf_admin_publicacoes"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_admin_publicacoes"];

// Mensagem de retorno
$mensagem = $_SESSION["mensagem_admin_publicacoes"] ?? "";
unset($_SESSION["mensagem_admin_publicacoes"]);

// Recebe os filtros
$busca = $_GET["busca"] ?? "";
$tipoFiltro = $_GET["tipo"] ?? "";

if (!is_string($busca)) {
    $busca = "";
}

if (
    !is_string($tipoFiltro) ||
    !in_array($tipoFiltro, ["aula", "dica", "trabalho"], true)
) {
    $tipoFiltro = "";
}

$busca = trim($busca);

if (function_exists("mb_substr")) {
    $busca = mb_substr($busca, 0, 150);
} else {
    $busca = substr($busca, 0, 150);
}

// Filtro de categoria
$categoriaFiltro = 0;

if (
    isset($_GET["categoria"]) &&
    is_string($_GET["categoria"]) &&
    ctype_digit($_GET["categoria"])
) {
    $categoriaFiltro = (int) $_GET["categoria"];
}

// Filtro de nível
$nivelFiltro = 0;

if (
    isset($_GET["nivel"]) &&
    is_string($_GET["nivel"]) &&
    ctype_digit($_GET["nivel"])
) {
    $nivelFiltro = (int) $_GET["nivel"];
}

// Página atual
$pagina = 1;

if (
    isset($_GET["pagina"]) &&
    is_string($_GET["pagina"]) &&
    ctype_digit($_GET["pagina"])
) {
    $pagina = max(1, (int) $_GET["pagina"]);
}

$itensPorPagina = 20;

// Busca categorias, inclusive as inativas
$resultadoCategorias = $conn->query(
    "SELECT id, nome
     FROM categorias
     ORDER BY nome ASC"
);

$categorias = $resultadoCategorias->fetch_all(MYSQLI_ASSOC);

// Busca níveis, inclusive os inativos
$resultadoNiveis = $conn->query(
    "SELECT id, nome, ordem
     FROM niveis
     ORDER BY ordem ASC"
);

$niveis = $resultadoNiveis->fetch_all(MYSQLI_ASSOC);

// Parâmetros da pesquisa
$termoBusca = "%" . $busca . "%";

// Consulta a quantidade de resultados
$sqlContagem = "
    SELECT COUNT(*) AS total
    FROM conteudos c
    INNER JOIN usuarios u ON u.id = c.autor_id
    WHERE (? = '' OR c.tipo_conteudo = ?)
      AND (? = 0 OR c.categoria_id = ?)
      AND (? = 0 OR c.nivel_id = ?)
      AND (
            ? = ''
            OR c.titulo LIKE ?
            OR c.descricao LIKE ?
            OR u.apelido LIKE ?
            OR u.nome LIKE ?
      )
";

$stmt = $conn->prepare($sqlContagem);

$stmt->bind_param(
    "ssiiiisssss",
    $tipoFiltro,
    $tipoFiltro,
    $categoriaFiltro,
    $categoriaFiltro,
    $nivelFiltro,
    $nivelFiltro,
    $busca,
    $termoBusca,
    $termoBusca,
    $termoBusca,
    $termoBusca
);

$stmt->execute();

$estatistica = $stmt->get_result()->fetch_assoc();

$stmt->close();

$totalPublicacoes = (int) $estatistica["total"];

$totalPaginas = max(
    1,
    (int) ceil($totalPublicacoes / $itensPorPagina)
);

// Impede solicitar páginas inexistentes
$pagina = min($pagina, $totalPaginas);

$offset = ($pagina - 1) * $itensPorPagina;

// Consulta as publicações
$sqlPublicacoes = "
    SELECT
        c.id,
        c.titulo,
        c.descricao,
        c.tipo_conteudo,
        c.tipo_midia,
        c.midia_url,
        c.data_publicacao,

        u.nome AS autor_nome,
        u.apelido AS autor_apelido,
        u.status AS autor_status,

        cat.nome AS categoria_nome,
        n.nome AS nivel_nome

    FROM conteudos c

    INNER JOIN usuarios u
        ON u.id = c.autor_id

    INNER JOIN categorias cat
        ON cat.id = c.categoria_id

    INNER JOIN niveis n
        ON n.id = c.nivel_id

    WHERE (? = '' OR c.tipo_conteudo = ?)
      AND (? = 0 OR c.categoria_id = ?)
      AND (? = 0 OR c.nivel_id = ?)
      AND (
            ? = ''
            OR c.titulo LIKE ?
            OR c.descricao LIKE ?
            OR u.apelido LIKE ?
            OR u.nome LIKE ?
      )

    ORDER BY c.data_publicacao DESC, c.id DESC

    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sqlPublicacoes);

$stmt->bind_param(
    "ssiiiisssssii",
    $tipoFiltro,
    $tipoFiltro,
    $categoriaFiltro,
    $categoriaFiltro,
    $nivelFiltro,
    $nivelFiltro,
    $busca,
    $termoBusca,
    $termoBusca,
    $termoBusca,
    $termoBusca,
    $itensPorPagina,
    $offset
);

$stmt->execute();

$resultado = $stmt->get_result();
$publicacoes = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Rótulos dos tipos
$tiposConteudo = [
    "aula" => "Aula",
    "dica" => "Dica",
    "trabalho" => "Trabalho"
];

// Escapamento de saída
$esc = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};

// Monta URLs de paginação preservando os filtros
$urlPagina = static function ($numero) use (
    $busca,
    $tipoFiltro,
    $categoriaFiltro,
    $nivelFiltro
) {
    return "/admin/publicacoes/index.php?" . http_build_query([
        "busca" => $busca,
        "tipo" => $tipoFiltro,
        "categoria" => $categoriaFiltro,
        "nivel" => $nivelFiltro,
        "pagina" => $numero
    ]);
};

require_once __DIR__ . "/../../includes/header.php";

?>

<section class="ad-admin-page ad-admin-publications-page">

    <div class="ad-admin-inner">

        <a href="/admin/index.php" class="ad-admin-back-link">
            ← Voltar ao painel administrativo
        </a>

        <div class="ad-admin-section-heading">

            <div>
                <span class="ad-admin-eyebrow">ADMINISTRAÇÃO</span>

                <h1>Moderar publicações</h1>

                <p>
                    Consulte os conteúdos publicados na comunidade
                    e remova aqueles que violarem as regras da plataforma.
                </p>
            </div>

        </div>

        <?php if ($mensagem !== ""): ?>

            <div class="ad-admin-feedback">
                <?= $esc($mensagem) ?>
            </div>

        <?php endif; ?>


        <!-- INDICADOR -->

        <div class="ad-admin-panel ad-admin-publication-counter">

            <div>
                <span>Total de publicações encontradas</span>
                <strong><?= $totalPublicacoes ?></strong>
            </div>

        </div>


        <!-- FILTROS -->

        <div class="ad-admin-panel">

            <h2>Pesquisar publicações</h2>

            <form method="GET" action="index.php">

                <div class="ad-admin-publication-filters">

                    <div class="ad-admin-form-group">

                        <label for="busca">Buscar publicação</label>

                        <input
                            type="search"
                            id="busca"
                            name="busca"
                            maxlength="150"
                            placeholder="Título, descrição ou autor"
                            value="<?= $esc($busca) ?>">

                    </div>

                    <div class="ad-admin-form-group">

                        <label for="tipo">Tipo de conteúdo</label>

                        <select id="tipo" name="tipo">

                            <option value="">Todos</option>

                            <?php foreach ($tiposConteudo as $chave => $rotulo): ?>

                                <option
                                    value="<?= $esc($chave) ?>"
                                    <?= $tipoFiltro === $chave ? "selected" : "" ?>>
                                    <?= $esc($rotulo) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="ad-admin-form-group">

                        <label for="categoria">Categoria</label>

                        <select id="categoria" name="categoria">

                            <option value="0">Todas</option>

                            <?php foreach ($categorias as $categoria): ?>

                                <option
                                    value="<?= (int) $categoria["id"] ?>"
                                    <?= $categoriaFiltro === (int) $categoria["id"] ? "selected" : "" ?>>
                                    <?= $esc($categoria["nome"]) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="ad-admin-form-group">

                        <label for="nivel">Nível</label>

                        <select id="nivel" name="nivel">

                            <option value="0">Todos</option>

                            <?php foreach ($niveis as $nivel): ?>

                                <option
                                    value="<?= (int) $nivel["id"] ?>"
                                    <?= $nivelFiltro === (int) $nivel["id"] ? "selected" : "" ?>>
                                    <?= $esc($nivel["nome"]) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="ad-admin-user-filter-buttons">

                        <button
                            type="submit"
                            class="ad-admin-primary-button">
                            Pesquisar
                        </button>

                        <a
                            href="/admin/publicacoes/index.php"
                            class="ad-admin-secondary-button">
                            Limpar
                        </a>

                    </div>

                </div>

            </form>

        </div>


        <!-- TABELA DE PUBLICAÇÕES -->

        <div class="ad-admin-panel">

            <div class="ad-admin-list-heading">

                <h2>Publicações cadastradas</h2>

                <span>
                    Página <?= $pagina ?> de <?= $totalPaginas ?>
                </span>

            </div>

            <?php if (empty($publicacoes)): ?>

                <p class="ad-admin-empty">
                    Nenhuma publicação encontrada com os filtros informados.
                </p>

            <?php else: ?>

                <div class="ad-admin-table-wrapper">

                    <table class="ad-admin-table ad-admin-publications-table">

                        <thead>

                            <tr>
                                <th>Publicação</th>
                                <th>Tipo</th>
                                <th>Classificação</th>
                                <th>Autor</th>
                                <th>Data</th>
                                <th>Ação</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($publicacoes as $publicacao): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= $esc($publicacao["titulo"]) ?>
                                        </strong>

                                        <?php if (!empty($publicacao["descricao"])): ?>

                                            <div class="ad-admin-content-description">
                                                <?= $esc($publicacao["descricao"]) ?>
                                            </div>

                                        <?php endif; ?>

                                        <div class="ad-admin-user-details">

                                            <?php if ($publicacao["tipo_midia"] === "imagem"): ?>

                                                Mídia: imagem

                                            <?php elseif ($publicacao["tipo_midia"] === "youtube"): ?>

                                                Mídia: YouTube

                                            <?php else: ?>

                                                Sem mídia

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                    <td>
                                        <?= $esc(
                                            $tiposConteudo[$publicacao["tipo_conteudo"]]
                                                ?? $publicacao["tipo_conteudo"]
                                        ) ?>
                                    </td>

                                    <td>

                                        <strong>
                                            <?= $esc($publicacao["categoria_nome"]) ?>
                                        </strong>

                                        <div class="ad-admin-user-details">
                                            <?= $esc($publicacao["nivel_nome"]) ?>
                                        </div>

                                    </td>

                                    <td>

                                        <strong>
                                            <?= $esc($publicacao["autor_nome"]) ?>
                                        </strong>

                                        <div class="ad-admin-user-details">
                                            @<?= $esc($publicacao["autor_apelido"]) ?>
                                        </div>

                                        <?php if ($publicacao["autor_status"] === "bloqueado"): ?>

                                            <span class="ad-admin-status ad-admin-status-inactive">
                                                Autor bloqueado
                                            </span>

                                        <?php elseif ($publicacao["autor_status"] === "excluido"): ?>

                                            <span class="ad-admin-status ad-admin-status-inactive">
                                                Conta excluída
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?= !empty($publicacao["data_publicacao"])
                                            ? date(
                                                "d/m/Y",
                                                strtotime($publicacao["data_publicacao"])
                                            )
                                            : "-" ?>

                                    </td>

                                    <td>

                                        <div class="ad-admin-row-actions">

                                            <!-- VISUALIZAR PUBLICAÇÃO -->
                                            <a
                                                href="/conteudo/index.php?id=<?= (int) $publicacao["id"] ?>&admin_preview=1"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="ad-admin-preview-link">
                                                Visualizar
                                            </a>

                                            <!-- REMOVER PUBLICAÇÃO -->
                                            <form
                                                action="/admin/publicacoes/excluir.php"
                                                method="POST"
                                                onsubmit="return confirm('Tem certeza de que deseja remover esta publicação? Essa ação é permanente e não poderá ser desfeita.');">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= $esc($csrfToken) ?>">

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $publicacao["id"] ?>">

                                                <button
                                                    type="submit"
                                                    class="ad-admin-action-button ad-admin-danger-button">
                                                    Remover
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- PAGINAÇÃO -->

                <?php if ($totalPaginas > 1): ?>

                    <div class="ad-admin-pagination">

                        <?php if ($pagina > 1): ?>

                            <a href="<?= $esc($urlPagina($pagina - 1)) ?>">
                                ← Anterior
                            </a>

                        <?php endif; ?>

                        <span>
                            Página <?= $pagina ?> de <?= $totalPaginas ?>
                        </span>

                        <?php if ($pagina < $totalPaginas): ?>

                            <a href="<?= $esc($urlPagina($pagina + 1)) ?>">
                                Próxima →
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>

        <p class="ad-admin-note">
            A remoção de uma publicação é permanente. Seus links
            complementares, curtidas e salvamentos também serão removidos.
        </p>

    </div>

</section>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
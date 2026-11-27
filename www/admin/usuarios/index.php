<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

$tituloPagina = "Gerenciar usuários";
$paginaAtual = "admin";

// Token CSRF
if (empty($_SESSION["csrf_admin_usuarios"])) {
    $_SESSION["csrf_admin_usuarios"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_admin_usuarios"];

// Mensagem de retorno
$mensagem = $_SESSION["mensagem_admin_usuarios"] ?? "";
unset($_SESSION["mensagem_admin_usuarios"]);

// Recebe os filtros
$tipoFiltro = $_GET["tipo"] ?? "";
$statusFiltro = $_GET["status"] ?? "";

$busca = isset($_GET["busca"]) && is_string($_GET["busca"])
    ? trim($_GET["busca"])
    : "";

if (!is_string($tipoFiltro) ||
    !in_array($tipoFiltro, ["aluno", "professor"], true)) {
    $tipoFiltro = "";
}

if (!is_string($statusFiltro) ||
    !in_array($statusFiltro, ["ativo", "bloqueado", "excluido"], true)) {
    $statusFiltro = "";
}

// Limita o tamanho da pesquisa
if (strlen($busca) > 150) {
    $busca = substr($busca, 0, 150);
}

// Indicadores gerais
$resultadoStats = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'ativo') AS ativos,
        SUM(status = 'bloqueado') AS bloqueados,
        SUM(status = 'excluido') AS excluidos
     FROM usuarios
     WHERE tipo_usuario IN ('aluno', 'professor')"
);

$estatisticas = $resultadoStats->fetch_assoc();

// Pesquisa de usuários com filtros
$termoBusca = "%" . $busca . "%";

$stmt = $conn->prepare(
    "SELECT
        id,
        nome,
        apelido,
        email,
        tipo_usuario,
        status,
        data_cadastro
     FROM usuarios
     WHERE tipo_usuario IN ('aluno', 'professor')
       AND (? = '' OR tipo_usuario = ?)
       AND (? = '' OR status = ?)
       AND (
            ? = ''
            OR nome LIKE ?
            OR apelido LIKE ?
            OR email LIKE ?
       )
     ORDER BY
        CASE status
            WHEN 'ativo' THEN 1
            WHEN 'bloqueado' THEN 2
            ELSE 3
        END,
        nome ASC"
);

$stmt->bind_param(
    "ssssssss",
    $tipoFiltro,
    $tipoFiltro,
    $statusFiltro,
    $statusFiltro,
    $busca,
    $termoBusca,
    $termoBusca,
    $termoBusca
);

$stmt->execute();

$resultado = $stmt->get_result();
$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Escapamento de saída
$esc = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};

require_once __DIR__ . "/../../includes/header.php";

?>

<section class="ad-admin-page ad-admin-users-page">

    <div class="ad-admin-inner">

        <a href="/admin/index.php" class="ad-admin-back-link">
            ← Voltar ao painel administrativo
        </a>

        <div class="ad-admin-section-heading">

            <div>
                <span class="ad-admin-eyebrow">ADMINISTRAÇÃO</span>

                <h1>Gerenciar usuários</h1>

                <p>
                    Consulte e gerencie as contas cadastradas
                    na comunidade ArtDraw.
                </p>
            </div>

        </div>

        <?php if ($mensagem !== ""): ?>

            <div class="ad-admin-feedback">
                <?= $esc($mensagem) ?>
            </div>

        <?php endif; ?>


        <!-- INDICADORES -->

        <div class="ad-admin-user-stats">

            <div class="ad-admin-user-stat">
                <span>Total de usuários</span>
                <strong><?= (int) $estatisticas["total"] ?></strong>
            </div>

            <div class="ad-admin-user-stat">
                <span>Contas ativas</span>
                <strong><?= (int) $estatisticas["ativos"] ?></strong>
            </div>

            <div class="ad-admin-user-stat">
                <span>Contas bloqueadas</span>
                <strong><?= (int) $estatisticas["bloqueados"] ?></strong>
            </div>

            <div class="ad-admin-user-stat">
                <span>Contas excluídas</span>
                <strong><?= (int) $estatisticas["excluidos"] ?></strong>
            </div>

        </div>


        <!-- PESQUISA E FILTROS -->

        <div class="ad-admin-panel">

            <h2>Pesquisar usuários</h2>

            <form method="GET" action="index.php">

                <div class="ad-admin-user-filters">

                    <div class="ad-admin-form-group">

                        <label for="busca">Buscar</label>

                        <input
                            type="search"
                            id="busca"
                            name="busca"
                            placeholder="Nome, apelido ou e-mail"
                            value="<?= $esc($busca) ?>"
                        >

                    </div>

                    <div class="ad-admin-form-group">

                        <label for="tipo">Tipo de usuário</label>

                        <select id="tipo" name="tipo">

                            <option value="">Todos</option>

                            <option
                                value="aluno"
                                <?= $tipoFiltro === "aluno" ? "selected" : "" ?>
                            >
                                Alunos
                            </option>

                            <option
                                value="professor"
                                <?= $tipoFiltro === "professor" ? "selected" : "" ?>
                            >
                                Professores
                            </option>

                        </select>

                    </div>

                    <div class="ad-admin-form-group">

                        <label for="status">Situação</label>

                        <select id="status" name="status">

                            <option value="">Todos</option>

                            <option
                                value="ativo"
                                <?= $statusFiltro === "ativo" ? "selected" : "" ?>
                            >
                                Ativos
                            </option>

                            <option
                                value="bloqueado"
                                <?= $statusFiltro === "bloqueado" ? "selected" : "" ?>
                            >
                                Bloqueados
                            </option>

                            <option
                                value="excluido"
                                <?= $statusFiltro === "excluido" ? "selected" : "" ?>
                            >
                                Excluídos
                            </option>

                        </select>

                    </div>

                    <div class="ad-admin-user-filter-buttons">

                        <button
                            type="submit"
                            class="ad-admin-primary-button"
                        >
                            Pesquisar
                        </button>

                        <a
                            href="/admin/usuarios/index.php"
                            class="ad-admin-secondary-button"
                        >
                            Limpar
                        </a>

                    </div>

                </div>

            </form>

        </div>


        <!-- LISTA DE USUÁRIOS -->

        <div class="ad-admin-panel">

            <div class="ad-admin-list-heading">

                <h2>Usuários cadastrados</h2>

                <span>
                    <?= count($usuarios) ?>
                    <?= count($usuarios) === 1 ? "resultado" : "resultados" ?>
                </span>

            </div>

            <?php if (empty($usuarios)): ?>

                <p class="ad-admin-empty">
                    Nenhum usuário encontrado com os filtros informados.
                </p>

            <?php else: ?>

                <div class="ad-admin-table-wrapper">

                    <table class="ad-admin-table ad-admin-users-table">

                        <thead>

                            <tr>
                                <th>Usuário</th>
                                <th>Tipo</th>
                                <th>Cadastro</th>
                                <th>Situação</th>
                                <th>Ações</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($usuarios as $u): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= $esc($u["nome"]) ?>
                                        </strong>

                                        <div class="ad-admin-user-details">
                                            @<?= $esc($u["apelido"]) ?>
                                        </div>

                                        <div class="ad-admin-user-details">
                                            <?= $esc($u["email"]) ?>
                                        </div>

                                    </td>

                                    <td>

                                        <?= $u["tipo_usuario"] === "professor"
                                            ? "Professor"
                                            : "Aluno" ?>

                                    </td>

                                    <td>

                                        <?= !empty($u["data_cadastro"])
                                            ? date("d/m/Y", strtotime($u["data_cadastro"]))
                                            : "-" ?>

                                    </td>

                                    <td>

                                        <?php if ($u["status"] === "ativo"): ?>

                                            <span class="ad-admin-status ad-admin-status-active">
                                                Ativo
                                            </span>

                                        <?php elseif ($u["status"] === "bloqueado"): ?>

                                            <span class="ad-admin-status ad-admin-status-inactive">
                                                Bloqueado
                                            </span>

                                        <?php else: ?>

                                            <span class="ad-admin-status ad-admin-status-inactive">
                                                Excluído
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="ad-admin-row-actions">

                                            <?php if ($u["status"] === "ativo"): ?>

                                                <form
                                                    action="/admin/usuarios/alterar-status.php"
                                                    method="POST"
                                                    onsubmit="return confirm('Deseja realmente bloquear este usuário?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= $esc($csrfToken) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int) $u["id"] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="ad-admin-action-button"
                                                    >
                                                        Bloquear
                                                    </button>

                                                </form>

                                            <?php elseif ($u["status"] === "bloqueado"): ?>

                                                <form
                                                    action="/admin/usuarios/alterar-status.php"
                                                    method="POST"
                                                    onsubmit="return confirm('Deseja desbloquear este usuário?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= $esc($csrfToken) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int) $u["id"] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="ad-admin-action-button"
                                                    >
                                                        Desbloquear
                                                    </button>

                                                </form>

                                            <?php endif; ?>


                                            <?php if ($u["status"] !== "excluido"): ?>

                                                <form
                                                    action="/admin/usuarios/excluir.php"
                                                    method="POST"
                                                    onsubmit="return confirm('Deseja excluir esta conta? As publicações do usuário serão preservadas.');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= $esc($csrfToken) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int) $u["id"] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="ad-admin-action-button ad-admin-danger-button"
                                                    >
                                                        Excluir
                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <span class="ad-admin-user-no-action">
                                                    Sem ações disponíveis
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

        <p class="ad-admin-note">
            Contas administrativas são gerenciadas separadamente.
            A exclusão lógica não remove as publicações do usuário.
        </p>

    </div>

</section>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
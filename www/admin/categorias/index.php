<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

$tituloPagina = "Gerenciar categorias";
$paginaAtual = "admin";

// Gera o token de segurança do formulário.
if (empty($_SESSION["csrf_admin_categorias"])) {
    $_SESSION["csrf_admin_categorias"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_admin_categorias"];

// Recupera uma mensagem de retorno, caso exista.
$mensagem = $_SESSION["mensagem_admin_categorias"] ?? "";
unset($_SESSION["mensagem_admin_categorias"]);

// Verifica se existe uma categoria sendo editada.
$categoriaEdicao = null;

$idEdicao = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

if ($idEdicao) {
    $stmt = $conn->prepare(
        "SELECT id, nome FROM categorias WHERE id = ?"
    );

    $stmt->bind_param("i", $idEdicao);
    $stmt->execute();
    $stmt->bind_result($idCategoria, $nomeCategoria);

    if ($stmt->fetch()) {
        $categoriaEdicao = [
            "id" => $idCategoria,
            "nome" => $nomeCategoria
        ];
    }

    $stmt->close();
}

// Busca todas as categorias, inclusive as desativadas.
$resultado = $conn->query(
    "SELECT id, nome, ativo
     FROM categorias
     ORDER BY ativo DESC, nome ASC"
);

$categorias = $resultado->fetch_all(MYSQLI_ASSOC);

// Função para exibir textos com segurança.
$esc = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};

require_once __DIR__ . "/../../includes/header.php";

?>

<section class="ad-admin-page ad-admin-categories">

    <div class="ad-admin-inner">

        <a href="/admin/index.php" class="ad-admin-back-link">
            ← Voltar ao painel administrativo
        </a>

        <div class="ad-admin-section-heading">
            <div>
                <span class="ad-admin-eyebrow">ADMINISTRAÇÃO</span>
                <h1>Gerenciar categorias</h1>
                <p>
                    Organize os assuntos utilizados para classificar
                    as publicações do ArtDraw.
                </p>
            </div>
        </div>

        <?php if ($mensagem !== ""): ?>
            <div class="ad-admin-feedback">
                <?= $esc($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- FORMULÁRIO DE CADASTRO E EDIÇÃO -->
        <div class="ad-admin-panel">

            <h2>
                <?= $categoriaEdicao
                    ? "Editar categoria"
                    : "Adicionar categoria" ?>
            </h2>

            <form action="/admin/categorias/salvar.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $esc($csrfToken) ?>"
                >

                <?php if ($categoriaEdicao): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $categoriaEdicao["id"] ?>"
                    >
                <?php endif; ?>

                <div class="ad-admin-form-group">

                    <label for="nome_categoria">
                        Nome da categoria
                    </label>

                    <input
                        type="text"
                        id="nome_categoria"
                        name="nome"
                        maxlength="100"
                        placeholder="Ex.: Desenho digital"
                        value="<?= $esc($categoriaEdicao["nome"] ?? "") ?>"
                        required
                    >

                </div>

                <div class="ad-admin-form-actions">

                    <button type="submit" class="ad-admin-primary-button">
                        <?= $categoriaEdicao
                            ? "Salvar alterações"
                            : "Cadastrar categoria" ?>
                    </button>

                    <?php if ($categoriaEdicao): ?>
                        <a href="/admin/categorias/index.php"
                           class="ad-admin-secondary-button">
                            Cancelar edição
                        </a>
                    <?php endif; ?>

                </div>

            </form>

        </div>

        <!-- LISTA DE CATEGORIAS -->
        <div class="ad-admin-panel">

            <div class="ad-admin-list-heading">
                <h2>Categorias cadastradas</h2>

                <span>
                    <?= count($categorias) ?>
                    <?= count($categorias) === 1 ? "categoria" : "categorias" ?>
                </span>
            </div>

            <?php if (empty($categorias)): ?>

                <p class="ad-admin-empty">
                    Nenhuma categoria cadastrada.
                </p>

            <?php else: ?>

                <div class="ad-admin-table-wrapper">

                    <table class="ad-admin-table">

                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($categorias as $categoria): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?= $esc($categoria["nome"]) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php if ((int) $categoria["ativo"] === 1): ?>
                                            <span class="ad-admin-status ad-admin-status-active">
                                                Ativa
                                            </span>
                                        <?php else: ?>
                                            <span class="ad-admin-status ad-admin-status-inactive">
                                                Inativa
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="ad-admin-row-actions">

                                            <a
                                                href="/admin/categorias/index.php?editar=<?= (int) $categoria["id"] ?>"
                                                class="ad-admin-action-link"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                action="/admin/categorias/alterar-status.php"
                                                method="POST"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= $esc($csrfToken) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int) $categoria["id"] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="ad-admin-action-button"
                                                >
                                                    <?= (int) $categoria["ativo"] === 1
                                                        ? "Desativar"
                                                        : "Reativar" ?>
                                                </button>

                                            </form>

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
            Desativar uma categoria impede sua utilização em novas
            publicações, mas não remove a categoria das publicações
            que já existem.
        </p>

    </div>

</section>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

$tituloPagina = "Gerenciar níveis";
$paginaAtual = "admin";

// Token de segurança
if (empty($_SESSION["csrf_admin_niveis"])) {
    $_SESSION["csrf_admin_niveis"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_admin_niveis"];

// Mensagem de retorno
$mensagem = $_SESSION["mensagem_admin_niveis"] ?? "";
unset($_SESSION["mensagem_admin_niveis"]);

// Busca o nível que será editado
$nivelEdicao = null;

$idEdicao = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

if ($idEdicao) {

    $stmt = $conn->prepare(
        "SELECT id, nome, ordem
         FROM niveis
         WHERE id = ?"
    );

    $stmt->bind_param("i", $idEdicao);
    $stmt->execute();

    $stmt->bind_result($idNivel, $nomeNivel, $ordemNivel);

    if ($stmt->fetch()) {
        $nivelEdicao = [
            "id" => $idNivel,
            "nome" => $nomeNivel,
            "ordem" => $ordemNivel
        ];
    }

    $stmt->close();
}

// Lista todos os níveis
$resultado = $conn->query(
    "SELECT id, nome, ordem, ativo
     FROM niveis
     ORDER BY ordem ASC"
);

$niveis = $resultado->fetch_all(MYSQLI_ASSOC);

// Escapamento de texto
$esc = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};

require_once __DIR__ . "/../../includes/header.php";

?>

<section class="ad-admin-page">

    <div class="ad-admin-inner">

        <a href="/admin/index.php" class="ad-admin-back-link">
            ← Voltar ao painel administrativo
        </a>

        <div class="ad-admin-section-heading">

            <div>
                <span class="ad-admin-eyebrow">ADMINISTRAÇÃO</span>

                <h1>Gerenciar níveis</h1>

                <p>
                    Organize os níveis de aprendizado disponíveis
                    na plataforma.
                </p>
            </div>

        </div>

        <?php if ($mensagem !== ""): ?>

            <div class="ad-admin-feedback">
                <?= $esc($mensagem) ?>
            </div>

        <?php endif; ?>


        <!-- FORMULÁRIO -->

        <div class="ad-admin-panel">

            <h2>
                <?= $nivelEdicao
                    ? "Editar nível"
                    : "Adicionar nível" ?>
            </h2>

            <form action="/admin/niveis/salvar.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $esc($csrfToken) ?>"
                >

                <?php if ($nivelEdicao): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $nivelEdicao["id"] ?>"
                    >

                <?php endif; ?>

                <div class="ad-admin-form-group">

                    <label for="nome">
                        Nome do nível
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="50"
                        placeholder="Ex.: Intermediário"
                        value="<?= $esc($nivelEdicao["nome"] ?? "") ?>"
                        required
                    >

                </div>

                <div class="ad-admin-form-group">

                    <label for="ordem">
                        Ordem de exibição
                    </label>

                    <input
                        type="number"
                        id="ordem"
                        name="ordem"
                        min="1"
                        max="255"
                        placeholder="Ex.: 1"
                        value="<?= $esc($nivelEdicao["ordem"] ?? "") ?>"
                        required
                    >

                    <small>
                        Define a sequência em que os níveis serão apresentados.
                    </small>

                </div>

                <div class="ad-admin-form-actions">

                    <button
                        type="submit"
                        class="ad-admin-primary-button"
                    >
                        <?= $nivelEdicao
                            ? "Salvar alterações"
                            : "Cadastrar nível" ?>
                    </button>

                    <?php if ($nivelEdicao): ?>

                        <a
                            href="/admin/niveis/index.php"
                            class="ad-admin-secondary-button"
                        >
                            Cancelar edição
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </div>


        <!-- TABELA DE NÍVEIS -->

        <div class="ad-admin-panel">

            <div class="ad-admin-list-heading">

                <h2>Níveis cadastrados</h2>

                <span>
                    <?= count($niveis) ?>
                    <?= count($niveis) === 1 ? "nível" : "níveis" ?>
                </span>

            </div>

            <?php if (empty($niveis)): ?>

                <p class="ad-admin-empty">
                    Nenhum nível cadastrado.
                </p>

            <?php else: ?>

                <div class="ad-admin-table-wrapper">

                    <table class="ad-admin-table">

                        <thead>
                            <tr>
                                <th>Ordem</th>
                                <th>Nível</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($niveis as $nivel): ?>

                                <tr>

                                    <td>
                                        <?= (int) $nivel["ordem"] ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= $esc($nivel["nome"]) ?>
                                        </strong>
                                    </td>

                                    <td>

                                        <?php if ((int) $nivel["ativo"] === 1): ?>

                                            <span class="ad-admin-status ad-admin-status-active">
                                                Ativo
                                            </span>

                                        <?php else: ?>

                                            <span class="ad-admin-status ad-admin-status-inactive">
                                                Inativo
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="ad-admin-row-actions">

                                            <a
                                                href="/admin/niveis/index.php?editar=<?= (int) $nivel["id"] ?>"
                                                class="ad-admin-action-link"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                action="/admin/niveis/alterar-status.php"
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
                                                    value="<?= (int) $nivel["id"] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="ad-admin-action-button"
                                                >
                                                    <?= (int) $nivel["ativo"] === 1
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
            Desativar um nível impede sua utilização em novas publicações.
            As publicações existentes continuam vinculadas ao nível original.
        </p>

    </div>

</section>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
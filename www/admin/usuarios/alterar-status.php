<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

// Valida CSRF
$tokenSessao = $_SESSION["csrf_admin_usuarios"] ?? "";
$tokenFormulario = $_POST["csrf_token"] ?? "";

if (
    !is_string($tokenSessao) ||
    $tokenSessao === "" ||
    !is_string($tokenFormulario) ||
    !hash_equals($tokenSessao, $tokenFormulario)
) {
    $_SESSION["mensagem_admin_usuarios"] =
        "Não foi possível validar a solicitação.";

    header("Location: index.php");
    exit;
}

// Valida ID
$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {

    $_SESSION["mensagem_admin_usuarios"] =
        "Usuário inválido.";

    header("Location: index.php");
    exit;
}

try {

    $conn->begin_transaction();

    // Consulta o usuário e bloqueia a linha durante a operação.
    $stmt = $conn->prepare(
        "SELECT tipo_usuario, status
         FROM usuarios
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt->bind_result($tipoUsuario, $statusAtual);

    $encontrado = $stmt->fetch();

    $stmt->close();

    if (!$encontrado) {

        $conn->rollback();

        $_SESSION["mensagem_admin_usuarios"] =
            "Usuário não encontrado.";

        header("Location: index.php");
        exit;
    }

    // Impede alterações em contas administrativas.
    if (!in_array($tipoUsuario, ["aluno", "professor"], true)) {

        $conn->rollback();

        $_SESSION["mensagem_admin_usuarios"] =
            "Esta conta não pode ser gerenciada por este módulo.";

        header("Location: index.php");
        exit;
    }

    // Define o novo status.
    if ($statusAtual === "ativo") {

        $novoStatus = "bloqueado";

    } elseif ($statusAtual === "bloqueado") {

        $novoStatus = "ativo";

    } else {

        $conn->rollback();

        $_SESSION["mensagem_admin_usuarios"] =
            "Não é possível alterar o status de uma conta excluída.";

        header("Location: index.php");
        exit;
    }

    // Atualiza a conta.
    $atualizar = $conn->prepare(
        "UPDATE usuarios
         SET status = ?,
             data_atualizacao = CURRENT_TIMESTAMP
         WHERE id = ?"
    );

    $atualizar->bind_param("si", $novoStatus, $id);
    $atualizar->execute();
    $atualizar->close();

    $conn->commit();

    $_SESSION["mensagem_admin_usuarios"] =
        $novoStatus === "bloqueado"
            ? "Usuário bloqueado com sucesso."
            : "Usuário desbloqueado com sucesso.";

} catch (Throwable $e) {

    $conn->rollback();

    error_log($e->getMessage());

    $_SESSION["mensagem_admin_usuarios"] =
        "Ocorreu um erro ao atualizar a conta.";
}

header("Location: index.php");
exit;
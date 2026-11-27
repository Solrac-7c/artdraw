<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

// Validação CSRF
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

    // Consulta o usuário.
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

    // Não permite excluir contas administrativas.
    if (!in_array($tipoUsuario, ["aluno", "professor"], true)) {

        $conn->rollback();

        $_SESSION["mensagem_admin_usuarios"] =
            "Esta conta não pode ser excluída por este módulo.";

        header("Location: index.php");
        exit;
    }

    if ($statusAtual === "excluido") {

        $conn->rollback();

        $_SESSION["mensagem_admin_usuarios"] =
            "Esta conta já foi excluída.";

        header("Location: index.php");
        exit;
    }

    // Exclusão lógica.
    $atualizar = $conn->prepare(
        "UPDATE usuarios
         SET status = 'excluido',
             data_exclusao = CURRENT_TIMESTAMP,
             data_atualizacao = CURRENT_TIMESTAMP
         WHERE id = ?"
    );

    $atualizar->bind_param("i", $id);
    $atualizar->execute();
    $atualizar->close();

    $conn->commit();

    $_SESSION["mensagem_admin_usuarios"] =
        "Conta excluída com sucesso. As publicações foram preservadas.";

} catch (Throwable $e) {

    $conn->rollback();

    error_log($e->getMessage());

    $_SESSION["mensagem_admin_usuarios"] =
        "Ocorreu um erro ao excluir a conta.";
}

header("Location: index.php");
exit;
<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

// Valida CSRF
$tokenSessao = $_SESSION["csrf_admin_niveis"] ?? "";
$tokenFormulario = $_POST["csrf_token"] ?? "";

if (
    !is_string($tokenSessao) ||
    $tokenSessao === "" ||
    !is_string($tokenFormulario) ||
    !hash_equals($tokenSessao, $tokenFormulario)
) {
    $_SESSION["mensagem_admin_niveis"] =
        "Não foi possível validar a solicitação.";

    header("Location: index.php");
    exit;
}

// Valida o ID
$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {

    $_SESSION["mensagem_admin_niveis"] =
        "Nível inválido.";

    header("Location: index.php");
    exit;
}

// Busca o nível
$stmt = $conn->prepare(
    "SELECT nome, ativo
     FROM niveis
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$stmt->bind_result($nome, $ativo);

if (!$stmt->fetch()) {

    $stmt->close();

    $_SESSION["mensagem_admin_niveis"] =
        "Nível não encontrado.";

    header("Location: index.php");
    exit;
}

$stmt->close();

// Inverte o status
$novoStatus = ((int) $ativo === 1) ? 0 : 1;

$atualizar = $conn->prepare(
    "UPDATE niveis
     SET ativo = ?
     WHERE id = ?"
);

$atualizar->bind_param("ii", $novoStatus, $id);
$atualizar->execute();
$atualizar->close();

if ($novoStatus === 1) {

    $_SESSION["mensagem_admin_niveis"] =
        "Nível reativado com sucesso.";

} else {

    $_SESSION["mensagem_admin_niveis"] =
        "Nível desativado com sucesso.";
}

header("Location: index.php");
exit;
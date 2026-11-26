<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

// Valida o token de segurança.
$tokenSessao = $_SESSION["csrf_admin_categorias"] ?? "";
$tokenFormulario = $_POST["csrf_token"] ?? "";

if (
    !is_string($tokenSessao) ||
    $tokenSessao === "" ||
    !is_string($tokenFormulario) ||
    !hash_equals($tokenSessao, $tokenFormulario)
) {
    $_SESSION["mensagem_admin_categorias"] =
        "Não foi possível validar a solicitação.";

    header("Location: index.php");
    exit;
}

// Valida o ID.
$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id) {

    $_SESSION["mensagem_admin_categorias"] =
        "Categoria inválida.";

    header("Location: index.php");
    exit;
}

// Busca a categoria.
$stmt = $conn->prepare(
    "SELECT nome, ativo FROM categorias WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nome, $ativo);

if (!$stmt->fetch()) {

    $stmt->close();

    $_SESSION["mensagem_admin_categorias"] =
        "Categoria não encontrada.";

    header("Location: index.php");
    exit;
}

$stmt->close();

// Inverte o status atual.
$novoStatus = ((int) $ativo === 1) ? 0 : 1;

$atualizar = $conn->prepare(
    "UPDATE categorias
     SET ativo = ?, data_atualizacao = CURRENT_TIMESTAMP
     WHERE id = ?"
);

$atualizar->bind_param("ii", $novoStatus, $id);
$atualizar->execute();
$atualizar->close();

if ($novoStatus === 1) {

    $_SESSION["mensagem_admin_categorias"] =
        "Categoria reativada com sucesso.";

} else {

    $_SESSION["mensagem_admin_categorias"] =
        "Categoria desativada com sucesso.";
}

header("Location: index.php");
exit;
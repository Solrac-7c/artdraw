<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

// Validação CSRF
$tokenSessao = $_SESSION["csrf_admin_niveis"] ?? "";
$tokenFormulario = $_POST["csrf_token"] ?? "";

if (
    !is_string($tokenSessao) ||
    $tokenSessao === "" ||
    !is_string($tokenFormulario) ||
    !hash_equals($tokenSessao, $tokenFormulario)
) {
    $_SESSION["mensagem_admin_niveis"] =
        "Não foi possível validar a solicitação. Tente novamente.";

    header("Location: index.php");
    exit;
}

// Recebe os dados
$nome = trim($_POST["nome"] ?? "");

$id = filter_var(
    $_POST["id"] ?? 0,
    FILTER_VALIDATE_INT
);

$ordem = filter_var(
    $_POST["ordem"] ?? null,
    FILTER_VALIDATE_INT
);

if ($id === false || $id < 0) {
    $id = 0;
}

// Valida o nome
preg_match_all('/./us', $nome, $caracteres);
$quantidadeCaracteres = count($caracteres[0] ?? []);

if ($nome === "" || $quantidadeCaracteres > 50) {

    $_SESSION["mensagem_admin_niveis"] =
        "Informe um nome válido com até 50 caracteres.";

    header("Location: index.php");
    exit;
}

// Valida a ordem
if ($ordem === false || $ordem < 1 || $ordem > 255) {

    $_SESSION["mensagem_admin_niveis"] =
        "Informe uma ordem válida entre 1 e 255.";

    header("Location: index.php");
    exit;
}

try {

    if ($id > 0) {

        // Verifica se o nível existe
        $verificar = $conn->prepare(
            "SELECT id FROM niveis WHERE id = ?"
        );

        $verificar->bind_param("i", $id);
        $verificar->execute();
        $verificar->store_result();

        if ($verificar->num_rows === 0) {

            $verificar->close();

            $_SESSION["mensagem_admin_niveis"] =
                "O nível informado não foi encontrado.";

            header("Location: index.php");
            exit;
        }

        $verificar->close();

        // Atualiza o nível
        $stmt = $conn->prepare(
            "UPDATE niveis
             SET nome = ?, ordem = ?
             WHERE id = ?"
        );

        $stmt->bind_param("sii", $nome, $ordem, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION["mensagem_admin_niveis"] =
            "Nível atualizado com sucesso.";

    } else {

        // Cadastra um novo nível
        $stmt = $conn->prepare(
            "INSERT INTO niveis (nome, ordem)
             VALUES (?, ?)"
        );

        $stmt->bind_param("si", $nome, $ordem);
        $stmt->execute();
        $stmt->close();

        $_SESSION["mensagem_admin_niveis"] =
            "Nível cadastrado com sucesso.";
    }

} catch (mysqli_sql_exception $e) {

    if ((int) $e->getCode() === 1062) {

        $_SESSION["mensagem_admin_niveis"] =
            "Já existe um nível com esse nome ou com essa posição de ordem.";

    } else {

        error_log($e->getMessage());

        $_SESSION["mensagem_admin_niveis"] =
            "Ocorreu um erro ao salvar o nível.";
    }
}

header("Location: index.php");
exit;
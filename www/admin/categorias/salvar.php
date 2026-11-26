<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

// Validação do token de segurança.
$tokenSessao = $_SESSION["csrf_admin_categorias"] ?? "";
$tokenFormulario = $_POST["csrf_token"] ?? "";

if (
    !is_string($tokenSessao) ||
    $tokenSessao === "" ||
    !is_string($tokenFormulario) ||
    !hash_equals($tokenSessao, $tokenFormulario)
) {
    $_SESSION["mensagem_admin_categorias"] =
        "Não foi possível validar a solicitação. Tente novamente.";

    header("Location: index.php");
    exit;
}

// Recupera e valida os dados.
$nome = trim($_POST["nome"] ?? "");

$id = filter_var(
    $_POST["id"] ?? 0,
    FILTER_VALIDATE_INT
);

if (!$id) {
    $id = 0;
}

// Validação do nome.
preg_match_all('/./us', $nome, $caracteres);
$quantidadeCaracteres = count($caracteres[0]);

if ($nome === "" || $quantidadeCaracteres > 100) {

    $_SESSION["mensagem_admin_categorias"] =
        "Informe um nome válido com até 100 caracteres.";

    header("Location: index.php");
    exit;
}

try {

    if ($id > 0) {

        // Verifica se a categoria realmente existe.
        $verificar = $conn->prepare(
            "SELECT id FROM categorias WHERE id = ?"
        );

        $verificar->bind_param("i", $id);
        $verificar->execute();
        $verificar->store_result();

        if ($verificar->num_rows === 0) {

            $verificar->close();

            $_SESSION["mensagem_admin_categorias"] =
                "A categoria informada não foi encontrada.";

            header("Location: index.php");
            exit;
        }

        $verificar->close();

        // Atualiza o nome da categoria.
        $stmt = $conn->prepare(
            "UPDATE categorias
             SET nome = ?, data_atualizacao = CURRENT_TIMESTAMP
             WHERE id = ?"
        );

        $stmt->bind_param("si", $nome, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION["mensagem_admin_categorias"] =
            "Categoria atualizada com sucesso.";

    } else {

        // Cadastra uma nova categoria.
        $stmt = $conn->prepare(
            "INSERT INTO categorias (nome) VALUES (?)"
        );

        $stmt->bind_param("s", $nome);
        $stmt->execute();
        $stmt->close();

        $_SESSION["mensagem_admin_categorias"] =
            "Categoria cadastrada com sucesso.";
    }

} catch (mysqli_sql_exception $e) {

    if ((int) $e->getCode() === 1062) {

        $_SESSION["mensagem_admin_categorias"] =
            "Já existe uma categoria com esse nome.";

    } else {

        error_log($e->getMessage());

        $_SESSION["mensagem_admin_categorias"] =
            "Ocorreu um erro ao salvar a categoria.";
    }
}

header("Location: index.php");
exit;
<?php

require_once __DIR__ . "/../../includes/permissoes.php";
require_once __DIR__ . "/../../includes/conexao.php";

$usuario = exigirPerfil(["admin"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método não permitido.");
}

// Validação CSRF
$tokenSessao = $_SESSION["csrf_admin_publicacoes"] ?? "";
$tokenFormulario = $_POST["csrf_token"] ?? "";

if (
    !is_string($tokenSessao) ||
    $tokenSessao === "" ||
    !is_string($tokenFormulario) ||
    !hash_equals($tokenSessao, $tokenFormulario)
) {
    $_SESSION["mensagem_admin_publicacoes"] =
        "Não foi possível validar a solicitação.";

    header("Location: index.php");
    exit;
}

// Validação do ID
$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {

    $_SESSION["mensagem_admin_publicacoes"] =
        "Publicação inválida.";

    header("Location: index.php");
    exit;
}

$transacaoAtiva = false;

try {

    $conn->begin_transaction();
    $transacaoAtiva = true;

    // Busca a mídia vinculada à publicação.
    // FOR UPDATE impede alterações concorrentes durante a operação.
    $stmt = $conn->prepare(
        "SELECT tipo_midia, midia_url
         FROM conteudos
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt->bind_result($tipoMidia, $midiaUrl);

    $encontrado = $stmt->fetch();

    $stmt->close();

    if (!$encontrado) {

        $conn->rollback();
        $transacaoAtiva = false;

        $_SESSION["mensagem_admin_publicacoes"] =
            "Publicação não encontrada.";

        header("Location: index.php");
        exit;
    }

    // Remove a publicação.
    $excluir = $conn->prepare(
        "DELETE FROM conteudos
         WHERE id = ?"
    );

    $excluir->bind_param("i", $id);
    $excluir->execute();

    $excluido = $excluir->affected_rows;

    $excluir->close();

    if ($excluido !== 1) {

        throw new RuntimeException(
            "Não foi possível remover a publicação."
        );
    }

    // Confirma a exclusão no banco.
    $conn->commit();
    $transacaoAtiva = false;


    // Remove o arquivo local, se a publicação possuía imagem.
    if (
        $tipoMidia === "imagem" &&
        is_string($midiaUrl) &&
        $midiaUrl !== ""
    ) {

        $caminhoUrl = parse_url($midiaUrl, PHP_URL_PATH);

        if (!is_string($caminhoUrl)) {
            $caminhoUrl = $midiaUrl;
        }

        $nomeArquivo = basename($caminhoUrl);

        // Aceita somente nomes gerados pelo sistema.
        if (
            preg_match(
                '/\A[a-f0-9]{32}\.(jpg|png|webp)\z/i',
                $nomeArquivo
            )
        ) {

            $caminhoArquivo = __DIR__
                . "/../../uploads/conteudos/"
                . $nomeArquivo;

            if (
                is_file($caminhoArquivo) &&
                !unlink($caminhoArquivo)
            ) {
                error_log(
                    "Não foi possível remover o arquivo: "
                    . $nomeArquivo
                );
            }
        }
    }

    $_SESSION["mensagem_admin_publicacoes"] =
        "Publicação removida com sucesso.";

} catch (Throwable $e) {

    if ($transacaoAtiva) {
        $conn->rollback();
    }

    error_log($e->getMessage());

    $_SESSION["mensagem_admin_publicacoes"] =
        "Ocorreu um erro ao remover a publicação.";
}

header("Location: index.php");
exit;
<?php

require_once __DIR__ . "/../includes/auth.php";

// Exige autenticação.
$usuario = exigirLogin();

// Somente professores podem excluir publicações.
if (($usuario["tipo_usuario"] ?? "") !== "professor") {
    http_response_code(403);
    exit("Acesso permitido somente para professores.");
}

// A exclusão aceita somente requisições POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Método não permitido.");
}

$idProfessor = (int) $usuario["id"];

// Valida o token CSRF.
$tokenRecebido = $_POST["csrf_token"] ?? "";
$tokenSessao = $_SESSION["csrf_excluir_publicacao"] ?? "";

if (
    !is_string($tokenRecebido) ||
    !is_string($tokenSessao) ||
    $tokenSessao === "" ||
    !hash_equals($tokenSessao, $tokenRecebido)
) {
    http_response_code(403);
    exit("Solicitação inválida ou expirada. Atualize o painel e tente novamente.");
}

// Valida o ID recebido pelo formulário.
$idPublicacao = filter_input(
    INPUT_POST,
    "id",
    FILTER_VALIDATE_INT
);

if (!$idPublicacao || $idPublicacao < 1) {
    http_response_code(400);
    exit("Identificador de publicação inválido.");
}

$transacaoIniciada = false;

try {

    // Inicia uma transação para proteger a exclusão.
    $conn->begin_transaction();
    $transacaoIniciada = true;

    // Busca a publicação e verifica se pertence ao professor.
    // Agora aceita Aula, Dica ou Trabalho.
    $sql = "
        SELECT
            id,
            tipo_midia,
            midia_url
        FROM conteudos
        WHERE id = ?
          AND autor_id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $idPublicacao,
        $idProfessor
    );

    $stmt->execute();

    $publicacao = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    // Não revela se uma publicação de outro professor existe.
    if (!$publicacao) {

        $conn->rollback();
        $transacaoIniciada = false;

        http_response_code(404);
        exit("Publicação não encontrada ou indisponível.");
    }

    // Guarda os dados da mídia antes de excluir o registro.
    $tipoMidia = $publicacao["tipo_midia"];
    $midiaUrl = $publicacao["midia_url"];

    // Exclui somente a publicação pertencente ao professor.
    $stmt = $conn->prepare("
        DELETE FROM conteudos
        WHERE id = ?
          AND autor_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $idPublicacao,
        $idProfessor
    );

    $stmt->execute();

    $linhasExcluidas = $stmt->affected_rows;

    $stmt->close();

    if ($linhasExcluidas !== 1) {
        throw new RuntimeException(
            "A publicação não pôde ser excluída."
        );
    }

    // Confirma a exclusão no banco.
    // Links, curtidas e salvamentos são removidos pelo ON DELETE CASCADE.
    $conn->commit();
    $transacaoIniciada = false;

    // Remove a imagem do servidor somente após o commit.
    if (
        $tipoMidia === "imagem" &&
        is_string($midiaUrl) &&
        str_starts_with($midiaUrl, "/uploads/conteudos/")
    ) {

        $nomeArquivo = basename($midiaUrl);

        // Aceita somente nomes gerados pelo sistema.
        $nomeValido = preg_match(
            '/^[a-f0-9]{32}\.(jpg|png|webp)$/i',
            $nomeArquivo
        );

        $caminhoEsperado = "/uploads/conteudos/" . $nomeArquivo;

        if (
            $nomeValido &&
            $midiaUrl === $caminhoEsperado
        ) {

            $caminhoArquivo =
                __DIR__ . "/../uploads/conteudos/" . $nomeArquivo;

            // Não remove diretórios nem segue links simbólicos.
            if (
                is_file($caminhoArquivo) &&
                !is_link($caminhoArquivo)
            ) {

                if (!unlink($caminhoArquivo)) {
                    error_log(
                        "Não foi possível remover a imagem da publicação "
                            . $idPublicacao
                    );
                }
            }
        }
    }

    // Mensagem de sucesso para o painel.
    $_SESSION["mensagem_professor"] =
        "A publicação foi excluída com sucesso.";

    $_SESSION["tipo_mensagem_professor"] = "sucesso";

    header("Location: /professor/index.php");
    exit;
} catch (Throwable $erro) {

    // Reverte a transação se ainda estiver aberta.
    if ($transacaoIniciada) {

        try {
            $conn->rollback();
        } catch (Throwable $erroRollback) {
            error_log(
                "Erro ao reverter exclusão ArtDraw: "
                    . $erroRollback->getMessage()
            );
        }
    }

    error_log(
        "Erro ao excluir publicação ArtDraw: "
            . $erro->getMessage()
    );

    $_SESSION["mensagem_professor"] =
        "Não foi possível excluir a publicação. Tente novamente.";

    $_SESSION["tipo_mensagem_professor"] = "erro";

    header("Location: /professor/index.php");
    exit;
}

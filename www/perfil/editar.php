<?php

require_once __DIR__ . "/../includes/auth.php";

// Somente usuários autenticados podem acessar esta página.
$usuario = exigirLogin();

// Apenas alunos e professores podem editar seus perfis.
if (!in_array(
    $usuario["tipo_usuario"] ?? "",
    ["aluno", "professor"],
    true
)) {
    http_response_code(403);
    exit("Você não tem permissão para editar este perfil.");
}

// Proteção CSRF.
if (empty($_SESSION["csrf_perfil"])) {
    $_SESSION["csrf_perfil"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_perfil"];

// Mensagem de sucesso após o redirecionamento.
$mensagemSucesso = $_SESSION["mensagem_perfil"] ?? "";
unset($_SESSION["mensagem_perfil"]);

$erros = [];

// Valores atuais do usuário.
$nome = $usuario["nome"] ?? "";
$apelido = $usuario["apelido"] ?? "";
$biografia = $usuario["biografia"] ?? "";
$fotoAtual = $usuario["foto_perfil"] ?? null;

// Escapa valores antes de exibi-los no HTML.
$escapar = static function ($valor) {
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
};

// Recupera somente valores textuais enviados pelo formulário.
$obterTexto = static function ($valor): string {
    return is_string($valor) ? trim($valor) : "";
};

// Conta caracteres, considerando acentos e caracteres especiais.
$contarCaracteres = static function (string $texto): int {
    $resultado = preg_match_all('/./us', $texto, $correspondencias);

    return $resultado === false
        ? strlen($texto)
        : $resultado;
};

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Valida o token de segurança.
    $tokenRecebido = $_POST["csrf_token"] ?? "";

    if (
        !is_string($tokenRecebido) ||
        !hash_equals($csrfToken, $tokenRecebido)
    ) {
        $erros[] = "A solicitação expirou ou é inválida. Atualize a página e tente novamente.";
    }

    // Recupera os dados enviados.
    $nome = $obterTexto($_POST["nome"] ?? "");
    $apelido = $obterTexto($_POST["apelido"] ?? "");
    $biografia = $obterTexto($_POST["biografia"] ?? "");

    // Valida nome.
    if ($nome === "") {
        $erros[] = "Informe seu nome.";
    } elseif ($contarCaracteres($nome) > 100) {
        $erros[] = "O nome deve ter no máximo 100 caracteres.";
    }

    // Valida apelido.
    if ($apelido === "") {
        $erros[] = "Informe seu apelido.";
    } elseif ($contarCaracteres($apelido) > 50) {
        $erros[] = "O apelido deve ter no máximo 50 caracteres.";
    }

    // Valida biografia.
    if ($contarCaracteres($biografia) > 500) {
        $erros[] = "A biografia deve ter no máximo 500 caracteres.";
    }

    // Verifica se outro usuário já utiliza o apelido.
    if ($apelido !== "") {

        $sql = "
            SELECT id
            FROM usuarios
            WHERE apelido = ?
              AND id <> ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        $idUsuario = (int) $usuario["id"];

        $stmt->bind_param("si", $apelido, $idUsuario);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $erros[] = "Este apelido já está sendo utilizado por outro usuário.";
        }

        $stmt->close();
    }

    // Mantém a foto atual caso nenhuma nova imagem seja enviada.
    $fotoFinal = $fotoAtual;
    $arquivoNovo = null;

    $arquivoUpload = $_FILES["foto_perfil"] ?? null;
    $temNovaFoto = (
        is_array($arquivoUpload) &&
        ($arquivoUpload["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    );

    $extensaoFoto = null;

    // Validação da imagem enviada.
    if ($temNovaFoto) {

        if (($arquivoUpload["error"] ?? null) !== UPLOAD_ERR_OK) {

            $erros[] = "Não foi possível receber a imagem. Verifique o arquivo e tente novamente.";

        } elseif ((int) $arquivoUpload["size"] > 2 * 1024 * 1024) {

            $erros[] = "A foto deve ter no máximo 2 MB.";

        } elseif (
            empty($arquivoUpload["tmp_name"]) ||
            !is_uploaded_file($arquivoUpload["tmp_name"])
        ) {

            $erros[] = "O arquivo enviado é inválido.";

        } else {

            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo === false) {

                $erros[] = "Não foi possível validar o formato da imagem.";

            } else {

                $mime = finfo_file(
                    $finfo,
                    $arquivoUpload["tmp_name"]
                );

                finfo_close($finfo);

                // Somente estes três formatos serão aceitos.
                $formatosPermitidos = [
                    "image/jpeg" => "jpg",
                    "image/pjpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                $informacoesImagem = @getimagesize(
                    $arquivoUpload["tmp_name"]
                );

                if (
                    !isset($formatosPermitidos[$mime]) ||
                    $informacoesImagem === false
                ) {

                    $erros[] = "Envie uma imagem válida nos formatos JPG, PNG ou WebP.";

                } else {

                    $extensaoFoto = $formatosPermitidos[$mime];
                }
            }
        }
    }

    // Se não houver erros, salva a nova foto no servidor.
    if (empty($erros) && $temNovaFoto && $extensaoFoto !== null) {

        $diretorioFotos = __DIR__ . "/../uploads/perfis";

        if (
            !is_dir($diretorioFotos) &&
            !mkdir($diretorioFotos, 0755, true) &&
            !is_dir($diretorioFotos)
        ) {

            $erros[] = "Não foi possível preparar a pasta de armazenamento das fotos.";

        } else {

            $nomeArquivo = bin2hex(random_bytes(16)) . "." . $extensaoFoto;

            $caminhoDestino = $diretorioFotos . "/" . $nomeArquivo;

            if (
                move_uploaded_file(
                    $arquivoUpload["tmp_name"],
                    $caminhoDestino
                )
            ) {

                $arquivoNovo = $caminhoDestino;
                $fotoFinal = "/uploads/perfis/" . $nomeArquivo;

            } else {

                $erros[] = "Não foi possível salvar a foto enviada.";
            }
        }
    }

    // Atualiza somente a conta identificada pela sessão.
    if (empty($erros)) {

        try {

            $sql = "
                UPDATE usuarios
                SET
                    nome = ?,
                    apelido = ?,
                    biografia = ?,
                    foto_perfil = ?,
                    data_atualizacao = NOW()
                WHERE id = ?
                  AND status = 'ativo'
                  AND tipo_usuario IN ('aluno', 'professor')
            ";

            $stmt = $conn->prepare($sql);

            $idUsuario = (int) $usuario["id"];

            $stmt->bind_param(
                "ssssi",
                $nome,
                $apelido,
                $biografia,
                $fotoFinal,
                $idUsuario
            );

            $stmt->execute();
            $stmt->close();

            // Exclui a foto antiga somente se ela estiver
            // dentro da pasta de fotos de perfil do ArtDraw.
            if (
                $fotoFinal !== $fotoAtual &&
                is_string($fotoAtual) &&
                str_starts_with($fotoAtual, "/uploads/perfis/")
            ) {

                $nomeFotoAntiga = basename($fotoAtual);

                $caminhoFotoAntiga =
                    __DIR__ . "/../uploads/perfis/" . $nomeFotoAntiga;

                if (is_file($caminhoFotoAntiga)) {
                    unlink($caminhoFotoAntiga);
                }
            }

            $_SESSION["mensagem_perfil"] = "Seu perfil foi atualizado com sucesso.";

            header("Location: /perfil/editar.php");
            exit;

        } catch (Throwable $erro) {

            // Remove a nova foto caso o banco não consiga atualizar.
            if ($arquivoNovo && is_file($arquivoNovo)) {
                unlink($arquivoNovo);
            }

            error_log("Erro ao atualizar perfil ArtDraw: " . $erro->getMessage());

            $erros[] = "Não foi possível atualizar seu perfil. Tente novamente.";
        }
    }
}

$tituloPagina = "Editar perfil";
$paginaAtual = "perfil";

require_once __DIR__ . "/../includes/header.php";

?>

<div class="ad-edit-profile-page">

    <div class="ad-edit-profile-heading">
        <h1>Editar perfil</h1>
        <p>Atualize suas informações na comunidade ArtDraw.</p>
    </div>

    <?php if ($mensagemSucesso !== ""): ?>

        <div class="ad-edit-profile-success">
            <?= $escapar($mensagemSucesso) ?>
        </div>

    <?php endif; ?>

    <?php if (!empty($erros)): ?>

        <div class="ad-edit-profile-errors">

            <?php foreach ($erros as $erro): ?>

                <p><?= $escapar($erro) ?></p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <form
        action="/perfil/editar.php"
        method="POST"
        enctype="multipart/form-data"
        class="ad-edit-profile-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= $escapar($csrfToken) ?>"
        >

        <div class="ad-edit-profile-photo">

            <label>Foto de perfil</label>

            <?php if (!empty($fotoAtual)): ?>

                <img
                    src="<?= $escapar($fotoAtual) ?>"
                    alt="Sua foto de perfil"
                    class="ad-edit-profile-preview"
                >

            <?php else: ?>

                <div class="ad-edit-profile-placeholder">
                    AD
                </div>

            <?php endif; ?>

            <input
                type="file"
                name="foto_perfil"
                 accept=".jfif,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >

            <small>
                JPG, PNG ou WebP. Tamanho máximo: 2 MB.
            </small>

        </div>

        <div class="ad-edit-profile-field">

            <label for="nome">Nome</label>

            <input
                type="text"
                id="nome"
                name="nome"
                maxlength="100"
                value="<?= $escapar($nome) ?>"
                required
            >

        </div>

        <div class="ad-edit-profile-field">

            <label for="apelido">Apelido</label>

            <input
                type="text"
                id="apelido"
                name="apelido"
                maxlength="50"
                value="<?= $escapar($apelido) ?>"
                required
            >

            <small>
                Este é o nome que aparecerá publicamente no ArtDraw.
            </small>

        </div>

        <div class="ad-edit-profile-field">

            <label for="biografia">Biografia</label>

            <textarea
                id="biografia"
                name="biografia"
                rows="5"
                maxlength="500"
                placeholder="Conte um pouco sobre você..."
            ><?= $escapar($biografia) ?></textarea>

            <small>
                Máximo de 500 caracteres.
            </small>

        </div>

        <div class="ad-edit-profile-actions">

            <button type="submit" class="ad-edit-profile-save">
                Salvar alterações
            </button>

            <a href="/perfil/index.php" class="ad-edit-profile-cancel">
                Cancelar
            </a>

        </div>

    </form>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

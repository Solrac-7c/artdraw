<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/youtube.php";

// Somente professores autenticados podem editar publicações.
$usuario = exigirLogin();

if (($usuario["tipo_usuario"] ?? "") !== "professor") {
    http_response_code(403);
    exit("Acesso permitido somente para professores.");
}

$idProfessor = (int) $usuario["id"];

// Recupera o ID da publicação pela URL.
$idPublicacao = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$idPublicacao || $idPublicacao < 1) {
    http_response_code(404);
    exit("Aula não encontrada.");
}

// Busca a publicação verificando sua autoria.
// Nesta etapa, permitimos a edição somente de aulas.
$sqlPublicacao = "
    SELECT
        id,
        titulo,
        descricao,
        conteudo,
        tipo_conteudo,
        tipo_midia,
        midia_url,
        autor_id,
        categoria_id,
        nivel_id
    FROM conteudos
    WHERE id = ?
      AND autor_id = ?
      AND tipo_conteudo = 'aula'
    LIMIT 1
";

$stmt = $conn->prepare($sqlPublicacao);
$stmt->bind_param("ii", $idPublicacao, $idProfessor);
$stmt->execute();

$publicacao = $stmt->get_result()->fetch_assoc();

$stmt->close();

// Não revela se uma publicação de outro professor existe.
if (!$publicacao) {
    http_response_code(404);
    exit("Aula não encontrada ou indisponível.");
}

// Token de proteção CSRF.
if (empty($_SESSION["csrf_editar_publicacao"])) {
    $_SESSION["csrf_editar_publicacao"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_editar_publicacao"];

// Funções auxiliares.
$escapar = static function ($valor) {
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
};

$obterTexto = static function ($valor): string {
    return is_string($valor) ? trim($valor) : "";
};

$contarCaracteres = static function (string $texto): int {
    $resultado = preg_match_all('/./us', $texto, $correspondencias);

    return $resultado === false
        ? strlen($texto)
        : $resultado;
};

$obterId = static function ($valor): int {
    if (!is_scalar($valor)) {
        return 0;
    }

    $id = filter_var((string) $valor, FILTER_VALIDATE_INT);

    return $id !== false && $id > 0 ? (int) $id : 0;
};

// Busca categorias ativas e também a categoria atual,
// caso ela tenha sido desativada pelo administrador.
$stmt = $conn->prepare("
    SELECT id, nome, ativo
    FROM categorias
    WHERE ativo = 1 OR id = ?
    ORDER BY nome ASC
");

$stmt->bind_param("i", $publicacao["categoria_id"]);
$stmt->execute();

$categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Busca níveis ativos e também o nível atual,
// caso ele tenha sido desativado pelo administrador.
$stmt = $conn->prepare("
    SELECT id, nome, ativo
    FROM niveis
    WHERE ativo = 1 OR id = ?
    ORDER BY ordem ASC
");

$stmt->bind_param("i", $publicacao["nivel_id"]);
$stmt->execute();

$niveis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Recupera os links complementares atuais.
$stmt = $conn->prepare("
    SELECT titulo, url
    FROM links_complementares
    WHERE conteudo_id = ?
    ORDER BY ordem ASC, id ASC
");

$stmt->bind_param("i", $idPublicacao);
$stmt->execute();

$linksOriginais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Valores iniciais do formulário.
$titulo = $publicacao["titulo"];
$descricao = $publicacao["descricao"];
$textoCompleto = $publicacao["conteudo"];

$categoriaId = (int) $publicacao["categoria_id"];
$nivelId = (int) $publicacao["nivel_id"];

$tipoMidia = $publicacao["tipo_midia"] ?? "nenhum";

$urlYoutube = (
    $tipoMidia === "youtube"
        ? $publicacao["midia_url"]
        : ""
);

$linksTituloFormulario = array_map(
    $obterTexto,
    array_column($linksOriginais, "titulo")
);

$linksUrlFormulario = array_map(
    $obterTexto,
    array_column($linksOriginais, "url")
);

if (empty($linksTituloFormulario)) {
    $linksTituloFormulario = [""];
}

if (empty($linksUrlFormulario)) {
    $linksUrlFormulario = [""];
}

$erros = [];

// Processamento do formulário.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validação CSRF.
    $tokenRecebido = $_POST["csrf_token"] ?? "";

    if (
        !is_string($tokenRecebido) ||
        !hash_equals($csrfToken, $tokenRecebido)
    ) {
        $erros[] = "Solicitação inválida ou expirada. Atualize a página e tente novamente.";
    }

    // Recuperação dos campos.
    $titulo = $obterTexto($_POST["titulo"] ?? "");
    $descricao = $obterTexto($_POST["descricao"] ?? "");
    $textoCompleto = $obterTexto($_POST["conteudo"] ?? "");

    $categoriaId = $obterId($_POST["categoria_id"] ?? "");
    $nivelId = $obterId($_POST["nivel_id"] ?? "");

    $tipoMidia = $obterTexto($_POST["tipo_midia"] ?? "nenhum");
    $urlYoutube = $obterTexto($_POST["youtube_url"] ?? "");

    // Validação do título.
    if ($titulo === "") {
        $erros[] = "Informe o título da aula.";
    } elseif ($contarCaracteres($titulo) > 150) {
        $erros[] = "O título deve ter no máximo 150 caracteres.";
    }

    // Validação da descrição.
    if ($descricao === "") {
        $erros[] = "Informe uma descrição para a aula.";
    } elseif ($contarCaracteres($descricao) > 1000) {
        $erros[] = "A descrição deve ter no máximo 1000 caracteres.";
    }

    // Validação do conteúdo completo.
    if ($textoCompleto === "") {
        $erros[] = "Escreva o conteúdo completo da aula.";
    }

    // Verifica a categoria.
    if ($categoriaId <= 0) {

        $erros[] = "Selecione uma categoria.";

    } else {

        $stmt = $conn->prepare("
            SELECT id
            FROM categorias
            WHERE id = ?
              AND (ativo = 1 OR id = ?)
            LIMIT 1
        ");

        $categoriaAtualId = (int) $publicacao["categoria_id"];

        $stmt->bind_param(
            "ii",
            $categoriaId,
            $categoriaAtualId
        );

        $stmt->execute();

        $categoriaExiste = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$categoriaExiste) {
            $erros[] = "A categoria selecionada não está disponível.";
        }
    }

    // Verifica o nível.
    if ($nivelId <= 0) {

        $erros[] = "Selecione um nível.";

    } else {

        $stmt = $conn->prepare("
            SELECT id
            FROM niveis
            WHERE id = ?
              AND (ativo = 1 OR id = ?)
            LIMIT 1
        ");

        $nivelAtualId = (int) $publicacao["nivel_id"];

        $stmt->bind_param(
            "ii",
            $nivelId,
            $nivelAtualId
        );

        $stmt->execute();

        $nivelExiste = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$nivelExiste) {
            $erros[] = "O nível selecionado não está disponível.";
        }
    }

    // Validação do tipo de mídia.
    if (!in_array($tipoMidia, ["nenhum", "imagem", "youtube"], true)) {
        $erros[] = "Selecione um tipo de mídia válido.";
        $tipoMidia = "nenhum";
    }

    // Validação do vídeo do YouTube.
    if ($tipoMidia === "youtube") {

        if ($urlYoutube === "") {

            $erros[] = "Informe o endereço do vídeo do YouTube.";

        } elseif (
            $contarCaracteres($urlYoutube) > 500 ||
            !artdrawYoutubeVideoId($urlYoutube)
        ) {

            $erros[] = "Informe um endereço válido de vídeo do YouTube.";
        }
    }

    // Recuperação dos links complementares.
    $titulosRecebidos = is_array($_POST["links_titulo"] ?? null)
        ? $_POST["links_titulo"]
        : [];

    $urlsRecebidas = is_array($_POST["links_url"] ?? null)
        ? $_POST["links_url"]
        : [];

    $linksTituloFormulario = array_map(
        $obterTexto,
        array_slice($titulosRecebidos, 0, 10)
    );

    $linksUrlFormulario = array_map(
        $obterTexto,
        array_slice($urlsRecebidas, 0, 10)
    );

    $totalLinks = max(
        count($titulosRecebidos),
        count($urlsRecebidas)
    );

    $linksValidados = [];

    if ($totalLinks > 10) {
        $erros[] = "Você pode adicionar no máximo 10 links complementares.";
    }

    // Valida cada link.
    for ($i = 0; $i < min($totalLinks, 10); $i++) {

        $tituloLink = $obterTexto($titulosRecebidos[$i] ?? "");
        $urlLink = $obterTexto($urlsRecebidas[$i] ?? "");

        if ($tituloLink === "" && $urlLink === "") {
            continue;
        }

        if ($urlLink === "") {
            $erros[] = "Informe o endereço de cada link complementar preenchido.";
            continue;
        }

        if ($contarCaracteres($tituloLink) > 150) {
            $erros[] = "O título de cada link deve ter no máximo 150 caracteres.";
            continue;
        }

        if ($contarCaracteres($urlLink) > 1000) {
            $erros[] = "Cada endereço deve ter no máximo 1000 caracteres.";
            continue;
        }

        $partesUrl = parse_url($urlLink);
        $protocolo = is_array($partesUrl)
            ? strtolower($partesUrl["scheme"] ?? "")
            : "";

        if (
            filter_var($urlLink, FILTER_VALIDATE_URL) === false ||
            !in_array($protocolo, ["http", "https"], true)
        ) {
            $erros[] = "Um dos links complementares possui um endereço inválido.";
            continue;
        }

        $linksValidados[] = [
            "titulo" => $tituloLink,
            "url" => $urlLink
        ];
    }

    // Verifica se uma nova imagem foi enviada.
    $arquivoImagem = $_FILES["midia_imagem"] ?? null;

    $temNovaImagem = (
        is_array($arquivoImagem) &&
        isset($arquivoImagem["error"]) &&
        $arquivoImagem["error"] !== UPLOAD_ERR_NO_FILE
    );

    $extensaoImagem = null;

    if ($tipoMidia === "imagem") {

        if ($temNovaImagem) {

            if ($arquivoImagem["error"] !== UPLOAD_ERR_OK) {

                $erros[] = "O envio da imagem falhou. Confira o tamanho do arquivo e tente novamente.";

            } elseif ((int) $arquivoImagem["size"] > 5 * 1024 * 1024) {

                $erros[] = "A imagem deve ter no máximo 5 MB.";

            } elseif (
                empty($arquivoImagem["tmp_name"]) ||
                !is_uploaded_file($arquivoImagem["tmp_name"])
            ) {

                $erros[] = "O arquivo de imagem enviado é inválido.";

            } else {

                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                if ($finfo === false) {

                    $erros[] = "Não foi possível validar o formato da imagem.";

                } else {

                    $mime = finfo_file(
                        $finfo,
                        $arquivoImagem["tmp_name"]
                    );

                    finfo_close($finfo);

                    $informacoesImagem = @getimagesize(
                        $arquivoImagem["tmp_name"]
                    );

                    $formatosPermitidos = [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp"
                    ];

                    if (
                        !isset($formatosPermitidos[$mime]) ||
                        $informacoesImagem === false ||
                        ($informacoesImagem["mime"] ?? "") !== $mime
                    ) {

                        $erros[] = "Envie uma imagem válida nos formatos JPG, JFIF, PNG ou WebP.";

                    } else {

                        $extensaoImagem = $formatosPermitidos[$mime];
                    }
                }
            }

        } elseif (
            $publicacao["tipo_midia"] !== "imagem" ||
            empty($publicacao["midia_url"])
        ) {

            $erros[] = "Selecione uma imagem para a aula.";
        }
    }

    // Só atualiza quando não existem erros.
    if (empty($erros)) {

        $arquivoSalvo = null;
        $transacaoIniciada = false;

        try {

            $tipoMidiaFinal = null;
            $midiaUrlFinal = null;

            // Prepara a mídia que será mantida ou substituída.
            if ($tipoMidia === "imagem") {

                if ($temNovaImagem) {

                    $diretorioMidias = __DIR__ . "/../uploads/conteudos";

                    if (
                        !is_dir($diretorioMidias) &&
                        !mkdir($diretorioMidias, 0755, true) &&
                        !is_dir($diretorioMidias)
                    ) {
                        throw new RuntimeException(
                            "Não foi possível preparar a pasta de imagens."
                        );
                    }

                    $nomeArquivo = bin2hex(random_bytes(16))
                        . "."
                        . $extensaoImagem;

                    $caminhoDestino = $diretorioMidias . "/" . $nomeArquivo;

                    if (
                        !move_uploaded_file(
                            $arquivoImagem["tmp_name"],
                            $caminhoDestino
                        )
                    ) {
                        throw new RuntimeException(
                            "Não foi possível salvar a nova imagem."
                        );
                    }

                    $arquivoSalvo = $caminhoDestino;

                    $midiaUrlFinal = "/uploads/conteudos/" . $nomeArquivo;

                } else {

                    // Mantém a imagem que já estava cadastrada.
                    $midiaUrlFinal = $publicacao["midia_url"];
                }

                $tipoMidiaFinal = "imagem";

            } elseif ($tipoMidia === "youtube") {

                $tipoMidiaFinal = "youtube";
                $midiaUrlFinal = $urlYoutube;
            }

            // Inicia a transação.
            $conn->begin_transaction();
            $transacaoIniciada = true;

            // Atualiza a publicação somente se pertencer ao professor.
            $sqlAtualizacao = "
                UPDATE conteudos
                SET
                    titulo = ?,
                    descricao = ?,
                    conteudo = ?,
                    tipo_midia = ?,
                    midia_url = ?,
                    categoria_id = ?,
                    nivel_id = ?
                WHERE id = ?
                  AND autor_id = ?
                  AND tipo_conteudo = 'aula'
            ";

            $stmt = $conn->prepare($sqlAtualizacao);

            $stmt->bind_param(
                "sssssiiii",
                $titulo,
                $descricao,
                $textoCompleto,
                $tipoMidiaFinal,
                $midiaUrlFinal,
                $categoriaId,
                $nivelId,
                $idPublicacao,
                $idProfessor
            );

            $stmt->execute();
            $stmt->close();

            // Reorganiza os links complementares.
            $stmt = $conn->prepare("
                DELETE FROM links_complementares
                WHERE conteudo_id = ?
            ");

            $stmt->bind_param("i", $idPublicacao);
            $stmt->execute();
            $stmt->close();

            // Insere os links atualizados.
            if (!empty($linksValidados)) {

                $stmtLink = $conn->prepare("
                    INSERT INTO links_complementares (
                        conteudo_id,
                        titulo,
                        url,
                        ordem
                    ) VALUES (?, ?, ?, ?)
                ");

                $tituloLinkBanco = null;
                $urlLinkBanco = "";
                $ordemLink = 1;

                $stmtLink->bind_param(
                    "issi",
                    $idPublicacao,
                    $tituloLinkBanco,
                    $urlLinkBanco,
                    $ordemLink
                );

                foreach ($linksValidados as $indice => $link) {

                    $tituloLinkBanco = $link["titulo"] !== ""
                        ? $link["titulo"]
                        : null;

                    $urlLinkBanco = $link["url"];
                    $ordemLink = $indice + 1;

                    $stmtLink->execute();
                }

                $stmtLink->close();
            }

            // Confirma a atualização.
            $conn->commit();
            $transacaoIniciada = false;

            // Remove a imagem antiga somente depois do commit.
            if (
                $publicacao["tipo_midia"] === "imagem" &&
                $publicacao["midia_url"] !== $midiaUrlFinal &&
                is_string($publicacao["midia_url"]) &&
                str_starts_with(
                    $publicacao["midia_url"],
                    "/uploads/conteudos/"
                )
            ) {

                $nomeFotoAntiga = basename($publicacao["midia_url"]);

                $caminhoFotoAntiga =
                    __DIR__ . "/../uploads/conteudos/" . $nomeFotoAntiga;

                if (is_file($caminhoFotoAntiga)) {
                    unlink($caminhoFotoAntiga);
                }
            }

            // Retorna à publicação atualizada.
            header(
                "Location: /conteudo/index.php?id=" . $idPublicacao
            );

            exit;

        } catch (Throwable $erro) {

            // Reverte as alterações em caso de falha.
            if ($transacaoIniciada) {

                try {
                    $conn->rollback();
                } catch (Throwable $erroRollback) {
                    error_log(
                        "Erro ao reverter edição ArtDraw: "
                        . $erroRollback->getMessage()
                    );
                }
            }

            // Remove a nova imagem se a atualização falhar.
            if (
                $arquivoSalvo !== null &&
                is_file($arquivoSalvo)
            ) {
                unlink($arquivoSalvo);
            }

            error_log(
                "Erro ao editar aula ArtDraw: "
                . $erro->getMessage()
            );

            $erros[] = "Não foi possível atualizar a aula. Tente novamente.";
        }
    }
}

// Garante pelo menos uma linha para os links.
$quantidadeLinhasLinks = min(
    10,
    max(
        1,
        count($linksTituloFormulario),
        count($linksUrlFormulario)
    )
);

// Configurações da página.
$tituloPagina = "Editar aula";
$paginaAtual = "professor";

require_once __DIR__ . "/../includes/header.php";

?>

<div class="ad-publication-page">

    <a href="/professor/index.php" class="ad-publication-back">
        ← Voltar ao painel
    </a>

    <header class="ad-publication-heading">

        <span class="ad-publication-eyebrow">
            ÁREA DO PROFESSOR
        </span>

        <h1>Editar aula</h1>

        <p>
            Atualize as informações da sua publicação.
        </p>

    </header>

    <?php if (!empty($erros)): ?>

        <div class="ad-publication-errors">

            <?php foreach ($erros as $erro): ?>

                <p><?= $escapar($erro) ?></p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <form
        action="/professor/editar-publicacao.php?id=<?= (int) $idPublicacao ?>"
        method="POST"
        enctype="multipart/form-data"
        class="ad-publication-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= $escapar($csrfToken) ?>"
        >

        <div class="ad-publication-type">

            <span>Tipo de publicação</span>

            <strong>Aula</strong>

        </div>

        <!-- Título -->

        <div class="ad-publication-field">

            <label for="titulo">Título da aula</label>

            <input
                type="text"
                id="titulo"
                name="titulo"
                maxlength="150"
                value="<?= $escapar($titulo) ?>"
                required
            >

            <small>Máximo de 150 caracteres.</small>

        </div>

        <!-- Descrição -->

        <div class="ad-publication-field">

            <label for="descricao">Descrição</label>

            <textarea
                id="descricao"
                name="descricao"
                rows="3"
                maxlength="1000"
                required
            ><?= $escapar($descricao) ?></textarea>

            <small>Máximo de 1000 caracteres.</small>

        </div>

        <!-- Categoria e nível -->

        <div class="ad-publication-grid">

            <div class="ad-publication-field">

                <label for="categoria_id">Categoria</label>

                <select
                    id="categoria_id"
                    name="categoria_id"
                    required
                >

                    <option value="">Selecione uma categoria</option>

                    <?php foreach ($categorias as $categoria): ?>

                        <option
                            value="<?= (int) $categoria["id"] ?>"
                            <?= $categoriaId === (int) $categoria["id"] ? "selected" : "" ?>
                        >
                            <?= $escapar($categoria["nome"]) ?>
                            <?= !(bool) $categoria["ativo"] ? " (inativa — atual)" : "" ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="ad-publication-field">

                <label for="nivel_id">Nível de aprendizagem</label>

                <select
                    id="nivel_id"
                    name="nivel_id"
                    required
                >

                    <option value="">Selecione um nível</option>

                    <?php foreach ($niveis as $nivel): ?>

                        <option
                            value="<?= (int) $nivel["id"] ?>"
                            <?= $nivelId === (int) $nivel["id"] ? "selected" : "" ?>
                        >
                            <?= $escapar($nivel["nome"]) ?>
                            <?= !(bool) $nivel["ativo"] ? " (inativo — atual)" : "" ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

        <!-- Conteúdo -->

        <div class="ad-publication-field">

            <label for="conteudo">Conteúdo da aula</label>

            <textarea
                id="conteudo"
                name="conteudo"
                rows="10"
                required
            ><?= $escapar($textoCompleto) ?></textarea>

            <small>
                Edite a explicação completa da sua aula.
            </small>

        </div>

        <!-- Mídia principal -->

        <section class="ad-publication-section">

            <div class="ad-publication-section-heading">

                <h2>Mídia principal</h2>

                <p>
                    Você pode manter, substituir ou remover a mídia atual.
                </p>

            </div>

            <div class="ad-publication-field">

                <label for="tipo_midia">Tipo de mídia</label>

                <select
                    id="tipo_midia"
                    name="tipo_midia"
                >

                    <option
                        value="nenhum"
                        <?= $tipoMidia === "nenhum" ? "selected" : "" ?>
                    >
                        Sem mídia
                    </option>

                    <option
                        value="imagem"
                        <?= $tipoMidia === "imagem" ? "selected" : "" ?>
                    >
                        Imagem
                    </option>

                    <option
                        value="youtube"
                        <?= $tipoMidia === "youtube" ? "selected" : "" ?>
                    >
                        Vídeo do YouTube
                    </option>

                </select>

            </div>

            <div
                class="ad-publication-media-field"
                id="campo-imagem"
                <?= $tipoMidia !== "imagem" ? "hidden" : "" ?>
            >

                <label for="midia_imagem">Substituir imagem</label>

                <?php if (
                    $publicacao["tipo_midia"] === "imagem" &&
                    !empty($publicacao["midia_url"])
                ): ?>

                    <div class="ad-publication-current-media">

                        <img
                            src="<?= $escapar($publicacao["midia_url"]) ?>"
                            alt="Imagem atualmente cadastrada"
                        >

                        <small>Imagem atual. Se não enviar outra, ela será mantida.</small>

                    </div>

                <?php endif; ?>

                <input
                    type="file"
                    id="midia_imagem"
                    name="midia_imagem"
                    accept=".jfif,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <small>
                    JPG, JFIF, PNG ou WebP. Tamanho máximo de 5 MB.
                </small>

            </div>

            <div
                class="ad-publication-media-field"
                id="campo-youtube"
                <?= $tipoMidia !== "youtube" ? "hidden" : "" ?>
            >

                <label for="youtube_url">Endereço do vídeo</label>

                <input
                    type="url"
                    id="youtube_url"
                    name="youtube_url"
                    value="<?= $escapar($urlYoutube) ?>"
                    placeholder="https://www.youtube.com/watch?v=..."
                    maxlength="500"
                >

                <small>
                    Informe o novo endereço ou mantenha o atual.
                </small>

            </div>

        </section>

        <!-- Links complementares -->

        <section class="ad-publication-section">

            <div class="ad-publication-section-heading">

                <h2>Links complementares</h2>

                <p>
                    Edite, remova ou acrescente materiais de apoio.
                </p>

            </div>

            <div id="lista-links">

                <?php for (
                    $i = 0;
                    $i < $quantidadeLinhasLinks;
                    $i++
                ): ?>

                    <div class="ad-publication-link-row">

                        <div class="ad-publication-field">

                            <label>Título do link (opcional)</label>

                            <input
                                type="text"
                                name="links_titulo[]"
                                maxlength="150"
                                value="<?= $escapar($linksTituloFormulario[$i] ?? "") ?>"
                                placeholder="Ex.: Material de apoio"
                            >

                        </div>

                        <div class="ad-publication-field">

                            <label>Endereço do link</label>

                            <input
                                type="url"
                                name="links_url[]"
                                maxlength="1000"
                                value="<?= $escapar($linksUrlFormulario[$i] ?? "") ?>"
                                placeholder="https://..."
                            >

                        </div>

                        <button
                            type="button"
                            class="ad-publication-remove-link"
                        >
                            Remover
                        </button>

                    </div>

                <?php endfor; ?>

            </div>

            <button
                type="button"
                id="ad-adicionar-link"
                class="ad-publication-add-link"
            >
                + Adicionar outro link
            </button>

        </section>

        <!-- Ações -->

        <div class="ad-publication-actions">

            <button
                type="submit"
                class="ad-publication-submit"
            >
                Salvar alterações
            </button>

            <a
                href="/professor/index.php"
                class="ad-publication-cancel"
            >
                Cancelar
            </a>

        </div>

    </form>

    <template id="modelo-link">

        <div class="ad-publication-link-row">

            <div class="ad-publication-field">

                <label>Título do link (opcional)</label>

                <input
                    type="text"
                    name="links_titulo[]"
                    maxlength="150"
                    placeholder="Ex.: Material de apoio"
                >

            </div>

            <div class="ad-publication-field">

                <label>Endereço do link</label>

                <input
                    type="url"
                    name="links_url[]"
                    maxlength="1000"
                    placeholder="https://..."
                >

            </div>

            <button
                type="button"
                class="ad-publication-remove-link"
            >
                Remover
            </button>

        </div>

    </template>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // Alterna os campos conforme o tipo de mídia.
    const seletorMidia = document.getElementById("tipo_midia");
    const campoImagem = document.getElementById("campo-imagem");
    const campoYoutube = document.getElementById("campo-youtube");

    if (seletorMidia && campoImagem && campoYoutube) {

        const inputImagem = document.getElementById("midia_imagem");
        const inputYoutube = document.getElementById("youtube_url");

        function atualizarCamposMidia() {

            const tipo = seletorMidia.value;

            campoImagem.hidden = tipo !== "imagem";
            campoYoutube.hidden = tipo !== "youtube";

            inputImagem.disabled = tipo !== "imagem";
            inputYoutube.disabled = tipo !== "youtube";
        }

        seletorMidia.addEventListener("change", atualizarCamposMidia);

        atualizarCamposMidia();
    }

    // Adiciona e remove links complementares.
    const listaLinks = document.getElementById("lista-links");
    const botaoAdicionar = document.getElementById("ad-adicionar-link");
    const modeloLink = document.getElementById("modelo-link");

    if (listaLinks && botaoAdicionar && modeloLink) {

        botaoAdicionar.addEventListener("click", function () {

            const total = listaLinks.querySelectorAll(
                ".ad-publication-link-row"
            ).length;

            if (total >= 10) {
                alert("Você pode adicionar no máximo 10 links.");
                return;
            }

            listaLinks.appendChild(
                modeloLink.content.cloneNode(true)
            );
        });

        listaLinks.addEventListener("click", function (evento) {

            const botaoRemover = evento.target.closest(
                ".ad-publication-remove-link"
            );

            if (!botaoRemover) {
                return;
            }

            const linha = botaoRemover.closest(
                ".ad-publication-link-row"
            );

            const linhas = listaLinks.querySelectorAll(
                ".ad-publication-link-row"
            );

            if (linhas.length > 1) {

                linha.remove();

            } else {

                linha.querySelectorAll("input").forEach(function (input) {
                    input.value = "";
                });
            }
        });
    }

});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

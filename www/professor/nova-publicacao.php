<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/youtube.php";

// Somente professores autenticados podem publicar.
$usuario = exigirLogin();

if (($usuario["tipo_usuario"] ?? "") !== "professor") {
    http_response_code(403);
    exit("Acesso permitido somente para professores.");
}

$idProfessor = (int) $usuario["id"];

// Tipos de publicação permitidos no ArtDraw.
$tiposConteudo = [
    "aula" => "Aula",
    "dica" => "Dica",
    "trabalho" => "Trabalho"
];

// O formulário começa com o tipo recebido pela URL.
// Quando não houver tipo informado, utiliza Aula.
$tipoInicial = $_GET["tipo"] ?? "aula";

$tipoConteudo = (
    is_string($tipoInicial) &&
    isset($tiposConteudo[$tipoInicial])
)
    ? $tipoInicial
    : "aula";

// Token de proteção CSRF.
if (empty($_SESSION["csrf_publicacao"])) {
    $_SESSION["csrf_publicacao"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_publicacao"];

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

// Busca categorias ativas.
$sqlCategorias = "
    SELECT id, nome
    FROM categorias
    WHERE ativo = 1
    ORDER BY nome ASC
";

$resultadoCategorias = $conn->query($sqlCategorias);
$categorias = $resultadoCategorias->fetch_all(MYSQLI_ASSOC);

// Busca níveis ativos.
$sqlNiveis = "
    SELECT id, nome
    FROM niveis
    WHERE ativo = 1
    ORDER BY ordem ASC
";

$resultadoNiveis = $conn->query($sqlNiveis);
$niveis = $resultadoNiveis->fetch_all(MYSQLI_ASSOC);

// Valores iniciais do formulário.
$titulo = "";
$descricao = "";
$textoCompleto = "";
$categoriaId = 0;
$nivelId = 0;
$tipoMidia = "nenhum";
$urlYoutube = "";

$linksTituloFormulario = [""];
$linksUrlFormulario = [""];

$erros = [];

// Processamento do formulário.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Valida o tipo de publicação recebido.
    $tipoRecebido = $_POST["tipo_conteudo"] ?? "";

    if (
       !is_string($tipoRecebido) ||
       !array_key_exists($tipoRecebido, $tiposConteudo)
    ) {

       $erros[] = "Selecione um tipo de publicação válido.";

    } else {

       $tipoConteudo = $tipoRecebido;

    }

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

    // Verifica se a categoria existe e está ativa.
    if ($categoriaId <= 0) {

        $erros[] = "Selecione uma categoria.";

    } else {

        $stmt = $conn->prepare("
            SELECT id
            FROM categorias
            WHERE id = ?
              AND ativo = 1
            LIMIT 1
        ");

        $stmt->bind_param("i", $categoriaId);
        $stmt->execute();

        $categoriaExiste = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$categoriaExiste) {
            $erros[] = "A categoria selecionada não está disponível.";
        }
    }

    // Verifica se o nível existe e está ativo.
    if ($nivelId <= 0) {

        $erros[] = "Selecione um nível.";

    } else {

        $stmt = $conn->prepare("
            SELECT id
            FROM niveis
            WHERE id = ?
              AND ativo = 1
            LIMIT 1
        ");

        $stmt->bind_param("i", $nivelId);
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

    // Validação da URL do YouTube.
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

    // Preparação dos links complementares.
    $titulosRecebidos = is_array($_POST["links_titulo"] ?? null)
        ? $_POST["links_titulo"]
        : [];

    $urlsRecebidas = is_array($_POST["links_url"] ?? null)
        ? $_POST["links_url"]
        : [];

    $linksTituloFormulario = $titulosRecebidos;
    $linksUrlFormulario = $urlsRecebidas;

    $totalLinks = max(
        count($titulosRecebidos),
        count($urlsRecebidas)
    );

    $linksValidados = [];

    if ($totalLinks > 10) {
        $erros[] = "Você pode adicionar no máximo 10 links complementares.";
    }

    // Valida individualmente cada link.
    for ($i = 0; $i < min($totalLinks, 10); $i++) {

        $tituloLink = $obterTexto($titulosRecebidos[$i] ?? "");
        $urlLink = $obterTexto($urlsRecebidas[$i] ?? "");

        // Ignora linhas completamente vazias.
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
            $erros[] = "Cada endereço de link deve ter no máximo 1000 caracteres.";
            continue;
        }

        $partesUrl = parse_url($urlLink);
        $protocolo = strtolower($partesUrl["scheme"] ?? "");

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

    // Validação da imagem, quando selecionada.
    $extensaoImagem = null;
    $arquivoImagem = $_FILES["midia_imagem"] ?? null;

    if ($tipoMidia === "imagem") {

        if (!is_array($arquivoImagem)) {

            $erros[] = "Selecione uma imagem para a aula.";

        } else {

            $erroUpload = $arquivoImagem["error"] ?? UPLOAD_ERR_NO_FILE;

            if ($erroUpload === UPLOAD_ERR_NO_FILE) {

                $erros[] = "Selecione uma imagem para a aula.";

            } elseif ($erroUpload !== UPLOAD_ERR_OK) {

                $erros[] = "O envio da imagem falhou. Confira o tamanho do arquivo e tente novamente.";

            } elseif ((int) ($arquivoImagem["size"] ?? 0) > 5 * 1024 * 1024) {

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
        }
    }

    // Só tenta salvar quando não existem erros de validação.
    if (empty($erros)) {

        $arquivoSalvo = null;

        try {

            $midiaUrlFinal = null;
            $tipoMidiaFinal = null;

            // Salva a imagem no servidor.
            if ($tipoMidia === "imagem") {

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

                // Nome aleatório: não utiliza o nome original do arquivo.
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
                        "Não foi possível salvar a imagem."
                    );
                }

                $arquivoSalvo = $caminhoDestino;

                $tipoMidiaFinal = "imagem";
                $midiaUrlFinal = "/uploads/conteudos/" . $nomeArquivo;

            } elseif ($tipoMidia === "youtube") {

                $tipoMidiaFinal = "youtube";
                $midiaUrlFinal = $urlYoutube;
            }

            // Inicia a transação do banco.
            $conn->begin_transaction();

            // Insere a publicação.
            $sql = "
                INSERT INTO conteudos (
                    titulo,
                    descricao,
                    conteudo,
                    tipo_conteudo,
                    tipo_midia,
                    midia_url,
                    autor_id,
                    categoria_id,
                    nivel_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssssssiii",
                $titulo,
                $descricao,
                $textoCompleto,
                $tipoConteudo,
                $tipoMidiaFinal,
                $midiaUrlFinal,
                $idProfessor,
                $categoriaId,
                $nivelId
            );

            $stmt->execute();

            $idNovaPublicacao = (int) $conn->insert_id;

            $stmt->close();

            // Insere os links complementares.
            if (!empty($linksValidados)) {

                $sqlLinks = "
                    INSERT INTO links_complementares (
                        conteudo_id,
                        titulo,
                        url,
                        ordem
                    ) VALUES (?, ?, ?, ?)
                ";

                $stmtLink = $conn->prepare($sqlLinks);

                $tituloLinkBanco = null;
                $urlLinkBanco = "";
                $ordemLink = 1;

                $stmtLink->bind_param(
                    "issi",
                    $idNovaPublicacao,
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

            // Confirma a gravação da publicação e dos links.
            $conn->commit();

            // Abre a página da nova publicação.
            header(
                "Location: /conteudo/index.php?id=" . $idNovaPublicacao
            );

            exit;

        } catch (Throwable $erro) {

            // Desfaz qualquer operação do banco que ainda não foi confirmada.
            try {
                $conn->rollback();
            } catch (Throwable $erroRollback) {
                error_log(
                    "Erro ao reverter publicação ArtDraw: "
                    . $erroRollback->getMessage()
                );
            }

            // Remove o arquivo caso o banco não tenha concluído a publicação.
            if (
                $arquivoSalvo !== null &&
                is_file($arquivoSalvo)
            ) {
                unlink($arquivoSalvo);
            }

            error_log(
                "Erro ao cadastrar aula ArtDraw: "
                . $erro->getMessage()
            );

            $erros[] = "Não foi possível publicar a aula. Verifique os dados e tente novamente.";
        }
    }
}

// Limita as linhas exibidas depois de uma tentativa inválida.
$quantidadeLinhasLinks = min(
    10,
    max(
        1,
        count($linksTituloFormulario),
        count($linksUrlFormulario)
    )
);

// Configurações da página.
$tituloPagina = "Nova publicação";
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

        <h1>Criar publicação</h1>

        <p>
            Compartilhe seus conhecimentos e trabalhos com a comunidade ArtDraw.
        </p>

    </header>

    <?php if (!empty($erros)): ?>

        <div class="ad-publication-errors">

            <?php foreach ($erros as $erro): ?>

                <p><?= $escapar($erro) ?></p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <?php if (empty($categorias) || empty($niveis)): ?>

        <div class="ad-publication-errors">
            Não existem categorias ou níveis ativos disponíveis.
            Entre em contato com o administrador.
        </div>

    <?php else: ?>

        <form
            action="/professor/nova-publicacao.php"
            method="POST"
            enctype="multipart/form-data"
            class="ad-publication-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= $escapar($csrfToken) ?>"
            >

            <div class="ad-publication-field">

    <label for="tipo_conteudo">
        Tipo de publicação
    </label>

    <select
        id="tipo_conteudo"
        name="tipo_conteudo"
        required
    >

        <?php foreach ($tiposConteudo as $valor => $nome): ?>

            <option
                value="<?= $escapar($valor) ?>"
                <?= $tipoConteudo === $valor ? "selected" : "" ?>
            >
                <?= $escapar($nome) ?>
            </option>

        <?php endforeach; ?>

    </select>

    <small>
        Escolha o formato de conteúdo que deseja compartilhar.
    </small>

</div>

            <!-- Título -->

            <div class="ad-publication-field">

                <label for="titulo">Título da Publicação</label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    maxlength="150"
                    value="<?= $escapar($titulo) ?>"
                    placeholder="Ex.: Introdução ao desenho em perspectiva"
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
                    placeholder="Apresente brevemente o assunto abordado na aula..."
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
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

            <!-- Conteúdo completo -->

            <div class="ad-publication-field">

                <label for="conteudo">Conteúdo completo</label>

                <textarea
                    id="conteudo"
                    name="conteudo"
                    rows="10"
                    placeholder="Escreva aqui o desenvolvimento completo da aula..."
                    required
                ><?= $escapar($textoCompleto) ?></textarea>

                <small>
                    Utilize este espaço para explicar o assunto,
                    apresentar exemplos e orientar o aluno.
                </small>

            </div>

            <!-- Mídia principal -->

            <section class="ad-publication-section">

                <div class="ad-publication-section-heading">

                    <h2>Mídia principal</h2>

                    <p>
                        Adicione uma imagem ou um vídeo para complementar a aula.
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

                    <label for="midia_imagem">Escolha a imagem</label>

                    <input
                        type="file"
                        id="midia_imagem"
                        name="midia_imagem"
                        accept=".jfif,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small>
                        Formatos JPG, JFIF, PNG e WebP. Tamanho máximo: 5 MB.
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
                        Cole o endereço de um vídeo válido do YouTube.
                    </small>

                </div>

            </section>

            <!-- Links complementares -->

            <section class="ad-publication-section">

                <div class="ad-publication-section-heading">

                    <h2>Links complementares</h2>

                    <p>
                        Adicione materiais externos que possam ajudar no aprendizado.
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
                    Publicar conteúdo
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

    <?php endif; ?>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // Alterna os campos conforme o tipo de mídia selecionado.
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

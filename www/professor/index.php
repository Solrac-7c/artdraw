<?php

require_once __DIR__ . "/../includes/auth.php";

// Exige que o usuário esteja autenticado.
$usuario = exigirLogin();

// Somente professores podem acessar esta área.
if (($usuario["tipo_usuario"] ?? "") !== "professor") {
    http_response_code(403);
    exit("Acesso permitido somente para professores.");
}

$idProfessor = (int) $usuario["id"];

// Token de segurança das exclusões.
if (empty($_SESSION["csrf_excluir_publicacao"])) {
    $_SESSION["csrf_excluir_publicacao"] = bin2hex(random_bytes(32));
}

$csrfExcluirToken = $_SESSION["csrf_excluir_publicacao"];

// Recupera mensagens após uma operação no painel.
$mensagemProfessor = $_SESSION["mensagem_professor"] ?? "";
$tipoMensagemProfessor = $_SESSION["tipo_mensagem_professor"] ?? "";

unset(
    $_SESSION["mensagem_professor"],
    $_SESSION["tipo_mensagem_professor"]
);

// Escapa valores antes de exibi-los no HTML.
$escapar = static function ($valor) {
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
};

// Busca o resumo das publicações do professor.
$sqlResumo = "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(tipo_conteudo = 'aula'), 0) AS aulas,
        COALESCE(SUM(tipo_conteudo = 'dica'), 0) AS dicas,
        COALESCE(SUM(tipo_conteudo = 'trabalho'), 0) AS trabalhos
    FROM conteudos
    WHERE autor_id = ?
";

$stmt = $conn->prepare($sqlResumo);
$stmt->bind_param("i", $idProfessor);
$stmt->execute();

$resumo = $stmt->get_result()->fetch_assoc();

$stmt->close();

// Busca as publicações feitas por este professor.
$sqlPublicacoes = "
    SELECT
        c.id,
        c.titulo,
        c.descricao,
        c.tipo_conteudo,
        c.tipo_midia,
        c.data_publicacao,
        cat.nome AS categoria,
        n.nome AS nivel
    FROM conteudos c
    LEFT JOIN categorias cat
        ON cat.id = c.categoria_id
    LEFT JOIN niveis n
        ON n.id = c.nivel_id
    WHERE c.autor_id = ?
    ORDER BY c.data_publicacao DESC, c.id DESC
";

$stmt = $conn->prepare($sqlPublicacoes);
$stmt->bind_param("i", $idProfessor);
$stmt->execute();

$publicacoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Configurações da página.
$tituloPagina = "Área do professor";
$paginaAtual = "professor";

require_once __DIR__ . "/../includes/header.php";

?>

<div class="ad-teacher-panel">
    <?php if ($mensagemProfessor !== ""): ?>

        <div class="ad-teacher-feedback <?= $tipoMensagemProfessor === "sucesso" ? "is-success" : "is-error" ?>">
        <?= $escapar($mensagemProfessor) ?>
        </div>

    <?php endif; ?>

    <section class="ad-teacher-welcome">

        <div>
            <span class="ad-teacher-eyebrow">
                ÁREA DO PROFESSOR
            </span>

            <h1>
                Olá, <?= $escapar($usuario["apelido"]) ?>!
            </h1>

            <p>
                Gerencie suas publicações e compartilhe conhecimentos
                com a comunidade ArtDraw.
            </p>
        </div>

        <a
            href="/perfil/index.php"
            class="ad-teacher-profile-link"
        >
            Ver meu perfil
        </a>

    </section>

    <section class="ad-teacher-statistics">

        <article class="ad-teacher-stat-card">

            <span>Total de publicações</span>

            <strong>
                <?= (int) ($resumo["total"] ?? 0) ?>
            </strong>

        </article>

        <article class="ad-teacher-stat-card">

            <span>Aulas</span>

            <strong>
                <?= (int) ($resumo["aulas"] ?? 0) ?>
            </strong>

        </article>

        <article class="ad-teacher-stat-card">

            <span>Dicas</span>

            <strong>
                <?= (int) ($resumo["dicas"] ?? 0) ?>
            </strong>

        </article>

        <article class="ad-teacher-stat-card">

            <span>Trabalhos</span>

            <strong>
                <?= (int) ($resumo["trabalhos"] ?? 0) ?>
            </strong>

        </article>

    </section>

    <section class="ad-teacher-publications">

        <div class="ad-teacher-section-heading">

            <div>
                <h2>Minhas publicações</h2>

                <p>
                    Consulte os conteúdos publicados pela sua conta.
                </p>
            </div>

        </div>

        <?php if (count($publicacoes) > 0): ?>

            <div class="ad-teacher-publication-list">

                <?php foreach ($publicacoes as $publicacao): ?>

                    <article class="ad-teacher-publication">

                        <div class="ad-teacher-publication-information">

                            <span class="ad-teacher-content-type">
                                <?= $escapar(ucfirst($publicacao["tipo_conteudo"])) ?>
                            </span>

                            <h3>
                                <?= $escapar($publicacao["titulo"]) ?>
                            </h3>

                            <p>
                                <?= $escapar($publicacao["descricao"]) ?>
                            </p>

                            <div class="ad-teacher-publication-details">

                                <span>
                                    <?= $escapar($publicacao["categoria"] ?? "Sem categoria") ?>
                                </span>

                                <span>
                                    <?= $escapar($publicacao["nivel"] ?? "Sem nível") ?>
                                </span>

                                <span>
                                    <?php
                                    if ($publicacao["tipo_midia"] === "imagem") {
                                        echo "Imagem";
                                    } elseif ($publicacao["tipo_midia"] === "youtube") {
                                        echo "YouTube";
                                    } else {
                                        echo "Sem mídia";
                                    }
                                    ?>
                                </span>

                                <span>
                                    <?php
                                    echo !empty($publicacao["data_publicacao"])
                                        ? date(
                                            "d/m/Y",
                                            strtotime($publicacao["data_publicacao"])
                                        )
                                        : "Data não disponível";
                                    ?>
                                </span>

                            </div>

                        </div>

                        <div class="ad-teacher-publication-actions">

                            <a
                                href="/conteudo/index.php?id=<?= (int) $publicacao["id"] ?>"
                                class="ad-teacher-view-link"
                            >
                                Visualizar
                            </a>

                            <?php if ($publicacao["tipo_conteudo"] === "aula"): ?>

                                <a
                                    href="/professor/editar-publicacao.php?id=<?= (int) $publicacao["id"] ?>"
                                    class="ad-teacher-view-link"
                                >
                                    Editar
                                </a>

                            <form
                                action="/professor/excluir-publicacao.php"
                                method="POST"
                                class="ad-teacher-delete-form"
                                onsubmit="return confirm('Tem certeza de que deseja excluir esta aula? Essa ação não poderá ser desfeita.');"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= $escapar($csrfExcluirToken) ?>"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $publicacao["id"] ?>"
                            >

                            <button
                                type="submit"
                                class="ad-teacher-delete-button"
                            >
                                Excluir
                            </button>

                        </form>

    <?php endif; ?>

</div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="ad-teacher-empty">

                <h3>Você ainda não possui publicações</h3>

                <p>
                    Quando você publicar uma aula, dica ou trabalho,
                    seus conteúdos aparecerão nesta área.
                </p>

            </div>

        <?php endif; ?>

    </section>

</div>

<!-- Botão flutuante para cadastrar nova aula -->
<a
    href="/professor/nova-publicacao.php?tipo=aula"
    class="ad-teacher-fab"
    aria-label="Cadastrar nova aula"
    title="Cadastrar nova aula"
>
    <span aria-hidden="true">+</span>
</a>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

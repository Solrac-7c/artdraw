<?php

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/youtube.php";

// Recupera o usuário atualmente autenticado.
$usuario = usuarioAtual();

// Quando não existe ID na URL, abre o perfil do próprio usuário.
if (!isset($_GET["id"])) {

    $usuario = exigirLogin();
    $idPerfil = (int) $usuario["id"];

} else {

    // Quando existe ID, consulta o perfil público solicitado.
    $idPerfil = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

    if (!$idPerfil || $idPerfil < 1) {
        $idPerfil = 0;
    }
}

// Inicializa as variáveis da página.
$perfil = null;
$publicacoes = [];

// Busca apenas os dados que podem aparecer publicamente.
if ($idPerfil > 0) {

    $sql = "
        SELECT
            id,
            nome,
            apelido,
            tipo_usuario,
            foto_perfil,
            biografia
        FROM usuarios
        WHERE id = ?
          AND status = 'ativo'
          AND tipo_usuario IN ('aluno', 'professor')
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idPerfil);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $perfil = $resultado->fetch_assoc();

    $stmt->close();
}

// Se o perfil for de um professor, busca suas publicações.
if ($perfil && $perfil["tipo_usuario"] === "professor") {

    $sql = "
        SELECT
            c.id,
            c.titulo,
            c.descricao,
            c.tipo_conteudo,
            c.tipo_midia,
            c.midia_url,
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
        LIMIT 24
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idPerfil);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $publicacoes = $resultado->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
}

if (!$perfil) {
    http_response_code(404);
}

// Verifica se o perfil acessado pertence ao usuário autenticado.
$perfilEhMeu = (
    $usuario &&
    isset($usuario["id"]) &&
    (int) $usuario["id"] === (int) $idPerfil
);

// Define o título da página.
$tituloPagina = $perfil
    ? "Perfil de " . $perfil["apelido"]
    : "Perfil não encontrado";

$paginaAtual = "perfil";

// Função para proteger os valores exibidos no HTML.
$escapar = static function ($valor) {
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
};

require_once __DIR__ . "/../includes/header.php";

?>

<div class="ad-profile-page">

    <?php if (!$perfil): ?>

        <section class="ad-profile-not-found">
            <h1>Perfil não encontrado</h1>
            <p>
                Este perfil não está disponível ou não existe.
            </p>

            <a href="/index.php" class="ad-profile-back">
                Voltar ao início
            </a>
        </section>

    <?php else: ?>

        <section class="ad-profile-header">

            <div class="ad-profile-avatar">

                <?php if (!empty($perfil["foto_perfil"])): ?>

                    <img
                        src="<?= $escapar($perfil["foto_perfil"]) ?>"
                        alt="Foto de perfil de <?= $escapar($perfil["apelido"]) ?>"
                    >

                <?php else: ?>

                    <span>AD</span>

                <?php endif; ?>

            </div>

            <div class="ad-profile-information">

                <span class="ad-profile-role">
                    <?= $perfil["tipo_usuario"] === "professor"
                        ? "Professor"
                        : "Aluno" ?>
                </span>

                <h1>
                    <?= $escapar($perfil["apelido"]) ?>
                </h1>

                <p class="ad-profile-name">
                    <?= $escapar($perfil["nome"]) ?>
                </p>

                <p class="ad-profile-biography">
                    <?= !empty($perfil["biografia"])
                        ? nl2br($escapar($perfil["biografia"]))
                        : "Este usuário ainda não adicionou uma biografia." ?>
                </p>

                <?php if ($perfil["tipo_usuario"] === "professor"): ?>

                    <a href="/professor/index.php" class="ad-profile-teacher-area">
                        Área do professor
                    </a>

                <?php endif; ?>

                <?php if ($perfilEhMeu): ?>

                    <a
                        href="/perfil/editar.php"
                        class="ad-profile-edit"
                    >
                        Editar perfil
                    </a>
            </div>
    <?php endif; ?>
        </section>

        <?php if ($perfil["tipo_usuario"] === "professor"): ?>

            <section class="ad-profile-publications">

                <div class="ad-profile-section-title">
                    <h2>Publicações</h2>

                    <p>
                        Conteúdos compartilhados por este professor.
                    </p>
                </div>

                <?php if (count($publicacoes) > 0): ?>

                    <div class="ad-profile-publication-grid">

                        <?php foreach ($publicacoes as $publicacao): ?>

                            <article class="ad-profile-publication">

                                <a
                                    href="/conteudo/index.php?id=<?= (int) $publicacao["id"] ?>"
                                    class="ad-profile-publication-link"
                                >

                                    <div class="ad-profile-publication-media">

                                        <?php if (
                                            $publicacao["tipo_midia"] === "imagem" &&
                                            !empty($publicacao["midia_url"])
                                        ): ?>

                                            <img
                                                src="<?= $escapar($publicacao["midia_url"]) ?>"
                                                alt="Imagem da publicação"
                                            >

                                        <?php elseif (
                                            $publicacao["tipo_midia"] === "youtube" &&
                                            !empty($publicacao["midia_url"])
                                        ): ?>

                                            <?php
                                            $videoId = artdrawYoutubeVideoId(
                                                $publicacao["midia_url"]
                                            );
                                            ?>

                                            <?php if ($videoId): ?>

                                                <img
                                                    src="https://img.youtube.com/vi/<?= $escapar($videoId) ?>/mqdefault.jpg"
                                                    alt="Miniatura do vídeo"
                                                >

                                            <?php else: ?>

                                                <span>Vídeo do YouTube</span>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span>ArtDraw</span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="ad-profile-publication-content">

                                        <span class="ad-profile-publication-type">
                                            <?= $escapar(ucfirst($publicacao["tipo_conteudo"])) ?>
                                        </span>

                                        <h3>
                                            <?= $escapar($publicacao["titulo"]) ?>
                                        </h3>

                                        <p>
                                            <?= $escapar($publicacao["descricao"]) ?>
                                        </p>

                                        <div class="ad-profile-publication-details">

                                            <span>
                                                <?= $escapar($publicacao["categoria"] ?? "Sem categoria") ?>
                                            </span>

                                            <span>
                                                <?= $escapar($publicacao["nivel"] ?? "Sem nível") ?>
                                            </span>

                                        </div>

                                    </div>

                                </a>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p class="ad-profile-empty">
                        Este professor ainda não possui publicações.
                    </p>

                <?php endif; ?>

            </section>

        <?php else: ?>

            <section class="ad-profile-student">

                <h2>Perfil de aluno</h2>

                <p>
                    Este é o espaço público de identificação deste estudante
                    na comunidade ArtDraw.
                </p>

            </section>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>

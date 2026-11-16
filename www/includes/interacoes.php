<?php

/*
|--------------------------------------------------------------------------
| 1. AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/auth.php";

// Exige que exista um usuário autenticado.
$usuario = exigirLogin();


/*
|--------------------------------------------------------------------------
| 2. VERIFICAÇÃO DO MÉTODO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);
    exit("Método não permitido.");

}


/*
|--------------------------------------------------------------------------
| 3. VERIFICAÇÃO DO PERFIL
|--------------------------------------------------------------------------
*/

// Somente alunos e professores podem interagir.

if (!in_array(
    $usuario["tipo_usuario"], ["aluno", "professor"], true
)) {

    http_response_code(403);
    exit("Você não possui permissão para realizar esta ação.");

}


/*
|--------------------------------------------------------------------------
| 4. PROTEÇÃO CSRF
|--------------------------------------------------------------------------
*/

$tokenSessao = $_SESSION["csrf_interacao"] ?? "";

$tokenRecebido = $_POST["csrf_token"] ?? "";

if (
    !is_string($tokenSessao) || !is_string($tokenRecebido) || $tokenSessao === "" || !hash_equals($tokenSessao, $tokenRecebido)
) {

    http_response_code(403);
    exit("Solicitação inválida. Atualize a página e tente novamente.");

}


/*
|--------------------------------------------------------------------------
| 5. RECEBIMENTO DOS DADOS
|--------------------------------------------------------------------------
*/

$conteudoId = filter_var(
    $_POST["conteudo_id"] ?? null, 
    FILTER_VALIDATE_INT
);

$acao = $_POST["acao"] ?? "";

$retorno = $_POST["retorno"] ?? "/index.php";


// Valida o identificador.

if (!$conteudoId || $conteudoId <= 0) {

    http_response_code(400);
    exit("Conteúdo inválido.");

}


// Aceita somente as duas ações previstas.

$acoesPermitidas = [
    "curtir" => "curtidas",
    "salvar" => "salvamentos"
];

if (!isset($acoesPermitidas[$acao])) {

    http_response_code(400);
    exit("Ação inválida.");

}


// Impede redirecionamentos externos.

if (
    !is_string($retorno) ||
    !preg_match(
        '#\A/(?:index\.php|conteudo/index\.php\?id=[1-9][0-9]*)\z#',
        $retorno
    )
) {

    $retorno = "/index.php";

}


/*
|--------------------------------------------------------------------------
| 6. VERIFICAR A EXISTÊNCIA DA PUBLICAÇÃO
|--------------------------------------------------------------------------
*/

$sqlConteudo = "
    SELECT c.id

    FROM conteudos c

    INNER JOIN usuarios u
        ON c.autor_id = u.id

    WHERE c.id = ?

    AND u.status <> 'bloqueado'
";

$stmt = $conn->prepare($sqlConteudo);

$stmt->bind_param("i", $conteudoId);

$stmt->execute();

$resultado = $stmt->get_result();

$conteudoExiste = $resultado->num_rows > 0;

$stmt->close();


if (!$conteudoExiste) {

    http_response_code(404);
    exit("Publicação indisponível.");

}


/*
|--------------------------------------------------------------------------
| 7. PROCESSAR CURTIDA OU SALVAMENTO
|--------------------------------------------------------------------------
*/

// O nome da tabela vem exclusivamente da lista permitida.
$tabela = $acoesPermitidas[$acao];

$usuarioId = (int) $usuario["id"];


// Verifica se a interação já existe.

$sqlVerificar = "
    SELECT id

    FROM $tabela

    WHERE usuario_id = ?
    AND conteudo_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sqlVerificar);

$stmt->bind_param("ii", $usuarioId, $conteudoId);

$stmt->execute();

$resultado = $stmt->get_result();

$interacaoExiste = $resultado->num_rows > 0;

$stmt->close();


// Se já existe, remove.
// Se não existe, registra.

if ($interacaoExiste) {

    $sqlRemover = "
        DELETE FROM $tabela

        WHERE usuario_id = ?
        AND conteudo_id = ?
    ";

    $stmt = $conn->prepare($sqlRemover);

    $stmt->bind_param("ii", $usuarioId, $conteudoId);

    $stmt->execute();

    $stmt->close();

} else {

    $sqlInserir = "
        INSERT IGNORE INTO $tabela (
            usuario_id,
            conteudo_id
        )

        VALUES (?, ?)
    ";

    $stmt = $conn->prepare($sqlInserir);

    $stmt->bind_param("ii", $usuarioId, $conteudoId);

    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| 8. VOLTAR PARA A PÁGINA ANTERIOR
|--------------------------------------------------------------------------
*/

header("Location: " . $retorno, true, 303);

exit;

?>

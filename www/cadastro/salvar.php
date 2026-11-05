<?php

require_once("../includes/sessao.php");
require_once("../includes/conexao.php");


// Verifica o método de envio
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;

}


// Recebe os dados
$nome = trim($_POST["nome"] ?? "");
$apelido = trim($_POST["apelido"] ?? "");
$email = strtolower(trim($_POST["email"] ?? ""));
$tipoUsuario = $_POST["tipo_usuario"] ?? "";
$senha = $_POST["senha"] ?? "";
$confirmarSenha = $_POST["confirmar_senha"] ?? "";


// Validação básica
if (
    $nome === "" ||
    $apelido === "" ||
    $email === "" ||
    $senha === "" ||
    $confirmarSenha === ""
) {

    header("Location: index.php?erro=dados_invalidos");
    exit;

}


// Valida e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    header("Location: index.php?erro=email_invalido");
    exit;

}


// Valida tipo de usuário
if (
    $tipoUsuario !== "aluno" &&
    $tipoUsuario !== "professor"
) {

    header("Location: index.php?erro=dados_invalidos");
    exit;

}


// Valida tamanho dos campos
if (
    strlen($nome) > 100 ||
    strlen($apelido) > 50 ||
    strlen($email) > 150
) {

    header("Location: index.php?erro=dados_invalidos");
    exit;

}


// Valida senha
if (
    strlen($senha) < 8 ||
    strlen($senha) > 72
) {

    header("Location: index.php?erro=dados_invalidos");
    exit;

}


// Confirmação de senha
if ($senha !== $confirmarSenha) {

    header("Location: index.php?erro=senha_diferente");
    exit;

}


// Verifica se o e-mail já existe
$sql = "SELECT id
        FROM usuarios
        WHERE email = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "s", $email);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) > 0) {

    mysqli_stmt_close($stmt);

    header("Location: index.php?erro=email_existente");
    exit;

}

mysqli_stmt_close($stmt);


// Gera o hash da senha
$hash = password_hash($senha, PASSWORD_DEFAULT);


// Insere o usuário
$sql = "INSERT INTO usuarios (
            nome,
            apelido,
            email,
            senha,
            tipo_usuario,
            status
        )
        VALUES (?, ?, ?, ?, ?, 'ativo')";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $nome,
    $apelido,
    $email,
    $hash,
    $tipoUsuario
);


// Executa o cadastro
try {

    mysqli_stmt_execute($stmt);

    $idUsuario = mysqli_insert_id($conn);

} catch (mysqli_sql_exception $e) {

    // Código 1062: duplicidade de registro
    if ($e->getCode() === 1062) {

        header("Location: index.php?erro=email_existente");
        exit;

    }

    error_log("Erro ao cadastrar usuário: " . $e->getMessage());

    header("Location: index.php?erro=dados_invalidos");
    exit;

}

mysqli_stmt_close($stmt);


// Cria a sessão automaticamente
session_regenerate_id(true);

$_SESSION["usuario_id"] = $idUsuario;


// Redireciona para a Home
header("Location: /index.php");
exit;

?>
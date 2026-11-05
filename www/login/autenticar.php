<?php

require_once("../includes/sessao.php");
require_once("../includes/conexao.php");


// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;

}


// Recebe os dados
$email = strtolower(trim($_POST["email"] ?? ""));
$senha = $_POST["senha"] ?? "";


// Valida os campos
if ($email === "" || $senha === "") {

    header("Location: index.php?erro=campos");
    exit;

}


// Consulta o usuário
$sql = "SELECT id, nome, apelido, email, senha,
               tipo_usuario, status
        FROM usuarios
        WHERE email = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "s", $email);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

$usuarioBanco = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);


// Verifica se o usuário existe
if (!$usuarioBanco) {

    header("Location: index.php?erro=credenciais");
    exit;

}


// Verifica se a conta está ativa
if ($usuarioBanco["status"] !== "ativo") {

    header("Location: index.php?erro=bloqueada");
    exit;

}


// Verifica a senha
if (!password_verify($senha, $usuarioBanco["senha"])) {

    header("Location: index.php?erro=credenciais");
    exit;

}


// Regenera o identificador da sessão
session_regenerate_id(true);


// Armazena o ID na sessão
$_SESSION["usuario_id"] = (int) $usuarioBanco["id"];


// Redireciona para a Home
header("Location: /index.php");
exit;

?>
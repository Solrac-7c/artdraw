<?php

require_once("includes/sessao.php");

// Limpa os dados da sessão
$_SESSION = [];

// Remove o cookie da sessão, se estiver habilitado
if (ini_get("session.use_cookies")) {

    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );

}

// Destrói a sessão
session_destroy();

// Retorna para o login
header("Location: /login/index.php");
exit;

?>
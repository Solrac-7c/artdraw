<?php

require_once(__DIR__ . "/sessao.php");
require_once(__DIR__ . "/conexao.php");


// Busca os dados do usuário atualmente autenticado
function usuarioAtual() {

    global $conn;

    static $consultado = false;
    static $usuario = null;

    if ($consultado) {
        return $usuario;
    }

    $consultado = true;

    // Verifica se existe usuário na sessão
    if (!isset($_SESSION["usuario_id"])) {
        return null;
    }

    $id = $_SESSION["usuario_id"];

    // Busca os dados atualizados no banco
    $sql = "SELECT
                id,
                nome,
                apelido,
                email,
                tipo_usuario,
                foto_perfil,
                biografia,
                status
            FROM usuarios
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    $usuario = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmt);


    // Verifica se o usuário existe e está ativo
    if (!$usuario || $usuario["status"] !== "ativo") {

        $_SESSION = [];

        session_destroy();

        $usuario = null;

        return null;

    }

    return $usuario;
}


// Exige que exista um usuário autenticado
function exigirLogin() {

    $usuario = usuarioAtual();

    if (!$usuario) {

        header("Location: /login/index.php");

        exit;

    }

    return $usuario;

}
?>
<?php

require_once(__DIR__ . "/auth.php");


// Exige um dos perfis autorizados
function exigirPerfil(array $perfisPermitidos) {

    $usuario = exigirLogin();

    if (!in_array(
        $usuario["tipo_usuario"],
        $perfisPermitidos,
        true
    )) {

        http_response_code(403);

        die("Você não possui permissão para acessar esta página.");

    }

    return $usuario;

}
?>
<?php

// Restringe a página a determinados papéis (Telas.pdf: criar, editar e
// deletar aula são exclusivos de instrutor/adm).
//
// Uso na página:
//     require_once(__DIR__ . "/../includes/autoriza.php");
// Para outro conjunto de papéis, defina $papeisPermitidos antes do require.
//
// O papel do usuário será gravado em $_SESSION["usuario_papel"] no login
// (Sprint 4).

require_once(__DIR__ . "/auth.php");

$papeisPermitidos = $papeisPermitidos ?? ["instrutor", "adm"];

if (!in_array($_SESSION["usuario_papel"] ?? "", $papeisPermitidos, true)) {

    $_SESSION["msg"] = "Esta área é restrita a instrutores.";
    $_SESSION["class"] = "alert alert-danger";

    header("Location: /index.php");
    exit;
}

?>

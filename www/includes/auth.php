<?php

// Protege páginas que exigem login. Será ligado nas páginas no Sprint 4,
// quando login e cadastro existirem.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["usuario_id"])) {
    header("Location: /login/index.php");
    exit;
}

?>

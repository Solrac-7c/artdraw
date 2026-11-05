<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $conn = new mysqli(
        "mysql",
        "root",
        "1234",
        "sistema_artdraw"
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    error_log("Erro de conexão: " . $e->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
    }

    exit("Não foi possível conectar ao banco de dados.");

}
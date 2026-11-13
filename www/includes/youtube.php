
<?php

/*
|--------------------------------------------------------------------------
| EXTRAIR E VALIDAR O ID DE UM VÍDEO DO YOUTUBE
|--------------------------------------------------------------------------
*/

function artdrawYoutubeVideoId($url) {

    // Verifica se recebeu uma URL válida.
    if (!is_string($url) || trim($url) === "") {
        return null;
    }

    $url = trim($url);

    $partes = parse_url($url);

    if (!$partes || empty($partes["host"])) {
        return null;
    }


    // Somente HTTP e HTTPS.
    $esquema = strtolower($partes["scheme"] ?? "");

    if (!in_array($esquema, ["http", "https"], true)) {
        return null;
    }


    // Não aceita credenciais ou portas personalizadas.
    if (
        isset($partes["user"]) ||
        isset($partes["pass"]) ||
        isset($partes["port"])
    ) {
        return null;
    }


    // Domínios permitidos.
    $dominio = strtolower($partes["host"]);

    $dominiosPermitidos = [
        "youtube.com",
        "www.youtube.com",
        "m.youtube.com",
        "youtu.be",
        "youtube-nocookie.com",
        "www.youtube-nocookie.com"
    ];

    if (!in_array($dominio, $dominiosPermitidos, true)) {
        return null;
    }


    $caminho = $partes["path"] ?? "";

    $idVideo = null;


    // Formato curto: youtu.be/ID
    if ($dominio === "youtu.be") {

        $idVideo = trim($caminho, "/");

    } else {

        // Formato tradicional: youtube.com/watch?v=ID
        if ($caminho === "/watch") {

            parse_str($partes["query"] ?? "", $parametros);

            $idVideo = $parametros["v"] ?? null;

        } else {

            // Formatos embed, shorts e live.
            if (
                preg_match(
                    '#^/(?:embed|shorts|live)/([A-Za-z0-9_-]{11})/?$#',
                    $caminho,
                    $correspondencia
                )
            ) {

                $idVideo = $correspondencia[1];

            }

        }

    }


    // O identificador precisa ter 11 caracteres válidos.
    if (
        !is_string($idVideo) ||
        !preg_match('/^[A-Za-z0-9_-]{11}$/', $idVideo)
    ) {

        return null;

    }


    return $idVideo;

}

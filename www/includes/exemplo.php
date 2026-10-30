<?php

// PROVISÓRIO (Sprint 2): dados fixos no lugar do banco.
//
// No Sprint 3 as listas abaixo viram tabelas (categorias e aulas) e as
// funções passam a consultar o MySQL, mantendo os mesmos nomes e retornos.
// Assim as páginas não precisam ser reescritas.


function listarCategorias(): array
{
    return [
        ["id" => 1, "nome" => "Desenho Técnico"],
        ["id" => 2, "nome" => "Mão livre"]
    ];
}


function listarNiveis(): array
{
    return [
        "iniciante"     => "Iniciante",
        "intermediario" => "Intermediário",
        "avancado"      => "Avançado"
    ];
}


function listarAulas(): array
{
    return [

        [
            "id"           => 1,
            "titulo"       => "Introdução ao desenho técnico",
            "categoria_id" => 1,
            "nivel"        => "iniciante",
            "descricao"    => "Conheça os instrumentos, os tipos de linha e as normas básicas do desenho técnico.\n\n"
                            . "Materiais: lápis HB e 2H, borracha macia, esquadros e folha A4.\n"
                            . "Passo a passo: prenda a folha na prancheta, trace a margem e treine linhas cheias, tracejadas e de centro."
        ],

        [
            "id"           => 2,
            "titulo"       => "Esquadros e réguas na prática",
            "categoria_id" => 1,
            "nivel"        => "iniciante",
            "descricao"    => "Aprenda a posicionar os esquadros para traçar paralelas, perpendiculares e ângulos de 45° e 60°.\n\n"
                            . "Dica: mantenha o lápis sempre com a mesma inclinação para o traço ficar uniforme."
        ],

        [
            "id"           => 3,
            "titulo"       => "Vistas ortográficas de uma peça",
            "categoria_id" => 1,
            "nivel"        => "intermediario",
            "descricao"    => "Represente a vista frontal, superior e lateral de uma peça simples, mantendo o alinhamento entre as vistas.\n\n"
                            . "Passo a passo: desenhe a vista frontal, projete as linhas para a vista superior e depois para a lateral."
        ],

        [
            "id"           => 4,
            "titulo"       => "Perspectiva isométrica passo a passo",
            "categoria_id" => 1,
            "nivel"        => "intermediario",
            "descricao"    => "Construa um sólido em perspectiva isométrica usando eixos a 30°.\n\n"
                            . "Passo a passo: trace os três eixos, marque as medidas em cada um deles e feche as faces do sólido."
        ],

        [
            "id"           => 5,
            "titulo"       => "Cotagem e escala em projetos",
            "categoria_id" => 1,
            "nivel"        => "avancado",
            "descricao"    => "Aplique cotas, setas e escalas de redução e ampliação conforme as normas, sem poluir o desenho.\n\n"
                            . "Dica: posicione as cotas fora da peça e evite cruzar linhas de cota."
        ],

        [
            "id"           => 6,
            "titulo"       => "Linhas e contornos à mão livre",
            "categoria_id" => 2,
            "nivel"        => "iniciante",
            "descricao"    => "Exercícios de aquecimento para ganhar controle do traço: linhas retas, curvas e contornos simples.\n\n"
                            . "Dica: desenhe movendo o ombro e o cotovelo, não só o pulso."
        ],

        [
            "id"           => 7,
            "titulo"       => "Sombreamento com lápis grafite",
            "categoria_id" => 2,
            "nivel"        => "intermediario",
            "descricao"    => "Crie volume usando hachuras, esfumado e gradação de tons com lápis de durezas diferentes.\n\n"
                            . "Passo a passo: defina a fonte de luz, marque as áreas de sombra e esfume do tom mais escuro para o mais claro."
        ],

        [
            "id"           => 8,
            "titulo"       => "Proporção e anatomia da figura humana",
            "categoria_id" => 2,
            "nivel"        => "avancado",
            "descricao"    => "Estude as proporções do corpo humano e construa figuras em movimento a partir de formas simples.\n\n"
                            . "Passo a passo: comece pela linha de ação, marque as articulações e dê volume com cilindros e esferas."
        ]

    ];
}


function nomeCategoria(int $id): string
{
    foreach (listarCategorias() as $categoria) {

        if ($categoria["id"] === $id) {
            return $categoria["nome"];
        }

    }

    return "Sem categoria";
}


function nomeNivel(string $nivel): string
{
    return listarNiveis()[$nivel] ?? $nivel;
}


function buscarAula(int $id): ?array
{
    foreach (listarAulas() as $aula) {

        if ($aula["id"] === $id) {
            return $aula;
        }

    }

    return null;
}


// Compara sem diferenciar maiúsculas de minúsculas. Usa mb_stripos quando a
// extensão mbstring estiver ativa (aceita acentos) e stripos como alternativa.
function contem(string $texto, string $trecho): bool
{
    if (function_exists("mb_stripos")) {
        return mb_stripos($texto, $trecho) !== false;
    }

    return stripos($texto, $trecho) !== false;
}


function filtrarAulas(
    string $busca = "",
    int $categoriaId = 0,
    string $nivel = ""
): array {

    $resultado = [];

    foreach (listarAulas() as $aula) {

        if ($categoriaId > 0 && $aula["categoria_id"] !== $categoriaId) {
            continue;
        }

        if ($nivel !== "" && $aula["nivel"] !== $nivel) {
            continue;
        }

        if ($busca !== "") {

            $texto = $aula["titulo"]
                . " " . $aula["descricao"]
                . " " . nomeCategoria($aula["categoria_id"]);

            if (!contem($texto, $busca)) {
                continue;
            }

        }

        $resultado[] = $aula;
    }

    return $resultado;
}

?>
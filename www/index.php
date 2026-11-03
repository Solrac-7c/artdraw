<?php

require_once("includes/auth.php");

echo "<h1>ArtDraw - Teste de conexão</h1>";

echo "<p>Conexão com o banco realizada com sucesso!</p>";

echo "<hr>";

$usuario = usuarioAtual();

if ($usuario) {

    echo "<h3>Usuário autenticado</h3>";

    echo "Nome: " . htmlspecialchars($usuario["nome"]) . "<br>";

    echo "Apelido: " . htmlspecialchars($usuario["apelido"]) . "<br>";

    echo "E-mail: " . htmlspecialchars($usuario["email"]) . "<br>";

    echo "Perfil: " . htmlspecialchars($usuario["tipo_usuario"]) . "<br>";

    echo "Status: " . htmlspecialchars($usuario["status"]) . "<br>";

} else {

    echo "<h3>Visitante</h3>";

    echo "<p>Nenhum usuário autenticado.</p>";

}
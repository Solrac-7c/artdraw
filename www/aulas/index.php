
<?php
require_once __DIR__ . "/includes/exemplo.php";

$aulas = listarAulas();
?>

<section class="lista-aulas">

    <?php foreach ($aulas as $aula): ?>

        <?php require __DIR__ . "/includes/card_aula.php"; ?>

    <?php endforeach; ?>

</section>

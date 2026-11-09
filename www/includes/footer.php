
<?php

$anoAtual = date("Y");

?>

    </main>


    <!-- Rodapé compartilhado -->

    <footer class="ad-footer">

        <div class="ad-container">

            <div class="ad-footer-inner">


                <!-- Marca e apresentação -->

                <div>

                    <a
                        href="/index.php"
                        class="ad-footer-brand"
                    >

                        ArtDraw<span class="ad-brand-dot">.</span>

                    </a>

                    <p class="ad-footer-text">

                        Aprenda, compartilhe e desenvolva sua arte.

                    </p>

                </div>


                <!-- Direitos autorais -->

                <p class="ad-footer-text">

                    &copy; <?= $anoAtual ?> ArtDraw.
                    Todos os direitos reservados.

                </p>


            </div>

        </div>

    </footer>


    <!-- JavaScript do Bootstrap -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    ></script>


</body>

</html>

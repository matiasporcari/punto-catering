<?php
/**
 * Vista parcial: pie común a todas las páginas.
 */
?>
</main>

<footer class="pie-sitio">
    <div class="contenedor pie-interior">
        <div>
            <p class="logo logo-claro">Punto Catering<span class="logo-punto">.</span></p>
            <p>Catering para empresas, de la pausa de café a la cena de fin de año.</p>
        </div>
        <nav aria-label="Pie de página">
            <a href="<?= escapar(url('catalogo.php')) ?>">Catálogo</a>
            <a href="<?= escapar(url('presupuesto.php')) ?>">Pedir presupuesto</a>
            <a href="<?= escapar(url('contacto.php')) ?>">Contacto</a>
        </nav>
        <p class="pie-creditos">Trabajo final de Programación Web - UCH<br>Bautista Navarro y Matias Porcari</p>
    </div>
</footer>
</body>
</html>

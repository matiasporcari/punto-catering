<?php
/**
 * Vista parcial: tarjeta de un menú del catálogo.
 * Necesita la variable $menu, con los datos del menú y el nombre de su categoría.
 */
?>
<article class="tarjeta-menu">
    <img class="tarjeta-menu-imagen" src="<?= escapar(url('recursos/img/menus/' . $menu['ilustracion'] . '.svg')) ?>" alt="" width="400" height="240">
    <div class="tarjeta-menu-cuerpo">
        <p class="sobretitulo"><?= escapar($menu['categoria']) ?></p>
        <h3><?= escapar($menu['nombre']) ?></h3>
        <p class="tarjeta-menu-descripcion"><?= escapar($menu['descripcion']) ?></p>
        <ul class="lista-distintivos">
            <?php if ($menu['es_vegetariano']): ?><li class="distintivo">Vegetariano</li><?php endif; ?>
            <?php if ($menu['es_sin_tacc']): ?><li class="distintivo">Sin TACC</li><?php endif; ?>
            <li class="distintivo distintivo-neutro">Desde <?= (int) $menu['minimo_personas'] ?> personas</li>
        </ul>
        <div class="tarjeta-menu-pie">
            <p class="precio"><strong><?= formato_moneda($menu['precio_por_persona']) ?></strong> <span>por persona</span></p>
            <a class="boton boton-secundario" href="<?= escapar(url('presupuesto.php?menu=' . $menu['id_menu'])) ?>">Lo quiero cotizar</a>
        </div>
    </div>
</article>

<?php
/**
 * Página de inicio: presenta el servicio y dirige hacia el pedido de presupuesto.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/arranque.php';

$titulo_pagina = 'Catering para empresas';

// Se destacan los tres menús más elegidos en pedidos no cancelados.
$menus_destacados = obtener_conexion()->query(
    "SELECT m.*, c.nombre AS categoria, COUNT(p.id_pedido) AS veces_pedido
     FROM menus m
     INNER JOIN categorias c ON c.id_categoria = m.id_categoria
     LEFT JOIN detalle_pedido d ON d.id_menu = m.id_menu
     LEFT JOIN pedidos p ON p.id_pedido = d.id_pedido AND p.estado <> 'cancelado'
     WHERE m.activo = 1
     GROUP BY m.id_menu
     ORDER BY veces_pedido DESC, m.nombre
     LIMIT 3"
)->fetchAll();

$categorias = obtener_categorias();

require __DIR__ . '/includes/encabezado.php';
?>

<section class="portada">
    <div class="contenedor portada-interior">
        <div class="portada-texto">
            <p class="sobretitulo">Catering para empresas</p>
            <h1>Tu próxima reunión, resuelta en un solo pedido.</h1>
            <p class="portada-bajada">Elegí el menú, indicá la fecha y la cantidad de personas, y obtené al instante un precio estimado. Nosotros nos ocupamos del resto.</p>
            <div class="grupo-botones">
                <a class="boton boton-primario boton-grande" href="<?= escapar(url('presupuesto.php')) ?>">Obtener mi presupuesto</a>
                <a class="boton boton-claro boton-grande" href="<?= escapar(url('catalogo.php')) ?>">Ver el catálogo</a>
            </div>
        </div>
        <img class="portada-imagen" src="<?= escapar(url('recursos/img/portada.svg')) ?>" alt="Mesa vista desde arriba con platos, café y ensaladas" width="560" height="480">
    </div>
</section>

<section class="seccion">
    <div class="contenedor">
        <h2>Cómo funciona</h2>
        <ol class="lista-pasos">
            <li>
                <h3>Armás tu pedido</h3>
                <p>Elegís uno o más menús y ves el precio estimado mientras completás los datos.</p>
            </li>
            <li>
                <h3>Recibís el presupuesto</h3>
                <p>Revisamos el pedido y publicamos el precio final en tu cuenta, listo para imprimir.</p>
            </li>
            <li>
                <h3>Lo confirmás con un clic</h3>
                <p>Aprobás desde tu cuenta y llegamos con todo preparado el día del evento.</p>
            </li>
        </ol>
    </div>
</section>

<section class="seccion seccion-alterna">
    <div class="contenedor">
        <div class="titulo-con-enlace">
            <h2>Los más elegidos</h2>
            <a href="<?= escapar(url('catalogo.php')) ?>">Ver todos los menús</a>
        </div>
        <div class="grilla-menus">
            <?= tarjetas_de_menus($menus_destacados) ?>
        </div>
    </div>
</section>

<section class="seccion">
    <div class="contenedor">
        <h2>Una propuesta para cada ocasión</h2>
        <div class="grilla-categorias">
            <?php foreach ($categorias as $categoria): ?>
                <a class="tarjeta-categoria" href="<?= escapar(url('catalogo.php?categoria=' . $categoria['id_categoria'])) ?>">
                    <h3><?= escapar($categoria['nombre']) ?></h3>
                    <p><?= escapar($categoria['descripcion']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="franja-llamado">
    <div class="contenedor franja-llamado-interior">
        <div>
            <h2>¿Tenés un evento en mente?</h2>
            <p>Conocé el precio estimado en menos de dos minutos, sin compromiso.</p>
        </div>
        <a class="boton boton-primario boton-grande" href="<?= escapar(url('presupuesto.php')) ?>">Obtener mi presupuesto</a>
    </div>
</section>

<?php require __DIR__ . '/includes/pie.php'; ?>

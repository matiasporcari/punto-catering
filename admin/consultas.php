<?php
/**
 * Administración: consultas recibidas por el formulario de contacto.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

requerir_administrador();

$titulo_pagina = 'Consultas';
$seccion_admin = 'consultas';
$conexion = obtener_conexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_consulta = entero_de($_POST, 'id_consulta');
    $accion = texto_post('accion');

    if ($accion === 'alternar') {
        $conexion->prepare('UPDATE consultas SET leida = 1 - leida WHERE id_consulta = ?')->execute([$id_consulta]);
        establecer_mensaje('exito', 'Se actualizó la consulta.');
    } elseif ($accion === 'eliminar') {
        $conexion->prepare('DELETE FROM consultas WHERE id_consulta = ?')->execute([$id_consulta]);
        establecer_mensaje('exito', 'Se eliminó la consulta.');
    }

    redirigir('admin/consultas.php');
}

// Primero las que falta leer y, dentro de cada grupo, las más recientes.
$consultas = $conexion->query('SELECT * FROM consultas ORDER BY leida, fecha DESC')->fetchAll();

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor contenedor-angosto">
        <h1>Consultas</h1>

        <?php if ($consultas === []): ?>
            <p class="tarjeta estado-vacio">No hay consultas por ahora.</p>
        <?php endif; ?>

        <?php foreach ($consultas as $consulta): ?>
            <article class="tarjeta consulta <?= $consulta['leida'] ? 'consulta-leida' : '' ?>">
                <header class="consulta-encabezado">
                    <div>
                        <h2><?= escapar($consulta['nombre']) ?></h2>
                        <p class="texto-suave">
                            <?= $consulta['empresa'] !== '' ? escapar($consulta['empresa']) . ' · ' : '' ?><?= escapar($consulta['correo']) ?> · <?= formato_fecha($consulta['fecha']) ?>
                        </p>
                    </div>
                    <?php if (!$consulta['leida']): ?><span class="etiqueta-estado estado-pendiente">Sin leer</span><?php endif; ?>
                </header>
                <p><?= nl2br(escapar($consulta['mensaje'])) ?></p>
                <div class="acciones-fila">
                    <a href="mailto:<?= escapar($consulta['correo']) ?>?subject=<?= rawurlencode('Tu consulta a Punto Catering') ?>">Responder por correo</a>
                    <form method="post">
                        <?= campo_csrf() ?>
                        <input type="hidden" name="id_consulta" value="<?= (int) $consulta['id_consulta'] ?>">
                        <input type="hidden" name="accion" value="alternar">
                        <button class="enlace-boton" type="submit"><?= $consulta['leida'] ? 'Marcar sin leer' : 'Marcar como leída' ?></button>
                    </form>
                    <form method="post" data-confirmar="¿Eliminar esta consulta?">
                        <?= campo_csrf() ?>
                        <input type="hidden" name="id_consulta" value="<?= (int) $consulta['id_consulta'] ?>">
                        <input type="hidden" name="accion" value="eliminar">
                        <button class="enlace-boton enlace-peligro" type="submit">Eliminar</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

<?php
/**
 * Panel de la empresa: lista de sus pedidos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

$usuario = requerir_cliente();
$titulo_pagina = 'Mis pedidos';

$consulta = obtener_conexion()->prepare(
    'SELECT p.*, GROUP_CONCAT(m.nombre ORDER BY m.nombre SEPARATOR ", ") AS menus
     FROM pedidos p
     INNER JOIN detalle_pedido d ON d.id_pedido = p.id_pedido
     INNER JOIN menus m ON m.id_menu = d.id_menu
     WHERE p.id_usuario = ?
     GROUP BY p.id_pedido
     ORDER BY p.fecha_evento DESC'
);
$consulta->execute([$usuario['id_usuario']]);
$pedidos = $consulta->fetchAll();

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <div class="titulo-con-enlace">
            <div>
                <h1>Mis pedidos</h1>
                <p class="bajada"><?= escapar($usuario['razon_social']) ?></p>
            </div>
            <a class="boton boton-primario" href="<?= escapar(url('presupuesto.php')) ?>">Pedir un presupuesto nuevo</a>
        </div>

        <?php if ($pedidos === []): ?>
            <div class="tarjeta estado-vacio">
                <h2>Todavía no hiciste pedidos</h2>
                <p>Elegí un menú del catálogo y conocé el precio estimado en menos de dos minutos.</p>
                <a class="boton boton-secundario" href="<?= escapar(url('catalogo.php')) ?>">Ver el catálogo</a>
            </div>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">N.º</th>
                            <th scope="col">Evento</th>
                            <th scope="col">Menús</th>
                            <th scope="col" class="celda-numero">Personas</th>
                            <th scope="col" class="celda-numero">Total</th>
                            <th scope="col">Estado</th>
                            <th scope="col"><span class="solo-lector">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?= (int) $pedido['id_pedido'] ?></td>
                                <td class="celda-sin-corte"><?= formato_fecha($pedido['fecha_evento']) ?> · <?= formato_hora($pedido['hora_evento']) ?></td>
                                <td><?= escapar($pedido['menus']) ?></td>
                                <td class="celda-numero"><?= (int) $pedido['cantidad_personas'] ?></td>
                                <td class="celda-numero celda-sin-corte">
                                    <?= formato_moneda($pedido['total_final'] ?? $pedido['total_estimado']) ?>
                                    <?php if ($pedido['total_final'] === null): ?><small class="texto-suave">estimado</small><?php endif; ?>
                                </td>
                                <td><?= etiqueta_estado($pedido['estado']) ?></td>
                                <td><a href="<?= escapar(url('cuenta/pedido.php?id=' . $pedido['id_pedido'])) ?>">Ver detalle</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

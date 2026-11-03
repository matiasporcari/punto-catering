<?php
/**
 * Detalle de un pedido de la empresa, con las acciones permitidas según su estado:
 * editar, cancelar, aprobar o rechazar el presupuesto y eliminarlo del historial.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

$usuario = requerir_cliente();
$id_usuario = (int) $usuario['id_usuario'];
$id_pedido = entero_de($_GET, 'id');

// Procesamiento de las acciones de la empresa.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = texto_post('accion');
    $exito = false;

    switch ($accion) {
        case 'aprobar':
            $exito = cambiar_estado_pedido_cliente($id_pedido, $id_usuario, 'aprobado', ['presupuestado']);
            $mensaje_exito = '¡Presupuesto aprobado! Ya estamos preparando tu evento.';
            break;
        case 'rechazar':
            $exito = cambiar_estado_pedido_cliente($id_pedido, $id_usuario, 'rechazado', ['presupuestado']);
            $mensaje_exito = 'Registramos que rechazaste el presupuesto.';
            break;
        case 'cancelar':
            $exito = cambiar_estado_pedido_cliente($id_pedido, $id_usuario, 'cancelado', ESTADOS_CANCELABLES);
            $mensaje_exito = 'Cancelamos tu pedido.';
            break;
        case 'eliminar':
            $marcadores = implode(',', array_fill(0, count(ESTADOS_ELIMINABLES), '?'));
            $borrado = obtener_conexion()->prepare(
                "DELETE FROM pedidos WHERE id_pedido = ? AND id_usuario = ? AND estado IN ($marcadores)"
            );
            $borrado->execute([$id_pedido, $id_usuario, ...ESTADOS_ELIMINABLES]);

            if ($borrado->rowCount() > 0) {
                establecer_mensaje('exito', 'Eliminamos el pedido de tu historial.');
                redirigir('cuenta/pedidos.php');
            }
            break;
    }

    if ($exito) {
        establecer_mensaje('exito', $mensaje_exito);
    } else {
        establecer_mensaje('error', 'No se pudo realizar esa acción con el estado actual del pedido.');
    }
    redirigir('cuenta/pedido.php?id=' . $id_pedido);
}

// El pedido se busca filtrando por empresa: un pedido ajeno se comporta como inexistente.
$pedido = obtener_pedido($id_pedido, $id_usuario);

if ($pedido === null) {
    establecer_mensaje('error', 'No encontramos ese pedido.');
    redirigir('cuenta/pedidos.php');
}

$detalle = obtener_detalle_pedido($id_pedido);
$titulo_pagina = 'Pedido N.º ' . $id_pedido;

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor contenedor-angosto">
        <p><a href="<?= escapar(url('cuenta/pedidos.php')) ?>">← Volver a mis pedidos</a></p>
        <div class="titulo-con-enlace">
            <h1>Pedido N.º <?= $id_pedido ?></h1>
            <?= etiqueta_estado($pedido['estado']) ?>
        </div>

        <?php if ($pedido['estado'] === 'presupuestado'): ?>
            <div class="tarjeta destacado">
                <p class="sobretitulo">Tu presupuesto está listo</p>
                <p class="importe-grande"><?= formato_moneda($pedido['total_final']) ?></p>
                <?php if ($pedido['comentario_admin'] !== ''): ?>
                    <p><?= escapar($pedido['comentario_admin']) ?></p>
                <?php endif; ?>
                <div class="grupo-botones">
                    <form method="post">
                        <?= campo_csrf() ?>
                        <input type="hidden" name="accion" value="aprobar">
                        <button class="boton boton-primario" type="submit">Aprobar mi presupuesto</button>
                    </form>
                    <form method="post" data-confirmar="¿Seguro que querés rechazar este presupuesto?">
                        <?= campo_csrf() ?>
                        <input type="hidden" name="accion" value="rechazar">
                        <button class="boton boton-claro" type="submit">Rechazar</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="tarjeta">
            <h2>Datos del evento</h2>
            <dl class="lista-datos">
                <div><dt>Fecha</dt><dd><?= formato_fecha($pedido['fecha_evento']) ?> a las <?= formato_hora($pedido['hora_evento']) ?></dd></div>
                <div><dt>Dirección</dt><dd><?= escapar($pedido['direccion_evento']) ?></dd></div>
                <div><dt>Personas</dt><dd><?= (int) $pedido['cantidad_personas'] ?></dd></div>
                <?php if ($pedido['observaciones'] !== ''): ?>
                    <div><dt>Observaciones</dt><dd><?= escapar($pedido['observaciones']) ?></dd></div>
                <?php endif; ?>
            </dl>
        </div>

        <div class="tarjeta">
            <h2>Menús</h2>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Menú</th>
                            <th scope="col" class="celda-numero">Por persona</th>
                            <th scope="col" class="celda-numero">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalle as $linea): ?>
                            <tr>
                                <td><?= escapar($linea['nombre']) ?></td>
                                <td class="celda-numero"><?= formato_moneda($linea['precio_unitario']) ?></td>
                                <td class="celda-numero"><?= formato_moneda((float) $linea['precio_unitario'] * (int) $pedido['cantidad_personas']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row" colspan="2">Total estimado</th>
                            <td class="celda-numero"><?= formato_moneda($pedido['total_estimado']) ?></td>
                        </tr>
                        <?php if ($pedido['total_final'] !== null): ?>
                            <tr>
                                <th scope="row" colspan="2">Precio final</th>
                                <td class="celda-numero"><strong><?= formato_moneda($pedido['total_final']) ?></strong></td>
                            </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
            <?php if ($pedido['estado'] !== 'presupuestado' && $pedido['comentario_admin'] !== ''): ?>
                <p class="nota-admin"><strong>Comentario de Punto Catering:</strong> <?= escapar($pedido['comentario_admin']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grupo-botones">
            <a class="boton boton-secundario" href="<?= escapar(url('cuenta/imprimir_pedido.php?id=' . $id_pedido)) ?>">Ver para imprimir</a>

            <?php if ($pedido['estado'] === 'pendiente'): ?>
                <a class="boton boton-secundario" href="<?= escapar(url('presupuesto.php?id=' . $id_pedido)) ?>">Editar mi pedido</a>
            <?php endif; ?>

            <?php if (in_array($pedido['estado'], ESTADOS_CANCELABLES, true)): ?>
                <form method="post" data-confirmar="¿Seguro que querés cancelar este pedido?">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="accion" value="cancelar">
                    <button class="boton boton-peligro" type="submit">Cancelar pedido</button>
                </form>
            <?php endif; ?>

            <?php if (in_array($pedido['estado'], ESTADOS_ELIMINABLES, true)): ?>
                <form method="post" data-confirmar="El pedido se va a eliminar definitivamente. ¿Continuar?">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="accion" value="eliminar">
                    <button class="boton boton-peligro" type="submit">Eliminar del historial</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

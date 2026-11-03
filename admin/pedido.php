<?php
/**
 * Administración: gestión de un pedido. Permite presupuestarlo, cambiar su estado o eliminarlo.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

requerir_administrador();

$seccion_admin = 'pedidos';
$id_pedido = entero_de($_GET, 'id');
$pedido = obtener_pedido($id_pedido);

if ($pedido === null) {
    establecer_mensaje('error', 'No encontramos ese pedido.');
    redirigir('admin/pedidos.php');
}

$errores = [];
$datos = [
    'estado' => $pedido['estado'],
    'total_final' => $pedido['total_final'] ?? '',
    'comentario_admin' => $pedido['comentario_admin'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = obtener_conexion();

    if (texto_post('accion') === 'eliminar') {
        // El detalle se borra solo por la regla ON DELETE CASCADE de la base de datos.
        $conexion->prepare('DELETE FROM pedidos WHERE id_pedido = ?')->execute([$id_pedido]);
        establecer_mensaje('exito', "Se eliminó el pedido N.º $id_pedido.");
        redirigir('admin/pedidos.php');
    }

    $datos = [
        'estado' => texto_post('estado'),
        'total_final' => str_replace(',', '.', texto_post('total_final')),
        'comentario_admin' => texto_post('comentario_admin'),
    ];

    if (!isset(ESTADOS_PEDIDO[$datos['estado']])) {
        $errores['estado'] = 'Elegí un estado válido.';
    }

    // El precio final es opcional, salvo en los estados que lo necesitan.
    if ($datos['total_final'] !== '' && (!is_numeric($datos['total_final']) || (float) $datos['total_final'] <= 0 || (float) $datos['total_final'] >= 10000000000)) {
        $errores['total_final'] = 'Ingresá un importe válido, mayor a cero.';
    } elseif ($datos['total_final'] === '' && in_array($datos['estado'], ['presupuestado', 'aprobado', 'finalizado'], true)) {
        $errores['total_final'] = 'Para ese estado hay que indicar el precio final.';
    }

    if (mb_strlen($datos['comentario_admin']) > 500) {
        $errores['comentario_admin'] = 'El comentario no puede superar los 500 caracteres.';
    }

    if ($errores === []) {
        $actualizacion = $conexion->prepare('UPDATE pedidos SET estado = ?, total_final = ?, comentario_admin = ? WHERE id_pedido = ?');
        $actualizacion->execute([
            $datos['estado'],
            $datos['total_final'] === '' ? null : $datos['total_final'],
            $datos['comentario_admin'],
            $id_pedido,
        ]);

        establecer_mensaje('exito', 'Se actualizó el pedido.');
        redirigir('admin/pedido.php?id=' . $id_pedido);
    }
}

$detalle = obtener_detalle_pedido($id_pedido);
$titulo_pagina = 'Pedido N.º ' . $id_pedido;

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <p><a href="<?= escapar(url('admin/pedidos.php')) ?>">← Volver a los pedidos</a></p>
        <div class="titulo-con-enlace">
            <h1>Pedido N.º <?= $id_pedido ?></h1>
            <?= etiqueta_estado($pedido['estado']) ?>
        </div>

        <div class="distribucion-dos-columnas">
            <div>
                <div class="tarjeta">
                    <h2>Empresa</h2>
                    <dl class="lista-datos">
                        <div><dt>Razón social</dt><dd><?= escapar($pedido['razon_social']) ?></dd></div>
                        <div><dt>CUIT</dt><dd><?= escapar($pedido['cuit']) ?></dd></div>
                        <div><dt>Contacto</dt><dd><?= escapar($pedido['nombre_contacto']) ?></dd></div>
                        <div><dt>Correo</dt><dd><a href="mailto:<?= escapar($pedido['correo']) ?>"><?= escapar($pedido['correo']) ?></a></dd></div>
                        <div><dt>Teléfono</dt><dd><?= escapar($pedido['telefono']) ?></dd></div>
                    </dl>
                </div>
                <div class="tarjeta">
                    <h2>Evento</h2>
                    <dl class="lista-datos">
                        <div><dt>Fecha</dt><dd><?= formato_fecha($pedido['fecha_evento']) ?> a las <?= formato_hora($pedido['hora_evento']) ?></dd></div>
                        <div><dt>Dirección</dt><dd><?= escapar($pedido['direccion_evento']) ?></dd></div>
                        <div><dt>Personas</dt><dd><?= (int) $pedido['cantidad_personas'] ?></dd></div>
                        <?php if ($pedido['observaciones'] !== ''): ?>
                            <div><dt>Observaciones</dt><dd><?= escapar($pedido['observaciones']) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <h3>Menús</h3>
                    <ul class="lista-simple">
                        <?php foreach ($detalle as $linea): ?>
                            <li><?= escapar($linea['nombre']) ?> <span class="texto-suave">· <?= formato_moneda($linea['precio_unitario']) ?> por persona</span></li>
                        <?php endforeach; ?>
                    </ul>
                    <p><strong>Total estimado: <?= formato_moneda($pedido['total_estimado']) ?></strong></p>
                </div>
            </div>

            <div>
                <form class="tarjeta formulario" method="post" novalidate>
                    <?= campo_csrf() ?>
                    <input type="hidden" name="accion" value="actualizar">
                    <h2>Presupuestar y actualizar</h2>
                    <div class="campo">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" <?= atributos_error($errores, 'estado') ?>>
                            <?php foreach (ESTADOS_PEDIDO as $valor => $texto): ?>
                                <option value="<?= escapar($valor) ?>" <?= $valor === $datos['estado'] ? 'selected' : '' ?>><?= escapar($texto) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= error_campo($errores, 'estado') ?>
                    </div>
                    <div class="campo">
                        <label for="total_final">Precio final ($)</label>
                        <input type="number" id="total_final" name="total_final" min="1" step="1" value="<?= escapar($datos['total_final']) ?>" <?= atributos_error($errores, 'total_final') ?>>
                        <?= error_campo($errores, 'total_final') ?>
                    </div>
                    <div class="campo">
                        <label for="comentario_admin">Comentario para la empresa</label>
                        <textarea id="comentario_admin" name="comentario_admin" rows="4" maxlength="500" <?= atributos_error($errores, 'comentario_admin') ?>><?= escapar($datos['comentario_admin']) ?></textarea>
                        <?= error_campo($errores, 'comentario_admin') ?>
                    </div>
                    <button class="boton boton-primario boton-ancho" type="submit">Guardar los cambios</button>
                </form>

                <div class="grupo-botones">
                    <a class="boton boton-secundario" href="<?= escapar(url('cuenta/imprimir_pedido.php?id=' . $id_pedido)) ?>">Ver para imprimir</a>
                    <form method="post" data-confirmar="El pedido se va a eliminar definitivamente. ¿Continuar?">
                        <?= campo_csrf() ?>
                        <input type="hidden" name="accion" value="eliminar">
                        <button class="boton boton-peligro" type="submit">Eliminar pedido</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

<?php
/**
 * Administración: lista de todos los pedidos, con filtro por estado.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

requerir_administrador();

$titulo_pagina = 'Pedidos';
$seccion_admin = 'pedidos';

// El filtro solo se aplica si es un estado conocido.
$estado_filtro = $_GET['estado'] ?? '';
$estado_filtro = is_string($estado_filtro) && isset(ESTADOS_PEDIDO[$estado_filtro]) ? $estado_filtro : '';

$sql = 'SELECT p.*, u.razon_social
        FROM pedidos p
        INNER JOIN usuarios u ON u.id_usuario = p.id_usuario';
$parametros = [];

if ($estado_filtro !== '') {
    $sql .= ' WHERE p.estado = ?';
    $parametros[] = $estado_filtro;
}

$consulta = obtener_conexion()->prepare($sql . ' ORDER BY p.fecha_evento');
$consulta->execute($parametros);
$pedidos = $consulta->fetchAll();

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <h1>Pedidos</h1>

        <form class="filtros" method="get">
            <div class="campo">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" data-enviar-al-cambiar>
                    <option value="">Todos</option>
                    <?php foreach (ESTADOS_PEDIDO as $valor => $texto): ?>
                        <option value="<?= escapar($valor) ?>" <?= $valor === $estado_filtro ? 'selected' : '' ?>><?= escapar($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="boton boton-secundario sin-js" type="submit">Filtrar</button>
        </form>

        <?php if ($pedidos === []): ?>
            <p class="tarjeta estado-vacio">No hay pedidos con ese estado.</p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">N.º</th>
                            <th scope="col">Empresa</th>
                            <th scope="col">Evento</th>
                            <th scope="col" class="celda-numero">Personas</th>
                            <th scope="col" class="celda-numero">Estimado</th>
                            <th scope="col" class="celda-numero">Final</th>
                            <th scope="col">Estado</th>
                            <th scope="col"><span class="solo-lector">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?= (int) $pedido['id_pedido'] ?></td>
                                <td><?= escapar($pedido['razon_social']) ?></td>
                                <td class="celda-sin-corte"><?= formato_fecha($pedido['fecha_evento']) ?></td>
                                <td class="celda-numero"><?= (int) $pedido['cantidad_personas'] ?></td>
                                <td class="celda-numero celda-sin-corte"><?= formato_moneda($pedido['total_estimado']) ?></td>
                                <td class="celda-numero celda-sin-corte"><?= $pedido['total_final'] === null ? '-' : formato_moneda($pedido['total_final']) ?></td>
                                <td><?= etiqueta_estado($pedido['estado']) ?></td>
                                <td><a href="<?= escapar(url('admin/pedido.php?id=' . $pedido['id_pedido'])) ?>">Gestionar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

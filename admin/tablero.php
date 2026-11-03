<?php
/**
 * Tablero del administrador: métricas generales calculadas en vivo desde la base de datos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

requerir_administrador();

$titulo_pagina = 'Tablero';
$seccion_admin = 'tablero';
$conexion = obtener_conexion();

// Cantidad de pedidos por estado; los estados sin pedidos también se muestran, en cero.
$pedidos_por_estado = array_fill_keys(array_keys(ESTADOS_PEDIDO), 0);
foreach ($conexion->query('SELECT estado, COUNT(*) AS cantidad FROM pedidos GROUP BY estado') as $fila) {
    $pedidos_por_estado[$fila['estado']] = (int) $fila['cantidad'];
}
$mayor_estado = max([1, ...array_values($pedidos_por_estado)]);

// Ingresos confirmados: pedidos aprobados por la empresa o ya realizados.
$ingresos_confirmados = (float) $conexion->query(
    "SELECT COALESCE(SUM(total_final), 0) FROM pedidos WHERE estado IN ('aprobado', 'finalizado')"
)->fetchColumn();

// Dinero a la espera de que la empresa responda.
$ingresos_en_espera = (float) $conexion->query(
    "SELECT COALESCE(SUM(total_final), 0) FROM pedidos WHERE estado = 'presupuestado'"
)->fetchColumn();

$empresas_activas = (int) $conexion->query(
    "SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente' AND activo = 1"
)->fetchColumn();

$consultas_sin_leer = (int) $conexion->query('SELECT COUNT(*) FROM consultas WHERE leida = 0')->fetchColumn();

// Los cinco menús más elegidos, sin contar pedidos cancelados.
$menus_mas_pedidos = $conexion->query(
    "SELECT m.nombre, COUNT(*) AS cantidad
     FROM detalle_pedido d
     INNER JOIN menus m ON m.id_menu = d.id_menu
     INNER JOIN pedidos p ON p.id_pedido = d.id_pedido
     WHERE p.estado <> 'cancelado'
     GROUP BY m.id_menu
     ORDER BY cantidad DESC, m.nombre
     LIMIT 5"
)->fetchAll();
$mayor_menu = max([1, ...array_map(fn(array $fila): int => (int) $fila['cantidad'], $menus_mas_pedidos)]);

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <h1>Tablero</h1>

        <div class="grilla-indicadores">
            <a class="indicador" href="<?= escapar(url('admin/pedidos.php?estado=pendiente')) ?>">
                <span class="indicador-titulo">Pedidos por revisar</span>
                <strong class="indicador-valor"><?= $pedidos_por_estado['pendiente'] ?></strong>
            </a>
            <div class="indicador">
                <span class="indicador-titulo">Ingresos confirmados</span>
                <strong class="indicador-valor"><?= formato_moneda($ingresos_confirmados) ?></strong>
            </div>
            <a class="indicador" href="<?= escapar(url('admin/pedidos.php?estado=presupuestado')) ?>">
                <span class="indicador-titulo">Presupuestos en espera</span>
                <strong class="indicador-valor"><?= formato_moneda($ingresos_en_espera) ?></strong>
            </a>
            <a class="indicador" href="<?= escapar(url('admin/usuarios.php')) ?>">
                <span class="indicador-titulo">Empresas activas</span>
                <strong class="indicador-valor"><?= $empresas_activas ?></strong>
            </a>
            <a class="indicador" href="<?= escapar(url('admin/consultas.php')) ?>">
                <span class="indicador-titulo">Consultas sin leer</span>
                <strong class="indicador-valor"><?= $consultas_sin_leer ?></strong>
            </a>
        </div>

        <div class="grilla-graficos">
            <section class="tarjeta">
                <h2>Pedidos por estado</h2>
                <ul class="grafico-barras">
                    <?php foreach ($pedidos_por_estado as $estado => $cantidad): ?>
                        <li title="<?= escapar(ESTADOS_PEDIDO[$estado] . ': ' . $cantidad) ?>">
                            <span class="barra-etiqueta"><?= escapar(ESTADOS_PEDIDO[$estado]) ?></span>
                            <span class="barra-pista"><span class="barra-relleno" style="width: <?= round($cantidad / $mayor_estado * 100) ?>%"></span></span>
                            <span class="barra-valor"><?= $cantidad ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="tarjeta">
                <h2>Menús más pedidos</h2>
                <?php if ($menus_mas_pedidos === []): ?>
                    <p class="texto-suave">Todavía no hay pedidos.</p>
                <?php else: ?>
                    <ul class="grafico-barras">
                        <?php foreach ($menus_mas_pedidos as $fila): ?>
                            <li title="<?= escapar($fila['nombre'] . ': ' . $fila['cantidad']) ?>">
                                <span class="barra-etiqueta"><?= escapar($fila['nombre']) ?></span>
                                <span class="barra-pista"><span class="barra-relleno" style="width: <?= round((int) $fila['cantidad'] / $mayor_menu * 100) ?>%"></span></span>
                                <span class="barra-valor"><?= (int) $fila['cantidad'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

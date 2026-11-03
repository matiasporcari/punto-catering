<?php
/**
 * Versión del presupuesto para imprimir o guardar como PDF desde el navegador.
 * Pueden verla la empresa dueña del pedido y los administradores.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

$usuario = requerir_sesion();
$id_pedido = entero_de($_GET, 'id');

// Un administrador ve cualquier pedido; una empresa, solo los propios.
$id_propietario = $usuario['rol'] === 'administrador' ? null : (int) $usuario['id_usuario'];
$pedido = obtener_pedido($id_pedido, $id_propietario);

if ($pedido === null) {
    establecer_mensaje('error', 'No encontramos ese pedido.');
    redirigir(pagina_inicio_de($usuario));
}

$detalle = obtener_detalle_pedido($id_pedido);
$pagina_volver = $usuario['rol'] === 'administrador' ? 'admin/pedido.php' : 'cuenta/pedido.php';
$es_precio_final = $pedido['total_final'] !== null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presupuesto N.º <?= $id_pedido ?> | <?= escapar(NOMBRE_SITIO) ?></title>
    <link rel="icon" href="<?= escapar(url('recursos/img/icono.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:opsz,wght@9..144,500;9..144,700&display=swap">
    <link rel="stylesheet" href="<?= escapar(url('recursos/css/estilos.css')) ?>">
    <script src="<?= escapar(url('recursos/js/principal.js')) ?>" defer></script>
</head>
<body class="pagina-impresion">
<div class="barra-impresion no-imprimir">
    <a href="<?= escapar(url($pagina_volver . '?id=' . $id_pedido)) ?>">← Volver al pedido</a>
    <button class="boton boton-primario" type="button" data-imprimir>Imprimir o guardar como PDF</button>
</div>

<main class="hoja-presupuesto">
    <header class="hoja-encabezado">
        <p class="logo">Punto Catering<span class="logo-punto">.</span></p>
        <div class="hoja-numero">
            <h1>Presupuesto N.º <?= $id_pedido ?></h1>
            <p>Emitido el <?= date('d/m/Y') ?> · <?= escapar(ESTADOS_PEDIDO[$pedido['estado']]) ?></p>
        </div>
    </header>

    <div class="hoja-columnas">
        <section>
            <h2>Empresa</h2>
            <p><strong><?= escapar($pedido['razon_social']) ?></strong><br>
                CUIT <?= escapar($pedido['cuit']) ?><br>
                <?= escapar($pedido['nombre_contacto']) ?><br>
                <?= escapar($pedido['correo']) ?> · <?= escapar($pedido['telefono']) ?></p>
        </section>
        <section>
            <h2>Evento</h2>
            <p><?= formato_fecha($pedido['fecha_evento']) ?> a las <?= formato_hora($pedido['hora_evento']) ?><br>
                <?= escapar($pedido['direccion_evento']) ?><br>
                <?= (int) $pedido['cantidad_personas'] ?> personas</p>
        </section>
    </div>

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
                    <td><strong><?= escapar($linea['nombre']) ?></strong><br><small><?= escapar($linea['descripcion']) ?></small></td>
                    <td class="celda-numero"><?= formato_moneda($linea['precio_unitario']) ?></td>
                    <td class="celda-numero"><?= formato_moneda((float) $linea['precio_unitario'] * (int) $pedido['cantidad_personas']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <?php if ($es_precio_final && (float) $pedido['total_final'] !== (float) $pedido['total_estimado']): ?>
                <tr>
                    <th scope="row" colspan="2">Subtotal</th>
                    <td class="celda-numero"><?= formato_moneda($pedido['total_estimado']) ?></td>
                </tr>
            <?php endif; ?>
            <tr class="hoja-total">
                <th scope="row" colspan="2"><?= $es_precio_final ? 'Precio final' : 'Total estimado' ?></th>
                <td class="celda-numero"><?= formato_moneda($pedido['total_final'] ?? $pedido['total_estimado']) ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if ($pedido['comentario_admin'] !== ''): ?>
        <p class="nota-admin"><?= escapar($pedido['comentario_admin']) ?></p>
    <?php endif; ?>

    <?php if (!$es_precio_final): ?>
        <p class="ayuda-campo">Importe orientativo: el precio final se confirma cuando revisamos el pedido.</p>
    <?php endif; ?>
</main>
</body>
</html>

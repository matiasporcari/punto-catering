<?php
/**
 * Formulario para pedir un presupuesto o editar un pedido todavía pendiente.
 *   presupuesto.php          - Pedido nuevo.
 *   presupuesto.php?menu=3   - Pedido nuevo con un menú ya elegido desde el catálogo.
 *   presupuesto.php?id=5     - Edición del pedido 5.
 * El precio estimado se actualiza en vivo con recursos/js/presupuesto.js.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/arranque.php';

$usuario = requerir_cliente();
$id_usuario = (int) $usuario['id_usuario'];
$scripts_pagina = ['presupuesto.js'];

$id_pedido = entero_de($_GET, 'id') ?: null;
$errores = [];
$ids_elegidos = [];
$datos = [
    'fecha_evento' => '',
    'hora_evento' => '',
    'direccion_evento' => '',
    'cantidad_personas' => '',
    'observaciones' => '',
];

// Al editar, se cargan los datos guardados y se verifica que el pedido se pueda modificar.
if ($id_pedido !== null) {
    $pedido = obtener_pedido($id_pedido, $id_usuario);

    if ($pedido === null || $pedido['estado'] !== 'pendiente') {
        establecer_mensaje('error', 'Ese pedido no se puede editar.');
        redirigir('cuenta/pedidos.php');
    }

    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = $pedido[$campo];
    }
    $datos['hora_evento'] = formato_hora($pedido['hora_evento']);
    $ids_elegidos = array_map(fn(array $fila): int => (int) $fila['id_menu'], obtener_detalle_pedido($id_pedido));
} elseif (entero_de($_GET, 'menu') > 0) {
    $ids_elegidos = [entero_de($_GET, 'menu')];
}

$ids_ya_guardados = $id_pedido !== null ? $ids_elegidos : [];
$menus_disponibles = obtener_menus_para_pedido($ids_ya_guardados);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'fecha_evento' => texto_post('fecha_evento'),
        'hora_evento' => texto_post('hora_evento'),
        'direccion_evento' => texto_post('direccion_evento'),
        'cantidad_personas' => entero_de($_POST, 'cantidad_personas'),
        'observaciones' => texto_post('observaciones'),
    ];

    // Solo se aceptan los menús disponibles; el resto de los valores enviados se descarta.
    $ids_elegidos = array_map('intval', is_array($_POST['menus'] ?? null) ? $_POST['menus'] : []);
    $menus_elegidos = array_values(array_filter(
        $menus_disponibles,
        fn(array $menu): bool => in_array((int) $menu['id_menu'], $ids_elegidos, true)
    ));

    $errores = validar_pedido($datos, $menus_elegidos);

    if ($errores === []) {
        $id_guardado = guardar_pedido($id_usuario, $datos, $menus_elegidos, $id_pedido);

        if ($id_guardado === null) {
            establecer_mensaje('error', 'El pedido ya fue revisado y no se puede editar.');
            redirigir('cuenta/pedidos.php');
        }

        establecer_mensaje('exito', $id_pedido === null
            ? 'Recibimos tu pedido. Te avisamos en esta página cuando el presupuesto esté listo.'
            : 'Guardamos los cambios de tu pedido.');
        redirigir('cuenta/pedido.php?id=' . $id_guardado);
    }
}

$titulo_pagina = $id_pedido === null ? 'Pedir presupuesto' : 'Editar pedido';

// Se agrupan los menús por categoría para mostrarlos ordenados.
$menus_por_categoria = [];
foreach ($menus_disponibles as $menu) {
    $menus_por_categoria[$menu['categoria']][] = $menu;
}

require __DIR__ . '/includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <h1><?= $id_pedido === null ? 'Pedir presupuesto' : 'Editar pedido N.º ' . $id_pedido ?></h1>
        <p class="bajada">Completá los datos y mirá el precio estimado a medida que elegís.</p>

        <form class="distribucion-presupuesto" id="formulario-presupuesto" method="post" novalidate>
            <?= campo_csrf() ?>

            <div class="presupuesto-columna">
                <fieldset class="tarjeta formulario">
                    <legend>1. Datos del evento</legend>
                    <div class="grilla-campos">
                        <div class="campo">
                            <label for="fecha_evento">Fecha</label>
                            <input type="date" id="fecha_evento" name="fecha_evento" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= escapar($datos['fecha_evento']) ?>" <?= atributos_error($errores, 'fecha_evento') ?>>
                            <?= error_campo($errores, 'fecha_evento') ?>
                        </div>
                        <div class="campo">
                            <label for="hora_evento">Hora</label>
                            <input type="time" id="hora_evento" name="hora_evento" required value="<?= escapar($datos['hora_evento']) ?>" <?= atributos_error($errores, 'hora_evento') ?>>
                            <?= error_campo($errores, 'hora_evento') ?>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="direccion_evento">Dirección</label>
                        <input id="direccion_evento" name="direccion_evento" required maxlength="200" autocomplete="street-address" value="<?= escapar($datos['direccion_evento']) ?>" <?= atributos_error($errores, 'direccion_evento') ?>>
                        <?= error_campo($errores, 'direccion_evento') ?>
                    </div>
                    <div class="campo">
                        <label for="cantidad_personas">Cantidad de personas</label>
                        <input type="number" id="cantidad_personas" name="cantidad_personas" required min="1" max="5000" inputmode="numeric" value="<?= escapar($datos['cantidad_personas'] ?: '') ?>" <?= atributos_error($errores, 'cantidad_personas') ?>>
                        <?= error_campo($errores, 'cantidad_personas') ?>
                    </div>
                    <div class="campo">
                        <label for="observaciones">Observaciones <span class="opcional">(opcional)</span></label>
                        <textarea id="observaciones" name="observaciones" rows="3" maxlength="500" placeholder="Dietas especiales, acceso al edificio, etc." <?= atributos_error($errores, 'observaciones') ?>><?= escapar($datos['observaciones']) ?></textarea>
                        <?= error_campo($errores, 'observaciones') ?>
                    </div>
                </fieldset>

                <fieldset class="tarjeta formulario">
                    <legend>2. Elegí los menús</legend>
                    <?= error_campo($errores, 'menus') ?>
                    <?php foreach ($menus_por_categoria as $nombre_categoria => $menus_categoria): ?>
                        <h3 class="titulo-grupo"><?= escapar($nombre_categoria) ?></h3>
                        <div class="lista-opciones">
                            <?php foreach ($menus_categoria as $menu): ?>
                                <label class="opcion-menu">
                                    <input
                                        type="checkbox"
                                        name="menus[]"
                                        value="<?= (int) $menu['id_menu'] ?>"
                                        data-nombre="<?= escapar($menu['nombre']) ?>"
                                        data-precio="<?= escapar($menu['precio_por_persona']) ?>"
                                        data-minimo="<?= (int) $menu['minimo_personas'] ?>"
                                        <?= in_array((int) $menu['id_menu'], $ids_elegidos, true) ? 'checked' : '' ?>>
                                    <span class="opcion-menu-texto">
                                        <strong><?= escapar($menu['nombre']) ?></strong>
                                        <small><?= escapar($menu['descripcion']) ?></small>
                                        <small class="opcion-menu-minimo">Desde <?= (int) $menu['minimo_personas'] ?> personas</small>
                                    </span>
                                    <span class="opcion-menu-precio"><?= formato_moneda($menu['precio_por_persona']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            </div>

            <aside class="tarjeta resumen-presupuesto" aria-live="polite">
                <h2>Tu presupuesto</h2>
                <ul class="resumen-lista" id="resumen-lista">
                    <li class="texto-suave">Todavía no elegiste menús.</li>
                </ul>
                <p class="resumen-personas"><span>Personas</span> <strong id="resumen-personas">0</strong></p>
                <p class="resumen-total"><span>Total estimado</span> <strong id="resumen-total">$ 0</strong></p>
                <p class="mensaje mensaje-aviso oculto" id="aviso-minimo"></p>
                <p class="ayuda-campo">Es un valor orientativo. El precio final aparece en tu cuenta cuando revisamos el pedido.</p>
                <button class="boton boton-primario boton-ancho" type="submit"><?= $id_pedido === null ? 'Enviar mi pedido' : 'Guardar los cambios' ?></button>
                <?php if ($id_pedido !== null): ?>
                    <a class="boton boton-claro boton-ancho" href="<?= escapar(url('cuenta/pedido.php?id=' . $id_pedido)) ?>">Volver sin guardar</a>
                <?php endif; ?>
            </aside>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/pie.php'; ?>

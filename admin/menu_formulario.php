<?php
/**
 * Administración: formulario para crear un menú o modificar uno existente (con ?id=).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

requerir_administrador();

$seccion_admin = 'menus';
$conexion = obtener_conexion();
$categorias = obtener_categorias();
$id_menu = entero_de($_GET, 'id') ?: null;
$errores = [];

$datos = [
    'id_categoria' => '',
    'nombre' => '',
    'descripcion' => '',
    'precio_por_persona' => '',
    'minimo_personas' => '10',
    'ilustracion' => 'buffet',
    'es_vegetariano' => 0,
    'es_sin_tacc' => 0,
];

// Al editar, se cargan los valores guardados.
if ($id_menu !== null) {
    $consulta = $conexion->prepare('SELECT * FROM menus WHERE id_menu = ?');
    $consulta->execute([$id_menu]);
    $menu = $consulta->fetch();

    if ($menu === false) {
        establecer_mensaje('error', 'No encontramos ese menú.');
        redirigir('admin/menus.php');
    }

    $datos = array_intersect_key($menu, $datos);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'id_categoria' => entero_de($_POST, 'id_categoria'),
        'nombre' => texto_post('nombre'),
        'descripcion' => texto_post('descripcion'),
        'precio_por_persona' => str_replace(',', '.', texto_post('precio_por_persona')),
        'minimo_personas' => entero_de($_POST, 'minimo_personas'),
        'ilustracion' => texto_post('ilustracion'),
        'es_vegetariano' => isset($_POST['es_vegetariano']) ? 1 : 0,
        'es_sin_tacc' => isset($_POST['es_sin_tacc']) ? 1 : 0,
    ];

    $ids_categorias = array_map(fn(array $categoria): int => (int) $categoria['id_categoria'], $categorias);

    if (!in_array($datos['id_categoria'], $ids_categorias, true)) {
        $errores['id_categoria'] = 'Elegí una categoría.';
    }
    if (mb_strlen($datos['nombre']) < 3 || mb_strlen($datos['nombre']) > 100) {
        $errores['nombre'] = 'Ingresá un nombre (entre 3 y 100 caracteres).';
    }
    if (mb_strlen($datos['descripcion']) < 10 || mb_strlen($datos['descripcion']) > 500) {
        $errores['descripcion'] = 'Ingresá una descripción (entre 10 y 500 caracteres).';
    }
    if (!is_numeric($datos['precio_por_persona']) || (float) $datos['precio_por_persona'] <= 0 || (float) $datos['precio_por_persona'] >= 100000000) {
        $errores['precio_por_persona'] = 'Ingresá un precio válido, mayor a cero.';
    }
    if ($datos['minimo_personas'] < 1 || $datos['minimo_personas'] > 5000) {
        $errores['minimo_personas'] = 'Ingresá un mínimo entre 1 y 5000 personas.';
    }
    if (!isset(ILUSTRACIONES_MENU[$datos['ilustracion']])) {
        $errores['ilustracion'] = 'Elegí una ilustración.';
    }

    if ($errores === []) {
        $valores = [
            $datos['id_categoria'],
            $datos['nombre'],
            $datos['descripcion'],
            $datos['precio_por_persona'],
            $datos['minimo_personas'],
            $datos['ilustracion'],
            $datos['es_vegetariano'],
            $datos['es_sin_tacc'],
        ];

        if ($id_menu === null) {
            $conexion->prepare(
                'INSERT INTO menus (id_categoria, nombre, descripcion, precio_por_persona, minimo_personas, ilustracion, es_vegetariano, es_sin_tacc)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute($valores);
            establecer_mensaje('exito', 'Se agregó el menú.');
        } else {
            $conexion->prepare(
                'UPDATE menus
                 SET id_categoria = ?, nombre = ?, descripcion = ?, precio_por_persona = ?, minimo_personas = ?, ilustracion = ?, es_vegetariano = ?, es_sin_tacc = ?
                 WHERE id_menu = ?'
            )->execute([...$valores, $id_menu]);
            establecer_mensaje('exito', 'Se guardaron los cambios del menú.');
        }

        redirigir('admin/menus.php');
    }
}

$titulo_pagina = $id_menu === null ? 'Agregar menú' : 'Editar menú';

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor contenedor-angosto">
        <p><a href="<?= escapar(url('admin/menus.php')) ?>">← Volver a los menús</a></p>
        <h1><?= escapar($titulo_pagina) ?></h1>

        <form class="tarjeta formulario" method="post" novalidate>
            <?= campo_csrf() ?>
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" required maxlength="100" value="<?= escapar($datos['nombre']) ?>" <?= atributos_error($errores, 'nombre') ?>>
                <?= error_campo($errores, 'nombre') ?>
            </div>
            <div class="grilla-campos">
                <div class="campo">
                    <label for="id_categoria">Categoría</label>
                    <select id="id_categoria" name="id_categoria" required <?= atributos_error($errores, 'id_categoria') ?>>
                        <option value="">Elegir…</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id_categoria'] ?>" <?= (int) $datos['id_categoria'] === (int) $categoria['id_categoria'] ? 'selected' : '' ?>><?= escapar($categoria['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= error_campo($errores, 'id_categoria') ?>
                </div>
                <div class="campo">
                    <label for="ilustracion">Ilustración</label>
                    <select id="ilustracion" name="ilustracion" <?= atributos_error($errores, 'ilustracion') ?>>
                        <?php foreach (ILUSTRACIONES_MENU as $valor => $texto): ?>
                            <option value="<?= escapar($valor) ?>" <?= $valor === $datos['ilustracion'] ? 'selected' : '' ?>><?= escapar($texto) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= error_campo($errores, 'ilustracion') ?>
                </div>
                <div class="campo">
                    <label for="precio_por_persona">Precio por persona ($)</label>
                    <input type="number" id="precio_por_persona" name="precio_por_persona" required min="1" step="1" value="<?= escapar($datos['precio_por_persona']) ?>" <?= atributos_error($errores, 'precio_por_persona') ?>>
                    <?= error_campo($errores, 'precio_por_persona') ?>
                </div>
                <div class="campo">
                    <label for="minimo_personas">Mínimo de personas</label>
                    <input type="number" id="minimo_personas" name="minimo_personas" required min="1" max="5000" value="<?= escapar($datos['minimo_personas']) ?>" <?= atributos_error($errores, 'minimo_personas') ?>>
                    <?= error_campo($errores, 'minimo_personas') ?>
                </div>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="4" required maxlength="500" <?= atributos_error($errores, 'descripcion') ?>><?= escapar($datos['descripcion']) ?></textarea>
                <?= error_campo($errores, 'descripcion') ?>
            </div>
            <div class="grupo-casillas">
                <label class="casilla"><input type="checkbox" name="es_vegetariano" value="1" <?= $datos['es_vegetariano'] ? 'checked' : '' ?>> Vegetariano</label>
                <label class="casilla"><input type="checkbox" name="es_sin_tacc" value="1" <?= $datos['es_sin_tacc'] ? 'checked' : '' ?>> Sin TACC</label>
            </div>
            <button class="boton boton-primario" type="submit"><?= $id_menu === null ? 'Agregar menú' : 'Guardar los cambios' ?></button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

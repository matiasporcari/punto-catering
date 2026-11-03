<?php
/**
 * Administración: alta, modificación y baja de categorías en una sola página.
 * Con ?editar=ID, el formulario se carga con los datos de esa categoría.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

requerir_administrador();

$titulo_pagina = 'Categorías';
$seccion_admin = 'categorias';
$conexion = obtener_conexion();
$errores = [];
$id_editando = entero_de($_GET, 'editar') ?: null;
$datos = ['nombre' => '', 'descripcion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (texto_post('accion') === 'eliminar') {
        $id_categoria = entero_de($_POST, 'id_categoria');

        // Una categoría con menús no se puede borrar, para no dejarlos sin categoría.
        $con_menus = $conexion->prepare('SELECT COUNT(*) FROM menus WHERE id_categoria = ?');
        $con_menus->execute([$id_categoria]);

        if ((int) $con_menus->fetchColumn() > 0) {
            establecer_mensaje('error', 'Esa categoría tiene menús. Movelos a otra antes de eliminarla.');
        } else {
            $conexion->prepare('DELETE FROM categorias WHERE id_categoria = ?')->execute([$id_categoria]);
            establecer_mensaje('exito', 'Se eliminó la categoría.');
        }
        redirigir('admin/categorias.php');
    }

    // Alta o modificación.
    $id_editando = entero_de($_POST, 'id_categoria') ?: null;
    $datos = ['nombre' => texto_post('nombre'), 'descripcion' => texto_post('descripcion')];

    if (mb_strlen($datos['nombre']) < 3 || mb_strlen($datos['nombre']) > 60) {
        $errores['nombre'] = 'Ingresá un nombre (entre 3 y 60 caracteres).';
    } else {
        $repetida = $conexion->prepare('SELECT COUNT(*) FROM categorias WHERE nombre = ? AND id_categoria <> ?');
        $repetida->execute([$datos['nombre'], $id_editando ?? 0]);

        if ((int) $repetida->fetchColumn() > 0) {
            $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
        }
    }
    if (mb_strlen($datos['descripcion']) > 255) {
        $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
    }

    if ($errores === []) {
        if ($id_editando === null) {
            $conexion->prepare('INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)')
                ->execute([$datos['nombre'], $datos['descripcion']]);
            establecer_mensaje('exito', 'Se agregó la categoría.');
        } else {
            $conexion->prepare('UPDATE categorias SET nombre = ?, descripcion = ? WHERE id_categoria = ?')
                ->execute([$datos['nombre'], $datos['descripcion'], $id_editando]);
            establecer_mensaje('exito', 'Se guardaron los cambios.');
        }
        redirigir('admin/categorias.php');
    }
} elseif ($id_editando !== null) {
    $consulta = $conexion->prepare('SELECT nombre, descripcion FROM categorias WHERE id_categoria = ?');
    $consulta->execute([$id_editando]);
    $datos = $consulta->fetch() ?: $datos;
}

// Listado con la cantidad de menús de cada categoría.
$categorias = $conexion->query(
    'SELECT c.*, COUNT(m.id_menu) AS cantidad_menus
     FROM categorias c
     LEFT JOIN menus m ON m.id_categoria = c.id_categoria
     GROUP BY c.id_categoria
     ORDER BY c.nombre'
)->fetchAll();

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <h1>Categorías</h1>

        <div class="distribucion-dos-columnas">
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Descripción</th>
                            <th scope="col" class="celda-numero">Menús</th>
                            <th scope="col"><span class="solo-lector">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?= escapar($categoria['nombre']) ?></td>
                                <td><?= escapar($categoria['descripcion']) ?></td>
                                <td class="celda-numero"><?= (int) $categoria['cantidad_menus'] ?></td>
                                <td>
                                    <div class="acciones-fila">
                                        <a href="<?= escapar(url('admin/categorias.php?editar=' . $categoria['id_categoria'])) ?>">Editar</a>
                                        <form method="post" data-confirmar="¿Eliminar la categoría «<?= escapar($categoria['nombre']) ?>»?">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_categoria" value="<?= (int) $categoria['id_categoria'] ?>">
                                            <button class="enlace-boton enlace-peligro" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form class="tarjeta formulario" method="post" novalidate>
                <?= campo_csrf() ?>
                <input type="hidden" name="accion" value="guardar">
                <?php if ($id_editando !== null): ?>
                    <input type="hidden" name="id_categoria" value="<?= $id_editando ?>">
                <?php endif; ?>
                <h2><?= $id_editando === null ? 'Agregar categoría' : 'Editar categoría' ?></h2>
                <div class="campo">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" required maxlength="60" value="<?= escapar($datos['nombre']) ?>" <?= atributos_error($errores, 'nombre') ?>>
                    <?= error_campo($errores, 'nombre') ?>
                </div>
                <div class="campo">
                    <label for="descripcion">Descripción <span class="opcional">(opcional)</span></label>
                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="255" <?= atributos_error($errores, 'descripcion') ?>><?= escapar($datos['descripcion']) ?></textarea>
                    <?= error_campo($errores, 'descripcion') ?>
                </div>
                <div class="grupo-botones">
                    <button class="boton boton-primario" type="submit"><?= $id_editando === null ? 'Agregar' : 'Guardar los cambios' ?></button>
                    <?php if ($id_editando !== null): ?>
                        <a class="boton boton-claro" href="<?= escapar(url('admin/categorias.php')) ?>">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

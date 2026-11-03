<?php
/**
 * Administración: lista de menús, con acciones de ocultar, mostrar y eliminar.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

requerir_administrador();

$titulo_pagina = 'Menús';
$seccion_admin = 'menus';
$conexion = obtener_conexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_menu = entero_de($_POST, 'id_menu');
    $accion = texto_post('accion');

    if ($accion === 'alternar') {
        // Invierte el valor actual: un menú oculto no aparece en el catálogo.
        $conexion->prepare('UPDATE menus SET activo = 1 - activo WHERE id_menu = ?')->execute([$id_menu]);
        establecer_mensaje('exito', 'Se actualizó la visibilidad del menú.');
    } elseif ($accion === 'eliminar') {
        // Un menú usado en pedidos no se borra, para no alterar el historial.
        $en_uso = $conexion->prepare('SELECT COUNT(*) FROM detalle_pedido WHERE id_menu = ?');
        $en_uso->execute([$id_menu]);

        if ((int) $en_uso->fetchColumn() > 0) {
            establecer_mensaje('error', 'Ese menú figura en pedidos y no se puede eliminar. Podés ocultarlo del catálogo.');
        } else {
            $conexion->prepare('DELETE FROM menus WHERE id_menu = ?')->execute([$id_menu]);
            establecer_mensaje('exito', 'Se eliminó el menú.');
        }
    }

    redirigir('admin/menus.php');
}

$menus = $conexion->query(
    'SELECT m.*, c.nombre AS categoria
     FROM menus m
     INNER JOIN categorias c ON c.id_categoria = m.id_categoria
     ORDER BY c.nombre, m.nombre'
)->fetchAll();

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <div class="titulo-con-enlace">
            <h1>Menús</h1>
            <a class="boton boton-primario" href="<?= escapar(url('admin/menu_formulario.php')) ?>">Agregar un menú</a>
        </div>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Categoría</th>
                        <th scope="col" class="celda-numero">Por persona</th>
                        <th scope="col" class="celda-numero">Mínimo</th>
                        <th scope="col">Catálogo</th>
                        <th scope="col"><span class="solo-lector">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menus as $menu): ?>
                        <tr>
                            <td><?= escapar($menu['nombre']) ?></td>
                            <td><?= escapar($menu['categoria']) ?></td>
                            <td class="celda-numero celda-sin-corte"><?= formato_moneda($menu['precio_por_persona']) ?></td>
                            <td class="celda-numero"><?= (int) $menu['minimo_personas'] ?></td>
                            <td><?= $menu['activo'] ? 'Visible' : '<span class="texto-suave">Oculto</span>' ?></td>
                            <td>
                                <div class="acciones-fila">
                                    <a href="<?= escapar(url('admin/menu_formulario.php?id=' . $menu['id_menu'])) ?>">Editar</a>
                                    <form method="post">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                                        <input type="hidden" name="accion" value="alternar">
                                        <button class="enlace-boton" type="submit"><?= $menu['activo'] ? 'Ocultar' : 'Mostrar' ?></button>
                                    </form>
                                    <form method="post" data-confirmar="¿Eliminar el menú «<?= escapar($menu['nombre']) ?>»?">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="id_menu" value="<?= (int) $menu['id_menu'] ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <button class="enlace-boton enlace-peligro" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

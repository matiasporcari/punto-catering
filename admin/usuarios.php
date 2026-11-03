<?php
/**
 * Administración: lista de usuarios, con acciones de activar, desactivar y eliminar.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

$administrador = requerir_administrador();

$titulo_pagina = 'Usuarios';
$seccion_admin = 'usuarios';
$conexion = obtener_conexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = entero_de($_POST, 'id_usuario');
    $accion = texto_post('accion');

    if ($id_usuario === (int) $administrador['id_usuario']) {
        // Se evita que el administrador se bloquee o se elimine a sí mismo.
        establecer_mensaje('error', 'No podés modificar tu propia cuenta desde esta pantalla.');
    } elseif ($accion === 'alternar') {
        $conexion->prepare('UPDATE usuarios SET activo = 1 - activo WHERE id_usuario = ?')->execute([$id_usuario]);
        establecer_mensaje('exito', 'Se actualizó el estado del usuario.');
    } elseif ($accion === 'eliminar') {
        // Un usuario con pedidos no se borra, para conservar el historial.
        $con_pedidos = $conexion->prepare('SELECT COUNT(*) FROM pedidos WHERE id_usuario = ?');
        $con_pedidos->execute([$id_usuario]);

        if ((int) $con_pedidos->fetchColumn() > 0) {
            establecer_mensaje('error', 'Ese usuario tiene pedidos y no se puede eliminar. Podés desactivarlo.');
        } else {
            $conexion->prepare('DELETE FROM usuarios WHERE id_usuario = ?')->execute([$id_usuario]);
            establecer_mensaje('exito', 'Se eliminó el usuario.');
        }
    }

    redirigir('admin/usuarios.php');
}

$usuarios = $conexion->query(
    'SELECT u.*, COUNT(p.id_pedido) AS cantidad_pedidos
     FROM usuarios u
     LEFT JOIN pedidos p ON p.id_usuario = u.id_usuario
     GROUP BY u.id_usuario
     ORDER BY u.rol, u.razon_social'
)->fetchAll();

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <h1>Usuarios</h1>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Razón social</th>
                        <th scope="col">CUIT</th>
                        <th scope="col">Contacto</th>
                        <th scope="col">Rol</th>
                        <th scope="col" class="celda-numero">Pedidos</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="solo-lector">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $fila): ?>
                        <tr>
                            <td><?= escapar($fila['razon_social']) ?></td>
                            <td class="celda-sin-corte"><?= escapar($fila['cuit']) ?></td>
                            <td><?= escapar($fila['nombre_contacto']) ?><br><small class="texto-suave"><?= escapar($fila['correo']) ?></small></td>
                            <td><?= $fila['rol'] === 'administrador' ? 'Administrador' : 'Empresa' ?></td>
                            <td class="celda-numero"><?= (int) $fila['cantidad_pedidos'] ?></td>
                            <td><?= $fila['activo'] ? 'Activo' : '<span class="texto-suave">Inactivo</span>' ?></td>
                            <td>
                                <?php if ((int) $fila['id_usuario'] !== (int) $administrador['id_usuario']): ?>
                                    <div class="acciones-fila">
                                        <form method="post">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="id_usuario" value="<?= (int) $fila['id_usuario'] ?>">
                                            <input type="hidden" name="accion" value="alternar">
                                            <button class="enlace-boton" type="submit"><?= $fila['activo'] ? 'Desactivar' : 'Activar' ?></button>
                                        </form>
                                        <form method="post" data-confirmar="¿Eliminar a «<?= escapar($fila['razon_social']) ?>»?">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="id_usuario" value="<?= (int) $fila['id_usuario'] ?>">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <button class="enlace-boton enlace-peligro" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="texto-suave">Tu cuenta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

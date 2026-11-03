<?php
/**
 * Perfil de la empresa: modificar sus datos y cambiar la contraseña.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

$usuario = requerir_cliente();
$id_usuario = (int) $usuario['id_usuario'];
$titulo_pagina = 'Mi perfil';

$errores = [];
$errores_contrasena = [];
$datos = array_intersect_key($usuario, array_flip(['razon_social', 'cuit', 'nombre_contacto', 'correo', 'telefono']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = obtener_conexion();

    if (texto_post('accion') === 'datos') {
        // Actualización de los datos de la empresa.
        foreach (array_keys($datos) as $campo) {
            $datos[$campo] = texto_post($campo);
        }

        $errores = validar_datos_empresa($datos);

        // El correo y el CUIT no pueden coincidir con los de otra cuenta.
        if ($errores === []) {
            $existentes = $conexion->prepare('SELECT correo, cuit FROM usuarios WHERE (correo = ? OR cuit = ?) AND id_usuario <> ?');
            $existentes->execute([$datos['correo'], $datos['cuit'], $id_usuario]);

            foreach ($existentes->fetchAll() as $existente) {
                if (mb_strtolower($existente['correo']) === mb_strtolower($datos['correo'])) {
                    $errores['correo'] = 'Ya hay otra cuenta con ese correo.';
                }
                if ($existente['cuit'] === $datos['cuit']) {
                    $errores['cuit'] = 'Ya hay otra cuenta con ese CUIT.';
                }
            }
        }

        if ($errores === []) {
            $actualizacion = $conexion->prepare(
                'UPDATE usuarios SET razon_social = ?, cuit = ?, nombre_contacto = ?, correo = ?, telefono = ? WHERE id_usuario = ?'
            );
            $actualizacion->execute([
                $datos['razon_social'],
                $datos['cuit'],
                $datos['nombre_contacto'],
                $datos['correo'],
                $datos['telefono'],
                $id_usuario,
            ]);

            establecer_mensaje('exito', 'Guardamos tus datos.');
            redirigir('cuenta/perfil.php');
        }
    } elseif (texto_post('accion') === 'contrasena') {
        // Cambio de contraseña: se pide la actual para confirmar la identidad.
        $actual = $_POST['contrasena_actual'] ?? '';
        $nueva = $_POST['contrasena_nueva'] ?? '';
        $repeticion = $_POST['repetir_contrasena'] ?? '';

        if (!is_string($actual) || !password_verify($actual, $usuario['contrasena_hash'])) {
            $errores_contrasena['contrasena_actual'] = 'La contraseña actual no es correcta.';
        }

        $error_nueva = validar_contrasena_nueva(is_string($nueva) ? $nueva : '', is_string($repeticion) ? $repeticion : '');
        if ($error_nueva !== null) {
            $errores_contrasena['contrasena_nueva'] = $error_nueva;
        }

        if ($errores_contrasena === []) {
            $actualizacion = $conexion->prepare('UPDATE usuarios SET contrasena_hash = ? WHERE id_usuario = ?');
            $actualizacion->execute([password_hash($nueva, PASSWORD_DEFAULT), $id_usuario]);

            establecer_mensaje('exito', 'Cambiamos tu contraseña.');
            redirigir('cuenta/perfil.php');
        }
    }
}

require __DIR__ . '/../includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor contenedor-angosto">
        <h1>Mi perfil</h1>

        <form class="tarjeta formulario" method="post" novalidate>
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="datos">
            <h2>Datos de la empresa</h2>
            <div class="grilla-campos">
                <div class="campo">
                    <label for="razon_social">Razón social</label>
                    <input id="razon_social" name="razon_social" required maxlength="120" value="<?= escapar($datos['razon_social']) ?>" <?= atributos_error($errores, 'razon_social') ?>>
                    <?= error_campo($errores, 'razon_social') ?>
                </div>
                <div class="campo">
                    <label for="cuit">CUIT</label>
                    <input id="cuit" name="cuit" required maxlength="13" value="<?= escapar($datos['cuit']) ?>" <?= atributos_error($errores, 'cuit') ?>>
                    <?= error_campo($errores, 'cuit') ?>
                </div>
                <div class="campo">
                    <label for="nombre_contacto">Persona de contacto</label>
                    <input id="nombre_contacto" name="nombre_contacto" required maxlength="100" value="<?= escapar($datos['nombre_contacto']) ?>" <?= atributos_error($errores, 'nombre_contacto') ?>>
                    <?= error_campo($errores, 'nombre_contacto') ?>
                </div>
                <div class="campo">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" required maxlength="30" value="<?= escapar($datos['telefono']) ?>" <?= atributos_error($errores, 'telefono') ?>>
                    <?= error_campo($errores, 'telefono') ?>
                </div>
            </div>
            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" required maxlength="150" value="<?= escapar($datos['correo']) ?>" <?= atributos_error($errores, 'correo') ?>>
                <?= error_campo($errores, 'correo') ?>
            </div>
            <button class="boton boton-primario" type="submit">Guardar mis datos</button>
        </form>

        <form class="tarjeta formulario" method="post" novalidate>
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="contrasena">
            <h2>Cambiar contraseña</h2>
            <div class="campo">
                <label for="contrasena_actual">Contraseña actual</label>
                <input type="password" id="contrasena_actual" name="contrasena_actual" required autocomplete="current-password" <?= atributos_error($errores_contrasena, 'contrasena_actual') ?>>
                <?= error_campo($errores_contrasena, 'contrasena_actual') ?>
            </div>
            <div class="grilla-campos">
                <div class="campo">
                    <label for="contrasena_nueva">Contraseña nueva</label>
                    <input type="password" id="contrasena_nueva" name="contrasena_nueva" required minlength="8" autocomplete="new-password" <?= atributos_error($errores_contrasena, 'contrasena_nueva') ?>>
                    <?= error_campo($errores_contrasena, 'contrasena_nueva') ?>
                </div>
                <div class="campo">
                    <label for="repetir_contrasena">Repetir contraseña nueva</label>
                    <input type="password" id="repetir_contrasena" name="repetir_contrasena" required autocomplete="new-password">
                </div>
            </div>
            <button class="boton boton-secundario" type="submit">Cambiar mi contraseña</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>

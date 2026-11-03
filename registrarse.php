<?php
/**
 * Registro de una empresa nueva. Al terminar, la sesión queda iniciada.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/arranque.php';

$usuario = usuario_actual();
if ($usuario !== null) {
    redirigir(pagina_inicio_de($usuario));
}

$titulo_pagina = 'Registrar empresa';
$errores = [];
$datos = ['razon_social' => '', 'cuit' => '', 'nombre_contacto' => '', 'correo' => '', 'telefono' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = texto_post($campo);
    }

    $contrasena = $_POST['contrasena'] ?? '';
    $repeticion = $_POST['repetir_contrasena'] ?? '';
    $contrasena = is_string($contrasena) ? $contrasena : '';
    $repeticion = is_string($repeticion) ? $repeticion : '';

    $errores = validar_datos_empresa($datos);

    $error_contrasena = validar_contrasena_nueva($contrasena, $repeticion);
    if ($error_contrasena !== null) {
        $errores['contrasena'] = $error_contrasena;
    }

    if ($errores === []) {
        $conexion = obtener_conexion();

        // Tanto el correo como el CUIT deben ser únicos.
        $existentes = $conexion->prepare('SELECT correo, cuit FROM usuarios WHERE correo = ? OR cuit = ?');
        $existentes->execute([$datos['correo'], $datos['cuit']]);

        foreach ($existentes->fetchAll() as $existente) {
            if (mb_strtolower($existente['correo']) === mb_strtolower($datos['correo'])) {
                $errores['correo'] = 'Ya hay una cuenta con ese correo.';
            }
            if ($existente['cuit'] === $datos['cuit']) {
                $errores['cuit'] = 'Ya hay una cuenta con ese CUIT.';
            }
        }
    }

    if ($errores === []) {
        $insercion = $conexion->prepare(
            'INSERT INTO usuarios (razon_social, cuit, nombre_contacto, correo, telefono, contrasena_hash)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insercion->execute([
            $datos['razon_social'],
            $datos['cuit'],
            $datos['nombre_contacto'],
            $datos['correo'],
            $datos['telefono'],
            password_hash($contrasena, PASSWORD_DEFAULT),
        ]);

        iniciar_sesion_de_usuario((int) $conexion->lastInsertId());
        establecer_mensaje('exito', '¡Bienvenidos! Ya podés pedir tu primer presupuesto.');
        redirigir_tras_ingreso('cliente', 'cuenta/pedidos.php');
    }
}

require __DIR__ . '/includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor contenedor-angosto">
        <h1>Registrar mi empresa</h1>
        <p class="bajada">Con la cuenta podés pedir presupuestos y seguir cada pedido.</p>

        <form class="tarjeta formulario" method="post" novalidate>
            <?= campo_csrf() ?>
            <div class="grilla-campos">
                <div class="campo">
                    <label for="razon_social">Razón social</label>
                    <input id="razon_social" name="razon_social" required maxlength="120" autocomplete="organization" value="<?= escapar($datos['razon_social']) ?>" <?= atributos_error($errores, 'razon_social') ?>>
                    <?= error_campo($errores, 'razon_social') ?>
                </div>
                <div class="campo">
                    <label for="cuit">CUIT</label>
                    <input id="cuit" name="cuit" required maxlength="13" placeholder="30-12345678-9" value="<?= escapar($datos['cuit']) ?>" <?= atributos_error($errores, 'cuit') ?>>
                    <?= error_campo($errores, 'cuit') ?>
                </div>
                <div class="campo">
                    <label for="nombre_contacto">Persona de contacto</label>
                    <input id="nombre_contacto" name="nombre_contacto" required maxlength="100" autocomplete="name" value="<?= escapar($datos['nombre_contacto']) ?>" <?= atributos_error($errores, 'nombre_contacto') ?>>
                    <?= error_campo($errores, 'nombre_contacto') ?>
                </div>
                <div class="campo">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" required maxlength="30" autocomplete="tel" value="<?= escapar($datos['telefono']) ?>" <?= atributos_error($errores, 'telefono') ?>>
                    <?= error_campo($errores, 'telefono') ?>
                </div>
            </div>
            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" required maxlength="150" autocomplete="email" value="<?= escapar($datos['correo']) ?>" <?= atributos_error($errores, 'correo') ?>>
                <?= error_campo($errores, 'correo') ?>
            </div>
            <div class="grilla-campos">
                <div class="campo">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" required minlength="8" autocomplete="new-password" <?= atributos_error($errores, 'contrasena') ?>>
                    <p class="ayuda-campo">Mínimo 8 caracteres.</p>
                    <?= error_campo($errores, 'contrasena') ?>
                </div>
                <div class="campo">
                    <label for="repetir_contrasena">Repetir contraseña</label>
                    <input type="password" id="repetir_contrasena" name="repetir_contrasena" required autocomplete="new-password">
                </div>
            </div>
            <button class="boton boton-primario boton-ancho" type="submit">Crear mi cuenta</button>
        </form>

        <p class="texto-centrado">¿Ya tenés cuenta? <a href="<?= escapar(url('ingresar.php')) ?>">Ingresá</a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/pie.php'; ?>

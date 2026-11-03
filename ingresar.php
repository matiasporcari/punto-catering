<?php
/**
 * Inicio de sesión de empresas y administradores.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/arranque.php';

// Quien ya inició sesión no necesita volver a ingresar.
$usuario = usuario_actual();
if ($usuario !== null) {
    redirigir(pagina_inicio_de($usuario));
}

$titulo_pagina = 'Ingresar';
$error = null;
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = texto_post('correo');
    $contrasena = $_POST['contrasena'] ?? '';

    $consulta = obtener_conexion()->prepare('SELECT * FROM usuarios WHERE correo = ?');
    $consulta->execute([$correo]);
    $usuario = $consulta->fetch();

    if ($usuario === false || !is_string($contrasena) || !password_verify($contrasena, $usuario['contrasena_hash'])) {
        // El mensaje es el mismo en ambos casos, para no revelar qué correos están registrados.
        $error = 'El correo o la contraseña no son correctos.';
    } elseif (!$usuario['activo']) {
        $error = 'Tu cuenta está desactivada. Escribinos desde la página de contacto.';
    } else {
        iniciar_sesion_de_usuario((int) $usuario['id_usuario']);
        redirigir_tras_ingreso($usuario['rol'], pagina_inicio_de($usuario));
    }
}

require __DIR__ . '/includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor contenedor-mini">
        <h1>Ingresar</h1>
        <p class="bajada">Accedé a tus pedidos y presupuestos.</p>

        <?php if ($error !== null): ?>
            <div class="mensaje mensaje-error" role="alert"><?= escapar($error) ?></div>
        <?php endif; ?>

        <form class="tarjeta formulario" method="post">
            <?= campo_csrf() ?>
            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" required autocomplete="email" value="<?= escapar($correo) ?>">
            </div>
            <div class="campo">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required autocomplete="current-password">
            </div>
            <button class="boton boton-primario boton-ancho" type="submit">Ingresar</button>
        </form>

        <p class="texto-centrado">¿Tu empresa todavía no tiene cuenta? <a href="<?= escapar(url('registrarse.php')) ?>">Registrala gratis</a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/pie.php'; ?>

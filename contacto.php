<?php
/**
 * Formulario de contacto: guarda la consulta para que la responda el administrador.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/arranque.php';

$titulo_pagina = 'Contacto';
$errores = [];
$datos = ['nombre' => '', 'empresa' => '', 'correo' => '', 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = texto_post($campo);
    }

    if (mb_strlen($datos['nombre']) < 2 || mb_strlen($datos['nombre']) > 100) {
        $errores['nombre'] = 'Ingresá tu nombre.';
    }
    if (mb_strlen($datos['empresa']) > 120) {
        $errores['empresa'] = 'El nombre de la empresa es demasiado largo.';
    }
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($datos['correo']) > 150) {
        $errores['correo'] = 'Ingresá un correo electrónico válido.';
    }
    if (mb_strlen($datos['mensaje']) < 10 || mb_strlen($datos['mensaje']) > 1000) {
        $errores['mensaje'] = 'Escribí tu consulta (entre 10 y 1000 caracteres).';
    }

    if ($errores === []) {
        $consulta = obtener_conexion()->prepare('INSERT INTO consultas (nombre, empresa, correo, mensaje) VALUES (?, ?, ?, ?)');
        $consulta->execute([$datos['nombre'], $datos['empresa'], $datos['correo'], $datos['mensaje']]);

        establecer_mensaje('exito', 'Recibimos tu consulta. Te vamos a responder por correo.');
        redirigir('contacto.php');
    }
}

// Si hay una sesión iniciada, se completan los datos conocidos para ahorrar tiempo.
$usuario = usuario_actual();
if ($usuario !== null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $datos['nombre'] = $usuario['nombre_contacto'];
    $datos['empresa'] = $usuario['razon_social'];
    $datos['correo'] = $usuario['correo'];
}

require __DIR__ . '/includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor contenedor-angosto">
        <h1>Contacto</h1>
        <p class="bajada">¿Necesitás algo a medida? Contanos y te respondemos por correo.</p>

        <form class="tarjeta formulario" method="post" novalidate>
            <?= campo_csrf() ?>
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" required maxlength="100" autocomplete="name" value="<?= escapar($datos['nombre']) ?>" <?= atributos_error($errores, 'nombre') ?>>
                <?= error_campo($errores, 'nombre') ?>
            </div>
            <div class="campo">
                <label for="empresa">Empresa <span class="opcional">(opcional)</span></label>
                <input id="empresa" name="empresa" maxlength="120" autocomplete="organization" value="<?= escapar($datos['empresa']) ?>" <?= atributos_error($errores, 'empresa') ?>>
                <?= error_campo($errores, 'empresa') ?>
            </div>
            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" required maxlength="150" autocomplete="email" value="<?= escapar($datos['correo']) ?>" <?= atributos_error($errores, 'correo') ?>>
                <?= error_campo($errores, 'correo') ?>
            </div>
            <div class="campo">
                <label for="mensaje">Consulta</label>
                <textarea id="mensaje" name="mensaje" rows="5" required maxlength="1000" <?= atributos_error($errores, 'mensaje') ?>><?= escapar($datos['mensaje']) ?></textarea>
                <?= error_campo($errores, 'mensaje') ?>
            </div>
            <button class="boton boton-primario" type="submit">Enviar mi consulta</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/pie.php'; ?>

<?php
/**
 * Cierra la sesión. Solo acepta POST, para que un enlace externo no pueda cerrarla.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/arranque.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION = [];
    session_destroy();

    // Se elimina también la cookie de la sesión en el navegador.
    $parametros = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $parametros['path'],
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

redirigir('index.php');

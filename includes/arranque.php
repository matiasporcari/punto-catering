<?php
/**
 * Archivo de inicio: toda página lo incluye en su primera línea de código.
 * Carga las dependencias, inicia la sesión y define el manejo de errores.
 */

declare(strict_types=1);

require_once __DIR__ . '/configuracion.php';
require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/catalogo.php';
require_once __DIR__ . '/pedidos.php';

/**
 * Ante un error inesperado, registra el detalle técnico y muestra un mensaje claro.
 * El caso más común en local es que MySQL esté apagado o la base sin importar.
 */
set_exception_handler(function (Throwable $error): void {
    error_log((string) $error);
    http_response_code(500);

    $es_error_de_conexion = $error instanceof PDOException
        && in_array((int) $error->getCode(), [2002, 1045, 1049], true);

    $detalle = $es_error_de_conexion
        ? 'No se pudo conectar a la base de datos. Verificá que MySQL esté iniciado en XAMPP y que el archivo base_de_datos/punto_catering.sql esté importado en phpMyAdmin.'
        : 'Ocurrió un error inesperado. Probá de nuevo en unos minutos.';

    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error</title>'
        . '<body style="font-family:sans-serif;max-width:40rem;margin:4rem auto;padding:0 1rem">'
        . '<h1>Algo salió mal</h1><p>' . escapar($detalle) . '</p></body></html>';
});

iniciar_sesion_segura();
verificar_csrf();

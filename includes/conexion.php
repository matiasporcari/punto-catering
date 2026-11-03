<?php
/**
 * Conexión a la base de datos mediante PDO.
 */

declare(strict_types=1);

/**
 * Devuelve la conexión compartida a la base de datos.
 * Se crea una sola vez por petición y usa consultas preparadas reales,
 * que protegen contra la inyección SQL.
 */
function obtener_conexion(): PDO
{
    static $conexion = null;

    if ($conexion === null) {
        $origen = 'mysql:host=' . BD_SERVIDOR . ';dbname=' . BD_NOMBRE . ';charset=utf8mb4';
        $conexion = new PDO($origen, BD_USUARIO, BD_CONTRASENA, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $conexion;
}

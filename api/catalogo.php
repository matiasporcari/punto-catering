<?php
/**
 * Devuelve en formato JSON los menús que cumplen con los filtros.
 * Lo consume recursos/js/catalogo.js para actualizar el catálogo sin recargar la página.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/arranque.php';

$menus = buscar_menus(leer_filtros_catalogo($_GET));

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'cantidad' => count($menus),
    'html' => tarjetas_de_menus($menus),
], JSON_UNESCAPED_UNICODE);

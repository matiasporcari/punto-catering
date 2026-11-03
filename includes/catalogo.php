<?php
/**
 * Consultas y vistas del catálogo de menús.
 */

declare(strict_types=1);

/** Devuelve todas las categorías ordenadas por nombre. */
function obtener_categorias(): array
{
    return obtener_conexion()->query('SELECT * FROM categorias ORDER BY nombre')->fetchAll();
}

/**
 * Lee los filtros del catálogo desde la dirección de la página.
 *
 * @return array{texto: string, categoria: int, vegetariano: bool, sin_tacc: bool}
 */
function leer_filtros_catalogo(array $origen): array
{
    $texto = $origen['texto'] ?? '';

    return [
        'texto' => is_string($texto) ? trim($texto) : '',
        'categoria' => entero_de($origen, 'categoria'),
        'vegetariano' => isset($origen['vegetariano']),
        'sin_tacc' => isset($origen['sin_tacc']),
    ];
}

/** Busca los menús activos que cumplen con los filtros indicados. */
function buscar_menus(array $filtros): array
{
    $sql = 'SELECT m.*, c.nombre AS categoria
            FROM menus m
            INNER JOIN categorias c ON c.id_categoria = m.id_categoria
            WHERE m.activo = 1';
    $parametros = [];

    // Las condiciones se agregan solo si el filtro está en uso.
    if ($filtros['texto'] !== '') {
        $sql .= ' AND (m.nombre LIKE ? OR m.descripcion LIKE ?)';
        $patron = '%' . $filtros['texto'] . '%';
        array_push($parametros, $patron, $patron);
    }
    if ($filtros['categoria'] > 0) {
        $sql .= ' AND m.id_categoria = ?';
        $parametros[] = $filtros['categoria'];
    }
    if ($filtros['vegetariano']) {
        $sql .= ' AND m.es_vegetariano = 1';
    }
    if ($filtros['sin_tacc']) {
        $sql .= ' AND m.es_sin_tacc = 1';
    }

    $consulta = obtener_conexion()->prepare($sql . ' ORDER BY c.nombre, m.precio_por_persona');
    $consulta->execute($parametros);

    return $consulta->fetchAll();
}

/** Devuelve el HTML de las tarjetas de una lista de menús. */
function tarjetas_de_menus(array $menus): string
{
    if ($menus === []) {
        return '<p class="estado-vacio">No encontramos menús con esos filtros. Probá quitando alguno.</p>';
    }

    ob_start();
    foreach ($menus as $menu) {
        require __DIR__ . '/tarjeta_menu.php';
    }
    return (string) ob_get_clean();
}

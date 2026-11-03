<?php
/**
 * Catálogo público de menús.
 * Los filtros funcionan sin recargar (recursos/js/catalogo.js) y, si JavaScript
 * no está disponible, también como un formulario común.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/arranque.php';

$titulo_pagina = 'Catálogo';
$scripts_pagina = ['catalogo.js'];

$filtros = leer_filtros_catalogo($_GET);
$menus = buscar_menus($filtros);
$categorias = obtener_categorias();

require __DIR__ . '/includes/encabezado.php';
?>

<section class="seccion">
    <div class="contenedor">
        <h1>Catálogo de menús</h1>
        <p class="bajada">Precios por persona. Filtrá por ocasión o por necesidad alimentaria.</p>

        <form class="filtros" id="filtros-catalogo" method="get" data-api="<?= escapar(url('api/catalogo.php')) ?>">
            <div class="campo filtro-texto">
                <label for="texto">Buscar</label>
                <input type="search" id="texto" name="texto" placeholder="Por ejemplo: café, ensalada…" value="<?= escapar($filtros['texto']) ?>">
            </div>
            <div class="campo">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id_categoria'] ?>" <?= $filtros['categoria'] === (int) $categoria['id_categoria'] ? 'selected' : '' ?>>
                            <?= escapar($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="casilla"><input type="checkbox" name="vegetariano" value="1" <?= $filtros['vegetariano'] ? 'checked' : '' ?>> Vegetariano</label>
            <label class="casilla"><input type="checkbox" name="sin_tacc" value="1" <?= $filtros['sin_tacc'] ? 'checked' : '' ?>> Sin TACC</label>
            <button class="boton boton-secundario sin-js" type="submit">Filtrar</button>
        </form>

        <p class="contador-resultados" id="contador-resultados" role="status"><?= count($menus) ?> <?= count($menus) === 1 ? 'menú' : 'menús' ?></p>
        <div class="grilla-menus" id="resultados-catalogo">
            <?= tarjetas_de_menus($menus) ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/pie.php'; ?>

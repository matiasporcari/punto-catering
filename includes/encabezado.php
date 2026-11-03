<?php
/**
 * Vista parcial: encabezado común a todas las páginas.
 * Variables opcionales de la página que lo incluye:
 *   $titulo_pagina - Título de la pestaña del navegador.
 *   $seccion_admin - Sección activa del menú del administrador.
 *   $scripts_pagina - Archivos JavaScript adicionales, dentro de recursos/js.
 */

$usuario_sesion = usuario_actual();
$mensaje = obtener_mensaje();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escapar(($titulo_pagina ?? 'Inicio') . ' | ' . NOMBRE_SITIO) ?></title>
    <meta name="description" content="Catering para empresas: coffee breaks, almuerzos de trabajo, cócteles y eventos corporativos. Pedí tu presupuesto en línea.">
    <link rel="icon" href="<?= escapar(url('recursos/img/icono.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:opsz,wght@9..144,500;9..144,700&display=swap">
    <link rel="stylesheet" href="<?= escapar(url('recursos/css/estilos.css')) ?>">
    <script src="<?= escapar(url('recursos/js/principal.js')) ?>" defer></script>
    <?php foreach ($scripts_pagina ?? [] as $script): ?>
        <script src="<?= escapar(url('recursos/js/' . $script)) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body>
<a class="salto-contenido" href="#contenido">Saltar al contenido</a>

<header class="encabezado-sitio">
    <div class="contenedor encabezado-interior">
        <a class="logo" href="<?= escapar(url('index.php')) ?>">Punto Catering<span class="logo-punto">.</span></a>

        <button class="boton-menu" type="button" aria-expanded="false" aria-controls="navegacion-principal" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <nav class="navegacion" id="navegacion-principal" aria-label="Principal">
            <a href="<?= escapar(url('index.php')) ?>">Inicio</a>
            <a href="<?= escapar(url('catalogo.php')) ?>">Catálogo</a>
            <a href="<?= escapar(url('contacto.php')) ?>">Contacto</a>

            <?php if ($usuario_sesion === null): ?>
                <a href="<?= escapar(url('ingresar.php')) ?>">Ingresar</a>
                <a class="boton boton-primario" href="<?= escapar(url('presupuesto.php')) ?>">Pedir presupuesto</a>
            <?php else: ?>
                <?php if ($usuario_sesion['rol'] === 'administrador'): ?>
                    <a href="<?= escapar(url('admin/tablero.php')) ?>">Administración</a>
                <?php else: ?>
                    <a href="<?= escapar(url('cuenta/pedidos.php')) ?>">Mis pedidos</a>
                    <a href="<?= escapar(url('cuenta/perfil.php')) ?>">Mi perfil</a>
                <?php endif; ?>
                <form class="formulario-salir" method="post" action="<?= escapar(url('salir.php')) ?>">
                    <?= campo_csrf() ?>
                    <button class="enlace-boton" type="submit">Salir</button>
                </form>
                <?php if ($usuario_sesion['rol'] === 'cliente'): ?>
                    <a class="boton boton-primario" href="<?= escapar(url('presupuesto.php')) ?>">Pedir presupuesto</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if (isset($seccion_admin)): ?>
    <nav class="navegacion-admin" aria-label="Administración">
        <div class="contenedor navegacion-admin-interior">
            <?php
            // Secciones del panel: clave de la sección => [archivo, texto visible].
            $secciones = [
                'tablero' => ['admin/tablero.php', 'Tablero'],
                'pedidos' => ['admin/pedidos.php', 'Pedidos'],
                'menus' => ['admin/menus.php', 'Menús'],
                'categorias' => ['admin/categorias.php', 'Categorías'],
                'usuarios' => ['admin/usuarios.php', 'Usuarios'],
                'consultas' => ['admin/consultas.php', 'Consultas'],
            ];
            foreach ($secciones as $clave => [$archivo, $texto]):
            ?>
                <a href="<?= escapar(url($archivo)) ?>" <?= $clave === $seccion_admin ? 'class="activa" aria-current="page"' : '' ?>><?= escapar($texto) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
<?php endif; ?>

<main id="contenido">
    <?php if ($mensaje !== null): ?>
        <div class="contenedor">
            <div class="mensaje mensaje-<?= escapar($mensaje['tipo']) ?>" role="status"><?= escapar($mensaje['texto']) ?></div>
        </div>
    <?php endif; ?>

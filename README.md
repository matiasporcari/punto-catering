# Punto Catering

Sitio web dinámico de catering para empresas, desarrollado con PHP y MySQL. Las empresas consultan el catálogo, piden presupuestos con un precio estimado en vivo y siguen sus pedidos. El administrador los cotiza y gestiona todo el contenido desde un panel.

Trabajo final de **Programación Web** (UCH).

**Integrantes:** Bautista Navarro y Matias Porcari.

## Entregables

| Entregable | Ubicación |
|---|---|
| Documento de UX | [PDF](documentacion/documento_ux.pdf) · [Word](documentacion/documento_ux.docx) |
| Prototipo en Figma | Ver [la sección correspondiente](#prototipo-en-figma) |
| Script de la base de datos | [base_de_datos/punto_catering.sql](base_de_datos/punto_catering.sql) |
| Diagramas | [documentacion/diagramas](documentacion/diagramas) |

## Prototipo en Figma

**Enlace:** _pendiente de agregar_

Las pantallas y los pasos para armarlo están en la [guía de Figma](documentacion/figma/GUIA_FIGMA.md).

## Cómo ejecutarlo

Se necesita [XAMPP](https://www.apachefriends.org/es/) con PHP 8.1 o superior. El proyecto se probó con PHP 8.2.

1. Copiá la carpeta del proyecto dentro de `C:\xampp\htdocs` con el nombre `punto-catering`.
2. Abrí el panel de XAMPP e iniciá **Apache** y **MySQL**.
3. Entrá a [phpMyAdmin](http://localhost/phpmyadmin), abrí la pestaña **Importar** y seleccioná `base_de_datos/punto_catering.sql`.
4. Abrí [http://localhost/punto-catering](http://localhost/punto-catering).

> Importar el script crea la base `punto_catering` desde cero y **borra los datos que tuviera**. Sirve también para restaurar los datos de ejemplo después de una demostración. No afecta a otras bases de datos.

Si MySQL usa otro usuario o contraseña, se cambian en `includes/configuracion.php`. Los valores incluidos son los de una instalación estándar de XAMPP.

Las fuentes tipográficas se cargan desde Google Fonts. Sin conexión a internet el sitio funciona igual, con fuentes alternativas.

### Cuentas de demostración

Son datos ficticios, solo para probar el sitio en local.

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@puntocatering.test` | `Admin123!` |
| Empresa | `compras@andina.test` | `Empresa123!` |
| Empresa | `oficina@lumen.test` | `Empresa123!` |
| Empresa | `rrhh@tecnored.test` | `Empresa123!` |

## Funcionalidades

**Visitantes**

- Catálogo con búsqueda y filtros por categoría, vegetariano y sin TACC, sin recargar la página.
- Formulario de contacto.
- Registro de empresa e inicio de sesión.

**Empresas**

- Pedido de presupuesto con precio estimado en vivo.
- Consulta, edición y cancelación de pedidos.
- Aprobación o rechazo del presupuesto.
- Presupuesto para imprimir o guardar como PDF.
- Modificación de los datos de la empresa y de la contraseña.

**Administrador**

- Tablero con métricas generales.
- Gestión de pedidos: estado, precio final, comentario y eliminación.
- Alta, baja y modificación de menús y categorías.
- Activación, desactivación y baja de usuarios.
- Lectura y eliminación de las consultas recibidas.

## Estructura del proyecto

```text
punto-catering/
├── index.php, catalogo.php, contacto.php    Páginas públicas
├── ingresar.php, registrarse.php, salir.php Manejo de la sesión
├── presupuesto.php                          Pedido y edición de presupuestos
├── api/                                     Respuestas JSON para los filtros del catálogo
├── cuenta/                                  Panel de la empresa
├── admin/                                   Panel del administrador
├── includes/                                Configuración, funciones, consultas y vistas comunes
├── recursos/                                CSS, JavaScript e imágenes
├── base_de_datos/                           Script SQL con la estructura y los datos de ejemplo
└── documentacion/                           Documento de UX, diagramas y pantallas para Figma
```

## Seguridad

- Consultas preparadas con PDO, contra la inyección SQL.
- Contraseñas guardadas con `password_hash`.
- Toda salida escapada con `htmlspecialchars`, contra XSS.
- Token CSRF obligatorio en todos los formularios POST.
- Control de permisos por rol y por propietario de cada pedido.
- Validación en el servidor de todos los datos, incluido el recálculo de los precios.

## Tecnologías

PHP 8, MySQL/MariaDB, phpMyAdmin, HTML5, CSS3 y JavaScript, sin frameworks.

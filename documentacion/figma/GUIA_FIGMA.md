# Guía para armar el prototipo en Figma

Esta carpeta tiene las 22 pantallas del sitio en dos versiones, listas para importar a Figma como capas editables:

- `alta_fidelidad/`: diseño final, con colores, tipografías e ilustraciones.
- `wireframes/`: las mismas pantallas en escala de grises, para mostrar la estructura.

Armar el prototipo lleva unos 15 minutos.

## 1. Crear el archivo

1. Entrá a [figma.com](https://www.figma.com), iniciá sesión y creá un **Design file**.
2. Cambiale el nombre a `Punto Catering`.
3. En el panel izquierdo, creá dos páginas: `Prototipo` y `Wireframes`.

## 2. Importar las pantallas

1. En la página `Prototipo`, seleccioná todos los archivos de `alta_fidelidad/` y arrastralos al lienzo.
2. Ordenalos en filas, de izquierda a derecha: públicas (01 a 05), empresa (06 a 10), administración (11 a 18) y móvil (19 a 22).
3. Repetí el paso 1 en la página `Wireframes`, con los archivos de `wireframes/`.

Cada archivo entra como un marco con el nombre de la pantalla. Figma ya incluye las fuentes **Fraunces**, **DM Sans** e **Inter**. Si avisa que falta alguna, elegila en el aviso de fuentes faltantes.

## 3. Conectar el prototipo

En la página `Prototipo`, abrí la pestaña **Prototype** del panel derecho. Para cada fila de la tabla:

1. Seleccioná el elemento de origen. Con `Ctrl` + clic se elige una capa dentro del marco.
2. Arrastrá el círculo azul hasta la pantalla de destino.
3. Dejá la interacción **On click → Navigate to**, con la animación **Instant**.

Los botones se importan como un rectángulo y un texto separados. Conviene conectar el rectángulo, o agruparlos antes con `Ctrl` + `G`.

| Origen | Elemento | Destino |
|---|---|---|
| `01_inicio` | Botón «Obtener mi presupuesto» | `03_ingresar` |
| `01_inicio` | Botón «Ver el catálogo» | `02_catalogo` |
| `01_inicio` | «Ingresar» en la barra superior | `03_ingresar` |
| `01_inicio` | «Contacto» en la barra superior | `05_contacto` |
| `02_catalogo` | Botón «Lo quiero cotizar» de una tarjeta | `03_ingresar` |
| `02_catalogo` | Logo | `01_inicio` |
| `05_contacto` | Logo | `01_inicio` |
| `03_ingresar` | Botón «Ingresar» | `06_pedir_presupuesto` |
| `03_ingresar` | Enlace «Registrala gratis» | `04_registro` |
| `04_registro` | Botón «Crear mi cuenta» | `06_pedir_presupuesto` |
| `06_pedir_presupuesto` | Botón «Enviar mi pedido» | `07_mis_pedidos` |
| `07_mis_pedidos` | «Ver detalle» de una fila | `08_detalle_pedido` |
| `07_mis_pedidos` | «Mi perfil» en la barra superior | `09_mi_perfil` |
| `08_detalle_pedido` | Botón «Ver para imprimir» | `10_presupuesto_imprimible` |
| `08_detalle_pedido` | «Volver a mis pedidos» | `07_mis_pedidos` |
| `10_presupuesto_imprimible` | «Volver al pedido» | `08_detalle_pedido` |
| `11_admin_tablero` | «Pedidos» en la barra verde | `12_admin_pedidos` |
| `11_admin_tablero` | «Menús» en la barra verde | `14_admin_menus` |
| `11_admin_tablero` | «Categorías» en la barra verde | `16_admin_categorias` |
| `11_admin_tablero` | «Usuarios» en la barra verde | `17_admin_usuarios` |
| `11_admin_tablero` | «Consultas» en la barra verde | `18_admin_consultas` |
| `12_admin_pedidos` | «Gestionar» de una fila | `13_admin_gestionar_pedido` |
| `13_admin_gestionar_pedido` | Botón «Guardar los cambios» | `12_admin_pedidos` |
| `14_admin_menus` | «Editar» de una fila | `15_admin_formulario_menu` |
| `15_admin_formulario_menu` | Botón «Guardar los cambios» | `14_admin_menus` |
| `19_movil_inicio` | Ícono del menú | `20_movil_menu_abierto` |
| `20_movil_menu_abierto` | «Catálogo» | `21_movil_catalogo` |
| `20_movil_menu_abierto` | Ícono del menú | `19_movil_inicio` |
| `21_movil_catalogo` | Botón «Lo quiero cotizar» de una tarjeta | `22_movil_pedir_presupuesto` |

## 4. Definir los puntos de partida

Seleccioná cada marco y, en **Prototype → Flow starting point**, agregá un flujo:

- `01_inicio`: «Empresa».
- `11_admin_tablero`: «Administrador».
- `19_movil_inicio`: «Móvil».

Las pantallas largas se recorren con desplazamiento dentro del marco. Para probar el prototipo, usá el botón **Present** (▶).

## 5. Compartir el enlace

1. Tocá **Share** y elegí **Anyone with the link can view**, para que el profesor pueda abrirlo.
2. Copiá el enlace.
3. Pegalo en `README.md`, en la sección «Prototipo en Figma», reemplazando el texto pendiente.
4. Subí el cambio a GitHub:

```bash
git add README.md
git commit -m "Agrega el enlace al prototipo de Figma"
git push
```

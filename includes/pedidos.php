<?php
/**
 * Reglas de negocio y consultas de los pedidos de presupuesto.
 */

declare(strict_types=1);

// Estados en los que la empresa todavía puede cancelar su pedido.
const ESTADOS_CANCELABLES = ['pendiente', 'presupuestado'];

// Estados terminales en los que la empresa puede borrar el pedido de su historial.
const ESTADOS_ELIMINABLES = ['cancelado', 'rechazado'];

/**
 * Busca un pedido por su identificador, junto con los datos de la empresa.
 * Si se indica $id_usuario, solo lo devuelve si pertenece a esa empresa:
 * así nadie puede ver un pedido ajeno cambiando el número en la dirección.
 */
function obtener_pedido(int $id_pedido, ?int $id_usuario = null): ?array
{
    $sql = 'SELECT p.*, u.razon_social, u.cuit, u.nombre_contacto, u.correo, u.telefono
            FROM pedidos p
            INNER JOIN usuarios u ON u.id_usuario = p.id_usuario
            WHERE p.id_pedido = ?';
    $parametros = [$id_pedido];

    if ($id_usuario !== null) {
        $sql .= ' AND p.id_usuario = ?';
        $parametros[] = $id_usuario;
    }

    $consulta = obtener_conexion()->prepare($sql);
    $consulta->execute($parametros);

    return $consulta->fetch() ?: null;
}

/** Devuelve los menús incluidos en un pedido, con el precio guardado en ese momento. */
function obtener_detalle_pedido(int $id_pedido): array
{
    $consulta = obtener_conexion()->prepare(
        'SELECT d.id_menu, d.precio_unitario, m.nombre, m.descripcion
         FROM detalle_pedido d
         INNER JOIN menus m ON m.id_menu = d.id_menu
         WHERE d.id_pedido = ?
         ORDER BY m.nombre'
    );
    $consulta->execute([$id_pedido]);

    return $consulta->fetchAll();
}

/**
 * Devuelve los menús que se pueden elegir en el formulario: los activos y,
 * al editar, los que el pedido ya tenía aunque luego se hayan desactivado.
 */
function obtener_menus_para_pedido(array $ids_ya_elegidos = []): array
{
    $sql = 'SELECT m.*, c.nombre AS categoria
            FROM menus m
            INNER JOIN categorias c ON c.id_categoria = m.id_categoria
            WHERE m.activo = 1';
    $parametros = [];

    if ($ids_ya_elegidos !== []) {
        $marcadores = implode(',', array_fill(0, count($ids_ya_elegidos), '?'));
        $sql .= " OR m.id_menu IN ($marcadores)";
        $parametros = array_values($ids_ya_elegidos);
    }

    $consulta = obtener_conexion()->prepare($sql . ' ORDER BY c.nombre, m.precio_por_persona');
    $consulta->execute($parametros);

    return $consulta->fetchAll();
}

/**
 * Valida los datos del formulario de presupuesto.
 *
 * @param array $datos Datos ingresados por la empresa.
 * @param array $menus_elegidos Menús seleccionados, ya leídos de la base de datos.
 * @return array<string, string> Errores por campo; vacío si todo es correcto.
 */
function validar_pedido(array $datos, array $menus_elegidos): array
{
    $errores = [];

    // El evento debe ser al menos un día después de hoy, para poder organizarlo.
    if (!es_fecha_valida($datos['fecha_evento'])) {
        $errores['fecha_evento'] = 'Elegí la fecha del evento.';
    } elseif ($datos['fecha_evento'] <= date('Y-m-d')) {
        $errores['fecha_evento'] = 'La fecha del evento debe ser posterior a hoy.';
    }

    if (!es_hora_valida($datos['hora_evento'])) {
        $errores['hora_evento'] = 'Elegí la hora del evento.';
    }

    if (mb_strlen($datos['direccion_evento']) < 5 || mb_strlen($datos['direccion_evento']) > 200) {
        $errores['direccion_evento'] = 'Ingresá la dirección del evento.';
    }

    if ($datos['cantidad_personas'] < 1 || $datos['cantidad_personas'] > 5000) {
        $errores['cantidad_personas'] = 'Ingresá una cantidad de personas entre 1 y 5000.';
    }

    if (mb_strlen($datos['observaciones']) > 500) {
        $errores['observaciones'] = 'Las observaciones no pueden superar los 500 caracteres.';
    }

    if ($menus_elegidos === []) {
        $errores['menus'] = 'Elegí al menos un menú.';
    } elseif (!isset($errores['cantidad_personas'])) {
        // Cada menú exige una cantidad mínima de personas.
        foreach ($menus_elegidos as $menu) {
            if ($datos['cantidad_personas'] < (int) $menu['minimo_personas']) {
                $errores['menus'] = '"' . $menu['nombre'] . '" requiere al menos ' . $menu['minimo_personas'] . ' personas.';
                break;
            }
        }
    }

    return $errores;
}

/**
 * Calcula el total estimado: personas por la suma de los precios de los menús.
 * Se calcula siempre en el servidor con los precios de la base de datos,
 * nunca con valores enviados por el navegador.
 */
function calcular_total_pedido(int $cantidad_personas, array $menus_elegidos): float
{
    $suma_precios = array_sum(array_map(fn(array $menu): float => (float) $menu['precio_por_persona'], $menus_elegidos));
    return $cantidad_personas * $suma_precios;
}

/**
 * Crea un pedido (si $id_pedido es null) o actualiza uno pendiente.
 * Usa una transacción para que el pedido y su detalle se guarden juntos o no se guarde nada.
 *
 * @return int|null Identificador del pedido, o null si ya no se podía editar.
 */
function guardar_pedido(int $id_usuario, array $datos, array $menus_elegidos, ?int $id_pedido = null): ?int
{
    $conexion = obtener_conexion();
    $total = calcular_total_pedido($datos['cantidad_personas'], $menus_elegidos);
    $valores = [
        $datos['fecha_evento'],
        $datos['hora_evento'],
        $datos['direccion_evento'],
        $datos['cantidad_personas'],
        $datos['observaciones'],
        $total,
    ];

    $conexion->beginTransaction();

    try {
        if ($id_pedido === null) {
            $consulta = $conexion->prepare(
                'INSERT INTO pedidos (fecha_evento, hora_evento, direccion_evento, cantidad_personas, observaciones, total_estimado, id_usuario)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([...$valores, $id_usuario]);
            $id_pedido = (int) $conexion->lastInsertId();
        } else {
            // Se bloquea la fila y se comprueba el estado, por si el administrador lo cambió mientras se editaba.
            $bloqueo = $conexion->prepare(
                "SELECT id_pedido FROM pedidos WHERE id_pedido = ? AND id_usuario = ? AND estado = 'pendiente' FOR UPDATE"
            );
            $bloqueo->execute([$id_pedido, $id_usuario]);

            if ($bloqueo->fetch() === false) {
                $conexion->rollBack();
                return null;
            }

            $consulta = $conexion->prepare(
                'UPDATE pedidos
                 SET fecha_evento = ?, hora_evento = ?, direccion_evento = ?, cantidad_personas = ?, observaciones = ?, total_estimado = ?
                 WHERE id_pedido = ?'
            );
            $consulta->execute([...$valores, $id_pedido]);

            // Se reemplaza el detalle completo por la nueva selección.
            $conexion->prepare('DELETE FROM detalle_pedido WHERE id_pedido = ?')->execute([$id_pedido]);
        }

        $insercion = $conexion->prepare('INSERT INTO detalle_pedido (id_pedido, id_menu, precio_unitario) VALUES (?, ?, ?)');
        foreach ($menus_elegidos as $menu) {
            $insercion->execute([$id_pedido, $menu['id_menu'], $menu['precio_por_persona']]);
        }

        $conexion->commit();
    } catch (Throwable $error) {
        $conexion->rollBack();
        throw $error;
    }

    return $id_pedido;
}

/**
 * Cambia el estado de un pedido de la empresa, solo si está en uno de los estados permitidos.
 * La condición va en la misma consulta para que no pueda saltarse el flujo.
 *
 * @return bool true si el pedido se actualizó.
 */
function cambiar_estado_pedido_cliente(int $id_pedido, int $id_usuario, string $estado_nuevo, array $estados_permitidos): bool
{
    $marcadores = implode(',', array_fill(0, count($estados_permitidos), '?'));
    $consulta = obtener_conexion()->prepare(
        "UPDATE pedidos SET estado = ? WHERE id_pedido = ? AND id_usuario = ? AND estado IN ($marcadores)"
    );
    $consulta->execute([$estado_nuevo, $id_pedido, $id_usuario, ...$estados_permitidos]);

    return $consulta->rowCount() > 0;
}

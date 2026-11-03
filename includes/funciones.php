<?php
/**
 * Funciones de uso general: rutas, salida segura, formatos y validaciones.
 */

declare(strict_types=1);

/* ---------- Rutas y redirecciones ---------- */

/**
 * Calcula la ruta base del sitio, para que funcione en cualquier carpeta
 * (por ejemplo /punto-catering en XAMPP o la raíz con el servidor de PHP).
 */
function ruta_base(): string
{
    static $base = null;

    if ($base === null) {
        $raiz = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
        $script = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME']));
        $relativa = substr($script, strlen($raiz));
        $base = substr($_SERVER['SCRIPT_NAME'], 0, -strlen($relativa));
    }

    return $base;
}

/** Devuelve la URL de un archivo del sitio a partir de su ruta relativa a la raíz. */
function url(string $ruta = ''): string
{
    return ruta_base() . '/' . ltrim($ruta, '/');
}

/** Redirige a otra página del sitio y termina la ejecución. */
function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

/* ---------- Salida segura ---------- */

/** Escapa un texto antes de mostrarlo en HTML, para evitar ataques XSS. */
function escapar(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/* ---------- Formatos ---------- */

/** Formatea un importe en pesos: 1234567.5 pasa a "$ 1.234.568". */
function formato_moneda(float|string|null $importe): string
{
    return '$ ' . number_format((float) $importe, 0, ',', '.');
}

/** Convierte una fecha de la base de datos al formato dd/mm/aaaa. */
function formato_fecha(string $fecha): string
{
    return date('d/m/Y', strtotime($fecha));
}

/** Convierte una hora de la base de datos al formato hh:mm. */
function formato_hora(string $hora): string
{
    return substr($hora, 0, 5);
}

/** Devuelve el HTML de la etiqueta de color de un estado de pedido. */
function etiqueta_estado(string $estado): string
{
    $texto = ESTADOS_PEDIDO[$estado] ?? $estado;
    return '<span class="etiqueta-estado estado-' . escapar($estado) . '">' . escapar($texto) . '</span>';
}

/* ---------- Lectura de datos enviados ---------- */

/** Lee un campo de texto enviado por POST, sin espacios en los extremos. */
function texto_post(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/** Lee un número entero positivo desde un arreglo; devuelve 0 si no es válido. */
function entero_de(array $origen, string $campo): int
{
    $valor = filter_var($origen[$campo] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $valor === false ? 0 : $valor;
}

/* ---------- Validaciones ---------- */

/** Indica si una fecha en formato aaaa-mm-dd existe en el calendario. */
function es_fecha_valida(string $fecha): bool
{
    $fecha_leida = DateTime::createFromFormat('Y-m-d', $fecha);
    return $fecha_leida !== false && $fecha_leida->format('Y-m-d') === $fecha;
}

/** Indica si una hora tiene el formato hh:mm. */
function es_hora_valida(string $hora): bool
{
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) === 1;
}

/**
 * Valida los datos de una empresa, compartidos por el registro y el perfil.
 *
 * @return array<string, string> Errores por campo; vacío si todo es correcto.
 */
function validar_datos_empresa(array $datos): array
{
    $errores = [];

    if (mb_strlen($datos['razon_social']) < 3 || mb_strlen($datos['razon_social']) > 120) {
        $errores['razon_social'] = 'Ingresá la razón social (entre 3 y 120 caracteres).';
    }
    if (preg_match('/^\d{2}-\d{8}-\d$/', $datos['cuit']) !== 1) {
        $errores['cuit'] = 'Ingresá el CUIT con el formato 30-12345678-9.';
    }
    if (mb_strlen($datos['nombre_contacto']) < 3 || mb_strlen($datos['nombre_contacto']) > 100) {
        $errores['nombre_contacto'] = 'Ingresá el nombre de la persona de contacto.';
    }
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($datos['correo']) > 150) {
        $errores['correo'] = 'Ingresá un correo electrónico válido.';
    }
    if (preg_match('/^[0-9 +()\-]{8,30}$/', $datos['telefono']) !== 1) {
        $errores['telefono'] = 'Ingresá un teléfono válido, solo con números y guiones.';
    }

    return $errores;
}

/**
 * Valida una contraseña nueva y su repetición.
 * Devuelve el mensaje de error, o null si es correcta.
 */
function validar_contrasena_nueva(string $contrasena, string $repeticion): ?string
{
    if (strlen($contrasena) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($contrasena !== $repeticion) {
        return 'Las contraseñas no coinciden.';
    }
    return null;
}

/* ---------- Vistas parciales ---------- */

/** Muestra el mensaje de error de un campo de formulario, si existe. */
function error_campo(array $errores, string $campo): string
{
    if (!isset($errores[$campo])) {
        return '';
    }
    return '<p class="error-campo" id="error-' . escapar($campo) . '">' . escapar($errores[$campo]) . '</p>';
}

/** Devuelve los atributos de accesibilidad de un campo con error. */
function atributos_error(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? 'aria-invalid="true" aria-describedby="error-' . escapar($campo) . '"' : '';
}

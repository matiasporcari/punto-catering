<?php
/**
 * Manejo de la sesión: autenticación, permisos, protección CSRF y mensajes.
 */

declare(strict_types=1);

/** Inicia la sesión con una cookie que JavaScript no puede leer. */
function iniciar_sesion_segura(): void
{
    session_name('punto_catering_sesion');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ---------- Usuario actual ---------- */

/**
 * Devuelve el usuario que inició sesión, o null si es un visitante.
 * Se consulta a la base de datos para reflejar enseguida cualquier cambio;
 * por ejemplo, si un administrador desactiva la cuenta.
 */
function usuario_actual(): ?array
{
    static $usuario = null;
    static $consultado = false;

    if (!$consultado) {
        $consultado = true;

        if (isset($_SESSION['id_usuario'])) {
            $consulta = obtener_conexion()->prepare('SELECT * FROM usuarios WHERE id_usuario = ? AND activo = 1');
            $consulta->execute([$_SESSION['id_usuario']]);
            $usuario = $consulta->fetch() ?: null;

            if ($usuario === null) {
                unset($_SESSION['id_usuario']);
            }
        }
    }

    return $usuario;
}

/** Registra el inicio de sesión, renovando el identificador para evitar su robo. */
function iniciar_sesion_de_usuario(int $id_usuario): void
{
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $id_usuario;
}

/** Devuelve la página principal que le corresponde a un usuario según su rol. */
function pagina_inicio_de(array $usuario): string
{
    return $usuario['rol'] === 'administrador' ? 'admin/tablero.php' : 'cuenta/pedidos.php';
}

/**
 * Tras ingresar o registrarse, vuelve a la página que se quería visitar.
 * Solo acepta rutas internas del sitio y solo para empresas; si no, va a la página principal del rol.
 */
function redirigir_tras_ingreso(string $rol, string $pagina_principal): never
{
    $destino = $_SESSION['destino_tras_ingreso'] ?? '';
    unset($_SESSION['destino_tras_ingreso']);

    if ($rol === 'cliente' && preg_match('#^/(?!/)#', $destino) === 1) {
        header('Location: ' . $destino);
        exit;
    }

    redirigir($pagina_principal);
}

/* ---------- Control de permisos ---------- */

/**
 * Exige haber iniciado sesión. Si no, envía al formulario de ingreso y
 * recuerda la página pedida para volver a ella después.
 */
function requerir_sesion(): array
{
    $usuario = usuario_actual();

    if ($usuario === null) {
        $_SESSION['destino_tras_ingreso'] = $_SERVER['REQUEST_URI'];
        establecer_mensaje('aviso', 'Iniciá sesión para continuar.');
        redirigir('ingresar.php');
    }

    return $usuario;
}

/** Exige una sesión de empresa cliente. */
function requerir_cliente(): array
{
    $usuario = requerir_sesion();

    if ($usuario['rol'] !== 'cliente') {
        establecer_mensaje('aviso', 'Esa sección es para las empresas clientes.');
        redirigir(pagina_inicio_de($usuario));
    }

    return $usuario;
}

/** Exige una sesión de administrador. */
function requerir_administrador(): array
{
    $usuario = requerir_sesion();

    if ($usuario['rol'] !== 'administrador') {
        establecer_mensaje('error', 'No tenés permiso para entrar a esa sección.');
        redirigir(pagina_inicio_de($usuario));
    }

    return $usuario;
}

/* ---------- Protección CSRF ---------- */

/** Devuelve el token de la sesión, creándolo si todavía no existe. */
function token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_csrf'];
}

/** Devuelve el campo oculto con el token, para incluir en cada formulario POST. */
function campo_csrf(): string
{
    return '<input type="hidden" name="token_csrf" value="' . escapar(token_csrf()) . '">';
}

/**
 * Rechaza cualquier envío POST sin un token válido.
 * Se ejecuta en cada petición desde arranque.php, así ningún formulario queda sin protección.
 */
function verificar_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['token_csrf'] ?? '';

    if (!is_string($token) || !hash_equals(token_csrf(), $token)) {
        http_response_code(403);
        exit('La sesión venció o el formulario no es válido. Volvé atrás y recargá la página.');
    }
}

/* ---------- Mensajes entre páginas ---------- */

/** Guarda un mensaje para mostrarlo en la próxima página. Tipos: exito, error o aviso. */
function establecer_mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Devuelve el mensaje pendiente y lo borra, para que se muestre una sola vez. */
function obtener_mensaje(): ?array
{
    $mensaje = $_SESSION['mensaje'] ?? null;
    unset($_SESSION['mensaje']);
    return $mensaje;
}

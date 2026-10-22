<?php
declare(strict_types=1);

/**
 * ============================================================
 * AUTENTICACIÓN Y PROTECCIÓN ADMINISTRATIVA
 * Proyecto: Patch_BolsaTrabajo
 * Archivo: public_html/includes/auth.php
 * ============================================================
 *
 * Este archivo:
 * - Inicia la sesión de forma segura.
 * - Comprueba si el administrador inició sesión.
 * - Protege páginas administrativas.
 * - Protege APIs administrativas mediante respuestas JSON.
 *
 * No contiene credenciales de base de datos.
 */

// Evitar iniciar la sesión más de una vez.
if (session_status() !== PHP_SESSION_ACTIVE) {

    // Detectar si la conexión actual utiliza HTTPS.
    $esHttps = (
        isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && strtolower((string) $_SERVER['HTTPS']) !== 'off'
    ) || (
        isset($_SERVER['SERVER_PORT'])
        && (int) $_SERVER['SERVER_PORT'] === 443
    );

    // Configuración de la cookie de sesión.
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Iniciar sesión.
    session_start();
}

/**
 * Comprueba si existe una sesión administrativa válida.
 */
function adminEstaAutenticado(): bool
{
    return isset(
        $_SESSION['admin_id'],
        $_SESSION['admin_correo'],
        $_SESSION['admin_nombre']
    )
    && is_numeric($_SESSION['admin_id'])
    && (int) $_SESSION['admin_id'] > 0
    && is_string($_SESSION['admin_correo'])
    && $_SESSION['admin_correo'] !== ''
    && is_string($_SESSION['admin_nombre'])
    && $_SESSION['admin_nombre'] !== '';
}

/**
 * Protege una página administrativa.
 *
 * Si no hay sesión, redirige al login.
 *
 * El parámetro permite especificar la ruta del login
 * desde cada página administrativa.
 */
function requerirAdminPagina(
    string $rutaLogin = '../../../login.php'
): void {
    if (!adminEstaAutenticado()) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        header('Location: ' . $rutaLogin, true, 302);
        exit;
    }
}

/**
 * Protege una API administrativa.
 *
 * Si no hay sesión, devuelve HTTP 401 y JSON,
 * sin redirigir a una página HTML.
 */
function requerirAdminApi(): void
{
    if (!adminEstaAutenticado()) {
        http_response_code(401);

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Sesión no autorizada. Inicia sesión nuevamente.',
            'datos' => null,
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

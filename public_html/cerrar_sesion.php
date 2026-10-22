<?php
declare(strict_types=1);

// Iniciar o recuperar la sesión administrativa.
require_once __DIR__ . '/includes/auth.php';

// Evitar que el navegador conserve páginas autenticadas en caché.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Eliminar todas las variables de sesión.
$_SESSION = [];

// Obtener los parámetros de la cookie actual.
$parametrosCookie = session_get_cookie_params();

// Invalidar la cookie de sesión en el navegador.
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $parametrosCookie['path'],
        'domain' => $parametrosCookie['domain'],
        'secure' => $parametrosCookie['secure'],
        'httponly' => $parametrosCookie['httponly'],
        'samesite' => $parametrosCookie['samesite'] ?? 'Lax',
    ]);
}

// Destruir la sesión del servidor.
session_destroy();

// Regresar al formulario de inicio de sesión.
header('Location: login.php', true, 302);
exit;

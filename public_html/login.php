<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$error = '';
$correo = '';

/*
|--------------------------------------------------------------------------
| Si ya inició sesión, enviarlo al panel administrativo.
|--------------------------------------------------------------------------
*/
if (adminEstaAutenticado()) {
    header(
        'Location: backend/modulo/Patch_BolsaTrabajo/Admin_BolsaTrabajo.php',
        true,
        302
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Token CSRF para proteger el formulario de inicio de sesión.
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Procesamiento del formulario.
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $tokenRecibido = (string) ($_POST['csrf_token'] ?? '');
    $tokenSesion = (string) ($_SESSION['login_csrf'] ?? '');

    if (
        $tokenRecibido === '' ||
        $tokenSesion === '' ||
        !hash_equals($tokenSesion, $tokenRecibido)
    ) {
        $error = 'La sesión del formulario ha caducado. Recarga la página e inténtalo nuevamente.';
    } elseif ($correo === '' || $password === '') {
        $error = 'Completa tu correo electrónico y contraseña.';
    } elseif (
        strlen($correo) > 255 ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL)
    ) {
        $error = 'El correo o la contraseña son incorrectos.';
    } else {
        $sql = 'SELECT id, nombre, correo, password, activo
                FROM patch_usuarios_admin
                WHERE correo = ?
                LIMIT 1';

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            error_log('Login: no fue posible preparar la consulta.');
            $error = 'No fue posible iniciar sesión. Intenta más tarde.';
        } else {
            $stmt->bind_param('s', $correo);

            if (!$stmt->execute()) {
                error_log('Login: falló la consulta de usuarios.');
                $error = 'No fue posible iniciar sesión. Intenta más tarde.';
                $stmt->close();
            } else {
                $stmt->bind_result(
                    $idUsuario,
                    $nombreUsuario,
                    $correoUsuario,
                    $hashPassword,
                    $activo
                );

                $encontrado = $stmt->fetch();
                $stmt->close();

                if (
                    $encontrado !== true ||
                    (int) $activo !== 1 ||
                    !password_verify($password, (string) $hashPassword)
                ) {
                    $error = 'El correo o la contraseña son incorrectos.';
                } else {
                    /*
                    | Regenerar el identificador de sesión para
                    | reducir el riesgo de fijación de sesión.
                    */
                    if (!session_regenerate_id(true)) {
                        error_log('Login: no se pudo regenerar la sesión.');
                        $error = 'No fue posible iniciar sesión. Intenta nuevamente.';
                    } else {
                        $_SESSION['admin_id'] = (int) $idUsuario;
                        $_SESSION['admin_nombre'] = (string) $nombreUsuario;
                        $_SESSION['admin_correo'] = (string) $correoUsuario;

                        unset($_SESSION['login_csrf']);

                        $actualizar = $conn->prepare(
                            'UPDATE patch_usuarios_admin
                             SET ultimo_acceso = NOW()
                             WHERE id = ?'
                        );

                        if ($actualizar !== false) {
                            $actualizar->bind_param('i', $idUsuario);

                            if (!$actualizar->execute()) {
                                error_log(
                                    'Login: no se pudo actualizar ultimo_acceso.'
                                );
                            }

                            $actualizar->close();
                        }

                        header(
                            'Location: backend/modulo/Patch_BolsaTrabajo/Admin_BolsaTrabajo.php',
                            true,
                            302
                        );
                        exit;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="robots" content="noindex, nofollow">

    <title>Acceso administrativo | Bolsa de Trabajo</title>

    <style>
        :root {
            --verde-oscuro: #123c2b;
            --verde-profundo: #174d35;
            --verde-principal: #198754;
            --verde-brillante: #28a66a;
            --verde-claro: #e9f7ef;
            --texto: #183329;
            --texto-secundario: #718178;
            --borde: #dce9e0;
            --blanco: #ffffff;
            --error-fondo: #fff0f0;
            --error-texto: #a52828;
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
            color: var(--texto);
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(61, 177, 113, 0.17),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 90% 90%,
                    rgba(25, 135, 84, 0.14),
                    transparent 35%
                ),
                #f3f8f4;
        }

        button,
        input {
            font: inherit;
        }

        .contenedor {
            width: 100%;
            max-width: 1000px;
            min-height: 590px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            background: var(--blanco);
            border: 1px solid rgba(220, 233, 224, 0.9);
            border-radius: 26px;
            box-shadow: 0 28px 80px rgba(18, 60, 43, 0.13);
        }

        .panel-marca {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 44px;
            color: var(--blanco);
            background: linear-gradient(
                145deg,
                var(--verde-oscuro) 0%,
                var(--verde-profundo) 56%,
                #218653 100%
            );
        }

        .panel-marca::before,
        .panel-marca::after {
            position: absolute;
            z-index: -1;
            content: "";
            border: 1px solid rgba(255, 255, 255, 0.13);
            border-radius: 50%;
        }

        .panel-marca::before {
            width: 380px;
            height: 380px;
            right: -190px;
            top: -140px;
        }

        .panel-marca::after {
            width: 300px;
            height: 300px;
            right: -115px;
            top: -70px;
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .marca-icono {
            width: 49px;
            height: 49px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.23);
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.12);
        }

        .marca-icono svg {
            width: 29px;
            height: 29px;
        }

        .marca-texto {
            font-size: 17px;
            font-weight: 750;
            letter-spacing: 0.2px;
        }

        .marca-subtitulo {
            margin-top: 4px;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #c2e9d0;
        }

        .presentacion {
            max-width: 380px;
            margin: 45px 0;
        }

        .etiqueta {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 13px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.08);
            color: #e3f6e9;
            font-size: 12px;
            font-weight: 600;
        }

        .punto {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #80e7a7;
            box-shadow: 0 0 12px rgba(128, 231, 167, 0.6);
        }

        .presentacion h1 {
            margin: 25px 0 18px;
            font-size: clamp(34px, 4vw, 47px);
            font-weight: 750;
            line-height: 1.12;
            letter-spacing: -1.8px;
        }

        .presentacion h1 span {
            color: #86e5a9;
        }

        .presentacion p {
            max-width: 340px;
            margin: 0;
            color: #d0e8d9;
            font-size: 15px;
            line-height: 1.85;
        }

        .beneficios {
            display: grid;
            gap: 15px;
            margin-top: 28px;
        }

        .beneficio {
            display: flex;
            align-items: center;
            gap: 11px;
            color: #e3f3e8;
            font-size: 13px;
        }

        .beneficio-icono {
            width: 25px;
            height: 25px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.13);
            color: #a7f0be;
            font-size: 14px;
        }

        .pie-marca {
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.16);
            color: #c5e0cf;
            font-size: 11px;
            line-height: 1.7;
        }

        .panel-login {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 55px 48px;
        }

        .icono-acceso {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin-bottom: 26px;
            border: 1px solid #d7efdf;
            border-radius: 18px;
            background: var(--verde-claro);
            color: var(--verde-principal);
        }

        .icono-acceso svg {
            width: 28px;
            height: 28px;
        }

        .panel-login h2 {
            margin: 0;
            color: #183b2b;
            font-size: 29px;
            font-weight: 750;
            letter-spacing: -0.8px;
        }

        .intro {
            margin: 11px 0 30px;
            color: var(--texto-secundario);
            font-size: 14px;
            line-height: 1.7;
        }

        .error {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 13px 14px;
            border: 1px solid #f2cece;
            border-radius: 10px;
            background: var(--error-fondo);
            color: var(--error-texto);
            font-size: 13px;
            line-height: 1.6;
        }

        .error svg {
            width: 19px;
            height: 19px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .campo {
            margin-bottom: 20px;
        }

        .campo label {
            display: block;
            margin-bottom: 9px;
            color: #2b4336;
            font-size: 13px;
            font-weight: 700;
        }

        .entrada-contenedor {
            position: relative;
        }

        .entrada-icono {
            position: absolute;
            top: 50%;
            left: 15px;
            width: 19px;
            height: 19px;
            color: #819488;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .campo input {
            width: 100%;
            min-height: 51px;
            padding: 13px 15px 13px 45px;
            border: 1px solid #dce6df;
            border-radius: 11px;
            outline: none;
            background: #fbfdfb;
            color: #24392d;
            font-size: 14px;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .campo input::placeholder {
            color: #a1afa6;
        }

        .campo input:focus {
            border-color: var(--verde-principal);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.11);
        }

        .campo input:focus + .entrada-icono {
            color: var(--verde-principal);
        }

        .campo input:focus-visible {
            outline: none;
        }

        .boton-login {
            position: relative;
            width: 100%;
            min-height: 53px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 6px;
            padding: 14px 18px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(
                110deg,
                #187849,
                #20965a
            );
            box-shadow: 0 8px 18px rgba(25, 135, 84, 0.2);
            color: white;
            font-size: 14px;
            font-weight: 750;
            cursor: pointer;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .boton-login:hover {
            transform: translateY(-1px);
            background: linear-gradient(
                110deg,
                #12663c,
                #187e4b
            );
            box-shadow: 0 11px 22px rgba(25, 135, 84, 0.27);
        }

        .boton-login:active {
            transform: translateY(0);
        }

        .boton-login:focus-visible {
            outline: 3px solid #a8e7be;
            outline-offset: 3px;
        }

        .boton-login svg {
            width: 19px;
            height: 19px;
        }

        .seguridad {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 22px;
            color: #829087;
            font-size: 11px;
        }

        .seguridad svg {
            width: 15px;
            height: 15px;
            color: #29945b;
        }

        .enlace-publico {
            margin-top: 30px;
            padding-top: 23px;
            border-top: 1px solid #edf1ed;
            text-align: center;
        }

        .enlace-publico a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #23784a;
            font-size: 13px;
            font-weight: 650;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .enlace-publico a:hover {
            color: #124e30;
            text-decoration: underline;
        }

        .enlace-publico svg {
            width: 17px;
            height: 17px;
        }

        @media (max-width: 760px) {
            body {
                padding: 18px;
            }

            .contenedor {
                max-width: 470px;
                min-height: auto;
                grid-template-columns: 1fr;
                border-radius: 22px;
            }

            .panel-marca {
                min-height: 255px;
                padding: 27px 28px;
            }

            .presentacion {
                margin: 28px 0 5px;
            }

            .presentacion h1 {
                max-width: 360px;
                margin: 19px 0 11px;
                font-size: 34px;
            }

            .presentacion p {
                font-size: 13px;
            }

            .beneficios,
            .pie-marca {
                display: none;
            }

            .panel-login {
                padding: 34px 28px 30px;
            }

            .icono-acceso {
                width: 51px;
                height: 51px;
                margin-bottom: 20px;
                border-radius: 15px;
            }

            .panel-login h2 {
                font-size: 26px;
            }
        }

        @media (max-width: 380px) {
            body {
                padding: 10px;
            }

            .panel-marca,
            .panel-login {
                padding-right: 21px;
                padding-left: 21px;
            }

            .presentacion h1 {
                font-size: 30px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>
    <main class="contenedor">

        <section class="panel-marca" aria-label="Bolsa de Trabajo">
            <div class="marca">
                <div class="marca-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path
                            d="M3.5 8.5h17v10a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2v-10Z"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M8 8.5V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2.5M3.5 12h17M10 12v2h4v-2"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>

                <div>
                    <div class="marca-texto">Kombitec</div>
                    <div class="marca-subtitulo">Bolsa de Trabajo</div>
                </div>
            </div>

            <div class="presentacion">
                <div class="etiqueta">
                    <span class="punto"></span>
                    Plataforma administrativa
                </div>

                <h1>
                    Gestiona el talento.<br>
                    <span>Impulsa el futuro.</span>
                </h1>

                <p>
                    Accede al espacio de administración para gestionar
                    vacantes y dar seguimiento a las postulaciones.
                </p>

                <div class="beneficios">
                    <div class="beneficio">
                        <span class="beneficio-icono">&#10003;</span>
                        Administración de vacantes
                    </div>

                    <div class="beneficio">
                        <span class="beneficio-icono">&#10003;</span>
                        Seguimiento de candidatos
                    </div>

                    <div class="beneficio">
                        <span class="beneficio-icono">&#10003;</span>
                        Acceso exclusivo para personal autorizado
                    </div>
                </div>
            </div>

            <div class="pie-marca">
                Acceso reservado para usuarios administrativos autorizados.
            </div>
        </section>

        <section class="panel-login" aria-labelledby="titulo-login">

            <div class="icono-acceso" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect
                        x="5"
                        y="10"
                        width="14"
                        height="11"
                        rx="2.5"
                        stroke="currentColor"
                        stroke-width="1.8"
                    />
                    <path
                        d="M8 10V7a4 4 0 0 1 8 0v3"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                    <circle
                        cx="12"
                        cy="15"
                        r="1.3"
                        fill="currentColor"
                    />
                    <path
                        d="M12 16.3v1.5"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

            <h2 id="titulo-login">Bienvenido</h2>

            <p class="intro">
                Ingresa tus credenciales para acceder al panel administrativo.
            </p>

            <?php if ($error !== ''): ?>
                <div class="error" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />
                        <path
                            d="M12 7.5v5"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                        <circle cx="12" cy="16" r="1" fill="currentColor"/>
                    </svg>

                    <span>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ) ?>
                    </span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="on">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        (string) $_SESSION['login_csrf'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <div class="campo">
                    <label for="correo">Correo electrónico</label>

                    <div class="entrada-contenedor">
                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            placeholder="nombre@empresa.com"
                            value="<?= htmlspecialchars(
                                $correo,
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ) ?>"
                            autocomplete="username"
                            maxlength="255"
                            required
                            autofocus
                        >

                        <svg
                            class="entrada-icono"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <path
                                d="m4 7 8 6 8-6"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>

                    <div class="entrada-contenedor">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required
                        >

                        <svg
                            class="entrada-icono"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <rect
                                x="5"
                                y="10"
                                width="14"
                                height="11"
                                rx="2"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <path
                                d="M8 10V7a4 4 0 0 1 8 0v3"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>
                </div>

                <button class="boton-login" type="submit">
                    Iniciar sesión

                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M5 12h14m-6-6 6 6-6 6"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </button>
            </form>

            <div class="seguridad">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        d="M12 3 19 6v5c0 4.5-2.8 7.8-7 10-4.2-2.2-7-5.5-7-10V6l7-3Z"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="m9 12 2 2 4-4"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

                Acceso protegido para personal autorizado
            </div>

            <div class="enlace-publico">
                <a href="BolsaDeTrabajo.php">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="m14 18-6-6 6-6"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                    Volver a la Bolsa de Trabajo
                </a>
            </div>

        </section>
    </main>
</body>
</html>

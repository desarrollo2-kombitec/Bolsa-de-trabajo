<?php
require_once __DIR__ . "/includes/config.php";
header('Content-Type: text/html; charset=utf-8');

// FUNCIONES AUXILIARES
function e($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8'); }
function campoVacante($valor): string { return trim((string)($valor ?? '')); }
function formatearFecha($fecha): string {
    if (empty($fecha)) return 'Fecha por confirmar';
    $timestamp = strtotime((string)$fecha);
    if (!$timestamp) return 'Fecha por confirmar';
    return date('d/m/Y', $timestamp);
}
function formatearFechaHora($fecha): string {
    if (empty($fecha)) return 'Fecha por confirmar';
    $timestamp = strtotime((string)$fecha);
    if (!$timestamp) return 'Fecha por confirmar';
    return date('d/m/Y H:i', $timestamp);
}
function resumenTexto($texto, int $longitud = 155): string {
    $texto = strip_tags((string)($texto ?? ''));
    $texto = html_entity_decode($texto, ENT_QUOTES, 'UTF-8');
    $texto = preg_replace('/\s+/', ' ', $texto);
    $texto = trim((string)$texto);
    if ($texto === '') return '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($texto, 'UTF-8') <= $longitud) return $texto;
        return rtrim(mb_substr($texto, 0, $longitud - 3, 'UTF-8')) . '...';
    }
    if (strlen($texto) <= $longitud) return $texto;
    return rtrim(substr($texto, 0, $longitud - 3)) . '...';
}
function limpiarContenidoRichText($contenido): string {
    $contenido = (string)($contenido ?? '');

    // El editor contenteditable puede generar <div> al presionar Enter.
    // Se convierten en <p> para conservar correctamente cada salto de línea.
    $contenido = preg_replace(
        '/<div\b[^>]*>/i',
        '<p>',
        $contenido
    );

    $contenido = preg_replace(
        '/<\/div>/i',
        '</p>',
        $contenido
    );

    // Conservar únicamente las etiquetas utilizadas por el editor.
    $contenido = strip_tags(
        $contenido,
        '<strong><b><em><i><ul><ol><li><p><br>'
    );

    return trim($contenido);
}
function obtenerRutaImagen($urlImagen): string {
    $urlImagen = trim((string)($urlImagen ?? ''));

    if ($urlImagen === '') {
        return '';
    }

    // Si ya es una URL completa, conservarla.
    if (preg_match('#^https?://#i', $urlImagen)) {
        return $urlImagen;
    }

    $urlImagen = str_replace('\\', '/', $urlImagen);
    $nombreImagen = basename($urlImagen);

    // La aplicación está publicada en IIS como /BolsaTrabajo.
    return '/BolsaTrabajo/backend/modulo/Patch_BolsaTrabajo/uploads/img_vacante/'
        . rawurlencode($nombreImagen);
}

// OBTENER TOKEN
$token = trim($_GET['token'] ?? '');

// VARIABLES
$vacante = null;
$error = null;

// VALIDAR TOKEN Y CONSULTAR VACANTE
if ($token === '') {
    $error = 'No se especificó la vacante que deseas consultar.';
} else {
    $sql = "SELECT id, token, titulo, descripcion, ubicacion, tipo_jornada, modalidad, lo_que_se_ofrece, requisitos, responsabilidades, activo, archivada, solo_imagen, url_imagen, fecha_publicacion, fecha_cierre, created_at, updated_at FROM patch_BolsaTrabajo WHERE token = ? AND activo = 1 AND archivada = 0 LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error = 'No fue posible consultar la vacante.';
    } else {
        $stmt->bind_param("s", $token);
        if (!$stmt->execute()) {
            $error = 'No fue posible consultar la vacante.';
        } else {
            $resultado = $stmt->get_result();
            $vacante = $resultado->fetch_assoc();
            if (!$vacante) $error = 'La vacante que buscas no está disponible o ya no se encuentra publicada.';
        }
        $stmt->close();
    }
}

// PREPARAR INFORMACIÓN
$titulo = '';
$descripcion = '';
$ubicacion = '';
$tipoJornada = '';
$modalidad = '';
$oferta = '';
$requisitos = '';
$responsabilidades = '';
$urlImagen = '';
$rutaImagen = '';
$soloImagen = false;
$fechaPublicacion = '';
$fechaCierre = '';
$fechaCreacion = '';
$fechaActualizacion = '';
$activo = false;
$archivada = false;
$idVacante = 0;

if ($vacante) {
    $titulo = campoVacante($vacante['titulo']);
    $descripcion = campoVacante($vacante['descripcion']);
    $ubicacion = campoVacante($vacante['ubicacion']);
    $tipoJornada = campoVacante($vacante['tipo_jornada']);
    $modalidad = campoVacante($vacante['modalidad']);
    $oferta = limpiarContenidoRichText($vacante['lo_que_se_ofrece']);
    $requisitos = limpiarContenidoRichText($vacante['requisitos']);
    $responsabilidades = limpiarContenidoRichText($vacante['responsabilidades']);
    $urlImagen = campoVacante($vacante['url_imagen']);
    $rutaImagen = obtenerRutaImagen($urlImagen);
    $soloImagen = (int)$vacante['solo_imagen'] === 1;
    $fechaPublicacion = formatearFecha($vacante['fecha_publicacion']);
    if (!empty($vacante['fecha_cierre'])) $fechaCierre = formatearFecha($vacante['fecha_cierre']);
    $fechaCreacion = formatearFechaHora($vacante['created_at']);
    $fechaActualizacion = formatearFechaHora($vacante['updated_at']);
    $activo = (int)$vacante['activo'] === 1;
    $archivada = (int)$vacante['archivada'] === 1;
    $idVacante = (int)$vacante['id'];
}

// DESCRIPCIÓN SEO
if ($vacante) {
    if ($descripcion !== '') {
        $descripcionSEO = resumenTexto($descripcion, 155);
    } else {
        $descripcionSEO = 'Consulta los detalles de la vacante ' . $titulo . ' en Kombitec.';
    }
} else {
    $descripcionSEO = 'Detalle de vacante | Kombitec';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($descripcionSEO) ?>">
    <title><?php if ($vacante): ?><?= e($titulo) ?> | Bolsa de Trabajo | Kombitec<?php else: ?>Vacante no disponible | Bolsa de Trabajo | Kombitec<?php endif; ?></title>
    <link rel="stylesheet" href="css/detalle-vacante.css">

    <!-- AJUSTE DE TARJETAS SUPERIORES: Ubicación / Tipo de jornada / Modalidad -->
    <style>
        .informacion-vacante .informacion-item-centrada {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            text-align: left !important;
            gap: 14px !important;
            width: 100%;
            box-sizing: border-box;
        }
        .informacion-vacante .informacion-item-centrada .informacion-icono {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 48px !important;
            height: 48px !important;
            min-width: 48px !important;
            max-width: 48px !important;
            min-height: 48px !important;
            max-height: 48px !important;
            flex: 0 0 48px !important;
            margin: 0 !important;
            box-sizing: border-box;
            border-radius: 10px;
        }
        .informacion-vacante .informacion-item-centrada .informacion-icono svg {
            display: block !important;
            width: 23px !important;
            height: 23px !important;
            min-width: 23px !important;
            max-width: 23px !important;
            min-height: 23px !important;
            max-height: 23px !important;
            flex: 0 0 23px !important;
            margin: 0 !important;
        }
        .informacion-vacante .informacion-item-centrada > div {
            width: auto !important;
            min-width: 0;
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            justify-content: center !important;
            text-align: left !important;
            margin: 0 !important;
        }
        .informacion-vacante .informacion-item-centrada > div > span {
            display: block;
            margin: 0 0 3px 0;
            padding: 0;
            text-align: left;
        }
        .informacion-vacante .informacion-item-centrada > div > strong {
            display: block;
            margin: 0;
            padding: 0;
            text-align: left;
            line-height: 1.35;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        @media (max-width: 700px) {
            .informacion-vacante .informacion-item-centrada { gap: 12px !important; }
            .informacion-vacante .informacion-item-centrada .informacion-icono {
                width: 44px !important;
                height: 44px !important;
                min-width: 44px !important;
                max-width: 44px !important;
                min-height: 44px !important;
                max-height: 44px !important;
                flex-basis: 44px !important;
            }
            .informacion-vacante .informacion-item-centrada .informacion-icono svg {
                width: 22px !important;
                height: 22px !important;
                min-width: 22px !important;
                max-width: 22px !important;
                min-height: 22px !important;
                max-height: 22px !important;
                flex-basis: 22px !important;
            }
        }
    </style>
</head>
<body>

<header class="detalle-header">
    <nav class="navbar">
        <a class="logo" href="BolsaDeTrabajo.php" aria-label="Bolsa de Trabajo">
            <span class="maletin" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 8V6.8A1.8 1.8 0 0 1 9.8 5h4.4A1.8 1.8 0 0 1 16 6.8V8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M4.5 9.5h15A1.5 1.5 0 0 1 21 11v7.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5V11a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3.3 13.2c2.4 1.5 5.1 2.2 8.7 2.2s6.3-.7 8.7-2.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span>Bolsa de Trabajo</span>
        </a>
        <div class="menu">
            <a href="BolsaDeTrabajo.php#vacantes">Vacantes</a>
            <a href="#footer">Contacto</a>
        </div>
    </nav>
</header>

<main class="detalle-contenido">
<?php if ($error): ?>
    <section class="vacante-error">
        <div class="vacante-error-icono" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 7v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="12" cy="16" r="1" fill="currentColor"/>
            </svg>
        </div>
        <span class="kicker">Bolsa de Trabajo</span>
        <h1>Vacante no disponible</h1>
        <p><?= e($error) ?></p>
        <a class="boton-principal" href="BolsaDeTrabajo.php#vacantes">
            <span aria-hidden="true">←</span>
            Ver vacantes disponibles
        </a>
    </section>
<?php else: ?>

    <div class="detalle-regreso">
        <a href="BolsaDeTrabajo.php#vacantes" class="boton-regresar">
            <span aria-hidden="true">←</span>
            Volver a vacantes
        </a>
    </div>

    <!-- ENCABEZADO DE LA VACANTE -->
    <section class="detalle-hero">
        <div class="detalle-hero-contenido">
            <span class="detalle-fecha">Publicada el <?= e($fechaPublicacion) ?></span>
            <h1><?= e($titulo ?: 'Vacante disponible') ?></h1>
            <?php if ($descripcion !== ''): ?>
                <div class="detalle-descripcion"><?= nl2br(e($descripcion)) ?></div>
            <?php else: ?>
                <div class="detalle-descripcion detalle-descripcion-vacia">Consulta la información completa de esta vacante.</div>
            <?php endif; ?>
        </div>
    </section>

    <!-- INFORMACIÓN PRINCIPAL -->
    <section class="informacion-vacante">

        <?php if ($ubicacion !== ''): ?>
            <div class="informacion-item informacion-item-centrada">
                <span class="informacion-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 10c0 5-8 10-8 10S4 15 4 10a8 8 0 1 1 16 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </span>
                <div>
                    <span>Ubicación</span>
                    <strong><?= e($ubicacion) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($tipoJornada !== ''): ?>
            <div class="informacion-item informacion-item-centrada">
                <span class="informacion-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div>
                    <span>Tipo de jornada</span>
                    <strong><?= e($tipoJornada) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($modalidad !== ''): ?>
            <div class="informacion-item informacion-item-centrada">
                <span class="informacion-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="5" width="16" height="11" rx="1.5" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M8 20h8M12 16v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div>
                    <span>Modalidad</span>
                    <strong><?= e($modalidad) ?></strong>
                </div>
            </div>
        <?php endif; ?>

    </section>

    <!-- CONTENIDO DE LA VACANTE -->
    <div class="detalle-grid">

        <!-- COLUMNA PRINCIPAL -->
        <div class="detalle-principal">

            <?php if ($rutaImagen !== ''): ?>
                <section class="bloque-imagen">
                    <div class="bloque-imagen-header">
                        <div>
                            <span>Anuncio de la vacante</span>
                            <h2>Información visual</h2>
                        </div>
                    </div>
                    <div class="imagen-contenedor">
                        <img src="<?= e($rutaImagen) ?>" alt="Imagen de la vacante <?= e($titulo) ?>" class="imagen-vacante">
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!$soloImagen): ?>

                <?php if ($oferta !== ''): ?>
                    <section class="bloque-detalle">
                        <div class="titulo-bloque">
                            <span class="icono-bloque" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 3l2.2 5.1L20 9l-4 3.8 1.1 5.6L12 15.7 6.9 18.4 8 12.8 4 9l5.8-.9L12 3Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <span>Beneficios</span>
                                <h2>Lo que ofrecemos</h2>
                            </div>
                        </div>
                        <div class="contenido-rich-text"><?= $oferta ?></div>
                    </section>
                <?php endif; ?>

                <?php if ($requisitos !== ''): ?>
                    <section class="bloque-detalle">
                        <div class="titulo-bloque">
                            <span class="icono-bloque" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 4h14v16H5z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <span>Perfil buscado</span>
                                <h2>Requisitos</h2>
                            </div>
                        </div>
                        <div class="contenido-rich-text"><?= $requisitos ?></div>
                    </section>
                <?php endif; ?>

                <?php if ($responsabilidades !== ''): ?>
                    <section class="bloque-detalle">
                        <div class="titulo-bloque">
                            <span class="icono-bloque" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M8 12l2.5 2.5L16 9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <span>Funciones del puesto</span>
                                <h2>Responsabilidades</h2>
                            </div>
                        </div>
                        <div class="contenido-rich-text"><?= $responsabilidades ?></div>
                    </section>
                <?php endif; ?>

                <?php if ($oferta === '' && $requisitos === '' && $responsabilidades === '' && $rutaImagen === ''): ?>
                    <section class="bloque-detalle bloque-sin-contenido">
                        <div class="icono-sin-contenido" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M12 11v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <circle cx="12" cy="8" r="1" fill="currentColor"/>
                            </svg>
                        </div>
                        <h2>Consulta la información completa de esta vacante.</h2>
                    </section>
                <?php endif; ?>

            <?php endif; ?>

        </div>

        <!-- COLUMNA LATERAL -->
        <aside class="detalle-lateral">

            <!-- CARD PARA POSTULARSE -->
            <section class="card-postulacion">
                <span class="card-kicker">¿Te interesa esta oportunidad?</span>
                <h2>Da el siguiente paso.</h2>
                <p>Si deseas participar en el proceso de selección, puedes enviarnos tu CV.</p>

                <button type="button" class="boton-postular" id="abrirModalPostulacion" data-vacante-token="<?= e($token) ?>" data-vacante-titulo="<?= e($titulo) ?>">
                    <span class="boton-postular-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5v-13Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="m4.5 6 7.5 5.5L19.5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>Postularse</span>
                    <span class="boton-postular-flecha" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12h13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="m13 6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>

                <span class="texto-postulacion">Al seleccionar esta opción podrás postularte a esta vacante.</span>
            </section>

            <!-- RESUMEN -->
            <section class="card-resumen">
                <div class="card-resumen-header">
                    <span>Resumen</span>
                    <h2>Datos de la vacante</h2>
                </div>

                <!-- PUESTO -->
                <div class="resumen-item">
                    <span class="resumen-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="4" y="7" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.7"/>
                            <path d="M9 7V5.8A1.8 1.8 0 0 1 10.8 4h2.4A1.8 1.8 0 0 1 15 5.8V7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            <path d="M4 12h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <span>Puesto</span>
                        <strong><?= e($titulo) ?></strong>
                    </div>
                </div>

                <!-- UBICACIÓN -->
                <?php if ($ubicacion !== ''): ?>
                    <div class="resumen-item">
                        <span class="resumen-icono" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 10c0 5-8 10-8 10S4 15 4 10a8 8 0 1 1 16 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.7"/>
                            </svg>
                        </span>
                        <div>
                            <span>Ubicación</span>
                            <strong><?= e($ubicacion) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- JORNADA -->
                <?php if ($tipoJornada !== ''): ?>
                    <div class="resumen-item">
                        <span class="resumen-icono" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <span>Jornada</span>
                            <strong><?= e($tipoJornada) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- MODALIDAD -->
                <?php if ($modalidad !== ''): ?>
                    <div class="resumen-item">
                        <span class="resumen-icono" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="4" y="5" width="16" height="11" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M8 20h8M12 16v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <span>Modalidad</span>
                            <strong><?= e($modalidad) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>

            </section>

            <a class="enlace-regresar" href="BolsaDeTrabajo.php#vacantes">
                <span aria-hidden="true">←</span>
                Ver todas las vacantes
            </a>

        </aside>
    </div>

<?php endif; ?>
</main>

<!-- MODAL DE POSTULACIÓN -->
<?php if (!$error): ?>
    <?php require_once __DIR__ . "/partials/modal/modal_postulacion.php"; ?>
<?php endif; ?>

<footer class="footer" id="footer">
    <div class="footer-contenido">
        <div>
            <strong>Bolsa de Trabajo</strong>
            <span>Encuentra tu próxima oportunidad.</span>
        </div>
        <a href="mailto:christianchavez394@gmail.com">Contacto</a>
    </div>
</footer>

</body>
</html>

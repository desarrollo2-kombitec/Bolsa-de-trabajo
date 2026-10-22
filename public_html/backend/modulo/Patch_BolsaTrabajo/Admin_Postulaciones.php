<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../includes/auth.php";
requerirAdminPagina('../../../login.php');
header('Content-Type: text/html; charset=utf-8');

/* ============================================================
   FUNCIONES AUXILIARES
   ============================================================ */

function e($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function estadoTexto(string $estado): string
{
    return match ($estado) {
        'nueva' => 'Por revisar',
        'en_proceso' => 'En proceso',
        'entrevista' => 'Entrevista',
        'aceptado' => 'Aceptado',
        'descartado' => 'Descartado',
        default => ucwords(str_replace('_', ' ', $estado))
    };
}

function estadoClase(string $estado): string
{
    return match ($estado) {
        'nueva' => 'review',
        'en_proceso' => 'process',
        'entrevista' => 'interview',
        'aceptado' => 'accepted',
        'descartado' => 'rejected',
        default => 'review'
    };
}

function formatearFecha($fecha): string
{
    if (empty($fecha)) {
        return 'Sin fecha';
    }

    $timestamp = strtotime($fecha);

    if ($timestamp === false) {
        return e($fecha);
    }

    return date('d/m/Y H:i', $timestamp);
}

function iniciales(string $nombre): string
{
    $nombre = trim($nombre);

    if ($nombre === '') {
        return '?';
    }

    $partes = preg_split('/\s+/', $nombre);
    $resultado = '';

    if (!empty($partes[0])) {
        $resultado .= mb_substr($partes[0], 0, 1);
    }

    if (count($partes) > 1) {
        $ultima = $partes[count($partes) - 1];
        $resultado .= mb_substr($ultima, 0, 1);
    }

    return mb_strtoupper($resultado);
}

function valorSeguro($valor, string $default = 'No especificado'): string
{
    $valor = trim((string)($valor ?? ''));

    return $valor !== '' ? $valor : $default;
}

/* ============================================================
   ESTADOS PERMITIDOS
   ============================================================ */

$estadosPermitidos = [
    'nueva',
    'en_proceso',
    'entrevista',
    'aceptado',
    'descartado'
];

/* ============================================================
   MENSAJES DE OPERACIÓN
   ============================================================ */

$mensajeOperacion = '';
$errorOperacion = '';

/* ============================================================
   ACTUALIZAR ESTADO DE POSTULACIÓN
   ============================================================ */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['accion'] ?? '') === 'actualizar_estado'
) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $estado = trim((string)($_POST['estado'] ?? ''));

    if ($id <= 0) {
        $errorOperacion = 'La postulación indicada no es válida.';
    } elseif (!in_array($estado, $estadosPermitidos, true)) {
        $errorOperacion = 'El estado seleccionado no es válido.';
    } else {
        $stmt = $conn->prepare("
            UPDATE patch_postulaciones
            SET estado = ?
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param('si', $estado, $id);

            if ($stmt->execute()) {
                $mensajeOperacion = 'El estado de la postulación se actualizó correctamente.';
            } else {
                $errorOperacion = 'No fue posible actualizar el estado de la postulación.';
            }

            $stmt->close();
        } else {
            $errorOperacion = 'No fue posible preparar la actualización.';
        }
    }
}

/* ============================================================
   FILTROS
   ============================================================ */

$buscar = trim((string)($_GET['buscar'] ?? ''));

$filtroVacante = isset($_GET['vacante'])
    ? max(0, (int)$_GET['vacante'])
    : 0;

$filtroEstado = trim((string)($_GET['estado'] ?? ''));

/* Evitar aplicar valores de estado que no estén permitidos. */
if (
    $filtroEstado !== '' &&
    !in_array($filtroEstado, $estadosPermitidos, true)
) {
    $filtroEstado = '';
}

/* ============================================================
   VACANTES CON POSTULACIONES
   ============================================================ */

$vacantesPostulaciones = [];

$sqlVacantes = "
    SELECT
        b.id,
        b.token,
        b.titulo,
        b.ubicacion,
        b.tipo_jornada,
        b.modalidad,
        b.activo,
        b.archivada,
        COUNT(p.id) AS total_postulaciones,
        SUM(CASE WHEN p.estado = 'nueva' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN p.estado = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
        SUM(CASE WHEN p.estado = 'entrevista' THEN 1 ELSE 0 END) AS entrevistas,
        SUM(CASE WHEN p.estado = 'aceptado' THEN 1 ELSE 0 END) AS aceptados,
        SUM(CASE WHEN p.estado = 'descartado' THEN 1 ELSE 0 END) AS descartados
    FROM patch_BolsaTrabajo b
    INNER JOIN patch_postulaciones p
        ON p.id_bolsa = b.id
    GROUP BY
        b.id,
        b.token,
        b.titulo,
        b.ubicacion,
        b.tipo_jornada,
        b.modalidad,
        b.activo,
        b.archivada
    ORDER BY
        total_postulaciones DESC,
        b.titulo ASC
";

$resultVacantes = $conn->query($sqlVacantes);

if ($resultVacantes) {
    while ($fila = $resultVacantes->fetch_assoc()) {
        $vacantesPostulaciones[] = $fila;
    }

    $resultVacantes->free();
}

/* ============================================================
   RESUMEN GENERAL
   ============================================================ */

$totalPostulaciones = 0;
$totalPendientes = 0;
$totalEnProceso = 0;
$totalEntrevistas = 0;
$totalAceptados = 0;
$totalDescartados = 0;
$totalCVGenerales = 0;

$sqlResumen = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN estado = 'nueva' THEN 1 ELSE 0 END) AS nuevas,
        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
        SUM(CASE WHEN estado = 'entrevista' THEN 1 ELSE 0 END) AS entrevistas,
        SUM(CASE WHEN estado = 'aceptado' THEN 1 ELSE 0 END) AS aceptados,
        SUM(CASE WHEN estado = 'descartado' THEN 1 ELSE 0 END) AS descartados,
        SUM(CASE WHEN id_bolsa IS NULL THEN 1 ELSE 0 END) AS cv_generales
    FROM patch_postulaciones
";

$resultResumen = $conn->query($sqlResumen);

if ($resultResumen) {
    $resumen = $resultResumen->fetch_assoc();

    $totalPostulaciones = (int)($resumen['total'] ?? 0);
    $totalPendientes = (int)($resumen['nuevas'] ?? 0);
    $totalEnProceso = (int)($resumen['en_proceso'] ?? 0);
    $totalEntrevistas = (int)($resumen['entrevistas'] ?? 0);
    $totalAceptados = (int)($resumen['aceptados'] ?? 0);
    $totalDescartados = (int)($resumen['descartados'] ?? 0);
    $totalCVGenerales = (int)($resumen['cv_generales'] ?? 0);

    $resultResumen->free();
}

/* ============================================================
   POSTULACIONES A VACANTES
   ============================================================ */

$postulaciones = [];

$where = ['p.id_bolsa IS NOT NULL'];

if ($buscar !== '') {
    $buscarSQL = $conn->real_escape_string($buscar);

    $where[] = "(
        p.nombre_completo LIKE '%{$buscarSQL}%'
        OR p.correo LIKE '%{$buscarSQL}%'
        OR p.telefono LIKE '%{$buscarSQL}%'
        OR b.titulo LIKE '%{$buscarSQL}%'
    )";
}

if ($filtroVacante > 0) {
    $where[] = "p.id_bolsa = " . $filtroVacante;
}

if ($filtroEstado !== '') {
    $estadoSQL = $conn->real_escape_string($filtroEstado);
    $where[] = "p.estado = '{$estadoSQL}'";
}

$whereSQL = implode(' AND ', $where);

$sqlPostulaciones = "
    SELECT
        p.id,
        p.id_bolsa,
        p.nombre_completo,
        p.correo,
        p.telefono,
        p.cv,
        p.comentarios,
        p.estado,
        p.fecha_postulacion,
        p.updated_at,
        b.titulo AS vacante_titulo,
        b.ubicacion AS vacante_ubicacion,
        b.tipo_jornada AS vacante_jornada,
        b.modalidad AS vacante_modalidad
    FROM patch_postulaciones p
    INNER JOIN patch_BolsaTrabajo b
        ON b.id = p.id_bolsa
    WHERE {$whereSQL}
    ORDER BY
        p.fecha_postulacion DESC,
        p.id DESC
";

$resultPostulaciones = $conn->query($sqlPostulaciones);

if ($resultPostulaciones) {
    while ($fila = $resultPostulaciones->fetch_assoc()) {
        $postulaciones[] = $fila;
    }

    $resultPostulaciones->free();
}

/* ============================================================
   RESPUESTA AJAX: DEVOLVER ÚNICAMENTE EL PANEL
   ============================================================ */

/*
 * postulaciones-listado.js solicita:
 *
 * Admin_Postulaciones.php?buscar=...&vacante=...&estado=...&ajax=listado
 *
 * En este caso no debemos devolver el documento HTML completo.
 * El JavaScript reemplaza #panelPostulaciones y conserva el resto
 * de la página, incluidos los CV generales, el resumen y el modal.
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    ($_GET['ajax'] ?? '') === 'listado'
) {
    require __DIR__ . '/partials/postulaciones_lista.php';
    exit;
}

/* ============================================================
   CV GENERALES
   ============================================================ */

$cvGenerales = [];

$sqlCVGenerales = "
    SELECT
        p.id,
        p.id_bolsa,
        p.nombre_completo,
        p.correo,
        p.telefono,
        p.cv,
        p.comentarios,
        p.estado,
        p.fecha_postulacion,
        p.updated_at
    FROM patch_postulaciones p
    WHERE p.id_bolsa IS NULL
    ORDER BY
        p.fecha_postulacion DESC,
        p.id DESC
";

$resultCVGenerales = $conn->query($sqlCVGenerales);

if ($resultCVGenerales) {
    while ($fila = $resultCVGenerales->fetch_assoc()) {
        $cvGenerales[] = $fila;
    }

    $resultCVGenerales->free();
}

/* ============================================================
   TÍTULO Y DESCRIPCIÓN
   ============================================================ */

$title = 'Postulaciones | Bolsa de Trabajo';
$description = 'Administración de postulaciones y CV generales de Kombitec';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($title); ?></title>

    <meta name="description" content="<?php echo e($description); ?>">

    <!-- FUENTE -->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap"
    >

    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <!-- CSS DEL MÓDULO -->
    <link rel="stylesheet" href="css/Admin_Postulaciones.css">
</head>

<body>
    <div class="patch-postulaciones-wrapper">

        <!-- MENÚ DE USUARIO -->
        <details class="patch-user-menu" id="patchUserMenu">
            <summary
                aria-label="Abrir menú de usuario"
                aria-haspopup="true"
            >
                <span class="patch-user-avatar">
                    <i class="fa fa-user" aria-hidden="true"></i>
                </span>

                <span class="patch-user-label">Mi cuenta</span>

                <i
                    class="fa fa-chevron-down patch-user-arrow"
                    aria-hidden="true"
                ></i>
            </summary>

            <div class="patch-user-dropdown">
                <div class="patch-user-info">
                    <strong>Sesión administrativa</strong>
                    <span>Opciones de tu cuenta</span>
                </div>

                <div class="patch-user-divider"></div>

                <a href="../../../cerrar_sesion.php">
                    <i class="fa fa-sign-out-alt" aria-hidden="true"></i>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </details>




        <div class="patch-postulaciones-container">

            <div class="patch-postulaciones-actions">
                <a href="Admin_BolsaTrabajo.php" class="patch-postulaciones-btn">
                    <i class="fa fa-arrow-left"></i>
                    Regresar a vacantes
                </a>
            </div>

            <br>

            <!-- ENCABEZADO -->
            <div class="patch-postulaciones-header">
                <div class="patch-postulaciones-header-content">
                    <div class="patch-postulaciones-header-info">

                        <div class="patch-postulaciones-breadcrumb">
                            <a href="Admin_BolsaTrabajo.php">Bolsa de Trabajo</a>
                            <i class="fa fa-angle-right"></i>
                            <span>Postulaciones</span>
                        </div>

                        <h1>Administración de postulaciones</h1>

                        <p>
                            Consulta y administra las personas que han enviado una
                            postulación a las vacantes publicadas y los CV generales
                            recibidos por Kombitec.
                        </p>
                    </div>

                    <div class="patch-postulaciones-header-icon">
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>

            <!-- RESUMEN -->
            <?php
            require_once __DIR__ . '/partials/postulaciones_resumen.php';
            ?>

            <!-- VACANTES CON POSTULACIONES -->
            <?php
            require_once __DIR__ . '/partials/postulaciones_vacantes.php';
            ?>

            <!-- LISTA DE POSTULACIONES -->
            <?php
            require_once __DIR__ . '/partials/postulaciones_lista.php';
            ?>

            <!-- CV GENERALES -->
            <?php
            require_once __DIR__ . '/partials/postulaciones_cv_generales.php';
            ?>

        </div>
    </div>

    <!-- MODAL DE DETALLE DE POSTULACIÓN -->
    <?php
    require_once __DIR__ . '/partials/modal_ver_postulacion.php';
    ?>

    <!-- FORMULARIO OCULTO PARA ACTUALIZAR ESTADO -->
    <form method="POST" id="formActualizarEstado" style="display:none;">
        <input type="hidden" name="accion" value="actualizar_estado">
        <input type="hidden" name="id" id="estadoPostulacionId" value="">
        <input type="hidden" name="estado" id="estadoPostulacionValor" value="">
    </form>

    <!-- TOAST DE CONFIRMACIÓN -->
    <div
        class="patch-toast"
        id="patchToast"
        role="status"
        aria-live="polite"
        aria-atomic="true"
    ></div>

    <!-- JAVASCRIPT -->
    <script src="js/postulaciones-api.js"></script>
    <script src="js/postulaciones-listado.js"></script>
    <script src="js/postulaciones-modal.js"></script>

    <!-- MENSAJES DE OPERACIÓN -->
    <?php if ($mensajeOperacion !== ''): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof mostrarToast === 'function') {
                    mostrarToast(
                        <?php
                        echo json_encode(
                            $mensajeOperacion,
                            JSON_HEX_TAG |
                            JSON_HEX_APOS |
                            JSON_HEX_AMP |
                            JSON_HEX_QUOT
                        );
                        ?>
                    );
                }
            });
        </script>
    <?php endif; ?>

    <?php if ($errorOperacion !== ''): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof mostrarToast === 'function') {
                    mostrarToast(
                        <?php
                        echo json_encode(
                            $errorOperacion,
                            JSON_HEX_TAG |
                            JSON_HEX_APOS |
                            JSON_HEX_AMP |
                            JSON_HEX_QUOT
                        );
                        ?>,
                        true
                    );
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>

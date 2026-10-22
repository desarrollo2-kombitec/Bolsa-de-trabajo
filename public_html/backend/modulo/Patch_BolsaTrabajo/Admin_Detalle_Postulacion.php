<?php
require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../includes/auth.php";
requerirAdminPagina('../../../login.php');
header('Content-Type: text/html; charset=utf-8');

/* =========================================================
   FUNCIONES AUXILIARES
   ========================================================= */
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
    if (empty($fecha)) return 'Sin fecha';
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return e($fecha);
    return date('d/m/Y H:i', $timestamp);
}

function formatearFechaCorta($fecha): string
{
    if (empty($fecha)) return 'Sin fecha';
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return e($fecha);
    return date('d/m/Y', $timestamp);
}

function iniciales(string $nombre): string
{
    $nombre = trim($nombre);
    if ($nombre === '') return '?';

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

/* =========================================================
   ESTADOS PERMITIDOS
   ========================================================= */
$estadosPermitidos = ['nueva', 'en_proceso', 'entrevista', 'aceptado', 'descartado'];

/* =========================================================
   IDENTIFICADOR DE LA VACANTE
   ========================================================= */
$idBolsa = 0;

if (isset($_GET['vacante'])) {
    $idBolsa = filter_var($_GET['vacante'], FILTER_VALIDATE_INT);
    if ($idBolsa === false) $idBolsa = 0;
}

if ($idBolsa <= 0 && isset($_GET['id_bolsa'])) {
    $idBolsa = filter_var($_GET['id_bolsa'], FILTER_VALIDATE_INT);
    if ($idBolsa === false) $idBolsa = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idBolsa <= 0 && isset($_POST['id_bolsa'])) {
    $idBolsa = filter_var($_POST['id_bolsa'], FILTER_VALIDATE_INT);
    if ($idBolsa === false) $idBolsa = 0;
}

/* =========================================================
   VARIABLES
   ========================================================= */
$mensajeOperacion = '';
$errorOperacion = '';
$vacante = null;
$postulacionesVacante = [];

/* =========================================================
   ACTUALIZAR ESTADO
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'actualizar_estado') {
    $idPostulacion = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $estado = trim((string)($_POST['estado'] ?? ''));

    if ($idPostulacion <= 0) {
        $errorOperacion = 'La postulación indicada no es válida.';
    } elseif (!in_array($estado, $estadosPermitidos, true)) {
        $errorOperacion = 'El estado seleccionado no es válido.';
    } else {
        $stmt = $conn->prepare("UPDATE patch_postulaciones SET estado = ? WHERE id = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param('si', $estado, $idPostulacion);

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

/* =========================================================
   VALIDAR ID DE VACANTE
   ========================================================= */
if ($idBolsa <= 0) {
    http_response_code(400);
    $tituloError = 'Vacante no válida';
    $descripcionError = 'No se recibió un identificador válido de la vacante.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($tituloError); ?> | Bolsa de Trabajo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/Admin_Detalle_Postulacion.css">
</head>
<body>
<div class="patch-detail-wrapper">
    <div class="patch-detail-container">
        <div class="patch-detail-actions">
            <a href="Admin_Postulaciones.php" class="patch-detail-btn"><i class="fa fa-arrow-left"></i> Regresar a postulaciones</a>
        </div>
        <div class="patch-detail-error">
            <div class="patch-detail-error-icon"><i class="fa fa-circle-exclamation"></i></div>
            <h1>Vacante no válida</h1>
            <p><?php echo e($descripcionError); ?></p>
            <a href="Admin_Postulaciones.php" class="patch-detail-error-button"><i class="fa fa-arrow-left"></i> Regresar a postulaciones</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

/* =========================================================
   OBTENER VACANTE
   ========================================================= */
$stmtVacante = $conn->prepare("
    SELECT
        id,
        token,
        titulo,
        descripcion,
        ubicacion,
        tipo_jornada,
        modalidad,
        lo_que_se_ofrece,
        requisitos,
        responsabilidades,
        activo,
        archivada,
        solo_imagen,
        url_imagen,
        fecha_publicacion,
        fecha_cierre
    FROM patch_BolsaTrabajo
    WHERE id = ?
    LIMIT 1
");

if ($stmtVacante) {
    $stmtVacante->bind_param('i', $idBolsa);

    if ($stmtVacante->execute()) {
        $resultadoVacante = $stmtVacante->get_result();

        if ($resultadoVacante && $resultadoVacante->num_rows > 0) {
            $vacante = $resultadoVacante->fetch_assoc();
            $resultadoVacante->free();
        }
    }

    $stmtVacante->close();
}

/* =========================================================
   VALIDAR QUE LA VACANTE EXISTA
   ========================================================= */
if (!$vacante) {
    http_response_code(404);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacante no encontrada | Bolsa de Trabajo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/Admin_Detalle_Postulacion.css">
</head>
<body>
<div class="patch-detail-wrapper">
    <div class="patch-detail-container">
        <div class="patch-detail-actions">
            <a href="Admin_Postulaciones.php" class="patch-detail-btn"><i class="fa fa-arrow-left"></i> Regresar a postulaciones</a>
        </div>
        <div class="patch-detail-error">
            <div class="patch-detail-error-icon"><i class="fa fa-circle-exclamation"></i></div>
            <h1>Vacante no encontrada</h1>
            <p>La vacante que intentas consultar no existe o ya no se encuentra disponible.</p>
            <a href="Admin_Postulaciones.php" class="patch-detail-error-button"><i class="fa fa-arrow-left"></i> Regresar a postulaciones</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

/* =========================================================
   OBTENER POSTULACIONES DE LA VACANTE
   ========================================================= */
$stmtPostulaciones = $conn->prepare("
    SELECT
        id,
        id_bolsa,
        nombre_completo,
        correo,
        telefono,
        cv,
        comentarios,
        estado,
        fecha_postulacion,
        updated_at
    FROM patch_postulaciones
    WHERE id_bolsa = ?
    ORDER BY fecha_postulacion DESC, id DESC
");

if ($stmtPostulaciones) {
    $stmtPostulaciones->bind_param('i', $idBolsa);

    if ($stmtPostulaciones->execute()) {
        $resultadoPostulaciones = $stmtPostulaciones->get_result();

        if ($resultadoPostulaciones) {
            while ($fila = $resultadoPostulaciones->fetch_assoc()) {
                $postulacionesVacante[] = $fila;
            }

            $resultadoPostulaciones->free();
        }
    }

    $stmtPostulaciones->close();
}

/* =========================================================
   CONTADORES
   ========================================================= */
$totalPostulacionesVacante = count($postulacionesVacante);
$pendientesVacante = 0;
$enProcesoVacante = 0;
$entrevistasVacante = 0;
$aceptadosVacante = 0;
$descartadosVacante = 0;

foreach ($postulacionesVacante as $fila) {
    $estadoFila = trim((string)($fila['estado'] ?? 'nueva'));

    if (!in_array($estadoFila, $estadosPermitidos, true)) {
        $estadoFila = 'nueva';
    }

    switch ($estadoFila) {
        case 'nueva':
            $pendientesVacante++;
            break;
        case 'en_proceso':
            $enProcesoVacante++;
            break;
        case 'entrevista':
            $entrevistasVacante++;
            break;
        case 'aceptado':
            $aceptadosVacante++;
            break;
        case 'descartado':
            $descartadosVacante++;
            break;
    }
}

/* =========================================================
   DATOS DE LA VACANTE
   ========================================================= */
$tituloVacante = valorSeguro($vacante['titulo'] ?? '', 'Vacante sin título');
$ubicacionVacante = valorSeguro($vacante['ubicacion'] ?? '', 'No especificada');
$jornadaVacante = valorSeguro($vacante['tipo_jornada'] ?? '', 'No especificada');
$modalidadVacante = valorSeguro($vacante['modalidad'] ?? '', 'No especificada');
$descripcionVacante = trim((string)($vacante['descripcion'] ?? ''));
$ofreceVacante = trim((string)($vacante['lo_que_se_ofrece'] ?? ''));
$requisitosVacante = trim((string)($vacante['requisitos'] ?? ''));
$responsabilidadesVacante = trim((string)($vacante['responsabilidades'] ?? ''));
$soloImagen = (int)($vacante['solo_imagen'] ?? 0);
$urlImagen = trim((string)($vacante['url_imagen'] ?? ''));

/* =========================================================
   ESTADO DE LA VACANTE
   ========================================================= */
$esArchivada = (int)($vacante['archivada'] ?? 0) === 1;
$esActiva = (int)($vacante['activo'] ?? 0) === 1;

if ($esArchivada) {
    $estadoVacanteTexto = 'Archivada';
    $estadoVacanteClase = 'archived';
    $estadoVacanteIcono = 'fa-box-archive';
} elseif ($esActiva) {
    $estadoVacanteTexto = 'Activa';
    $estadoVacanteClase = 'active';
    $estadoVacanteIcono = 'fa-circle-check';
} else {
    $estadoVacanteTexto = 'Inactiva';
    $estadoVacanteClase = 'inactive';
    $estadoVacanteIcono = 'fa-circle-pause';
}

/* =========================================================
   RUTA DE IMAGEN
   ========================================================= */
$rutaImagenVacante = '';

if ($urlImagen !== '') {
    $imagenNormalizada = str_replace('\\', '/', $urlImagen);

    if (
        str_starts_with($imagenNormalizada, 'http://') ||
        str_starts_with($imagenNormalizada, 'https://') ||
        str_starts_with($imagenNormalizada, '/')
    ) {
        $rutaImagenVacante = $imagenNormalizada;
    } elseif (str_starts_with($imagenNormalizada, 'backend/')) {
        $rutaImagenVacante = '../' . $imagenNormalizada;
    } elseif (str_starts_with($imagenNormalizada, 'uploads/')) {
        $rutaImagenVacante = $imagenNormalizada;
    } else {
        $rutaImagenVacante = 'uploads/img_vacante/' . ltrim($imagenNormalizada, '/');
    }
}

/* =========================================================
   INFORMACIÓN DE LA PÁGINA
   ========================================================= */
$title = $tituloVacante . ' | Postulaciones';
$description = 'Dashboard de postulaciones de la vacante ' . $tituloVacante . '.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <meta name="description" content="<?php echo e($description); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/Admin_Detalle_Postulacion.css">
    <link rel="stylesheet" href="css/Admin_Postulaciones.css">
</head>
<body>
<div class="patch-detail-wrapper">
    <div class="patch-detail-container">
        <div class="patch-vacancy-dashboard-back">
            <a href="Admin_Postulaciones.php" class="patch-detail-btn"><i class="fa fa-arrow-left"></i> Regresar a postulaciones</a>
        </div>

        <!-- =====================================================
             ENCABEZADO DE LA VACANTE
             ===================================================== -->
        <div class="patch-vacancy-dashboard-header">
            <div class="patch-vacancy-dashboard-top">
                <div class="patch-vacancy-dashboard-title">
                    <div class="patch-vacancy-dashboard-icon"><i class="fa fa-briefcase"></i></div>
                    <div>
                        <h1><?php echo e($tituloVacante); ?></h1>
                        <p>Dashboard de postulaciones de esta vacante.</p>
                    </div>
                </div>
                <div class="patch-vacancy-dashboard-status">
                    <span class="patch-vacancy-status <?php echo e($estadoVacanteClase); ?>">
                        <i class="fa <?php echo e($estadoVacanteIcono); ?>"></i>
                        <?php echo e($estadoVacanteTexto); ?>
                    </span>
                </div>
            </div>

            <div class="patch-vacancy-dashboard-meta">
                <div class="patch-vacancy-dashboard-meta-item">
                    <i class="fa fa-location-dot"></i>
                    <div>
                        <span>Ubicación</span>
                        <strong><?php echo e($ubicacionVacante); ?></strong>
                    </div>
                </div>
                <div class="patch-vacancy-dashboard-meta-item">
                    <i class="fa fa-clock"></i>
                    <div>
                        <span>Tipo de jornada</span>
                        <strong><?php echo e($jornadaVacante); ?></strong>
                    </div>
                </div>
                <div class="patch-vacancy-dashboard-meta-item">
                    <i class="fa fa-building"></i>
                    <div>
                        <span>Modalidad</span>
                        <strong><?php echo e($modalidadVacante); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- =====================================================
             ESTADÍSTICAS
             ===================================================== -->
        <div class="patch-vacancy-dashboard-stats">
            <div class="patch-vacancy-dashboard-stat">
                <div class="patch-vacancy-dashboard-stat-top">
                    <span class="patch-vacancy-dashboard-stat-label">Total</span>
                    <span class="patch-vacancy-dashboard-stat-icon"><i class="fa fa-users"></i></span>
                </div>
                <strong class="patch-vacancy-dashboard-stat-value"><?php echo $totalPostulacionesVacante; ?></strong>
                <span class="patch-vacancy-dashboard-stat-label">Postulaciones recibidas</span>
            </div>

            <div class="patch-vacancy-dashboard-stat review">
                <div class="patch-vacancy-dashboard-stat-top">
                    <span class="patch-vacancy-dashboard-stat-label">Por revisar</span>
                    <span class="patch-vacancy-dashboard-stat-icon"><i class="fa fa-clock"></i></span>
                </div>
                <strong class="patch-vacancy-dashboard-stat-value"><?php echo $pendientesVacante; ?></strong>
                <span class="patch-vacancy-dashboard-stat-label">Nuevas</span>
            </div>

            <div class="patch-vacancy-dashboard-stat process">
                <div class="patch-vacancy-dashboard-stat-top">
                    <span class="patch-vacancy-dashboard-stat-label">En proceso</span>
                    <span class="patch-vacancy-dashboard-stat-icon"><i class="fa fa-spinner"></i></span>
                </div>
                <strong class="patch-vacancy-dashboard-stat-value"><?php echo $enProcesoVacante; ?></strong>
                <span class="patch-vacancy-dashboard-stat-label">Candidatos en proceso</span>
            </div>

            <div class="patch-vacancy-dashboard-stat interview">
                <div class="patch-vacancy-dashboard-stat-top">
                    <span class="patch-vacancy-dashboard-stat-label">Entrevistas</span>
                    <span class="patch-vacancy-dashboard-stat-icon"><i class="fa fa-comments"></i></span>
                </div>
                <strong class="patch-vacancy-dashboard-stat-value"><?php echo $entrevistasVacante; ?></strong>
                <span class="patch-vacancy-dashboard-stat-label">En entrevista</span>
            </div>

            <div class="patch-vacancy-dashboard-stat accepted">
                <div class="patch-vacancy-dashboard-stat-top">
                    <span class="patch-vacancy-dashboard-stat-label">Aceptados</span>
                    <span class="patch-vacancy-dashboard-stat-icon"><i class="fa fa-circle-check"></i></span>
                </div>
                <strong class="patch-vacancy-dashboard-stat-value"><?php echo $aceptadosVacante; ?></strong>
                <span class="patch-vacancy-dashboard-stat-label">Candidatos aceptados</span>
            </div>

            <div class="patch-vacancy-dashboard-stat rejected">
                <div class="patch-vacancy-dashboard-stat-top">
                    <span class="patch-vacancy-dashboard-stat-label">Descartados</span>
                    <span class="patch-vacancy-dashboard-stat-icon"><i class="fa fa-circle-xmark"></i></span>
                </div>
                <strong class="patch-vacancy-dashboard-stat-value"><?php echo $descartadosVacante; ?></strong>
                <span class="patch-vacancy-dashboard-stat-label">Candidatos descartados</span>
            </div>
        </div>

        <!-- =====================================================
             INFORMACIÓN DE LA VACANTE
             ===================================================== -->
        <section class="patch-vacancy-dashboard-panel">
            <div class="patch-vacancy-dashboard-panel-header">
                <div>
                    <h2>Información de la vacante</h2>
                    <p>Información publicada originalmente en esta vacante.</p>
                </div>
            </div>

            <?php if ($soloImagen === 1 && $rutaImagenVacante !== ''): ?>
                <img src="<?php echo e($rutaImagenVacante); ?>" alt="<?php echo e($tituloVacante); ?>" class="patch-vacancy-image-dashboard">
            <?php else: ?>
                <?php if ($descripcionVacante !== ''): ?>
                    <div class="patch-vacancy-content-dashboard">
                        <h3>Descripción del puesto</h3>
                        <div><?php echo $descripcionVacante; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($ofreceVacante !== ''): ?>
                    <div class="patch-vacancy-content-dashboard">
                        <h3>Lo que se ofrece</h3>
                        <div><?php echo $ofreceVacante; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($requisitosVacante !== ''): ?>
                    <div class="patch-vacancy-content-dashboard">
                        <h3>Requisitos</h3>
                        <div><?php echo $requisitosVacante; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($responsabilidadesVacante !== ''): ?>
                    <div class="patch-vacancy-content-dashboard">
                        <h3>Responsabilidades</h3>
                        <div><?php echo $responsabilidadesVacante; ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- =====================================================
             POSTULACIONES DE LA VACANTE
             ===================================================== -->
        <section class="patch-vacancy-dashboard-panel">
            <div class="patch-vacancy-dashboard-panel-header">
                <div>
                    <h2>Postulaciones de esta vacante</h2>
                    <p>Consulta y administra exclusivamente los candidatos que se postularon a esta vacante.</p>
                </div>
            </div>

            <?php if (!empty($postulacionesVacante)): ?>
                <div class="patch-vacancy-dashboard-table-wrapper">
                    <table class="patch-vacancy-dashboard-table">
                        <thead>
                            <tr>
                                <th>Candidato</th>
                                <th>Teléfono</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($postulacionesVacante as $fila): ?>
                            <?php
                            $idFila = (int)($fila['id'] ?? 0);
                            $nombreFila = valorSeguro($fila['nombre_completo'] ?? '', 'Candidato');
                            $correoFila = valorSeguro($fila['correo'] ?? '', 'Sin correo');
                            $telefonoFila = valorSeguro($fila['telefono'] ?? '', 'Sin teléfono');
                            $estadoFila = trim((string)($fila['estado'] ?? 'nueva'));

                            if (!in_array($estadoFila, $estadosPermitidos, true)) {
                                $estadoFila = 'nueva';
                            }

                            $cvFila = trim((string)($fila['cv'] ?? ''));
                            $fechaFila = $fila['fecha_postulacion'] ?? null;
                            ?>
                            <tr data-postulacion-id="<?php echo $idFila; ?>" data-estado="<?php echo e($estadoFila); ?>">
                                <td>
                                    <div class="patch-vacancy-dashboard-candidate">
                                        <div class="patch-vacancy-dashboard-avatar"><?php echo e(iniciales($nombreFila)); ?></div>
                                        <div>
                                            <strong><?php echo e($nombreFila); ?></strong>
                                            <span><?php echo e($correoFila); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo e($telefonoFila); ?></td>
                                <td><?php echo e(formatearFecha($fechaFila)); ?></td>
                                <td>
                                    <span class="patch-dashboard-status patch-status <?php echo e(estadoClase($estadoFila)); ?>" data-estado="<?php echo e($estadoFila); ?>">
                                        <?php echo e(estadoTexto($estadoFila)); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="patch-dashboard-actions">
                                        <?php if ($idFila > 0): ?>
                                            <button type="button" class="patch-dashboard-action primary" onclick="cargarPostulacion(<?php echo $idFila; ?>)" title="Ver detalle de la postulación">
                                                <i class="fa fa-eye"></i> Ver detalle
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($cvFila !== ''): ?>
                                            <a href="api/ver_cv.php?id=<?php echo $idFila; ?>" class="patch-dashboard-action" target="_blank" rel="noopener noreferrer" title="Ver CV">
                                                <i class="fa fa-file-pdf"></i> CV
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="patch-dashboard-empty">
                    <div class="patch-dashboard-empty-icon"><i class="fa fa-users-slash"></i></div>
                    <h3>Esta vacante aún no tiene postulaciones</h3>
                    <p>Cuando un candidato se postule a esta vacante, aparecerá aquí.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- =========================================================
     MODAL ÚNICO DE DETALLE DE POSTULACIÓN
     ========================================================= -->
<?php require_once __DIR__ . "/partials/modal_ver_postulacion.php"; ?>

<!-- =========================================================
     FORMULARIO OCULTO DE RESPALDO
     ========================================================= -->
<form method="POST" id="formActualizarEstado" style="display:none;">
    <input type="hidden" name="accion" value="actualizar_estado">
    <input type="hidden" name="id" id="estadoPostulacionId" value="">
    <input type="hidden" name="estado" id="estadoPostulacionValor" value="">
    <input type="hidden" name="id_bolsa" value="<?php echo $idBolsa; ?>">
</form>

<!-- =========================================================
     TOAST DE DETALLE DE POSTULACIÓN
     ========================================================= -->
<div class="patch-toast-detalle_postulacion <?php echo $mensajeOperacion !== '' ? 'show success' : ($errorOperacion !== '' ? 'show error' : ''); ?>" id="patchToast">
    <?php if ($mensajeOperacion !== ''): ?>
        <div class="patch-toast-icon-detalle_postulacion">
            <i class="fa fa-check"></i>
        </div>
        <div class="patch-toast-content-detalle_postulacion">
            <strong>Cambio realizado exitosamente</strong>
            <span><?php echo e($mensajeOperacion); ?></span>
        </div>
    <?php elseif ($errorOperacion !== ''): ?>
        <div class="patch-toast-icon-detalle_postulacion">
            <i class="fa fa-xmark"></i>
        </div>
        <div class="patch-toast-content-detalle_postulacion">
            <strong>No se pudo realizar el cambio</strong>
            <span><?php echo e($errorOperacion); ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->
<script src="js/postulaciones-api.js"></script>
<script src="js/postulaciones-modal.js"></script>

<?php if ($mensajeOperacion !== '' || $errorOperacion !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toast = document.getElementById('patchToast');

    if (!toast || !toast.classList.contains('show')) return;

    setTimeout(function () {
        toast.classList.remove('show');
    }, 4000);
});
</script>
<?php endif; ?>

</body>
</html>

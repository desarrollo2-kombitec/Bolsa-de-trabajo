<?php
require_once __DIR__ . "/../../../../includes/auth.php";
requerirAdminApi();
require_once __DIR__ . "/../../../includes/config.php"; // conexión centralizada
header('Content-Type: application/json; charset=utf-8');

function respuestaJSON($ok, $mensaje = '', $datos = null) {
    echo json_encode([
        'ok'      => $ok,
        'mensaje' => $mensaje,
        'datos'   => $datos
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        respuestaJSON(false, 'La vacante indicada no es válida.');
    }

    // Buscar la vacante
    $sqlGet = "SELECT id, activo, archivada FROM patch_BolsaTrabajo WHERE id=?";
    $stmtGet = $conn->prepare($sqlGet);
    $stmtGet->bind_param("i", $id);
    $stmtGet->execute();
    $result = $stmtGet->get_result();
    $vacante = $result->fetch_assoc();
    $stmtGet->close();

    if (!$vacante) {
        respuestaJSON(false, 'La vacante no existe.');
    }

    // Una vacante archivada no puede activarse desde esta acción
    if ((int)$vacante['archivada'] === 1) {
        respuestaJSON(false, 'Una vacante archivada no puede activarse desde esta opción.');
    }

    // Cambiar estado
    $nuevoEstado = ((int)$vacante['activo'] === 1) ? 0 : 1;

    $sqlUpdate = "UPDATE patch_BolsaTrabajo SET activo=? WHERE id=?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ii", $nuevoEstado, $id);

    if ($stmtUpdate->execute()) {
        // Recuperar registro actualizado
        $sqlFinal = "SELECT * FROM patch_BolsaTrabajo WHERE id=?";
        $stmtFinal = $conn->prepare($sqlFinal);
        $stmtFinal->bind_param("i", $id);
        $stmtFinal->execute();
        $resultFinal = $stmtFinal->get_result();
        $actualizada = $resultFinal->fetch_assoc();
        $stmtFinal->close();

        $mensaje = $nuevoEstado === 1
            ? 'La vacante fue activada correctamente.'
            : 'La vacante fue desactivada correctamente.';

        respuestaJSON(true, $mensaje, $actualizada);
    } else {
        respuestaJSON(false, 'No fue posible cambiar el estado de la vacante.');
    }
}

respuestaJSON(false, 'Acción no válida.');

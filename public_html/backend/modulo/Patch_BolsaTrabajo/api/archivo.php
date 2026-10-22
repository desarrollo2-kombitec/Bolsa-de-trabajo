<?php
require_once __DIR__ . "/../../../../includes/auth.php";
requerirAdminApi();

require_once __DIR__ . "/../../../../includes/config.php"; // conexión centralizada
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
    $accion = $_POST['accion'] ?? '';

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

    if ($accion === 'archivar') {
        // Archivar: poner archivada=1 y activo=0
        $sqlUpdate = "UPDATE patch_BolsaTrabajo SET archivada=1, activo=0 WHERE id=?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $id);

        if ($stmtUpdate->execute()) {
            $sqlFinal = "SELECT * FROM patch_BolsaTrabajo WHERE id=?";
            $stmtFinal = $conn->prepare($sqlFinal);
            $stmtFinal->bind_param("i", $id);
            $stmtFinal->execute();
            $resultFinal = $stmtFinal->get_result();
            $actualizada = $resultFinal->fetch_assoc();
            $stmtFinal->close();

            respuestaJSON(true, 'La vacante fue archivada correctamente.', $actualizada);
        } else {
            respuestaJSON(false, 'No fue posible archivar la vacante.');
        }
    }

    if ($accion === 'desarchivar') {
        // Validar que esté archivada
        if ((int)$vacante['archivada'] !== 1) {
            respuestaJSON(false, 'La vacante seleccionada no está archivada.');
        }

        // Desarchivar: poner archivada=0 (activo se mantiene igual)
        $sqlUpdate = "UPDATE patch_BolsaTrabajo SET archivada=0 WHERE id=?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $id);

        if ($stmtUpdate->execute()) {
            $sqlFinal = "SELECT * FROM patch_BolsaTrabajo WHERE id=?";
            $stmtFinal = $conn->prepare($sqlFinal);
            $stmtFinal->bind_param("i", $id);
            $stmtFinal->execute();
            $resultFinal = $stmtFinal->get_result();
            $actualizada = $resultFinal->fetch_assoc();
            $stmtFinal->close();

            respuestaJSON(true, 'La vacante fue desarchivada correctamente.', $actualizada);
        } else {
            respuestaJSON(false, 'No fue posible desarchivar la vacante.');
        }
    }

    respuestaJSON(false, 'Acción no válida.');
}

respuestaJSON(false, 'Acción no válida.');

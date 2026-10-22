<?php
require_once __DIR__ . "/../../../../includes/auth.php";
requerirAdminApi();

error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . "/../../../../includes/config.php";

header('Content-Type: application/json; charset=utf-8');

function respuestaJSON(bool $ok, string $mensaje = '', $datos = []): void
{
    echo json_encode([
        'ok' => $ok,
        'mensaje' => $mensaje,
        'datos' => $datos
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaJSON(false, 'Método no permitido.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$estado = isset($_POST['estado']) ? trim((string)$_POST['estado']) : '';
$comentarioSeguimiento = isset($_POST['comentario_seguimiento'])
    ? trim((string)$_POST['comentario_seguimiento'])
    : '';

if ($id === false || $id === null || $id <= 0) {
    respuestaJSON(false, 'La postulación indicada no es válida.');
}

$estadosPermitidos = [
    'nueva',
    'en_proceso',
    'entrevista',
    'aceptado',
    'descartado'
];

if (!in_array($estado, $estadosPermitidos, true)) {
    respuestaJSON(false, 'El estado seleccionado no es válido.');
}

try {

    $sql = "
        UPDATE patch_postulaciones
        SET
            estado = ?,
            comentario_seguimiento = ?,
            updated_at = NOW()
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        respuestaJSON(
            false,
            'No fue posible preparar la actualización de la postulación.'
        );
    }

    $stmt->bind_param(
        'ssi',
        $estado,
        $comentarioSeguimiento,
        $id
    );

    if (!$stmt->execute()) {
        $stmt->close();

        respuestaJSON(
            false,
            'No fue posible actualizar la postulación.'
        );
    }

    if ($stmt->affected_rows === 0) {

        $stmt->close();

        $sqlExiste = "
            SELECT id
            FROM patch_postulaciones
            WHERE id = ?
            LIMIT 1
        ";

        $stmtExiste = $conn->prepare($sqlExiste);

        if (!$stmtExiste) {
            respuestaJSON(
                false,
                'No fue posible verificar la postulación.'
            );
        }

        $stmtExiste->bind_param('i', $id);
        $stmtExiste->execute();

        $resultadoExiste = $stmtExiste->get_result();

        if (!$resultadoExiste || $resultadoExiste->num_rows === 0) {
            $stmtExiste->close();

            respuestaJSON(
                false,
                'La postulación no fue encontrada.'
            );
        }

        $stmtExiste->close();
    } else {
        $stmt->close();
    }

    $sqlDatos = "
        SELECT
            id,
            estado,
            comentario_seguimiento,
            updated_at
        FROM patch_postulaciones
        WHERE id = ?
        LIMIT 1
    ";

    $stmtDatos = $conn->prepare($sqlDatos);

    if (!$stmtDatos) {
        respuestaJSON(
            true,
            'Postulación actualizada correctamente.'
        );
    }

    $stmtDatos->bind_param('i', $id);
    $stmtDatos->execute();

    $resultadoDatos = $stmtDatos->get_result();
    $datos = [];

    if ($resultadoDatos && $resultadoDatos->num_rows > 0) {

        $fila = $resultadoDatos->fetch_assoc();

        $datos = [
            'id' => (int)$fila['id'],
            'estado' => (string)$fila['estado'],
            'comentario_seguimiento' => (string)($fila['comentario_seguimiento'] ?? ''),
            'updated_at' => $fila['updated_at']
        ];
    }

    $stmtDatos->close();

    respuestaJSON(
        true,
        'Postulación actualizada correctamente.',
        $datos
    );

} catch (Throwable $e) {

    respuestaJSON(
        false,
        'Ocurrió un error al actualizar la postulación.'
    );
}
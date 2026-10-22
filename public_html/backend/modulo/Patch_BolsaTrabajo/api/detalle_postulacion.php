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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respuestaJSON(false, 'Método no permitido.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id <= 0) {
    respuestaJSON(false, 'La postulación indicada no es válida.');
}

try {

    $sql = "
        SELECT
            p.id,
            p.id_bolsa,
            p.nombre_completo,
            p.correo,
            p.telefono,
            p.cv,
            p.comentarios,
            p.comentario_seguimiento,
            p.estado,
            p.fecha_postulacion,
            p.updated_at,

            b.token AS vacante_token,
            b.titulo AS vacante_titulo,
            b.descripcion AS vacante_descripcion,
            b.ubicacion AS vacante_ubicacion,
            b.tipo_jornada AS vacante_tipo_jornada,
            b.modalidad AS vacante_modalidad,
            b.lo_que_se_ofrece AS vacante_ofrece,
            b.requisitos AS vacante_requisitos,
            b.responsabilidades AS vacante_responsabilidades

        FROM patch_postulaciones p

        LEFT JOIN patch_BolsaTrabajo b
            ON b.id = p.id_bolsa

        WHERE p.id = ?

        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        respuestaJSON(
            false,
            'No fue posible preparar la consulta de la postulación.'
        );
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();

        respuestaJSON(
            false,
            'No fue posible consultar la postulación.'
        );
    }

    $resultado = $stmt->get_result();

    if (!$resultado || $resultado->num_rows === 0) {
        $stmt->close();

        respuestaJSON(
            false,
            'La postulación no fue encontrada.'
        );
    }

    $fila = $resultado->fetch_assoc();

    $stmt->close();

    $esPostulacionVacante = !empty($fila['id_bolsa']);

    $vacante = null;

    if ($esPostulacionVacante) {

        $vacante = [
            'id' => (int)$fila['id_bolsa'],
            'token' => (string)($fila['vacante_token'] ?? ''),
            'titulo' => (string)($fila['vacante_titulo'] ?? ''),
            'descripcion' => (string)($fila['vacante_descripcion'] ?? ''),
            'ubicacion' => (string)($fila['vacante_ubicacion'] ?? ''),
            'tipo_jornada' => (string)($fila['vacante_tipo_jornada'] ?? ''),
            'modalidad' => (string)($fila['vacante_modalidad'] ?? ''),
            'lo_que_se_ofrece' => (string)($fila['vacante_ofrece'] ?? ''),
            'requisitos' => (string)($fila['vacante_requisitos'] ?? ''),
            'responsabilidades' => (string)($fila['vacante_responsabilidades'] ?? '')
        ];

    } else {

        $vacante = [
            'id' => null,
            'token' => '',
            'titulo' => 'CV General',
            'descripcion' => '',
            'ubicacion' => '',
            'tipo_jornada' => '',
            'modalidad' => '',
            'lo_que_se_ofrece' => '',
            'requisitos' => '',
            'responsabilidades' => ''
        ];
    }

    $postulacion = [
        'id' => (int)$fila['id'],
        'id_bolsa' => $fila['id_bolsa'] !== null
            ? (int)$fila['id_bolsa']
            : null,

        'tipo' => $esPostulacionVacante
            ? 'vacante'
            : 'general',

        'nombre_completo' => (string)$fila['nombre_completo'],
        'correo' => (string)$fila['correo'],
        'telefono' => (string)$fila['telefono'],
        'cv' => (string)$fila['cv'],
        'comentarios' => (string)($fila['comentarios'] ?? ''),
        'comentario_seguimiento' => (string)($fila['comentario_seguimiento'] ?? ''),
        'estado' => (string)$fila['estado'],
        'fecha_postulacion' => $fila['fecha_postulacion'],
        'updated_at' => $fila['updated_at'],

        'vacante' => $vacante
    ];

    respuestaJSON(
        true,
        'Postulación obtenida correctamente.',
        $postulacion
    );

} catch (Throwable $e) {

    respuestaJSON(
        false,
        'Ocurrió un error al consultar la postulación.'
    );
}

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

try {

    $buscar = trim((string)($_GET['buscar'] ?? ''));
    $vacante = trim((string)($_GET['vacante'] ?? ''));
    $estado = trim((string)($_GET['estado'] ?? ''));

    $sql = "
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
            b.token,
            b.titulo AS vacante_titulo,
            b.ubicacion AS vacante_ubicacion,
            b.tipo_jornada AS vacante_tipo_jornada,
            b.modalidad AS vacante_modalidad
        FROM patch_postulaciones p
        INNER JOIN patch_BolsaTrabajo b
            ON b.id = p.id_bolsa
        WHERE p.id_bolsa IS NOT NULL
    ";

    $parametros = [];
    $tipos = '';

    if ($buscar !== '') {

        $sql .= "
            AND (
                p.nombre_completo LIKE ?
                OR p.correo LIKE ?
                OR p.telefono LIKE ?
                OR b.titulo LIKE ?
                OR b.ubicacion LIKE ?
            )
        ";

        $textoBuscar = '%' . $buscar . '%';

        $parametros[] = $textoBuscar;
        $parametros[] = $textoBuscar;
        $parametros[] = $textoBuscar;
        $parametros[] = $textoBuscar;
        $parametros[] = $textoBuscar;

        $tipos .= 'sssss';
    }

    if ($vacante !== '') {

        $idVacante = filter_var($vacante, FILTER_VALIDATE_INT);

        if ($idVacante === false || $idVacante <= 0) {
            respuestaJSON(false, 'La vacante seleccionada no es válida.');
        }

        $sql .= " AND p.id_bolsa = ? ";

        $parametros[] = $idVacante;
        $tipos .= 'i';
    }

    if ($estado !== '') {

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

        $sql .= " AND p.estado = ? ";

        $parametros[] = $estado;
        $tipos .= 's';
    }

    $sql .= "
        ORDER BY p.fecha_postulacion DESC, p.id DESC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        respuestaJSON(false, 'No fue posible preparar la consulta de postulaciones.');
    }

    if (!empty($parametros)) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        respuestaJSON(false, 'No fue posible consultar las postulaciones.');
    }

    $resultado = $stmt->get_result();

    $postulaciones = [];

    while ($fila = $resultado->fetch_assoc()) {

        $postulaciones[] = [
            'id' => (int)$fila['id'],
            'id_bolsa' => (int)$fila['id_bolsa'],
            'nombre_completo' => (string)$fila['nombre_completo'],
            'correo' => (string)$fila['correo'],
            'telefono' => (string)$fila['telefono'],
            'cv' => (string)$fila['cv'],
            'comentarios' => (string)($fila['comentarios'] ?? ''),
            'estado' => (string)$fila['estado'],
            'fecha_postulacion' => $fila['fecha_postulacion'],
            'updated_at' => $fila['updated_at'],
            'vacante' => [
                'id' => (int)$fila['id_bolsa'],
                'token' => (string)$fila['token'],
                'titulo' => (string)$fila['vacante_titulo'],
                'ubicacion' => (string)($fila['vacante_ubicacion'] ?? ''),
                'tipo_jornada' => (string)($fila['vacante_tipo_jornada'] ?? ''),
                'modalidad' => (string)($fila['vacante_modalidad'] ?? '')
            ]
        ];
    }

    $stmt->close();

    respuestaJSON(
        true,
        'Postulaciones obtenidas correctamente.',
        [
            'total' => count($postulaciones),
            'postulaciones' => $postulaciones
        ]
    );

} catch (Throwable $e) {

    respuestaJSON(
        false,
        'Ocurrió un error al consultar las postulaciones.'
    );
}

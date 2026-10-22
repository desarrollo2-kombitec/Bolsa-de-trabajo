<?php
require_once __DIR__ . "/../../../../includes/auth.php";
requerirAdminApi();

error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . "/../../../../includes/config.php";
require_once __DIR__ . "/../../../../includes/correo.php";

header('Content-Type: application/json; charset=utf-8');


/* =========================================================
   RESPUESTA JSON
   ========================================================= */

function respuestaJSON(
    bool $ok,
    string $mensaje = '',
    array $datos = []
): void {

    echo json_encode(
        [
            'ok' => $ok,
            'mensaje' => $mensaje,
            'datos' => $datos
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   SOLO POST
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    respuestaJSON(
        false,
        'Método de solicitud no permitido.'
    );

}


/* =========================================================
   DATOS DEL FORMULARIO
   ========================================================= */

$nombreCompleto = trim(
    (string)($_POST['nombre_completo'] ?? '')
);

$correo = trim(
    (string)($_POST['correo'] ?? '')
);

$telefono = trim(
    (string)($_POST['telefono'] ?? '')
);

$comentarios = trim(
    (string)($_POST['comentarios'] ?? '')
);


/* =========================================================
   VALIDAR NOMBRE
   ========================================================= */

if ($nombreCompleto === '') {

    respuestaJSON(
        false,
        'Ingresa tu nombre completo.'
    );

}

if (mb_strlen($nombreCompleto) > 255) {

    respuestaJSON(
        false,
        'El nombre completo no puede superar los 255 caracteres.'
    );

}


/* =========================================================
   VALIDAR CORREO
   ========================================================= */

if ($correo === '') {

    respuestaJSON(
        false,
        'Ingresa tu correo electrónico.'
    );

}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

    respuestaJSON(
        false,
        'Ingresa un correo electrónico válido.'
    );

}

if (mb_strlen($correo) > 255) {

    respuestaJSON(
        false,
        'El correo electrónico no puede superar los 255 caracteres.'
    );

}


/* =========================================================
   VALIDAR TELÉFONO
   ========================================================= */

if ($telefono === '') {

    respuestaJSON(
        false,
        'Ingresa tu número de teléfono.'
    );

}

if (mb_strlen($telefono) > 30) {

    respuestaJSON(
        false,
        'El teléfono no puede superar los 30 caracteres.'
    );

}


/* =========================================================
   VALIDAR COMENTARIOS
   ========================================================= */

if (mb_strlen($comentarios) > 2000) {

    respuestaJSON(
        false,
        'Los comentarios no pueden superar los 2000 caracteres.'
    );

}


/* =========================================================
   VALIDAR ARCHIVO CV
   ========================================================= */

if (
    !isset($_FILES['cv']) ||
    !is_array($_FILES['cv'])
) {

    respuestaJSON(
        false,
        'Selecciona tu currículum en formato PDF.'
    );

}


$archivoCV = $_FILES['cv'];


/* =========================================================
   ERROR DE CARGA
   ========================================================= */

if (
    !isset($archivoCV['error']) ||
    $archivoCV['error'] !== UPLOAD_ERR_OK
) {

    respuestaJSON(
        false,
        'No fue posible recibir el archivo del CV.'
    );

}


/* =========================================================
   TAMAÑO MÁXIMO
   ========================================================= */

$maximoBytes = 5 * 1024 * 1024;

if (
    !isset($archivoCV['size']) ||
    $archivoCV['size'] <= 0
) {

    respuestaJSON(
        false,
        'El archivo del CV está vacío o no es válido.'
    );

}

if ($archivoCV['size'] > $maximoBytes) {

    respuestaJSON(
        false,
        'El CV no puede superar los 5 MB.'
    );

}


/* =========================================================
   NOMBRE ORIGINAL
   ========================================================= */

$nombreOriginal = trim(
    (string)($archivoCV['name'] ?? '')
);

if ($nombreOriginal === '') {

    respuestaJSON(
        false,
        'El archivo seleccionado no tiene un nombre válido.'
    );

}


/* =========================================================
   VALIDAR EXTENSIÓN
   ========================================================= */

$extension = strtolower(
    pathinfo(
        $nombreOriginal,
        PATHINFO_EXTENSION
    )
);

if ($extension !== 'pdf') {

    respuestaJSON(
        false,
        'El CV debe estar en formato PDF.'
    );

}


/* =========================================================
   VALIDAR MIME REAL
   ========================================================= */

$rutaTemporal = $archivoCV['tmp_name'] ?? '';

if (
    $rutaTemporal === '' ||
    !is_uploaded_file($rutaTemporal)
) {

    respuestaJSON(
        false,
        'El archivo recibido no es válido.'
    );

}


$finfo = new finfo(FILEINFO_MIME_TYPE);

$tipoMime = $finfo->file(
    $rutaTemporal
);

if ($tipoMime !== 'application/pdf') {

    respuestaJSON(
        false,
        'El archivo seleccionado no parece ser un PDF válido.'
    );

}


/* =========================================================
   CARPETA DE DESTINO
   ========================================================= */

$directorioCV =
    __DIR__ . "/../uploads/postulaciones";


if (!is_dir($directorioCV)) {

    if (
        !mkdir(
            $directorioCV,
            0755,
            true
        )
    ) {

        respuestaJSON(
            false,
            'No fue posible preparar la carpeta para guardar el CV.'
        );

    }

}


/* =========================================================
   VERIFICAR ESCRITURA
   ========================================================= */

if (!is_writable($directorioCV)) {

    respuestaJSON(
        false,
        'La carpeta de almacenamiento del CV no tiene permisos de escritura.'
    );

}


/* =========================================================
   GENERAR NOMBRE SEGURO
   ========================================================= */

try {

    $nombreArchivo =
        'cv_' .
        date('Ymd_His') .
        '_' .
        bin2hex(
            random_bytes(8)
        ) .
        '.pdf';

} catch (Throwable $e) {

    respuestaJSON(
        false,
        'No fue posible generar un nombre seguro para el CV.'
    );

}


$rutaFisicaCV =
    $directorioCV .
    DIRECTORY_SEPARATOR .
    $nombreArchivo;


/* =========================================================
   MOVER ARCHIVO
   ========================================================= */

if (
    !move_uploaded_file(
        $rutaTemporal,
        $rutaFisicaCV
    )
) {

    respuestaJSON(
        false,
        'No fue posible guardar el CV.'
    );

}


/* =========================================================
   RUTA PARA BASE DE DATOS
   ========================================================= */

$rutaBD =
    'backend/modulo/Patch_BolsaTrabajo/uploads/postulaciones/' .
    $nombreArchivo;


/* =========================================================
   GUARDAR EN BASE DE DATOS
   ========================================================= */

$sql = "
    INSERT INTO patch_postulaciones (
        id_bolsa,
        nombre_completo,
        correo,
        telefono,
        cv,
        comentarios,
        estado
    )
    VALUES (
        NULL,
        ?,
        ?,
        ?,
        ?,
        ?,
        'nueva'
    )
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    @unlink($rutaFisicaCV);

    respuestaJSON(
        false,
        'No fue posible preparar el registro del CV.'
    );

}


$stmt->bind_param(
    'sssss',
    $nombreCompleto,
    $correo,
    $telefono,
    $rutaBD,
    $comentarios
);


if (!$stmt->execute()) {

    $stmt->close();

    @unlink($rutaFisicaCV);

    respuestaJSON(
        false,
        'No fue posible guardar el CV en la base de datos.'
    );

}


$idPostulacion =
    (int)$stmt->insert_id;


$stmt->close();


/* =========================================================
   PREPARAR CORREO
   ========================================================= */

$correoDestino =
    'christianchavez394@gmail.com';


$asuntoCorreo =
    'Nuevo CV general - Bolsa de Trabajo';


$nombreSeguroCorreo =
    htmlspecialchars(
        $nombreCompleto,
        ENT_QUOTES,
        'UTF-8'
    );


$correoSeguro =
    htmlspecialchars(
        $correo,
        ENT_QUOTES,
        'UTF-8'
    );


$telefonoSeguro =
    htmlspecialchars(
        $telefono,
        ENT_QUOTES,
        'UTF-8'
    );


$comentariosSeguro =
    nl2br(
        htmlspecialchars(
            $comentarios,
            ENT_QUOTES,
            'UTF-8'
        )
    );


$mensajeCorreo = "
<!DOCTYPE html>
<html lang=\"es\">
<head>
    <meta charset=\"UTF-8\">
</head>

<body
    style=\"
        margin:0;
        padding:20px;
        background:#f8fafc;
        font-family:Arial,Helvetica,sans-serif;
        color:#17202a;
    \"
>

    <div
        style=\"
            max-width:680px;
            margin:0 auto;
            background:#ffffff;
            border:1px solid #e2e8f0;
            border-radius:12px;
            overflow:hidden;
        \"
    >

        <div
            style=\"
                padding:22px 24px;
                background:#16834b;
                color:#ffffff;
            \"
        >

            <div
                style=\"
                    font-size:12px;
                    font-weight:700;
                    letter-spacing:.08em;
                    text-transform:uppercase;
                    margin-bottom:6px;
                \"
            >
                Bolsa de Trabajo
            </div>

            <div
                style=\"
                    font-size:22px;
                    font-weight:700;
                \"
            >
                Nuevo CV general
            </div>

        </div>


        <div
            style=\"
                padding:24px;
            \"
        >

            <p
                style=\"
                    margin:0 0 20px;
                    font-size:14px;
                    line-height:1.6;
                    color:#475569;
                \"
            >
                Se recibió un nuevo currículum mediante el formulario
                general de la Bolsa de Trabajo.
            </p>


            <table
                style=\"
                    width:100%;
                    border-collapse:collapse;
                    font-size:14px;
                \"
            >

                <tr>
                    <td
                        style=\"
                            width:180px;
                            padding:10px 0;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-weight:600;
                        \"
                    >
                        Nombre completo
                    </td>

                    <td
                        style=\"
                            padding:10px 0;
                            border-bottom:1px solid #e5e7eb;
                            color:#17202a;
                        \"
                    >
                        {$nombreSeguroCorreo}
                    </td>
                </tr>


                <tr>
                    <td
                        style=\"
                            padding:10px 0;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-weight:600;
                        \"
                    >
                        Correo electrónico
                    </td>

                    <td
                        style=\"
                            padding:10px 0;
                            border-bottom:1px solid #e5e7eb;
                            color:#17202a;
                        \"
                    >
                        {$correoSeguro}
                    </td>
                </tr>


                <tr>
                    <td
                        style=\"
                            padding:10px 0;
                            border-bottom:1px solid #e5e7eb;
                            color:#64748b;
                            font-weight:600;
                        \"
                    >
                        Teléfono
                    </td>

                    <td
                        style=\"
                            padding:10px 0;
                            border-bottom:1px solid #e5e7eb;
                            color:#17202a;
                        \"
                    >
                        {$telefonoSeguro}
                    </td>
                </tr>


                <tr>
                    <td
                        style=\"
                            padding:10px 0;
                            color:#64748b;
                            font-weight:600;
                            vertical-align:top;
                        \"
                    >
                        Comentarios
                    </td>

                    <td
                        style=\"
                            padding:10px 0;
                            color:#17202a;
                            line-height:1.6;
                        \"
                    >
                        " .
                        (
                            $comentarios !== ''
                                ? $comentariosSeguro
                                : 'Sin comentarios.'
                        ) .
                    "
                    </td>
                </tr>

            </table>


            <div
                style=\"
                    margin-top:24px;
                    padding:14px 16px;
                    border-radius:8px;
                    background:#f0fdf4;
                    border:1px solid #bbf7d0;
                    color:#166534;
                    font-size:13px;
                    line-height:1.5;
                \"
            >
                El currículum vitae se encuentra adjunto a este correo
                electrónico.
            </div>


            <p
                style=\"
                    margin:20px 0 0;
                    color:#94a3b8;
                    font-size:11px;
                \"
            >
                ID interno de registro: {$idPostulacion}
            </p>

        </div>

    </div>

</body>
</html>
";


/* =========================================================
   ENVIAR CORREO
   ========================================================= */

$correoEnviado = false;


try {

    $correoEnviado = enviarCorreo(
        $correoDestino,
        $asuntoCorreo,
        $mensajeCorreo,
        $rutaFisicaCV,
        $nombreArchivo
    );

} catch (Throwable $e) {

    $correoEnviado = false;

}


/* =========================================================
   RESPUESTA FINAL
   ========================================================= */

if ($correoEnviado) {

    respuestaJSON(
        true,
        'Tu CV fue enviado correctamente. Gracias por compartir tu información.',
        [
            'id' => $idPostulacion,
            'correo_enviado' => true
        ]
    );

}


/*
 * El registro y el archivo ya fueron guardados correctamente.
 * Si el correo falla, no eliminamos ninguno de los dos.
 */

respuestaJSON(
    true,
    'Tu CV fue registrado correctamente. Sin embargo, no fue posible enviar la notificación por correo en este momento.',
    [
        'id' => $idPostulacion,
        'correo_enviado' => false
    ]
);

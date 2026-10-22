<?php
require_once __DIR__ . "/../../../../includes/auth.php";
requerirAdminApi();

error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . "/../../../../includes/config.php";


header('Content-Type: application/json; charset=utf-8');


// =========================================================
// RESPUESTA JSON
// =========================================================

function respuestaJSON($ok, $mensaje = '', $datos = null) {

    echo json_encode([
        'ok'      => $ok,
        'mensaje' => $mensaje,
        'datos'   => $datos
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =========================================================
// GENERAR TOKEN ÚNICO
// =========================================================

function generarTokenBolsa($conn) {

    do {

        $token = bin2hex(random_bytes(8));

        $sql = "SELECT id
                FROM patch_BolsaTrabajo
                WHERE token=?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            respuestaJSON(
                false,
                'No fue posible preparar la validación del token.'
            );

        }

        $stmt->bind_param(
            "s",
            $token
        );

        $stmt->execute();

        $stmt->store_result();

        $existe =
            $stmt->num_rows > 0;

        $stmt->close();

    } while ($existe);

    return $token;
}


// =========================================================
// GUARDAR IMAGEN DE VACANTE
// =========================================================

function guardarImagenVacante($archivo) {

    if (
        !isset($archivo) ||
        !is_array($archivo)
    ) {

        return [
            'ok' => false,
            'mensaje' => 'No se recibió ninguna imagen.'
        ];

    }


    // =====================================================
    // VALIDAR ERROR DE SUBIDA
    // =====================================================

    if (
        !isset($archivo['error']) ||
        $archivo['error'] !== UPLOAD_ERR_OK
    ) {

        return [
            'ok' => false,
            'mensaje' => 'No fue posible subir la imagen.'
        ];

    }


    // =====================================================
    // VALIDAR QUE EXISTA EL ARCHIVO
    // =====================================================

    if (
        !isset($archivo['tmp_name']) ||
        !is_uploaded_file($archivo['tmp_name'])
    ) {

        return [
            'ok' => false,
            'mensaje' => 'El archivo de imagen no es válido.'
        ];

    }


    // =====================================================
    // VALIDAR TIPO REAL DEL ARCHIVO
    // =====================================================

    $tiposPermitidos = [

        'image/jpeg' => 'jpg',

        'image/png'  => 'png',

        'image/webp' => 'webp'

    ];


    $finfo =
        new finfo(FILEINFO_MIME_TYPE);


    $tipoMime =
        $finfo->file(
            $archivo['tmp_name']
        );


    if (
        !isset(
            $tiposPermitidos[$tipoMime]
        )
    ) {

        return [
            'ok' => false,
            'mensaje' =>
                'La imagen debe estar en formato JPG, PNG o WEBP.'
        ];

    }


    // =====================================================
    // VALIDAR QUE REALMENTE SEA UNA IMAGEN
    // =====================================================

    $informacionImagen =
        @getimagesize(
            $archivo['tmp_name']
        );


    if (
        $informacionImagen === false
    ) {

        return [
            'ok' => false,
            'mensaje' =>
                'El archivo recibido no es una imagen válida.'
        ];

    }


    // =====================================================
    // DIRECTORIO DE DESTINO
    // =====================================================

    $directorio =
        __DIR__ .
        '/../uploads/img_vacante/';


    if (!is_dir($directorio)) {

        if (
            !mkdir(
                $directorio,
                0755,
                true
            )
        ) {

            return [
                'ok' => false,
                'mensaje' =>
                    'No fue posible crear la carpeta de imágenes.'
            ];

        }

    }


    // =====================================================
    // GENERAR NOMBRE ÚNICO
    // =====================================================

    $extension =
        $tiposPermitidos[$tipoMime];


    $nombreArchivo =
        'vacante_' .
        bin2hex(
            random_bytes(16)
        ) .
        '.' .
        $extension;


    $rutaFisica =
        $directorio .
        $nombreArchivo;


    // =====================================================
    // GUARDAR ARCHIVO
    // =====================================================

    if (
        !move_uploaded_file(
            $archivo['tmp_name'],
            $rutaFisica
        )
    ) {

        return [
            'ok' => false,
            'mensaje' =>
                'No fue posible guardar la imagen en el servidor.'
        ];

    }


    // =====================================================
    // URL PÚBLICA DE LA IMAGEN
    // =====================================================

    $urlImagen =
        '/Patch_BolsaTrabajo/public_html/backend/modulo/Patch_BolsaTrabajo/uploads/img_vacante/' .
        $nombreArchivo;


    return [

        'ok' => true,

        'mensaje' =>
            'Imagen guardada correctamente.',

        'url' =>
            $urlImagen,

        'ruta' =>
            $rutaFisica

    ];
}


// =========================================================
// CREAR VACANTE
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    // =====================================================
    // DATOS DEL FORMULARIO
    // =====================================================

    $titulo =
        trim(
            $_POST['titulo'] ?? ''
        );


    // =====================================================
    // NUEVOS CAMPOS
    // =====================================================

    $descripcion =
        trim(
            $_POST['descripcion'] ?? ''
        );


    $ubicacion =
        trim(
            $_POST['ubicacion'] ?? ''
        );


    $tipoJornada =
        trim(
            $_POST['tipo_jornada'] ?? ''
        );


    $modalidad =
        trim(
            $_POST['modalidad'] ?? ''
        );


    // =====================================================
    // CONTENIDO DE LA VACANTE
    // =====================================================

    $loQueSeOfrece =
        trim(
            $_POST['lo_que_se_ofrece'] ?? ''
        );


    $requisitos =
        trim(
            $_POST['requisitos'] ?? ''
        );


    $responsabilidades =
        trim(
            $_POST['responsabilidades'] ?? ''
        );


    // =====================================================
    // FECHA Y ESTADO
    // =====================================================

    $fechaCierre =
        trim(
            $_POST['fecha_cierre'] ?? ''
        );


    $activo =
        (int)(
            $_POST['activo'] ?? 1
        );


    $soloImagen =
        (int)(
            $_POST['solo_imagen'] ?? 0
        );


    // =====================================================
    // NORMALIZAR ESTADO
    // =====================================================

    $activo =
        $activo === 1
            ? 1
            : 0;


    $soloImagen =
        $soloImagen === 1
            ? 1
            : 0;


    // =====================================================
    // VALIDAR TÍTULO
    // =====================================================

    if (
        $titulo === ''
    ) {

        respuestaJSON(
            false,
            'El título de la vacante es obligatorio.'
        );

    }


    // =====================================================
    // VALIDAR CAMPOS DE TEXTO
    // SOLO CUANDO NO ES SOLO IMAGEN
    // =====================================================

    if (
        $soloImagen === 0
    ) {

        if (
            $loQueSeOfrece === ''
        ) {

            respuestaJSON(
                false,
                'El campo "Lo que se ofrece" es obligatorio.'
            );

        }


        if (
            $requisitos === ''
        ) {

            respuestaJSON(
                false,
                'El campo "Requisitos" es obligatorio.'
            );

        }


        if (
            $responsabilidades === ''
        ) {

            respuestaJSON(
                false,
                'El campo "Responsabilidades" es obligatorio.'
            );

        }

    }


    // =====================================================
    // VALIDAR IMAGEN EN MODO SOLO IMAGEN
    // =====================================================

    $tieneImagen =
        isset(
            $_FILES['imagen']
        ) &&
        isset(
            $_FILES['imagen']['error']
        ) &&
        $_FILES['imagen']['error'] ===
            UPLOAD_ERR_OK;


    if (
        $soloImagen === 1 &&
        !$tieneImagen
    ) {

        respuestaJSON(
            false,
            'Debes seleccionar una imagen para una vacante de solo imagen.'
        );

    }


    // =====================================================
    // GENERAR TOKEN
    // =====================================================

    $token =
        generarTokenBolsa(
            $conn
        );


    // =====================================================
    // FECHA DE CIERRE
    // =====================================================

    $fechaCierreDB =
        null;


    if (
        $fechaCierre !== ''
    ) {

        $fechaCierreDB =
            $fechaCierre .
            ' 23:59:59';

    }


    // =====================================================
    // IMAGEN
    // =====================================================

    $urlImagen =
        null;


    $rutaImagenGuardada =
        null;


    if (
        $tieneImagen
    ) {

        $resultadoImagen =
            guardarImagenVacante(
                $_FILES['imagen']
            );


        if (
            !$resultadoImagen['ok']
        ) {

            respuestaJSON(
                false,
                $resultadoImagen['mensaje']
            );

        }


        $urlImagen =
            $resultadoImagen['url'];


        $rutaImagenGuardada =
            $resultadoImagen['ruta'];

    }


    // =====================================================
    // INSERTAR VACANTE
    // =====================================================

    
    /* ===================================================== */
    /* INSERTAR VACANTE */
    /* ===================================================== */

    $sql = "INSERT INTO patch_BolsaTrabajo
            (
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
                fecha_cierre
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?
            )";

    $stmt = $conn->prepare($sql);



    if (
        !$stmt
    ) {

        // =================================================
        // ELIMINAR IMAGEN SI LA BD NO PUDO PREPARARSE
        // =================================================

        if (
            $rutaImagenGuardada &&
            file_exists(
                $rutaImagenGuardada
            )
        ) {

            @unlink(
                $rutaImagenGuardada
            );

        }


        respuestaJSON(
            false,
            'No fue posible preparar el registro de la vacante.'
        );

    }


    // =====================================================
    // ASIGNAR PARÁMETROS
    // =====================================================
    //
    // 9 strings:
    // token
    // titulo
    // descripcion
    // ubicacion
    // tipoJornada
    // modalidad
    // loQueSeOfrece
    // requisitos
    // responsabilidades
    //
    // 2 enteros:
    // activo
    // soloImagen
    //
    // 2 strings:
    // urlImagen
    // fechaCierreDB
    // =====================================================

    $stmt->bind_param(

        "sssssssssiiss",

        $token,

        $titulo,

        $descripcion,

        $ubicacion,

        $tipoJornada,

        $modalidad,

        $loQueSeOfrece,

        $requisitos,

        $responsabilidades,

        $activo,

        $soloImagen,

        $urlImagen,

        $fechaCierreDB

    );


    // =====================================================
    // EJECUTAR INSERT
    // =====================================================

    if (
        !$stmt->execute()
    ) {

        $stmt->close();


        // =================================================
        // SI FALLA BD, ELIMINAR IMAGEN YA GUARDADA
        // =================================================

        if (
            $rutaImagenGuardada &&
            file_exists(
                $rutaImagenGuardada
            )
        ) {

            @unlink(
                $rutaImagenGuardada
            );

        }


        respuestaJSON(
            false,
            'No fue posible guardar la vacante.'
        );

    }


    $stmt->close();


    // =====================================================
    // RECUPERAR REGISTRO CREADO
    // =====================================================

    $sqlGet =
        "SELECT *
         FROM patch_BolsaTrabajo
         WHERE token=?";


    $stmtGet =
        $conn->prepare(
            $sqlGet
        );


    if (
        !$stmtGet
    ) {

        respuestaJSON(
            true,
            'La vacante fue creada correctamente.'
        );

    }


    $stmtGet->bind_param(
        "s",
        $token
    );


    $stmtGet->execute();


    $result =
        $stmtGet->get_result();


    $vacante =
        $result->fetch_assoc();


    $stmtGet->close();


    // =====================================================
    // RESPUESTA FINAL
    // =====================================================

    respuestaJSON(

        true,

        'La vacante fue creada correctamente.',

        $vacante

    );

}


// =========================================================
// ACCIÓN NO VÁLIDA
// =========================================================

respuestaJSON(
    false,
    'Acción no válida.'
);
<?php
require_once __DIR__ . "/../../../../includes/auth.php";
requerirAdminApi();
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . "/../../../../includes/config.php";


header('Content-Type: application/json; charset=utf-8');


// =========================================================
// RESPUESTA JSON
// =========================================================

function respuestaJSON($ok, $mensaje = '', $datos = null)
{
    echo json_encode([
        'ok'      => $ok,
        'mensaje' => $mensaje,
        'datos'   => $datos
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =========================================================
// GUARDAR IMAGEN DE VACANTE
// =========================================================

// =========================================================
// GUARDAR IMAGEN DE VACANTE
// =========================================================

function guardarImagenVacante($archivo)
{
    if (!isset($archivo) || !is_array($archivo)) {
        return [
            'ok' => false,
            'mensaje' => 'No se recibió ninguna imagen.'
        ];
    }

    // Validar error de subida
    if (
        !isset($archivo['error']) ||
        $archivo['error'] !== UPLOAD_ERR_OK
    ) {
        return [
            'ok' => false,
            'mensaje' => 'No fue posible subir la imagen.'
        ];
    }

    // Validar archivo temporal
    if (
        !isset($archivo['tmp_name']) ||
        !is_uploaded_file($archivo['tmp_name'])
    ) {
        return [
            'ok' => false,
            'mensaje' => 'El archivo de imagen no es válido.'
        ];
    }

    // Tipos permitidos
    $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    // Validar MIME real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipoMime = $finfo->file($archivo['tmp_name']);

    if (!isset($tiposPermitidos[$tipoMime])) {
        return [
            'ok' => false,
            'mensaje' => 'La imagen debe estar en formato JPG, PNG o WEBP.'
        ];
    }

    // Validar imagen real
    if (@getimagesize($archivo['tmp_name']) === false) {
        return [
            'ok' => false,
            'mensaje' => 'El archivo recibido no es una imagen válida.'
        ];
    }

    // Directorio físico
    $directorio = __DIR__ . '/../uploads/img_vacante/';

    if (!is_dir($directorio)) {
        if (!mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            return [
                'ok' => false,
                'mensaje' => 'No fue posible crear la carpeta de imágenes.'
            ];
        }
    }

    // Generar nombre único
    $extension = $tiposPermitidos[$tipoMime];

    $nombreArchivo = 'vacante_' .
        bin2hex(random_bytes(16)) .
        '.' . $extension;

    $rutaFisica = $directorio . $nombreArchivo;

    // Guardar imagen
    if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
        return [
            'ok' => false,
            'mensaje' => 'No fue posible guardar la imagen en el servidor.'
        ];
    }

    // Confirmar que existe
    if (!is_file($rutaFisica)) {
        return [
            'ok' => false,
            'mensaje' => 'La imagen no se encontró después de guardarla.'
        ];
    }

    // =====================================================
    // CORREGIR PERMISOS HEREDADOS EN WINDOWS / IIS
    // =====================================================

    if (PHP_OS_FAMILY === 'Windows') {
        $funcionesDeshabilitadas = array_map(
            'trim',
            explode(',', (string) ini_get('disable_functions'))
        );

        if (
            !function_exists('exec') ||
            in_array('exec', $funcionesDeshabilitadas, true)
        ) {
            error_log(
                'No se pueden corregir permisos de imagen: exec no disponible. Archivo: ' .
                $rutaFisica
            );

            return [
                'ok' => false,
                'mensaje' => 'La imagen se guardó, pero el servidor no permite configurar sus permisos automáticamente.'
            ];
        }

        $salida = [];
        $codigo = 1;

        $comando = 'icacls ' .
            escapeshellarg($rutaFisica) .
            ' /reset';

        exec($comando . ' 2>&1', $salida, $codigo);

        if ($codigo !== 0) {
            error_log(
                'Error al corregir permisos de imagen: ' .
                $rutaFisica . ' | ' .
                implode(' ', $salida)
            );

            return [
                'ok' => false,
                'mensaje' => 'La imagen se guardó, pero no fue posible configurar sus permisos. Contacta al administrador.'
            ];
        }
    }

    // URL pública
    $urlImagen =
        '/Patch_BolsaTrabajo/public_html/backend/modulo/Patch_BolsaTrabajo/uploads/img_vacante/' .
        $nombreArchivo;

    return [
        'ok' => true,
        'mensaje' => 'Imagen guardada correctamente.',
        'url' => $urlImagen,
        'ruta' => $rutaFisica
    ];
}

// =========================================================
// OBTENER RUTA FÍSICA DE UNA IMAGEN EXISTENTE
// =========================================================

function obtenerRutaFisicaImagen($urlImagen)
{
    if (
        !$urlImagen ||
        !is_string($urlImagen)
    ) {

        return null;
    }


    $nombreArchivo =
        basename(
            parse_url(
                $urlImagen,
                PHP_URL_PATH
            )
        );


    if (
        !$nombreArchivo ||
        $nombreArchivo === '.' ||
        $nombreArchivo === '..'
    ) {

        return null;
    }


    return
        __DIR__ .
        '/../uploads/img_vacante/' .
        $nombreArchivo;
}


// =========================================================
// EDITAR VACANTE
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    // =====================================================
    // DATOS DEL FORMULARIO
    // =====================================================

    $id =
        (int)(
            $_POST['id'] ?? 0
        );


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
    // BANDERA PARA ELIMINAR IMAGEN
    // =====================================================

    $eliminarImagen =
        isset(
            $_POST['eliminar_imagen']
        ) &&
        $_POST['eliminar_imagen'] === '1';


    // =====================================================
    // NORMALIZAR VALORES
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
    // VALIDAR ID
    // =====================================================

    if (
        $id <= 0
    ) {

        respuestaJSON(
            false,
            'La vacante indicada no es válida.'
        );
    }


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
    // OBTENER VACANTE ACTUAL
    // =====================================================

    $sqlActual =
        "SELECT *
         FROM patch_BolsaTrabajo
         WHERE id=?";


    $stmtActual =
        $conn->prepare(
            $sqlActual
        );


    if (
        !$stmtActual
    ) {

        respuestaJSON(
            false,
            'No fue posible consultar la vacante.'
        );
    }


    $stmtActual->bind_param(
        "i",
        $id
    );


    if (
        !$stmtActual->execute()
    ) {

        $stmtActual->close();


        respuestaJSON(
            false,
            'No fue posible consultar la vacante.'
        );
    }


    $resultadoActual =
        $stmtActual->get_result();


    $vacanteActual =
        $resultadoActual->fetch_assoc();


    $stmtActual->close();


    // =====================================================
    // VALIDAR QUE EXISTA
    // =====================================================

    if (
        !$vacanteActual
    ) {

        respuestaJSON(
            false,
            'La vacante indicada no existe.'
        );
    }


    // =====================================================
    // VALIDAR CAMPOS DE TEXTO
    // SOLO SI NO ES SOLO IMAGEN
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
    // IMAGEN ACTUAL
    // =====================================================

    $urlImagenActual =
        !empty(
            $vacanteActual['url_imagen']
        )
            ? $vacanteActual['url_imagen']
            : null;


    $rutaImagenActual =
        obtenerRutaFisicaImagen(
            $urlImagenActual
        );


    // =====================================================
    // DETECTAR IMAGEN NUEVA
    // =====================================================

    $tieneImagenNueva =
        isset(
            $_FILES['imagen']
        ) &&
        isset(
            $_FILES['imagen']['error']
        ) &&
        $_FILES['imagen']['error'] ===
            UPLOAD_ERR_OK;


    // =====================================================
    // DETERMINAR NUEVA URL
    // =====================================================
    //
    // Por defecto se conserva exactamente
    // la imagen actual.
    //
    // Esto permite modificar cualquier otro
    // dato sin afectar la imagen.
    // =====================================================

    $urlImagenFinal =
        $urlImagenActual;


    $rutaImagenNueva =
        null;


    // =====================================================
    // CASO 1:
    // SE SELECCIONÓ UNA IMAGEN NUEVA
    // =====================================================

    if (
        $tieneImagenNueva
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


        $urlImagenFinal =
            $resultadoImagen['url'];


        $rutaImagenNueva =
            $resultadoImagen['ruta'];
    }


    // =====================================================
    // CASO 2:
    // SE SOLICITÓ ELIMINAR LA IMAGEN
    // =====================================================

    elseif (
        $eliminarImagen
    ) {

        $urlImagenFinal =
            null;
    }


    // =====================================================
    // VALIDAR MODO SOLO IMAGEN
    // =====================================================

    if (
        $soloImagen === 1 &&
        !$urlImagenFinal
    ) {

        // =================================================
        // Si se había subido una imagen nueva pero el
        // registro no puede continuar, eliminarla.
        // =================================================

        if (
            $rutaImagenNueva &&
            file_exists(
                $rutaImagenNueva
            )
        ) {

            @unlink(
                $rutaImagenNueva
            );
        }


        respuestaJSON(
            false,
            'Debes seleccionar una imagen para una vacante de solo imagen.'
        );
    }


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
    // ACTUALIZAR REGISTRO
    // =====================================================

    $sql =
        "UPDATE patch_BolsaTrabajo
         SET
            titulo=?,
            descripcion=?,
            ubicacion=?,
            tipo_jornada=?,
            modalidad=?,
            lo_que_se_ofrece=?,
            requisitos=?,
            responsabilidades=?,
            activo=?,
            solo_imagen=?,
            url_imagen=?,
            fecha_cierre=?
         WHERE id=?";


    $stmt =
        $conn->prepare(
            $sql
        );


    if (
        !$stmt
    ) {

        // =================================================
        // Si se había subido una imagen nueva y la consulta
        // no pudo prepararse, eliminar esa imagen.
        // =================================================

        if (
            $rutaImagenNueva &&
            file_exists(
                $rutaImagenNueva
            )
        ) {

            @unlink(
                $rutaImagenNueva
            );
        }


        respuestaJSON(
            false,
            'No fue posible preparar la actualización de la vacante.'
        );
    }


    // =====================================================
    // ASIGNAR PARÁMETROS
    // =====================================================
    //
    // Strings:
    // titulo
    // descripcion
    // ubicacion
    // tipoJornada
    // modalidad
    // loQueSeOfrece
    // requisitos
    // responsabilidades
    //
    // Integers:
    // activo
    // soloImagen
    //
    // Strings:
    // urlImagenFinal
    // fechaCierreDB
    //
    // Integer:
    // id
    // =====================================================

    $stmt->bind_param(
    "ssssssssiissi",
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
    $urlImagenFinal,
    $fechaCierreDB,
    $id
);


    // =====================================================
    // EJECUTAR ACTUALIZACIÓN
    // =====================================================

    if (
        !$stmt->execute()
    ) {

        $stmt->close();


        // =================================================
        // Si la BD falló después de guardar una imagen
        // nueva, eliminar la imagen para evitar archivos
        // huérfanos.
        // =================================================

        if (
            $rutaImagenNueva &&
            file_exists(
                $rutaImagenNueva
            )
        ) {

            @unlink(
                $rutaImagenNueva
            );
        }


        respuestaJSON(
            false,
            'No fue posible actualizar la vacante.'
        );
    }


    $stmt->close();


    // =====================================================
    // ELIMINAR IMAGEN ANTERIOR
    // =====================================================
    //
    // Solamente se elimina cuando:
    //
    // 1. Se reemplazó por una nueva.
    // 2. Se eliminó explícitamente.
    //
    // Si solamente se editó información de la vacante,
    // la imagen anterior permanece intacta.
    // =====================================================

    $debeEliminarImagenAnterior =
        $tieneImagenNueva ||
        (
            $eliminarImagen &&
            !$tieneImagenNueva
        );


    if (
        $debeEliminarImagenAnterior &&
        $rutaImagenActual &&
        file_exists(
            $rutaImagenActual
        )
    ) {

        // =================================================
        // Nunca eliminar la imagen anterior si por alguna
        // razón la ruta coincide con la nueva.
        // =================================================

        $rutaRealAnterior =
            realpath(
                $rutaImagenActual
            );


        $rutaRealNueva =
            $rutaImagenNueva
                ? realpath(
                    $rutaImagenNueva
                )
                : null;


        if (
            !$rutaRealNueva ||
            $rutaRealAnterior !==
                $rutaRealNueva
        ) {

            @unlink(
                $rutaImagenActual
            );
        }
    }


    // =====================================================
    // RECUPERAR REGISTRO ACTUALIZADO
    // =====================================================

    $sqlGet =
        "SELECT *
         FROM patch_BolsaTrabajo
         WHERE id=?";


    $stmtGet =
        $conn->prepare(
            $sqlGet
        );


    if (
        !$stmtGet
    ) {

        respuestaJSON(
            true,
            'La vacante fue actualizada correctamente.'
        );
    }


    $stmtGet->bind_param(
        "i",
        $id
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

        'La vacante fue actualizada correctamente.',

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
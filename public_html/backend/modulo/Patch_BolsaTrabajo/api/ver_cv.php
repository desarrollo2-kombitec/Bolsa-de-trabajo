<?php
require_once __DIR__ . "/../../../../includes/auth.php";
requerirAdminApi();

error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . "/../../../../includes/config.php";

/*
 * Este archivo entrega el CV asociado a una postulación.
 *
 * Uso:
 * ver_cv.php?id=1
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Método no permitido.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('La postulación indicada no es válida.');
}

try {

    /*
     * Buscamos únicamente el CV correspondiente
     * a la postulación solicitada.
     */
    $sql = "
        SELECT
            id,
            cv,
            nombre_completo
        FROM patch_postulaciones
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit('No fue posible preparar la consulta.');
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();

        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit('No fue posible consultar el CV.');
    }

    $resultado = $stmt->get_result();

    if (!$resultado || $resultado->num_rows === 0) {
        $stmt->close();

        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('La postulación no fue encontrada.');
    }

    $postulacion = $resultado->fetch_assoc();

    $stmt->close();

    $rutaBD = trim((string)($postulacion['cv'] ?? ''));

    if ($rutaBD === '') {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Esta postulación no tiene un CV registrado.');
    }

    /*
     * La base de datos guarda normalmente una ruta como:
     *
     * backend/modulo/Patch_BolsaTrabajo/uploads/postulaciones/archivo.pdf
     *
     * Convertimos esa ruta a una ruta física dentro de public_html.
     */

    $rutaBD = str_replace('\\', '/', $rutaBD);
    $rutaBD = ltrim($rutaBD, '/');

    /*
     * Permitimos únicamente archivos PDF.
     */
    $extension = strtolower(pathinfo($rutaBD, PATHINFO_EXTENSION));

    if ($extension !== 'pdf') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El archivo asociado no es un PDF válido.');
    }

    /*
     * La carpeta física donde se almacenan los CV.
     */
    $directorioCV = realpath(
        __DIR__ . "/../uploads/postulaciones"
    );

    if ($directorioCV === false || !is_dir($directorioCV)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit('La carpeta de CV no está disponible.');
    }

    /*
     * Extraemos únicamente el nombre del archivo.
     *
     * Esto evita que una ruta almacenada en BD pueda utilizar
     * ../ para intentar salir de la carpeta de CV.
     */
    $nombreArchivo = basename($rutaBD);

    if ($nombreArchivo === '' || $nombreArchivo === '.' || $nombreArchivo === '..') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El archivo indicado no es válido.');
    }

    /*
     * Validamos nuevamente que el nombre corresponda
     * a un PDF.
     */
    if (strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION)) !== 'pdf') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El archivo solicitado no es un PDF.');
    }

    /*
     * Ruta física final.
     */
    $rutaArchivo = $directorioCV . DIRECTORY_SEPARATOR . $nombreArchivo;

    /*
     * Verificamos que el archivo exista.
     */
    if (!is_file($rutaArchivo)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El CV no fue encontrado en el servidor.');
    }

    /*
     * Verificamos que pueda ser leído.
     */
    if (!is_readable($rutaArchivo)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('No es posible acceder al CV solicitado.');
    }

    /*
     * Validamos el tipo MIME real del archivo.
     */
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($rutaArchivo);

    if ($mime !== 'application/pdf') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        exit('El archivo no tiene un formato PDF válido.');
    }

    /*
     * Limpiamos cualquier salida previa antes de enviar el PDF.
     */
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    /*
     * Nombre seguro para mostrar al navegador.
     */
    $nombreCandidato = trim(
        (string)($postulacion['nombre_completo'] ?? '')
    );

    $nombreCandidato = preg_replace(
        '/[^A-Za-zÁÉÍÓÚáéíóúÑñ0-9 _-]/u',
        '',
        $nombreCandidato
    );

    $nombreCandidato = trim($nombreCandidato);

    if ($nombreCandidato === '') {
        $nombreCandidato = 'Candidato';
    }

    $nombreDescarga = 'CV_' . $nombreCandidato . '.pdf';

    /*
     * Cabeceras para visualizar el PDF directamente
     * en el navegador.
     */
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($rutaArchivo));
    header(
        'Content-Disposition: inline; filename="' .
        str_replace('"', '', $nombreDescarga) .
        '"'
    );
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    /*
     * Enviamos el PDF.
     */
    readfile($rutaArchivo);

    exit;

} catch (Throwable $e) {

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Ocurrió un error al intentar mostrar el CV.');
}

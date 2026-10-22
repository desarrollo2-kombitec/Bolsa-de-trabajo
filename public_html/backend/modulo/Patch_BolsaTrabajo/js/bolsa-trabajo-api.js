/* ============================================================
   API DE BOLSA DE TRABAJO
   ============================================================ */

const URL_CONTROLADOR = 'Admin_BolsaTrabajo.php';

/* ============================================================
   FUNCIÓN GENÉRICA PARA PETICIONES AJAX
   ============================================================ */

async function peticion(accion, datos = null) {
    let url;

    if (accion === 'listar' || accion === 'listarArchivadas') {
        url = 'api/listar.php?accion=' + encodeURIComponent(accion);
    } else {
        url = 'api/' + encodeURIComponent(accion) + '.php';
    }

    const opciones = {
        method: datos !== null ? 'POST' : 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    };

    if (datos !== null) {
        opciones.body = datos;
    }

    let respuesta;

    try {
        respuesta = await fetch(url, opciones);
    } catch (error) {
        console.error('Error de conexión con el servidor:', error);
        throw new Error('No fue posible conectar con el servidor. Comprueba tu conexión e inténtalo nuevamente.');
    }

    const raw = await respuesta.text();

    console.log('Petición API:', url);
    console.log('Estado HTTP:', respuesta.status);
    console.log('Tipo de contenido:', respuesta.headers.get('content-type'));
    console.log('Respuesta cruda del backend:', raw);

    let resultado;

    try {
        resultado = JSON.parse(raw);
    } catch (error) {
        console.error('La respuesta del servidor no es JSON válido:', error);

        if (respuesta.status === 406) {
            throw new Error(
                'Bluehost rechazó la petición con HTTP 406. ' +
                'Es posible que ModSecurity esté bloqueando el contenido enviado. ' +
                'Revisa la pestaña Red del navegador y los registros de seguridad del servidor.'
            );
        }

        if (respuesta.status === 403) {
            throw new Error('El servidor rechazó la petición con HTTP 403. Revisa los permisos y las reglas de seguridad.');
        }

        if (respuesta.status === 404) {
            throw new Error('No se encontró el recurso solicitado. Verifica la ruta de la API.');
        }

        if (respuesta.status >= 500) {
            throw new Error('El servidor presentó un error HTTP ' + respuesta.status + '. Revisa los registros PHP del hosting.');
        }

        throw new Error(
            'El servidor devolvió una respuesta que no es JSON válido (HTTP ' +
            respuesta.status + '). Consulta la consola para ver la respuesta completa.'
        );
    }

    if (!respuesta.ok) {
        throw new Error(
            resultado.mensaje ||
            'La petición falló con el código HTTP ' + respuesta.status + '.'
        );
    }

    return resultado;
}

/* ============================================================
   CARGAR VACANTES ACTIVAS E INACTIVAS
   ============================================================ */

async function cargarVacantes() {
    listaVacantes.innerHTML = `
        <div class="patch-loading">
            <i class="fa fa-spinner fa-spin"></i>
            Cargando vacantes...
        </div>`;

    try {
        const respuesta = await peticion('listar');

        if (!respuesta.ok) {
            throw new Error(respuesta.mensaje || 'No fue posible cargar las vacantes.');
        }

        vacantes = Array.isArray(respuesta.datos) ? respuesta.datos : [];
        actualizarContadores();
        renderizarVacantes();
    } catch (error) {
        console.error('Error al cargar vacantes:', error);

        listaVacantes.innerHTML = `
            <div class="patch-loading">
                <i class="fa fa-exclamation-triangle"></i>
                No fue posible cargar las vacantes.
            </div>`;

        mostrarToast(error.message || 'No fue posible cargar las vacantes.', true);
    }
}

/* ============================================================
   CARGAR VACANTES ARCHIVADAS
   ============================================================ */

async function cargarVacantesArchivadas() {
    listaVacantesArchivadas.innerHTML = `
        <div class="patch-loading">
            <i class="fa fa-spinner fa-spin"></i>
            Cargando vacantes archivadas...
        </div>`;

    try {
        const respuesta = await peticion('listarArchivadas');

        if (!respuesta.ok) {
            throw new Error(respuesta.mensaje || 'No fue posible cargar las vacantes archivadas.');
        }

        vacantesArchivadas = Array.isArray(respuesta.datos) ? respuesta.datos : [];
        renderizarVacantesArchivadas();
    } catch (error) {
        console.error('Error al cargar vacantes archivadas:', error);

        listaVacantesArchivadas.innerHTML = `
            <div class="patch-loading">
                <i class="fa fa-exclamation-triangle"></i>
                No fue posible cargar las vacantes archivadas.
            </div>`;

        mostrarToast(error.message || 'No fue posible cargar las vacantes archivadas.', true);
    }
}
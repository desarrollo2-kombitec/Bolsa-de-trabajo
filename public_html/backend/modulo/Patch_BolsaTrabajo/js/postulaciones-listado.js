
/**
 * ============================================================
 * LISTADO DE POSTULACIONES
 * Patch_BolsaTrabajo
 * ============================================================
 * - Búsqueda dinámica mediante AJAX.
 * - Filtros combinables por texto, vacante y estado.
 * - Funciona también al borrar caracteres.
 * - Sin recargar la página completa.
 * - Conserva el foco, el cursor y el scroll.
 * - Evita que respuestas antiguas reemplacen resultados nuevos.
 * - Paginación independiente por tabla.
 * - Eventos delegados para sobrevivir al reemplazo AJAX del panel.
 * ============================================================
 */

(function (window, document) {
    'use strict';

    let temporizadorBusqueda = null;
    let solicitudActual = null;
    let identificadorSolicitud = 0;

    const RETARDO_BUSQUEDA = 300;

    /* ============================================================
       OBTENER ELEMENTOS
    ============================================================ */

    function obtenerFormularioFiltros() {
        return document.getElementById('formFiltros');
    }

    function obtenerSelectVacante() {
        return document.getElementById('filtroVacante');
    }

    function obtenerSelectEstado() {
        return document.getElementById('filtroEstado');
    }

    function obtenerCampoBusqueda() {
        return document.getElementById('buscar');
    }

    function obtenerPanelPostulaciones() {
        return document.getElementById('panelPostulaciones');
    }

    /* ============================================================
       OBTENER FILTROS ACTUALES
    ============================================================ */

    function obtenerFiltrosActuales() {
        const campoBusqueda = obtenerCampoBusqueda();
        const selectVacante = obtenerSelectVacante();
        const selectEstado = obtenerSelectEstado();

        return {
            buscar: campoBusqueda ? campoBusqueda.value : '',
            vacante: selectVacante ? selectVacante.value : '0',
            estado: selectEstado ? selectEstado.value : ''
        };
    }

    function existenFiltrosActivos() {
        const filtros = obtenerFiltrosActuales();

        return (
            filtros.buscar.trim() !== '' ||
            (filtros.vacante !== '' && filtros.vacante !== '0') ||
            filtros.estado !== ''
        );
    }

    function actualizarEstadoFiltros() {
        const formulario = obtenerFormularioFiltros();

        if (formulario) {
            formulario.classList.toggle(
                'has-filters',
                existenFiltrosActivos()
            );
        }
    }

    /* ============================================================
       GUARDAR Y RESTAURAR ESTADO DE LA PÁGINA
    ============================================================ */

    function guardarEstadoPagina() {
        const elementoActivo = document.activeElement;
        const panel = obtenerPanelPostulaciones();

        let elementoId = '';
        let inicioSeleccion = null;
        let finSeleccion = null;
        let direccionSeleccion = null;

        if (
            panel &&
            elementoActivo &&
            panel.contains(elementoActivo)
        ) {
            elementoId = elementoActivo.id || '';

            if (
                elementoActivo.tagName === 'INPUT' ||
                elementoActivo.tagName === 'TEXTAREA'
            ) {
                try {
                    inicioSeleccion = elementoActivo.selectionStart;
                    finSeleccion = elementoActivo.selectionEnd;
                    direccionSeleccion = elementoActivo.selectionDirection;
                } catch (error) {
                    inicioSeleccion = null;
                    finSeleccion = null;
                    direccionSeleccion = null;
                }
            }
        }

        return {
            scrollX: window.scrollX,
            scrollY: window.scrollY,
            elementoId: elementoId,
            inicioSeleccion: inicioSeleccion,
            finSeleccion: finSeleccion,
            direccionSeleccion: direccionSeleccion
        };
    }

    function restaurarEstadoPagina(estado) {
        if (!estado) {
            return;
        }

        if (estado.elementoId) {
            const elemento = document.getElementById(estado.elementoId);

            if (elemento) {
                try {
                    elemento.focus({ preventScroll: true });
                } catch (error) {
                    elemento.focus();
                }

                if (
                    estado.inicioSeleccion !== null &&
                    estado.finSeleccion !== null &&
                    typeof elemento.setSelectionRange === 'function'
                ) {
                    try {
                        elemento.setSelectionRange(
                            estado.inicioSeleccion,
                            estado.finSeleccion,
                            estado.direccionSeleccion || 'none'
                        );
                    } catch (error) {
                        // Algunos controles no permiten seleccionar texto.
                    }
                }
            }
        }

        window.scrollTo(estado.scrollX, estado.scrollY);
    }

    /* ============================================================
       ACTUALIZAR URL SIN RECARGAR
    ============================================================ */

    function actualizarURLFiltros() {
        const filtros = obtenerFiltrosActuales();
        const url = new URL(window.location.href);

        url.searchParams.delete('ajax');
        url.searchParams.delete('buscar');
        url.searchParams.delete('vacante');
        url.searchParams.delete('estado');

        if (filtros.buscar.trim() !== '') {
            url.searchParams.set('buscar', filtros.buscar.trim());
        }

        if (filtros.vacante !== '' && filtros.vacante !== '0') {
            url.searchParams.set('vacante', filtros.vacante);
        }

        if (filtros.estado !== '') {
            url.searchParams.set('estado', filtros.estado);
        }

        window.history.replaceState({}, '', url.toString());
    }

    /* ============================================================
       FILTROS AJAX
    ============================================================ */

    async function aplicarFiltrosAjax(opciones) {
        opciones = opciones || {};

        if (temporizadorBusqueda !== null) {
            window.clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = null;
        }

        const formulario = obtenerFormularioFiltros();
        const panelActual = obtenerPanelPostulaciones();

        if (!formulario || !panelActual) {
            return;
        }

        actualizarEstadoFiltros();

        if (solicitudActual) {
            solicitudActual.abort();
            solicitudActual = null;
        }

        const numeroSolicitud = ++identificadorSolicitud;
        const controlador = new AbortController();

        solicitudActual = controlador;

        const url = new URL(
            formulario.getAttribute('action') || 'Admin_Postulaciones.php',
            window.location.href
        );

        const filtros = obtenerFiltrosActuales();

        /*
         * Construimos la URL con los tres filtros actuales.
         * Los valores vacíos también se contemplan para que
         * borrar caracteres elimine el criterio anterior.
         */
        url.search = '';
        url.searchParams.set('buscar', filtros.buscar.trim());
        url.searchParams.set('vacante', filtros.vacante || '0');
        url.searchParams.set('estado', filtros.estado);
        url.searchParams.set('ajax', 'listado');

        const botonesSubmit = formulario.querySelectorAll(
            'button[type="submit"], input[type="submit"]'
        );

        botonesSubmit.forEach(function (boton) {
            boton.disabled = true;
        });

        try {
            const respuesta = await window.fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                },
                credentials: 'same-origin',
                signal: controlador.signal
            });

            if (!respuesta.ok) {
                throw new Error(
                    'No se pudo actualizar el listado. HTTP ' +
                    respuesta.status
                );
            }

            const html = await respuesta.text();

            /*
             * Si se inició otra búsqueda mientras esta esperaba,
             * ignoramos esta respuesta.
             */
            if (numeroSolicitud !== identificadorSolicitud) {
                return;
            }

            const documento = new DOMParser().parseFromString(
                html,
                'text/html'
            );

            const panelNuevo = documento.getElementById(
                'panelPostulaciones'
            );

            const panelParaReemplazar = obtenerPanelPostulaciones();

            if (!panelNuevo || !panelParaReemplazar) {
                throw new Error(
                    'La respuesta del servidor no contiene ' +
                    'el panel #panelPostulaciones.'
                );
            }

            /*
             * Guardamos los valores que el usuario tiene en pantalla
             * justo antes de reemplazar el panel. Esto evita perder
             * caracteres escritos mientras la petición estaba activa.
             */
            const filtrosEnPantalla = obtenerFiltrosActuales();
            const estadoPagina = guardarEstadoPagina();

            const panelReemplazo = document.importNode(
                panelNuevo,
                true
            );

            panelParaReemplazar.replaceWith(panelReemplazo);

            /*
             * Recuperamos los controles nuevos, porque los anteriores
             * dejaron de existir al reemplazar el panel.
             */
            const nuevoCampoBusqueda = obtenerCampoBusqueda();
            const nuevoSelectVacante = obtenerSelectVacante();
            const nuevoSelectEstado = obtenerSelectEstado();

            if (nuevoCampoBusqueda) {
                nuevoCampoBusqueda.value = filtrosEnPantalla.buscar;
            }

            if (nuevoSelectVacante) {
                nuevoSelectVacante.value = filtrosEnPantalla.vacante;
            }

            if (nuevoSelectEstado) {
                nuevoSelectEstado.value = filtrosEnPantalla.estado;
            }

            actualizarEstadoFiltros();

            /*
             * Solo reinicializamos la paginación de postulaciones.
             * La paginación de CV generales permanece independiente.
             */
            inicializarPaginacionTablaPorId('postulaciones');

            actualizarURLFiltros();
            restaurarEstadoPagina(estadoPagina);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(
                    'Error al actualizar las postulaciones:',
                    error
                );
            }
        } finally {
            if (numeroSolicitud === identificadorSolicitud) {
                solicitudActual = null;

                const formularioActual = obtenerFormularioFiltros();

                if (formularioActual) {
                    const botonesActuales = formularioActual.querySelectorAll(
                        'button[type="submit"], input[type="submit"]'
                    );

                    botonesActuales.forEach(function (boton) {
                        boton.disabled = false;
                    });

                    actualizarEstadoFiltros();
                }
            }
        }
    }

    /* ============================================================
       BÚSQUEDA DINÁMICA
    ============================================================ */

    function programarBusqueda() {
        actualizarEstadoFiltros();

        if (temporizadorBusqueda !== null) {
            window.clearTimeout(temporizadorBusqueda);
        }

        temporizadorBusqueda = window.setTimeout(function () {
            temporizadorBusqueda = null;
            aplicarFiltrosAjax();
        }, RETARDO_BUSQUEDA);
    }

    function buscarPostulaciones() {
        if (temporizadorBusqueda !== null) {
            window.clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = null;
        }

        aplicarFiltrosAjax();
    }

    /* ============================================================
       FILTRAR POR VACANTE Y ESTADO
    ============================================================ */

    function filtrarVacante(id) {
        const select = obtenerSelectVacante();

        if (!select) {
            return;
        }

        const idVacante = parseInt(id, 10);

        if (!Number.isFinite(idVacante) || idVacante < 0) {
            return;
        }

        select.value = String(idVacante);
        aplicarFiltrosAjax();
    }

    function filtrarEstado(estado) {
        const select = obtenerSelectEstado();

        if (!select) {
            return;
        }

        select.value = String(estado || '').trim();
        aplicarFiltrosAjax();
    }

    function establecerFiltroVacante(id) {
        const select = obtenerSelectVacante();

        if (!select) {
            return;
        }

        const idVacante = parseInt(id, 10);

        select.value = (
            Number.isFinite(idVacante) && idVacante >= 0
        ) ? String(idVacante) : '0';

        actualizarEstadoFiltros();
    }

    /* ============================================================
       LIMPIAR TODOS LOS FILTROS
    ============================================================ */

    function limpiarFiltros() {
        const campoBusqueda = obtenerCampoBusqueda();
        const selectVacante = obtenerSelectVacante();
        const selectEstado = obtenerSelectEstado();

        if (campoBusqueda) {
            campoBusqueda.value = '';
        }

        if (selectVacante) {
            selectVacante.value = '0';
        }

        if (selectEstado) {
            selectEstado.value = '';
        }

        buscarPostulaciones();
    }

    /* ============================================================
       EVENTOS DE FILTROS: DELEGACIÓN
       IMPORTANTE:
       Los eventos se registran en document, no directamente en los
       controles. Así siguen funcionando tras cada reemplazo AJAX.
    ============================================================ */

    function configurarEventosDelegados() {
        if (document.documentElement.dataset.postulacionesEventos === 'true') {
            return;
        }

        document.documentElement.dataset.postulacionesEventos = 'true';

        document.addEventListener('input', function (event) {
            if (
                event.target &&
                event.target.id === 'buscar' &&
                event.target.closest('#formFiltros')
            ) {
                programarBusqueda();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (
                event.target &&
                event.target.id === 'buscar' &&
                event.target.closest('#formFiltros') &&
                event.key === 'Enter'
            ) {
                event.preventDefault();
                buscarPostulaciones();
            }
        });

        document.addEventListener('change', function (event) {
            const elemento = event.target;

            if (!elemento || !elemento.closest('#formFiltros')) {
                return;
            }

            if (
                elemento.id === 'filtroVacante' ||
                elemento.id === 'filtroEstado'
            ) {
                actualizarEstadoFiltros();
                buscarPostulaciones();
            }
        });

        document.addEventListener('submit', function (event) {
            if (
                event.target &&
                event.target.id === 'formFiltros'
            ) {
                event.preventDefault();
                buscarPostulaciones();
            }
        });
    }

    /* ============================================================
       SCROLL
    ============================================================ */

    function posicionarListado() {
        // No desplaza la página automáticamente.
    }

    /* ============================================================
       PAGINACIÓN: CREAR BOTONES
    ============================================================ */

    function crearBotonPagina(texto, pagina, opciones) {
        opciones = opciones || {};

        const boton = document.createElement('button');

        boton.type = 'button';
        boton.className = 'patch-pagination-button';
        boton.textContent = String(texto);
        boton.disabled = Boolean(opciones.disabled);

        if (opciones.active) {
            boton.classList.add('is-active');
            boton.setAttribute('aria-current', 'page');
        }

        if (opciones.label) {
            boton.setAttribute('aria-label', opciones.label);
        }

        if (typeof pagina === 'number' && !boton.disabled) {
            boton.addEventListener('click', function () {
                if (typeof opciones.onSelect === 'function') {
                    opciones.onSelect(pagina);
                }
            });
        }

        return boton;
    }

    /* ============================================================
       PAGINACIÓN: NÚMEROS DE PÁGINA
    ============================================================ */

    function obtenerNumerosPagina(paginaActual, totalPaginas) {
        if (totalPaginas <= 0) {
            return [];
        }

        if (totalPaginas <= 7) {
            return Array.from(
                { length: totalPaginas },
                function (_, indice) {
                    return indice + 1;
                }
            );
        }

        const paginas = [1];
        const inicio = Math.max(2, paginaActual - 1);
        const fin = Math.min(totalPaginas - 1, paginaActual + 1);

        if (inicio > 2) {
            paginas.push('...');
        }

        for (let pagina = inicio; pagina <= fin; pagina++) {
            paginas.push(pagina);
        }

        if (fin < totalPaginas - 1) {
            paginas.push('...');
        }

        paginas.push(totalPaginas);

        return paginas;
    }

    /* ============================================================
       PAGINACIÓN: ACTUALIZAR CONTROLES
    ============================================================ */

    function actualizarControlesPaginacion(estado) {
        const totalRegistros = estado.filas.length;
        const totalPaginas = Math.ceil(
            totalRegistros / estado.tamanoPagina
        );

        const paginaActual = totalPaginas > 0
            ? Math.min(Math.max(estado.pagina, 1), totalPaginas)
            : 1;

        estado.pagina = paginaActual;

        if (estado.resumen) {
            if (totalRegistros === 0) {
                estado.resumen.textContent = 'Mostrando 0 registros';
            } else {
                const primero =
                    (paginaActual - 1) * estado.tamanoPagina + 1;

                const ultimo = Math.min(
                    paginaActual * estado.tamanoPagina,
                    totalRegistros
                );

                estado.resumen.textContent =
                    'Mostrando ' + primero + '–' + ultimo +
                    ' de ' + totalRegistros + ' ' +
                    (totalRegistros === 1 ? 'registro' : 'registros');
            }
        }

        if (!estado.controles) {
            return;
        }

        estado.controles.replaceChildren();

        estado.controles.appendChild(
            crearBotonPagina('Anterior', paginaActual - 1, {
                disabled: paginaActual <= 1,
                label: 'Ir a la página anterior',
                onSelect: function (pagina) {
                    cambiarPagina(estado, pagina);
                }
            })
        );

        obtenerNumerosPagina(
            paginaActual,
            totalPaginas
        ).forEach(function (pagina) {
            if (pagina === '...') {
                const separador = document.createElement('span');

                separador.className = 'patch-pagination-ellipsis';
                separador.textContent = '…';
                separador.setAttribute('aria-hidden', 'true');

                estado.controles.appendChild(separador);
                return;
            }

            estado.controles.appendChild(
                crearBotonPagina(pagina, pagina, {
                    active: pagina === paginaActual,
                    label: 'Ir a la página ' + pagina,
                    onSelect: function (numeroPagina) {
                        cambiarPagina(estado, numeroPagina);
                    }
                })
            );
        });

        estado.controles.appendChild(
            crearBotonPagina('Siguiente', paginaActual + 1, {
                disabled:
                    totalPaginas === 0 || paginaActual >= totalPaginas,
                label: 'Ir a la página siguiente',
                onSelect: function (pagina) {
                    cambiarPagina(estado, pagina);
                }
            })
        );
    }

    /* ============================================================
       PAGINACIÓN: MOSTRAR FILAS
    ============================================================ */

    function mostrarPagina(estado) {
        const totalRegistros = estado.filas.length;
        const totalPaginas = Math.ceil(
            totalRegistros / estado.tamanoPagina
        );

        if (totalPaginas === 0) {
            estado.pagina = 1;
        } else {
            estado.pagina = Math.min(
                Math.max(estado.pagina, 1),
                totalPaginas
            );
        }

        const inicio = (estado.pagina - 1) * estado.tamanoPagina;
        const fin = inicio + estado.tamanoPagina;

        estado.filas.forEach(function (fila, indice) {
            fila.hidden = indice < inicio || indice >= fin;
        });

        actualizarControlesPaginacion(estado);
    }

    function cambiarPagina(estado, pagina) {
        const totalPaginas = Math.ceil(
            estado.filas.length / estado.tamanoPagina
        );

        if (
            !Number.isFinite(pagina) ||
            pagina < 1 ||
            pagina > totalPaginas ||
            pagina === estado.pagina
        ) {
            return;
        }

        estado.pagina = pagina;
        mostrarPagina(estado);
    }

    /* ============================================================
       PAGINACIÓN: INICIALIZAR UNA TABLA
    ============================================================ */

    function inicializarPaginacionTabla(contenedor) {
        const identificador = contenedor.getAttribute('data-pagination');

        if (!identificador) {
            return;
        }

        const tabla = document.querySelector(
            'table[data-paginated-table="' + identificador + '"]'
        );

        if (!tabla || !tabla.tBodies.length) {
            return;
        }

        const cuerpo = tabla.tBodies[0];
        const filas = Array.from(cuerpo.rows);

        const selectorTamano = document.querySelector(
            '[data-page-size="' + identificador + '"]'
        );

        const resumen = contenedor.querySelector(
            '[data-pagination-summary]'
        );

        const controles = contenedor.querySelector(
            '[data-page-controls]'
        );

        const estado = {
            identificador: identificador,
            tabla: tabla,
            filas: filas,
            pagina: 1,
            tamanoPagina: 10,
            selectorTamano: selectorTamano,
            resumen: resumen,
            controles: controles
        };

        if (selectorTamano) {
            const tamanoInicial = parseInt(selectorTamano.value, 10);

            if ([10, 25, 50].includes(tamanoInicial)) {
                estado.tamanoPagina = tamanoInicial;
            }

            /*
             * El listener se instala una sola vez en cada selector.
             * Al reemplazar el panel, el nuevo selector obtiene su
             * propio listener y la tabla de CV generales no se altera.
             */
            if (selectorTamano.dataset.paginationListener !== 'true') {
                selectorTamano.dataset.paginationListener = 'true';

                selectorTamano.addEventListener('change', function () {
                    const nuevoTamano = parseInt(
                        selectorTamano.value,
                        10
                    );

                    if (![10, 25, 50].includes(nuevoTamano)) {
                        selectorTamano.value = String(estado.tamanoPagina);
                        return;
                    }

                    estado.tamanoPagina = nuevoTamano;
                    estado.pagina = 1;

                    mostrarPagina(estado);
                });
            }
        }

        mostrarPagina(estado);
    }

    function inicializarPaginacionTablaPorId(identificador) {
        const contenedores = document.querySelectorAll(
            '[data-pagination="' + identificador + '"]'
        );

        contenedores.forEach(function (contenedor) {
            inicializarPaginacionTabla(contenedor);
        });
    }

    function inicializarPaginacion() {
        document.querySelectorAll('[data-pagination]').forEach(
            function (contenedor) {
                inicializarPaginacionTabla(contenedor);
            }
        );
    }

    /* ============================================================
       INICIALIZACIÓN
    ============================================================ */

    function inicializarListado() {
        configurarEventosDelegados();
        actualizarEstadoFiltros();
        posicionarListado();
        inicializarPaginacion();
    }

    /* ============================================================
       EXPONER FUNCIONES GLOBALES
       Compatibilidad con botones y otros scripts del módulo.
    ============================================================ */

    window.filtrarVacante = filtrarVacante;
    window.filtrarEstado = filtrarEstado;
    window.buscarPostulaciones = buscarPostulaciones;
    window.limpiarFiltros = limpiarFiltros;
    window.establecerFiltroVacante = establecerFiltroVacante;
    window.obtenerFiltrosActuales = obtenerFiltrosActuales;
    window.existenFiltrosActivos = existenFiltrosActivos;
    window.aplicarFiltrosAjax = aplicarFiltrosAjax;

    /* ============================================================
       ARRANQUE
    ============================================================ */

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            inicializarListado
        );
    } else {
        inicializarListado();
    }

})(window, document);

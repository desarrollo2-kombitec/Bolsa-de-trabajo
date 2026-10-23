// ==========================
// Formulario de Vacantes
// ==========================


// ==========================
// Estado de la imagen
// ==========================

let imagenSeleccionada = null;
let imagenExistente = '';
let eliminarImagenExistente = false;


// ==========================
// Elementos de imagen
// ==========================

const campoSoloImagen =
    document.getElementById('campoSoloImagen');

const campoImagen =
    document.getElementById('campoImagen');

const imagenDropzone =
    document.getElementById('imagenDropzone');

const btnSeleccionarImagen =
    document.getElementById('btnSeleccionarImagen');

const btnQuitarImagen =
    document.getElementById('btnQuitarImagen');

const imagenPreviewContainer =
    document.getElementById('imagenPreviewContainer');

const campoImagenPreview =
    document.getElementById('campoImagenPreview');

const campoImagenNombre =
    document.getElementById('campoImagenNombre');

const requiredImagen =
    document.getElementById('requiredImagen');


// ==========================
// Nuevos campos de vacante
// ==========================

const campoDescripcion =
    document.getElementById('campoDescripcion');

const campoUbicacion =
    document.getElementById('campoUbicacion');

const campoTipoJornada =
    document.getElementById('campoTipoJornada');

const campoModalidad =
    document.getElementById('campoModalidad');


// ==========================
// Abrir modal nueva vacante
// ==========================

function abrirModalNueva() {

    modoFormulario = 'crear';

    tituloModal.textContent =
        'Nueva vacante';

    formVacante.reset();

    campoId.value = '';


    // ==========================
    // Limpiar descripción
    // ==========================

    if (campoDescripcion) {

        campoDescripcion.value = '';

    }


    // ==========================
    // Ubicación predeterminada
    // ==========================

    if (campoUbicacion) {

        campoUbicacion.value =
            'San Luis Potosí, S.L.P.';

    }


    // ==========================
    // Limpiar tipo de jornada
    // ==========================

    if (campoTipoJornada) {

        campoTipoJornada.value = '';

    }


    // ==========================
    // Limpiar modalidad
    // ==========================

    if (campoModalidad) {

        campoModalidad.value = '';

    }


    // ==========================
    // Los campos contenteditable
    // no se limpian con form.reset()
    // ==========================

    campoOferta.innerHTML = '';

    campoRequisitos.innerHTML = '';

    campoResponsabilidades.innerHTML = '';


    // ==========================
    // Estado inicial
    // ==========================

    campoActivo.checked = true;


    // ==========================
    // Reiniciar estado de imagen
    // ==========================

    imagenSeleccionada = null;

    imagenExistente = '';

    eliminarImagenExistente = false;

    limpiarVistaImagen();


    // ==========================
    // Reiniciar Solo Imagen
    // ==========================

    campoSoloImagen.checked = false;

    actualizarEstadoSoloImagen();


    // ==========================
    // Mostrar modal
    // ==========================

    modalVacante.classList.add('active');

    modalVacante.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.style.overflow = 'hidden';


    // ==========================
    // Enfocar título
    // ==========================

    setTimeout(function() {

        campoTitulo.focus();

    }, 150);
}


// ==========================
// Abrir modal editar
// ==========================

function abrirModalEditar(vacante) {

    modoFormulario = 'editar';

    tituloModal.textContent =
        'Editar vacante';


    // ==========================
    // Datos básicos
    // ==========================

    campoId.value =
        vacante.id || '';

    campoTitulo.value =
        vacante.titulo || '';


    // ==========================
    // Descripción
    // ==========================

    if (campoDescripcion) {

        campoDescripcion.value =
            vacante.descripcion || '';

    }


    // ==========================
    // Ubicación
    // ==========================

    if (campoUbicacion) {

        campoUbicacion.value =
            vacante.ubicacion || '';

    }


    // ==========================
    // Tipo de jornada
    // ==========================

    if (campoTipoJornada) {

        campoTipoJornada.value =
            vacante.tipo_jornada || '';

    }


    // ==========================
    // Modalidad
    // ==========================

    if (campoModalidad) {

        campoModalidad.value =
            vacante.modalidad || '';

    }


    // ==========================
    // Contenido enriquecido
    // ==========================
    // Se utiliza innerHTML para
    // conservar formato HTML.

    campoOferta.innerHTML =
        vacante.lo_que_se_ofrece || '';

    campoRequisitos.innerHTML =
        vacante.requisitos || '';

    campoResponsabilidades.innerHTML =
        vacante.responsabilidades || '';


    // ==========================
    // Fecha
    // ==========================
    // El campo permanece oculto
    // visualmente, pero continúa
    // cargándose y enviándose.

    campoFechaCierre.value =
        fechaParaInput(
            vacante.fecha_cierre
        );


    // ==========================
    // Estado
    // ==========================

    campoActivo.checked =
        Number(vacante.activo) === 1;


    // ==========================
    // Estado de imagen
    // ==========================

    imagenSeleccionada = null;

    imagenExistente =
        vacante.url_imagen || '';

    eliminarImagenExistente = false;


    // ==========================
    // Mostrar imagen existente
    // ==========================

    if (imagenExistente) {

        mostrarVistaImagenExistente(
            imagenExistente
        );

    } else {

        limpiarVistaImagen();

    }


    // ==========================
    // Estado Solo Imagen
    // ==========================

    campoSoloImagen.checked =
        Number(vacante.solo_imagen) === 1;

    actualizarEstadoSoloImagen();


    // ==========================
    // Mostrar modal
    // ==========================

    modalVacante.classList.add('active');

    modalVacante.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.style.overflow = 'hidden';


    // ==========================
    // Enfocar título
    // ==========================

    setTimeout(function() {

        campoTitulo.focus();

    }, 150);
}


// ==========================
// Cerrar modal
// ==========================

function cerrarModal() {

    modalVacante.classList.remove(
        'active'
    );

    modalVacante.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.style.overflow = '';


    // ==========================
    // Reiniciar formulario
    // ==========================

    formVacante.reset();

    campoId.value = '';


    // ==========================
    // Limpiar editores
    // ==========================

    campoOferta.innerHTML = '';

    campoRequisitos.innerHTML = '';

    campoResponsabilidades.innerHTML = '';


    // ==========================
    // Limpiar nuevos campos
    // ==========================

    if (campoDescripcion) {

        campoDescripcion.value = '';

    }

    if (campoUbicacion) {

        campoUbicacion.value = '';

    }

    if (campoTipoJornada) {

        campoTipoJornada.value = '';

    }

    if (campoModalidad) {

        campoModalidad.value = '';

    }


    // ==========================
    // Reiniciar imagen
    // ==========================

    imagenSeleccionada = null;

    imagenExistente = '';

    eliminarImagenExistente = false;

    limpiarVistaImagen();


    // ==========================
    // Reiniciar Solo Imagen
    // ==========================

    campoSoloImagen.checked = false;

    actualizarEstadoSoloImagen();
}


// ==========================
// Estado del modo Solo Imagen
// ==========================

function actualizarEstadoSoloImagen() {

    const soloImagen =
        campoSoloImagen.checked;


    const editores = [
        campoOferta,
        campoRequisitos,
        campoResponsabilidades
    ];


    // ==========================
    // Habilitar / deshabilitar
    // editores
    // ==========================

    editores.forEach(function(editor) {

        if (!editor) {

            return;

        }


        editor.contentEditable =
            soloImagen
                ? 'false'
                : 'true';


        const contenedor =
            editor.closest(
                '.patch-editor'
            );


        if (contenedor) {

            contenedor.classList.toggle(
                'disabled',
                soloImagen
            );


            const botones =
                contenedor.querySelectorAll(
                    '.patch-editor-btn'
                );


            botones.forEach(function(boton) {

                boton.disabled =
                    soloImagen;

            });

        }

    });


    // ==========================
    // Indicadores de obligatoriedad
    // ==========================

    const requiredOferta =
        document.getElementById(
            'requiredOferta'
        );

    const requiredRequisitos =
        document.getElementById(
            'requiredRequisitos'
        );

    const requiredResponsabilidades =
        document.getElementById(
            'requiredResponsabilidades'
        );


    if (requiredOferta) {

        requiredOferta.style.display =
            soloImagen
                ? 'none'
                : 'inline';

    }


    if (requiredRequisitos) {

        requiredRequisitos.style.display =
            soloImagen
                ? 'none'
                : 'inline';

    }


    if (requiredResponsabilidades) {

        requiredResponsabilidades.style.display =
            soloImagen
                ? 'none'
                : 'inline';

    }


    // ==========================
    // Imagen obligatoria
    // ==========================

    if (requiredImagen) {

        requiredImagen.style.display =
            soloImagen
                ? 'inline'
                : 'none';

    }

}


// ==========================
// Mostrar vista previa
// imagen nueva
// ==========================

function mostrarVistaImagenNueva(file) {

    if (!file) {

        return;

    }


    imagenSeleccionada = file;

    eliminarImagenExistente = false;


    // ==========================
    // Crear URL temporal
    // ==========================

    const url =
        URL.createObjectURL(file);


    // ==========================
    // Liberar URL anterior
    // si existía una vista previa
    // generada previamente.
    // ==========================

    const urlAnterior =
        campoImagenPreview.dataset
            .previewUrl || '';


    if (urlAnterior) {

        try {

            URL.revokeObjectURL(
                urlAnterior
            );

        } catch (error) {

            console.warn(
                'No fue posible liberar la vista previa anterior.',
                error
            );

        }

    }


    campoImagenPreview.dataset.previewUrl =
        url;


    campoImagenPreview.src =
        url;

    campoImagenPreview.alt =
        'Vista previa de la imagen seleccionada';


    campoImagenNombre.textContent =
        file.name;


    imagenPreviewContainer.hidden =
        false;


    imagenDropzone.style.display =
        'none';

}


// ==========================
// Mostrar imagen existente
// ==========================
function mostrarVistaImagenExistente(urlImagen) {
    if (!urlImagen || !campoImagenPreview) {
        limpiarVistaImagen();
        return;
    }

    // Obtener únicamente el nombre del archivo.
    // Funciona con URL antiguas y rutas relativas.
    let nombreArchivo = '';

    try {
        const urlLimpia = String(urlImagen).trim();
        const url = new URL(urlLimpia, window.location.origin);

        nombreArchivo = url.pathname.split('/').pop();
    } catch (error) {
        nombreArchivo = String(urlImagen)
            .split(/[\\/]/)
            .pop()
            .split('?')[0];
    }

    if (!nombreArchivo) {
        limpiarVistaImagen();
        return;
    }

    // Ruta pública correcta de la aplicación en IIS.
    const urlFinal =
        '/BolsaTrabajo/backend/modulo/Patch_BolsaTrabajo/uploads/img_vacante/' +
        encodeURIComponent(nombreArchivo);

    // Liberar una vista previa temporal anterior, si existe.
    const urlAnterior =
        campoImagenPreview.dataset.previewUrl || '';

    if (urlAnterior) {
        URL.revokeObjectURL(urlAnterior);
    }

    campoImagenPreview.dataset.previewUrl = '';
    campoImagenPreview.onerror = function () {
        console.error(
            'No se pudo cargar la imagen existente:',
            this.src,
            'Verifica los permisos de IIS y que el archivo exista.'
        );
    };

    campoImagenPreview.onload = function () {
        console.log('Imagen existente cargada correctamente:', this.src);
    };

    campoImagenPreview.src = urlFinal;
    campoImagenPreview.alt = 'Imagen actual de la vacante';

    if (campoImagenNombre) {
        campoImagenNombre.textContent = nombreArchivo;
    }

    if (imagenPreviewContainer) {
        imagenPreviewContainer.hidden = false;
    }

    if (imagenDropzone) {
        imagenDropzone.style.display = 'none';
    }
}


// ==========================
// Limpiar imagen
// ==========================

function limpiarVistaImagen() {

    // ==========================
    // Liberar URL temporal
    // ==========================

    const urlAnterior =
        campoImagenPreview.dataset
            .previewUrl || '';


    if (urlAnterior) {

        try {

            URL.revokeObjectURL(
                urlAnterior
            );

        } catch (error) {

            console.warn(
                'No fue posible liberar la vista previa anterior.',
                error
            );

        }

    }


    campoImagenPreview.dataset.previewUrl =
        '';


    campoImagenPreview.src =
        '';

    campoImagenPreview.alt =
        '';


    campoImagenNombre.textContent =
        '';


    imagenPreviewContainer.hidden =
        true;


    imagenDropzone.style.display =
        '';


    if (campoImagen) {

        campoImagen.value =
            '';

    }


    if (imagenDropzone) {

        imagenDropzone.classList.remove(
            'dragover'
        );

    }

}


// ==========================
// Validar imagen
// ==========================

function validarImagen(file) {

    if (!file) {

        return false;

    }


    const tiposPermitidos = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];


    if (
        !file.type ||
        !tiposPermitidos.includes(
            file.type
        )
    ) {

        mostrarToast(
            'La imagen debe estar en formato JPG, PNG o WEBP.',
            true
        );

        return false;

    }


    return true;

}


// ==========================
// Seleccionar imagen
// ==========================

function seleccionarImagen(file) {

    if (!file) {

        return;

    }


    // ==========================
    // Validar tipo
    // ==========================

    if (!validarImagen(file)) {

        if (campoImagen) {

            campoImagen.value =
                '';

        }

        return;

    }


    // ==========================
    // Solo una imagen
    // ==========================

    imagenSeleccionada =
        file;


    // ==========================
    // Al seleccionar una imagen
    // nueva, la imagen anterior
    // deja de marcarse para eliminar.
    // editar.php se encargará de
    // reemplazarla correctamente.
    // ==========================

    eliminarImagenExistente =
        false;


    mostrarVistaImagenNueva(
        file
    );

}


// ==========================
// Botón seleccionar imagen
// ==========================

if (btnSeleccionarImagen) {

    btnSeleccionarImagen.addEventListener(
        'click',
        function(e) {

            e.preventDefault();

            campoImagen.click();

        }
    );

}


// ==========================
// Input de imagen
// ==========================

if (campoImagen) {

    campoImagen.addEventListener(
        'change',
        function() {

            if (
                this.files &&
                this.files.length > 0
            ) {

                seleccionarImagen(
                    this.files[0]
                );

            }

        }
    );

}


// ==========================
// Arrastrar imagen
// ==========================

if (imagenDropzone) {

    imagenDropzone.addEventListener(
        'dragover',
        function(e) {

            e.preventDefault();

            e.stopPropagation();


            imagenDropzone.classList.add(
                'dragover'
            );

        }
    );


    imagenDropzone.addEventListener(
        'dragleave',
        function(e) {

            e.preventDefault();

            e.stopPropagation();


            imagenDropzone.classList.remove(
                'dragover'
            );

        }
    );


    imagenDropzone.addEventListener(
        'drop',
        function(e) {

            e.preventDefault();

            e.stopPropagation();


            imagenDropzone.classList.remove(
                'dragover'
            );


            const archivos =
                e.dataTransfer.files;


            if (
                !archivos ||
                archivos.length === 0
            ) {

                return;

            }


            // ==========================
            // Solamente tomamos el
            // primer archivo.
            // ==========================

            seleccionarImagen(
                archivos[0]
            );

        }
    );

}


// ==========================
// Quitar imagen
// ==========================

if (btnQuitarImagen) {

    btnQuitarImagen.addEventListener(
        'click',
        function(e) {

            e.preventDefault();


            // ==========================
            // Si había una imagen guardada
            // en BD, marcarla para eliminar.
            // ==========================

            if (imagenExistente) {

                eliminarImagenExistente =
                    true;

            }


            // ==========================
            // La nueva imagen también
            // queda eliminada de la selección.
            // ==========================

            imagenSeleccionada =
                null;


            limpiarVistaImagen();

        }
    );

}


// ==========================
// Abrir modal nueva vacante
// ==========================

if (btnNuevaVacante) {

    btnNuevaVacante.addEventListener(
        'click',
        function(e) {

            e.preventDefault();

            abrirModalNueva();

        }
    );

}


// ==========================
// Cerrar modal
// ==========================

if (btnCerrarModal) {

    btnCerrarModal.addEventListener(
        'click',
        cerrarModal
    );

}


if (btnCancelarModal) {

    btnCancelarModal.addEventListener(
        'click',
        cerrarModal
    );

}


// ==========================
// Cerrar al hacer click fuera
// ==========================

if (modalVacante) {

    modalVacante.addEventListener(
        'click',
        function(e) {

            if (
                e.target === modalVacante
            ) {

                cerrarModal();

            }

        }
    );

}


// ==========================
// Cerrar con Escape
// ==========================

document.addEventListener(
    'keydown',
    function(e) {

        if (
            e.key === 'Escape' &&
            modalVacante &&
            modalVacante.classList.contains(
                'active'
            )
        ) {

            cerrarModal();

        }

    }
);


// ==========================
// Cambio de Solo Imagen
// ==========================

if (campoSoloImagen) {

    campoSoloImagen.addEventListener(
        'change',
        function() {

            actualizarEstadoSoloImagen();

        }
    );

}



 // ==========================
 // Envío del formulario
 // ==========================

formVacante.addEventListener(
    'submit',
    async function(e) {
        e.preventDefault();

        // ==========================
        // Estado actual
        // ==========================

        const soloImagen = campoSoloImagen.checked;
        const titulo = campoTitulo.value.trim();

        // ==========================
        // Nuevos campos
        // ==========================

        const descripcion = campoDescripcion ? campoDescripcion.value.trim() : '';
        const ubicacion = campoUbicacion ? campoUbicacion.value.trim() : '';
        const tipoJornada = campoTipoJornada ? campoTipoJornada.value.trim() : '';
        const modalidad = campoModalidad ? campoModalidad.value.trim() : '';

        // ==========================
        // Obtener HTML de editores
        // Se conserva el formato:
        // negritas, cursivas y listas.
        // ==========================

        const oferta = campoOferta.innerHTML.trim();
        const requisitos = campoRequisitos.innerHTML.trim();
        const responsabilidades = campoResponsabilidades.innerHTML.trim();

        // ==========================
        // Validar título
        // ==========================

        if (!titulo) {
            mostrarToast('Ingresa el título de la vacante.', true);
            campoTitulo.focus();
            return;
        }

        // ==========================
        // Validar ubicación
        // ==========================

        if (!ubicacion) {
            mostrarToast('Ingresa la ubicación de la vacante.', true);
            if (campoUbicacion) campoUbicacion.focus();
            return;
        }

        // ==========================
        // Validar tipo de jornada
        // ==========================

        if (!tipoJornada) {
            mostrarToast('Selecciona el tipo de jornada.', true);
            if (campoTipoJornada) campoTipoJornada.focus();
            return;
        }

        // ==========================
        // Validar modalidad
        // ==========================

        if (!modalidad) {
            mostrarToast('Selecciona la modalidad.', true);
            if (campoModalidad) campoModalidad.focus();
            return;
        }

        // ==========================
        // Validar textos
        // Solamente cuando NO es
        // modo Solo Imagen
        // ==========================

        if (!soloImagen) {
            if (!oferta) {
                mostrarToast('Ingresa lo que se ofrece.', true);
                campoOferta.focus();
                return;
            }

            if (!requisitos) {
                mostrarToast('Ingresa los requisitos.', true);
                campoRequisitos.focus();
                return;
            }

            if (!responsabilidades) {
                mostrarToast('Ingresa las responsabilidades.', true);
                campoResponsabilidades.focus();
                return;
            }
        }

        // ==========================
        // Validar imagen en modo
        // Solo Imagen
        // ==========================

        if (soloImagen && !imagenSeleccionada && !imagenExistente) {
            mostrarToast('Debes seleccionar una imagen para una vacante de solo imagen.', true);
            return;
        }

        // ==========================
        // Validar que si se eliminó
        // la imagen en modo Solo Imagen
        // exista una nueva.
        // ==========================

        if (soloImagen && eliminarImagenExistente && !imagenSeleccionada) {
            mostrarToast('Debes seleccionar una imagen para una vacante de solo imagen.', true);
            return;
        }

        // ==========================
        // Crear FormData
        // ==========================

        const datos = new FormData();

        // ==========================
        // Datos básicos
        // ==========================

        datos.append('titulo', titulo);

        // ==========================
        // Nuevos campos
        // ==========================

        datos.append('descripcion', descripcion);
        datos.append('ubicacion', ubicacion);
        datos.append('tipo_jornada', tipoJornada);
        datos.append('modalidad', modalidad);

        // ==========================
        // Contenido enriquecido
        // ==========================

        datos.append('lo_que_se_ofrece', oferta);
        datos.append('requisitos', requisitos);
        datos.append('responsabilidades', responsabilidades);

        // =====================================================
        // NUEVO: DEPURACIÓN DEL HTML ENVIADO AL SERVIDOR
        // Estos mensajes permiten revisar el contenido HTML
        // de los tres editores para investigar el error 406
        // de ModSecurity cuando se utilizan cursivas o viñetas.
        // No modifican ni eliminan el formato del contenido.
        // =====================================================

        console.log('NUEVO - HTML de oferta:', datos.get('lo_que_se_ofrece'));
        console.log('NUEVO - HTML de requisitos:', datos.get('requisitos'));
        console.log('NUEVO - HTML de responsabilidades:', datos.get('responsabilidades'));

        // ==========================
        // Fecha de cierre
        // El campo está oculto en el
        // modal, pero se conserva su
        // funcionalidad.
        // ==========================

        datos.append('fecha_cierre', campoFechaCierre.value);

        // ==========================
        // Estado
        // ==========================

        datos.append('activo', campoActivo.checked ? '1' : '0');

        // ==========================
        // Modo Solo Imagen
        // ==========================

        datos.append('solo_imagen', soloImagen ? '1' : '0');

        // ==========================
        // Imagen nueva
        // Solamente se envía cuando
        // realmente se seleccionó una
        // imagen nueva.
        // ==========================

        if (imagenSeleccionada) {
            datos.append('imagen', imagenSeleccionada, imagenSeleccionada.name);
        }

        // ==========================
        // Eliminar imagen existente
        // Solamente se envía cuando:
        // 1. Existe una imagen anterior.
        // 2. El usuario la quitó.
        // 3. No se seleccionó una nueva.
        //
        // Si hay una nueva imagen,
        // editar.php se encargará del
        // reemplazo.
        // ==========================

        if (eliminarImagenExistente && imagenExistente && !imagenSeleccionada) {
            datos.append('eliminar_imagen', '1');
        }

        // ==========================
        // Acción
        // ==========================

        let accion = 'crear';

        if (modoFormulario === 'editar') {
            accion = 'editar';
            datos.append('id', campoId.value);
        }

        // ==========================
        // Estado de guardado
        // ==========================

        btnGuardarVacante.disabled = true;

        btnGuardarVacante.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            Guardando...
        `;

        // ==========================
        // Enviar a API
        // ==========================

        try {
            const respuesta = await peticion(accion, datos);

            // ==========================
            // Validar respuesta
            // ==========================

            if (!respuesta || !respuesta.ok) {
                throw new Error(
                    respuesta && respuesta.mensaje
                        ? respuesta.mensaje
                        : 'No fue posible guardar la vacante.'
                );
            }

            // ==========================
            // Cerrar modal
            // ==========================

            cerrarModal();

            // ==========================
            // Recargar listado
            // ==========================

            await cargarVacantes();

            // ==========================
            // Mostrar mensaje
            // ==========================

            mostrarToast(respuesta.mensaje || 'Vacante guardada correctamente.');

        } catch (error) {
            console.error('Error al guardar vacante:', error);

            mostrarToast(
                error.message || 'Ocurrió un error al guardar la vacante.',
                true
            );

        } finally {
            // ==========================
            // Restaurar botón
            // ==========================

            btnGuardarVacante.disabled = false;

            btnGuardarVacante.innerHTML = `
                <i class="fa fa-save"></i>
                Guardar vacante
            `;
        }
    }
);

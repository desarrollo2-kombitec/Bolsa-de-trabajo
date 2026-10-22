<?php
declare(strict_types=1);

// ============================================================
// SEGURIDAD Y CONEXIÓN
// ============================================================

// Verificar la sesión antes de mostrar cualquier contenido.
require_once __DIR__ . "/../../../includes/auth.php";
requerirAdminPagina('../../../login.php');

// Conservar la conexión actual a la base de datos.
require_once __DIR__ . "/../../../includes/config.php";

// Evitar que el navegador conserve el panel en caché.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$title = "Bolsa de Trabajo";
$description = "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta
        name="description"
        content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>"
    >

    <!-- Fuente moderna -->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap"
    >

    <!-- CSS del módulo -->
    <link rel="stylesheet" href="css/Admin_BolsaTrabajo.css">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    
</head>

<body>



<body>



   

<div class="patch-bolsa-wrapper">

    <!-- MENÚ DE USUARIO -->
<details class="patch-user-menu" id="patchUserMenu">
    <summary
        aria-label="Abrir menú de usuario"
        aria-haspopup="true"
    >
        <span class="patch-user-avatar">
            <i class="fa fa-user" aria-hidden="true"></i>
        </span>

        <span class="patch-user-label">Mi cuenta</span>

        <i
            class="fa fa-chevron-down patch-user-arrow"
            aria-hidden="true"
        ></i>
    </summary>

    <div class="patch-user-dropdown">
        <div class="patch-user-info">
            <strong>Sesión administrativa</strong>
            <span>Opciones de tu cuenta</span>
        </div>

        <div class="patch-user-divider"></div>

        <a href="../../../cerrar_sesion.php">
            <i class="fa fa-sign-out-alt" aria-hidden="true"></i>
            <span>Cerrar sesión</span>
        </a>
    </div>
</details>

    <div class="container">

        <!-- ENCABEZADO -->
        <div class="patch-bolsa-header">

            <div>
                <h1>
                    <i class="fa fa-briefcase" aria-hidden="true"></i>
                    Bolsa de Trabajo
                </h1>

                <p>
                    Administra las vacantes disponibles y controla su publicación.
                </p>
            </div>

            <div class="patch-bolsa-header-actions">

                <a
                    href="Admin_Postulaciones.php"
                    class="patch-btn-primary"
                    id="btnMostrarPostulaciones"
                >
                    <i class="fa fa-users" aria-hidden="true"></i>
                    Mostrar Postulaciones
                </a>

                <a
                    href="javascript:;"
                    class="patch-btn-primary"
                    id="btnMostrarArchivadas"
                >
                    <i class="fa fa-archive" aria-hidden="true"></i>
                    Vacantes archivadas
                </a>

                <a
                    href="javascript:;"
                    class="patch-btn-primary"
                    id="btnNuevaVacante"
                >
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    Nueva vacante
                </a>

                

            </div>
        </div>

        <!-- RESUMEN -->
        <div class="patch-summary-grid">

            <div class="patch-summary-card">
                <div class="patch-summary-icon">
                    <i class="fa fa-check-circle" aria-hidden="true"></i>
                </div>

                <div class="patch-summary-content">
                    <span class="patch-summary-number" id="contadorActivas">0</span>
                    <span class="patch-summary-label">Vacantes activas</span>
                </div>
            </div>

            <div class="patch-summary-card">
                <div class="patch-summary-icon">
                    <i class="fa fa-pause-circle" aria-hidden="true"></i>
                </div>

                <div class="patch-summary-content">
                    <span class="patch-summary-number" id="contadorInactivas">0</span>
                    <span class="patch-summary-label">Vacantes inactivas</span>
                </div>
            </div>

            <div class="patch-summary-card">
                <div class="patch-summary-icon">
                    <i class="fa fa-briefcase" aria-hidden="true"></i>
                </div>

                <div class="patch-summary-content">
                    <span class="patch-summary-number" id="contadorTotal">0</span>
                    <span class="patch-summary-label">Total de vacantes</span>
                </div>
            </div>

        </div>

        <!-- FILTROS -->
        <div class="patch-filters">

            <div class="patch-filter-group patch-filter-search">
                <label class="patch-filter-label" for="buscarVacante">
                    Buscar vacante
                </label>

                <i class="fa fa-search" aria-hidden="true"></i>

                <input
                    type="search"
                    id="buscarVacante"
                    class="patch-filter-input"
                    placeholder="Buscar por nombre..."
                    autocomplete="off"
                >
            </div>

            <div class="patch-filter-group">
                <label class="patch-filter-label" for="filtroEstado">
                    Estado
                </label>

                <select id="filtroEstado" class="patch-filter-select">
                    <option value="todas">Todas</option>
                    <option value="activas">Activas</option>
                    <option value="inactivas">Inactivas</option>
                </select>
            </div>

            <div class="patch-filter-group">
                <label class="patch-filter-label" for="ordenVacantes">
                    Ordenar por
                </label>

                <select id="ordenVacantes" class="patch-filter-select">
                    <option value="recientes">Más recientes</option>
                    <option value="antiguas">Más antiguas</option>
                    <option value="az">Título A-Z</option>
                </select>
            </div>

            <div>
                <button
                    type="button"
                    class="patch-btn-clear"
                    id="btnLimpiarFiltros"
                >
                    <i class="fa fa-refresh" aria-hidden="true"></i>
                    Limpiar filtros
                </button>
            </div>

        </div>

        <!-- LISTADO -->
        <h2 class="patch-section-title">Vacantes registradas</h2>

        <div class="patch-vacancies-list" id="listaVacantes">
            <div class="patch-loading">
                <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                Cargando vacantes...
            </div>
        </div>

        <!-- ESTADO VACÍO -->
        <div class="patch-empty-state" id="estadoVacio">
            <i class="fa fa-search" aria-hidden="true"></i>
            <h3>No se encontraron vacantes</h3>
            <p>Intenta cambiar los filtros o registra una nueva vacante.</p>
        </div>

    </div>
</div>

<!-- MODAL NUEVA / EDITAR VACANTE -->
<?php include __DIR__ . '/partials/modal_vacante.php'; ?>

<!-- MODAL ARCHIVADAS -->
<?php include __DIR__ . '/partials/modal_archivadas.php'; ?>

<!-- MODAL CONFIRMAR ARCHIVADAS -->
<?php include __DIR__ . '/partials/modal_confirmar.php'; ?>

<!-- MODAL CONFIRMAR DESARCHIVAR -->
<?php include __DIR__ . '/partials/modal_confirmar_desarchivar.php'; ?>

<!-- TOAST -->
<div class="patch-toast" id="patchToast">
    <i class="fa fa-check-circle" aria-hidden="true"></i>
    <span id="patchToastMensaje"></span>
</div>

<!-- SCRIPTS MODULARES -->
<script src="js/bolsa-trabajo-ui.js?v=20260917-1"></script>
<script src="js/bolsa-trabajo-api.js?v=20260917-1"></script>
<script src="js/bolsa-trabajo-formulario.js?v=20260917-1"></script>
<script src="js/bolsa-trabajo-listado.js?v=20260917-1"></script>
<script src="js/bolsa-trabajo-archivadas.js?v=20260917-1"></script>

<script>
    // Variables globales utilizadas por los módulos existentes.
    let vacantes = [];
    let vacantesArchivadas = [];
    let modoFormulario = 'crear';
    let toastTimeout = null;

    // Cargar vacantes al abrir el panel.
    document.addEventListener('DOMContentLoaded', function () {
        cargarVacantes();
    });

    // Cerrar el menú de usuario al hacer clic fuera de él.
    document.addEventListener('click', function (event) {
        const menu = document.getElementById('patchUserMenu');

        if (menu && !menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });

    // Cerrar el menú con la tecla Escape.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const menu = document.getElementById('patchUserMenu');

            if (menu) {
                menu.removeAttribute('open');
                const boton = menu.querySelector('summary');

                if (boton) {
                    boton.focus();
                }
            }
        }
    });
</script>

</body>
</html>

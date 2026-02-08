<?php
// Configuración y sesión
require_once '../backend/config/config.php';
require_once '../backend/autologin.php';

// Verificar si el usuario está logueado
if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anotaciones - Sistema de Notas</title>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/anotaciones.css">
    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* Ajustes rápidos para masonry layout si no carga el CSS externo inmediatamente */
        .notes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../components/navbar.php'; ?>
    
    <!-- Contenido Principal -->
    <main class="container py-4 pb-5">
        
        <!-- Header Estilo Materias -->
        <div class="anotaciones-header">
            <div class="row align-items-center">
                <div class="col-12 col-md-6">
                    <h1><i class="fas fa-sticky-note"></i> Mis Anotaciones</h1>
                    <p>Gestiona tus notas y apuntes personales</p>
                </div>
                <div class="col-12 col-md-6 mt-3 mt-md-0 d-flex justify-content-md-end">
                    <!-- Barra de Filtros -->
                    <div class="d-flex align-items-center gap-1 filter-bar-custom p-2 rounded-pill shadow-sm" style="max-width: 100%;">
                        <select class="form-select border-0 bg-transparent shadow-none text-truncate" id="filterMateria" style="flex: 1; min-width: 0; cursor: pointer;">
                            <option value="all">Todas</option>
                            <!-- Se llenará con JS -->
                        </select>
                        <div class="vr flex-shrink-0"></div>
                        <input type="date" class="form-control border-0 bg-transparent shadow-none" id="filterDate" style="width: auto; max-width: 140px; cursor: pointer;">
                        <button class="btn btn-filter-clear rounded-circle flex-shrink-0" id="btnClearFilters" title="Limpiar filtros"><i class="fas fa-broom"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Notas -->
        <div class="notes-grid" id="notesGrid">
            <!-- Estado vacío inicial -->
            <div class="col-12 text-center py-5 text-muted w-100" style="grid-column: 1 / -1;">
                <i class="far fa-sticky-note fa-3x mb-3 opacity-50"></i>
                <p>No tienes notas aún. ¡Crea la primera arriba!</p>
            </div>
        </div>

    </main>

    <!-- Botón Flotante (FAB) -->
    <button class="btn btn-primary btn-fab" data-bs-toggle="modal" data-bs-target="#modalNota" title="Crear nueva nota">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Modal Editor Amplio -->
    <div class="modal fade" id="modalNota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen-md-down modal-dialog-centered">
            <div class="modal-content border-0">
                <!-- Header: Barra de Acciones (Tipo App) -->
                <div class="modal-header border-bottom py-2 px-3 align-items-center" style="background-color: var(--secondary-color); min-height: 60px;">
                    <div class="d-flex align-items-center flex-grow-1 gap-2">
                        <button type="button" class="btn btn-link text-muted p-0 me-2 d-md-none" data-bs-dismiss="modal"><i class="fas fa-arrow-left"></i></button>
                        <input type="hidden" id="noteId"> <!-- Campo oculto para ID -->
                        <input type="text" class="form-control border-0 shadow-none bg-transparent fw-bold fs-5 p-0 text-truncate" id="noteTitle" placeholder="Título del documento">
                    </div>
                    <button type="button" class="btn-close d-none d-md-block ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body: Espacio de Trabajo -->
                <div class="modal-body p-0 d-flex" id="modalBodyContent" style="background-color: var(--bg-body);">
                    
                    <!-- Barra Lateral de Herramientas (Izquierda en Escritorio, Abajo en Móvil) -->
                    <div id="tools-sidebar" class="d-flex flex-column">

                        <div class="sidebar-card d-flex flex-column flex-grow-1 position-relative">
                        
                        <!-- Botón Toggle Integrado (Solo Móvil) -->
                        <button id="mobile-toolbar-toggle" class="btn btn-light d-md-none d-flex align-items-center justify-content-center" type="button">
                            <i class="fas fa-chevron-up"></i>
                        </button>

                        <!-- Panel de Acciones -->
                        <div class="p-3 d-flex flex-column gap-3" id="actions-panel">
                            <button type="button" class="btn btn-primary w-100 rounded-pill fw-bold py-2" id="btnAddNote">
                                <i class="fas fa-save me-2"></i> Guardar Nota
                            </button>
                            
                            <div class="d-flex gap-2">
                                <select class="form-select border flex-grow-1" id="noteMateria" style="cursor: pointer;">
                                    <option value="">Sin materia</option>
                                </select>
                            </div>
                        </div>

                        <!-- Contenedor de la Barra de Herramientas Quill -->
                        <div id="toolbar-sticky-container" class="flex-grow-1 overflow-y-auto border-top">
                            <!-- La barra de herramientas de Quill se moverá aquí con JS -->
                        </div>
                        </div>
                    </div>

                    <!-- Contenedor Principal (Toolbar + Editor) -->
                    <div class="d-flex flex-column flex-grow-1" style="min-width: 0; min-height: 0;">

                    <!-- Área de Scroll (Fondo Gris) -->
                    <div class="flex-grow-1 overflow-y-auto" id="editor-scroll-area">
                        <!-- La "Hoja" de Papel -->
                        <div id="editor-page" class="bg-white shadow-sm mx-auto" style="position: relative;">
                            <div id="editor-container"></div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Quill Editor JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <!-- JS Específico -->
    <script src="../assets/js/anotaciones.js"></script>

    <!-- Script para Tooltips en la barra de herramientas -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Observador para detectar cuando Quill genera la barra de herramientas
            const observer = new MutationObserver((mutations, obs) => {
                const toolbar = document.querySelector('.ql-toolbar');
                if (toolbar) {
                    // Definición de tooltips para cada botón
                    const tooltips = {
                        '.ql-bold': 'Negrita',
                        '.ql-italic': 'Cursiva',
                        '.ql-underline': 'Subrayado',
                        '.ql-strike': 'Tachado',
                        '.ql-blockquote': 'Cita',
                        '.ql-code-block': 'Bloque de código',
                        '.ql-list[value="ordered"]': 'Lista numerada',
                        '.ql-list[value="bullet"]': 'Lista con viñetas',
                        '.ql-indent[value="-1"]': 'Disminuir sangría',
                        '.ql-indent[value="+1"]': 'Aumentar sangría',
                        '.ql-link': 'Insertar enlace',
                        '.ql-image': 'Insertar imagen',
                        '.ql-clean': 'Limpiar formato',
                        '.ql-color': 'Color de texto',
                        '.ql-background': 'Color de fondo',
                        '.ql-align': 'Alineación',
                        '.ql-header': 'Estilo de encabezado'
                    };

                    for (const [selector, title] of Object.entries(tooltips)) {
                        toolbar.querySelectorAll(selector).forEach(el => {
                            // Configuración avanzada del tooltip
                            const tooltip = new bootstrap.Tooltip(el, {
                                title: title,
                                placement: 'bottom',
                                trigger: 'hover' // IMPORTANTE: Solo hover, evita que se quede al hacer click (focus)
                            });
                            
                            // 1. Ocultar al hacer click
                            el.addEventListener('click', () => {
                                tooltip.hide();
                            });

                            // 2. PREVENIR que aparezca si el menú desplegable está abierto (clase ql-expanded)
                            el.addEventListener('show.bs.tooltip', function(e) {
                                if (this.classList.contains('ql-expanded')) {
                                    e.preventDefault(); // Cancela la aparición del tooltip
                                }
                            });
                        });
                    }
                    obs.disconnect(); // Detener observación una vez aplicados
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
</body>
</html>
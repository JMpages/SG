<?php
// Configuración y sesión
require_once '../backend/config/config.php';
require_once '../backend/auth/autologin.php';

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
    <link rel="stylesheet" href="../assets/css/materias.css">
    <link rel="stylesheet" href="../assets/css/anotaciones.css">
    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>
    
    <!-- Contenido Principal -->
    <main class="container py-4 pb-5">
        
        <!-- Header Estilo Materias -->
        <div class="anotaciones-header">
            <div class="row align-items-center">
                <div class="col-12 col-md-6 text-start">
                    <h1><i class="fas fa-sticky-note"></i> Mis Anotaciones</h1>
                    <p class="mb-0">Gestiona tus notas y apuntes personales</p>
                </div>
                <div class="col-12 col-md-6 mt-3 mt-md-0">
                    <div class="d-flex justify-content-md-end justify-content-start align-items-center gap-2 gap-sm-3 flex-nowrap">
                        
                        <!-- 1. Filtros (Izquierda/Centro) -->
                        <div class="filter-bar-premium flex-grow-1 flex-md-grow-0">
                            <div class="position-relative d-flex align-items-center flex-grow-1 filter-wrapper">
                                <i class="fas fa-book filter-icon-overlay"></i>
                                <select class="form-select select-filter-custom" id="filterMateria">
                                    <option value="all" style="background-color: #333; color: white;">Todas</option>
                                    <!-- Se llenará con JS -->
                                </select>
                            </div>
                            <div class="vr mx-1 opacity-25"></div>
                            <div class="position-relative d-flex align-items-center flex-grow-1 filter-wrapper">
                                <i class="fas fa-sort-amount-down filter-icon-overlay"></i>
                                <select class="form-select select-filter-custom" id="sortNotes">
                                    <option value="edited_desc" style="background-color: #333; color: white;">Editado (Reciente)</option>
                                    <option value="created_desc" style="background-color: #333; color: white;">Creado (Nuevo)</option>
                                    <option value="created_asc" style="background-color: #333; color: white;">Creado (Antiguo)</option>
                                </select>
                            </div>
                            <button class="btn btn-filter-clear-premium" id="btnClearFilters" title="Limpiar filtros">
                                <i class="fas fa-broom"></i>
                            </button>
                        </div>

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

                    <div class="d-flex align-items-center gap-1 me-2">
                        <!-- Estado de guardado -->
                        <div id="saveStatus" class="d-flex align-items-center me-2"></div>

                        <button type="button" class="btn btn-sm text-muted" id="btnUndo" title="Deshacer (Ctrl+Z)"><i class="fas fa-undo"></i></button>
                        <button type="button" class="btn btn-sm text-muted" id="btnRedo" title="Rehacer (Ctrl+Y)"><i class="fas fa-redo"></i></button>
                    </div>

                    <button type="button" class="btn-close d-none d-md-block ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body: Espacio de Trabajo -->
                <div class="modal-body p-0 d-flex flex-column flex-md-row" id="modalBodyContent" style="background-color: var(--bg-body);">
                    
                    <!-- Barra Lateral Estilo Canva (Nav + Paneles) -->
                    <div id="tools-sidebar" class="d-flex flex-column">
                        <div class="d-flex h-100 flex-column flex-md-row">
                            
                            <!-- 1. Menú de Iconos (Izquierda) -->
                            <div id="sidebar-nav" class="d-flex flex-row flex-md-column align-items-center py-0 py-md-3 gap-0 gap-md-3 z-1">
                                <button class="btn btn-nav-icon active" data-tab="estilo" title="Estilo">
                                    <i class="fas fa-font"></i>
                                    <span class="d-none d-md-block small mt-1">Estilo</span>
                                </button>
                                <button class="btn btn-nav-icon" data-tab="draw" title="Dibujo">
                                    <i class="fas fa-pen-fancy"></i>
                                    <span class="d-none d-md-block small mt-1">Dibujo</span>
                                </button>

                                <!-- Acciones MÓVIL (Materia y PDF) - Solo visibles en d-md-none -->
                                <div class="btn-nav-icon mt-md-auto pt-md-3 border-top-md position-relative d-md-none">
                                    <select class="form-select form-select-sm border-0 bg-transparent text-muted p-0 text-center w-100 h-100" id="noteMateriaMobile" style="cursor: pointer;" title="Materia">
                                        <option value="">&#xf02d;</option>
                                    </select>
                                </div>
                                
                                <button type="button" class="btn btn-nav-icon text-danger d-md-none" id="btnDownloadPdfMobile" title="Descargar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            </div>

                            <!-- 2. Contenido de Paneles (Derecha del menú) -->
                            <div id="sidebar-content" class="flex-grow-1 bg-light border-end position-relative d-flex flex-column">
                                
                                <!-- Botón Toggle Móvil -->
                                <button id="mobile-toolbar-toggle" class="btn btn-light d-md-none position-absolute top-0 end-0 m-2 z-3" type="button">
                                    <i class="fas fa-chevron-up"></i>
                                </button>

                                <!-- PANEL EDICIÓN (Inicio e Insertar comparten este contenedor para Quill) -->
                                <div id="panel-edicion" class="sidebar-panel h-100 d-flex flex-column">
                                    
                                    <!-- Acciones ESCRITORIO (Materia y PDF) - Restaurado -->
                                    <div class="p-3 d-none d-md-flex align-items-center gap-2 border-bottom bg-light" id="actions-panel">
                                        <select class="form-select form-select-sm border w-100" id="noteMateria" style="cursor: pointer;">
                                            <option value="">Sin materia</option>
                                        </select>
                                        <button class="btn btn-outline-danger" id="btnDownloadPdf" title="Descargar PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                    </div>

                                    <!-- Toolbar Quill Personalizado (Definido manualmente para separar pestañas) -->
                                    <div id="toolbar-sticky-container" class="flex-grow-1 overflow-y-auto bg-light">
                                        <div id="custom-toolbar">
                                            
                                            <!-- SECCIÓN INICIO (Formato de Texto) -->
                                            <div id="toolbar-estilo" class="toolbar-section p-0">
                                                <div class="d-flex flex-wrap gap-1 align-items-center justify-content-start w-100">
                                                    
                                                    <!-- === VISTA ESCRITORIO (Original) === -->
                                                    <span class="ql-formats me-1 d-none d-md-flex align-items-center">
                                                        <select class="ql-font" title="Fuente" style="width: 130px;"></select>
                                                        <select class="ql-size" title="Tamaño de fuente">
                                                            <option value="10px">10</option><option value="12px" selected>12</option><option value="14px">14</option><option value="16px">16</option><option value="18px">18</option><option value="20px">20</option><option value="24px">24</option><option value="32px">32</option>
                                                        </select>
                                                    </span>
                                                    <span class="ql-formats d-none d-md-flex gap-1 align-items-center me-1">
                                                        <button class="ql-bold" title="Negrita"></button>
                                                        <button class="ql-italic" title="Cursiva"></button>
                                                        <button class="ql-underline" title="Subrayado"></button>
                                                        <select class="ql-color" title="Color de texto"></select>
                                                    </span>
                                                    <span class="ql-formats d-none d-md-flex gap-1 align-items-center me-1">
                                                        <select class="ql-align" title="Alineación"></select>
                                                        <select class="ql-list" title="Listas">
                                                            <option value="ordered"></option>
                                                            <option value="bullet"></option>
                                                            <option selected></option>
                                                        </select>
                                                    </span>
                                                    <span class="ql-formats d-none d-md-flex gap-1 align-items-center flex-wrap">
                                                        <select class="ql-background" title="Color de fondo"></select>
                                                        <button class="ql-indent" value="-1" title="Disminuir sangría"></button>
                                                        <button class="ql-indent" value="+1" title="Aumentar sangría"></button>
                                                        <button class="ql-strike" title="Tachado"></button>
                                                        <button class="ql-clean" title="Limpiar formato"></button>
                                                    </span>

                                                    <!-- === VISTA MÓVIL (Independiente) === -->
                                                    <!-- Fila 1: Prioridad (Siempre visible) -->
                                                    <span class="ql-formats d-flex d-md-none gap-1 align-items-center flex-wrap justify-content-center w-100">
                                                        <select class="ql-size" title="Tamaño">
                                                            <option value="10px">10</option><option value="12px" selected>12</option><option value="14px">14</option><option value="16px">16</option><option value="18px">18</option><option value="20px">20</option><option value="24px">24</option><option value="32px">32</option>
                                                        </select>
                                                        <button class="ql-bold" title="Negrita"></button>
                                                        <button class="ql-italic" title="Cursiva"></button>
                                                        <button class="ql-underline" title="Subrayado"></button>
                                                        <select class="ql-color" title="Color"></select>
                                                    </span>
                                                    
                                                    <!-- Fila 2: Expandido (Visible al desplegar) -->
                                                    <div class="ql-formats d-flex d-md-none flex-column gap-1 align-items-center w-100 mobile-expanded-group">
                                                        <!-- Sub-fila 1: Fuente + Estilos extra -->
                                                        <div class="d-flex gap-1 justify-content-center w-100">
                                                            <select class="ql-font" title="Fuente" style="flex-grow: 1; max-width: 200px;"></select>
                                                            <button class="ql-strike" title="Tachado"></button>
                                                            <select class="ql-background" title="Fondo"></select>
                                                        </div>
                                                        <!-- Sub-fila 2: Párrafo y Acciones -->
                                                        <div class="d-flex gap-1 justify-content-center w-100">
                                                            <select class="ql-align" title="Alineación"></select>
                                                            <select class="ql-list" title="Listas">
                                                                <option value="ordered"></option>
                                                                <option value="bullet"></option>
                                                                <option selected></option>
                                                            </select>
                                                            <button class="ql-indent" value="-1" title="Disminuir sangría"></button>
                                                            <button class="ql-indent" value="+1" title="Aumentar sangría"></button>
                                                            <button class="ql-clean" title="Limpiar"></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PANEL DIBUJO -->
                                <div id="panel-draw" class="sidebar-panel h-100 d-none flex-column">
                                    
                                    <!-- VISTA ESCRITORIO -->
                                    <div class="d-none d-md-flex flex-column gap-3 p-3 h-100">
                                        <div>
                                            <label class="small text-muted fw-bold mb-2 d-block">Herramienta</label>
                                            <div class="btn-group w-100" role="group">
                                                <button type="button" class="btn btn-outline-secondary active btn-tool-pen" title="Lápiz">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-tool-eraser" title="Borrador">
                                                    <i class="fas fa-eraser"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="small text-muted fw-bold mb-0">Color del trazo</label>
                                            <div class="position-relative drawing-color-picker-container">
                                                <!-- Trigger: Lo que se ve siempre -->
                                                <div class="draw-color-trigger d-flex align-items-center justify-content-center p-1 border rounded" style="cursor: pointer; width: 50px; height: 34px;" title="Color del trazo">
                                                    <div class="current-draw-color" style="width: 20px; height: 20px; background-color: #000000; border-radius: 3px; border: 1px solid rgba(0,0,0,0.1);"></div>
                                                    <i class="fas fa-chevron-down ms-1 small text-muted" style="font-size: 0.7rem;"></i>
                                                </div>
                                                
                                                <!-- Dropdown: Lo que se despliega -->
                                                <div class="drawing-palette-dropdown position-absolute top-100 end-0 mt-1 p-2 border rounded shadow-sm d-none" style="z-index: 2000; width: 190px; background-color: var(--bg-light);">
                                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                                        <div class="draw-color-option" data-color="#000000" style="background-color: #000000;" title="Negro"></div>
                                                        <div class="draw-color-option" data-color="#e60000" style="background-color: #e60000;" title="Rojo"></div>
                                                        <div class="draw-color-option" data-color="#ff9900" style="background-color: #ff9900;" title="Naranja"></div>
                                                        <div class="draw-color-option" data-color="#ffff00" style="background-color: #ffff00;" title="Amarillo"></div>
                                                        <div class="draw-color-option" data-color="#008a00" style="background-color: #008a00;" title="Verde"></div>
                                                        <div class="draw-color-option" data-color="#0066cc" style="background-color: #0066cc;" title="Azul"></div>
                                                        <div class="draw-color-option" data-color="#8833ff" style="background-color: #8833ff;" title="Púrpura"></div>
                                                    </div>
                                                    <div class="border-top pt-2">
                                                        <label class="small text-muted mb-1 d-block">Personalizado:</label>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <input type="color" class="form-control form-control-color form-control-sm p-0 border-0 custom-pen-color" value="#000000" title="Elige tu color">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="small text-muted fw-bold mb-2 d-block">Grosor</label>
                                            <input type="range" class="form-range pen-width-input" min="1" max="5" step="0.5" value="2">
                                        </div>

                                        <hr class="my-1">

                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-danger btn-clear-canvas">
                                                <i class="fas fa-trash-alt me-2"></i>Limpiar Lienzo
                                            </button>
                                        </div>
                                        
                                        <div class="alert alert-warning small mt-2 mb-0">
                                            <i class="fas fa-exclamation-triangle me-1"></i> Los dibujos no salen en la exportación a PDF.
                                        </div>
                                    </div>

                                    <!-- VISTA MÓVIL (Barra inferior compacta) -->
                                    <div class="d-flex d-md-none flex-column w-100">
                                        <div class="d-flex align-items-center justify-content-between p-2 gap-2 w-100">
                                        <!-- Color Picker (Abre hacia arriba) -->
                                        <div class="position-relative drawing-color-picker-container">
                                            <div class="draw-color-trigger d-flex align-items-center justify-content-center p-1 border rounded" style="cursor: pointer; width: 40px; height: 36px;" title="Color">
                                                <div class="current-draw-color" style="width: 20px; height: 20px; background-color: #000000; border-radius: 3px; border: 1px solid rgba(0,0,0,0.1);"></div>
                                            </div>
                                            <div class="drawing-palette-dropdown position-absolute bottom-100 start-0 mb-1 p-2 border rounded shadow-sm d-none" style="z-index: 2000; width: 190px; background-color: var(--bg-light);">
                                                <div class="d-flex flex-wrap gap-2 mb-2">
                                                    <div class="draw-color-option" data-color="#000000" style="background-color: #000000;"></div>
                                                    <div class="draw-color-option" data-color="#e60000" style="background-color: #e60000;"></div>
                                                    <div class="draw-color-option" data-color="#ff9900" style="background-color: #ff9900;"></div>
                                                    <div class="draw-color-option" data-color="#ffff00" style="background-color: #ffff00;"></div>
                                                    <div class="draw-color-option" data-color="#008a00" style="background-color: #008a00;"></div>
                                                    <div class="draw-color-option" data-color="#0066cc" style="background-color: #0066cc;"></div>
                                                    <div class="draw-color-option" data-color="#8833ff" style="background-color: #8833ff;"></div>
                                                </div>
                                                <div class="border-top pt-2 d-flex align-items-center gap-2">
                                                    <label class="small text-muted mb-0">Personalizado:</label>
                                                    <input type="color" class="form-control form-control-color form-control-sm p-0 border-0 custom-pen-color" value="#000000">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Herramientas (Pen/Eraser) -->
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary active btn-tool-pen" title="Lápiz">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-tool-eraser" title="Borrador">
                                                <i class="fas fa-eraser"></i>
                                            </button>
                                        </div>

                                        <!-- Slider Grosor -->
                                        <div class="flex-grow-1 px-1 d-flex align-items-center justify-content-center">
                                            <input type="range" class="form-range pen-width-input m-0" min="1" max="5" step="0.5" value="2">
                                        </div>
                                        </div>

                                        <!-- Opciones Expandidas -->
                                        <div class="mobile-expanded-group px-2 pb-2">
                                            <button class="btn btn-outline-danger btn-clear-canvas w-100 mb-2">
                                                <i class="fas fa-trash-alt me-2"></i>Limpiar Lienzo
                                            </button>
                                            <div class="alert alert-warning small mb-0 p-2 text-center">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Los dibujos no salen en la exportación a PDF.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                    <!-- Contenedor Principal (Toolbar + Editor) -->
                    <div class="d-flex flex-column flex-grow-1" style="min-width: 0; min-height: 0;">

                    <!-- Área de Scroll (Fondo Gris) -->
                    <div class="flex-grow-1 overflow-y-auto" id="editor-scroll-area">
                        <!-- La "Hoja" de Papel -->
                        <div id="editor-page" class="bg-white shadow-sm mx-auto position-relative">
                            <div id="editor-container"></div>
                            <!-- Canvas Superpuesto -->
                            <canvas id="drawing-canvas"></canvas>
                            <!-- Cursor Preview para Borrador -->
                            <div id="eraser-cursor-preview"></div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Nota -->
    <div class="modal fade" id="modalEliminarNota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar esta nota?</p>
                    <p class="text-danger small"><strong>Atención:</strong> Esta acción no se puede deshacer.</p>
                    <input type="hidden" id="idNotaEliminar">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminarNota">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast para notificaciones -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2" id="toastIcon"></i>
                <strong class="me-auto" id="toastTitle">Notificación</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage"></div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Quill Editor JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <!-- Signature Pad (Escritura a mano) -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <!-- HTML2PDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <!-- JS Específico -->
    <script src="../assets/js/anotaciones.js?v=1.2"></script>
</body>
</html>
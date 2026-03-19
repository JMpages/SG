<?php
// Configuración y sesión
require_once '../backend/config/config.php';
require_once '../backend/auth/autologin.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materias - Sistema de Notas</title>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/materias.css">
    <style>
        /* Estilo personalizado para el select de ordenamiento */
        .select-sort-custom {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 0.5rem !important;
            padding: 0.4rem 1.5rem 0.4rem 2rem !important;
            font-weight: 500 !important;
            font-size: 0.8rem !important;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.6rem center !important;
            background-size: 10px 8px !important;
            height: 35px;
            line-height: 1.2;
            min-width: 100px;
        }
        
        .select-sort-custom:hover {
            background-color: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
        }

        .select-sort-custom:focus {
            background-color: rgba(255, 255, 255, 0.25) !important;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.15) !important;
            outline: none;
        }

        .sort-icon-overlay {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.85);
            pointer-events: none;
            font-size: 0.75rem;
            z-index: 5;
            display: flex;
            align-items: center;
        }

        /* Truncado de 2 líneas para nombres largos */
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .materia-card-header {
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>
    
    <!-- Contenido Principal -->
    <main class="container py-4 pb-5">
        <!-- Header -->
        <div class="materias-header">
            <div class="row align-items-center">
                <div class="col-12 col-md-6 text-start">
                    <h1><i class="fas fa-book"></i> Mis Materias</h1>
                    <p>Administra tus materias y criterios de evaluación</p>
                </div>
                <div class="col-12 col-md-6 mt-3 mt-md-0">
                    <div class="d-flex justify-content-md-end justify-content-start align-items-center gap-2 gap-sm-3 flex-nowrap">
                        
                        <!-- 1. Toggle de Vistas (Izquierda) -->
                        <div class="btn-group flex-shrink-0" role="group" aria-label="Cambiar vista">
                            <button type="button" class="btn btn-outline-light btn-sm active" id="btnViewGrid" title="Vista Cuadrícula">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button type="button" class="btn btn-outline-light btn-sm" id="btnViewList" title="Vista Lista">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>

                        <!-- 2. Ordenamiento (Centro) -->
                        <div class="flex-grow-1 flex-md-grow-0" style="min-width: 90px; max-width: 140px;">
                             <div class="position-relative d-flex align-items-center">
                                <i class="fas fa-sort sort-icon-overlay" style="left: 0.65rem; font-size: 0.7rem; top: 50%; transform: translateY(-50%); opacity: 0.8;"></i>
                                <select class="form-select select-sort-custom" id="selectSort" 
                                        style="padding-left: 1.6rem !important; padding-right: 1.4rem !important; font-size: 0.75rem !important; height: 35px; text-overflow: ellipsis;">
                                    <option value="newest" style="background-color: #333; color: white;">Recientes</option>
                                    <option value="oldest" style="background-color: #333; color: white;">Antiguas</option>
                                    <option value="alpha-asc" style="background-color: #333; color: white;">A-Z</option>
                                    <option value="alpha-desc" style="background-color: #333; color: white;">Z-A</option>
                                </select>
                             </div>
                        </div>

                        <!-- 3. Agregar (Derecha) -->
                        <button class="btn btn-agregar flex-shrink-0" data-bs-toggle="modal" data-bs-target="#agregarMateriaModal" style="white-space: nowrap; height: 35px; display: flex; align-items: center;">
                            <i class="fas fa-plus me-1"></i><span class="d-none d-sm-inline">Agregar Materia</span><span class="d-inline d-sm-none">Agregar</span>
                        </button>

                    </div>
                </div>
            </div>
        </div>

        <!-- Listado de Materias -->
        <div class="row" id="materiasContainer">
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No hay materias registradas</p>
                    <small>Agrega tu primera materia para comenzar</small>
                </div>
            </div>
        </div>
    </main>

    <!-- Botón flotante Índice -->
    <button class="btn btn-primary btn-indice-flotante" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasIndice" title="Índice de materias">
        <i class="fas fa-list-ul"></i>
    </button>

    <!-- Offcanvas Índice (Menú lateral para móviles) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasIndice" aria-labelledby="offcanvasIndiceLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasIndiceLabel"><i class="fas fa-list"></i> Índice de Materias</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="list-group list-group-flush" id="indiceLista">
                <!-- Lista generada dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Modal Agregar/Editar Materia -->
    <div class="modal fade" id="agregarMateriaModal" tabindex="-1" aria-labelledby="agregarMateriaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarMateriaLabel">
                        <i class="fas fa-plus-circle"></i> Agregar Nueva Materia
                    </h5>
                    <input type="hidden" id="materiaId" value="">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formMateria">
                    <div class="modal-body">
                        <!-- Información básica de la materia -->
                        <div class="row mb-3">
                            <div class="col-12 col-md-8">
                                <label for="nombreMateria" class="form-label">
                                    <span class="text-danger">*</span> Nombre de la Materia
                                </label>
                                <input type="text" class="form-control" id="nombreMateria" placeholder="Ej: Matemáticas" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="estadoMateria" class="form-label">Estado</label>
                                <select class="form-select" id="estadoMateria">
                                    <option value="1" selected>Activa</option>
                                    <option value="0">Inactiva</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcionMateria" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcionMateria" rows="2" placeholder="Detalles sobre la materia"></textarea>
                        </div>

                        <!-- Criterios de Evaluación -->
                        <div class="criterios-dynamica">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list-check me-2"></i>Criterios de Evaluación</h6>
                                <span class="badge border" id="totalPorcentajeBadge" style="background-color: var(--bg-light); color: var(--text-primary);">Total: 0%</span>
                            </div>
                            
                            <!-- Cabecera de columnas (Visible en escritorio) -->
                             <div class="row g-2 mb-2 px-2 d-none d-md-flex text-muted small fw-bold text-uppercase">
                                <div class="col-4">Nombre del Criterio</div>
                                <div class="col-2 text-center">Cant. Eval</div>
                                <div class="col-2 text-center">Valor (%)</div>
                                <div class="col-3 text-center">Nota Máx.</div>
                                <div class="col-1 text-center"></div>
                            </div>

                            <div id="criteriosContainer">
                                <!-- Se llena dinámicamente con JS -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-criterio" id="btnAgregarCriterio">
                                <i class="fas fa-plus"></i> Agregar Criterio
                            </button>
                        </div>

                        <div class="alert alert-info mt-3 mb-0" role="alert">
                            <small><i class="fas fa-info-circle"></i> Solo el nombre de la materia es obligatorio. Los criterios pueden sumarse gradualmente hasta llegar al 100%.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarMateria">
                            <i class="fas fa-save"></i> Guardar Materia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Materia -->
    <div class="modal fade" id="eliminarMateriaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar la materia <strong id="nombreMateriaEliminar"></strong>?</p>
                    <p class="text-danger small"><strong>Atención:</strong> Esta acción no se puede deshacer. Se eliminarán también todos los criterios de evaluación y las notas asociadas a esta materia.</p>
                    <input type="hidden" id="idMateriaEliminar">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Sí, eliminar</button>
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
                <small>Justo ahora</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Mensaje de notificación.
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Agregar nuevo criterio
        document.getElementById('btnAgregarCriterio').addEventListener('click', () => {
            agregarCriterio();
        });

        // Función para agregar criterio dinámicamente
        function agregarCriterio(criterio = null) {
            const container = document.getElementById('criteriosContainer');
            const newCriterio = document.createElement('div');
            newCriterio.classList.add('criterio-row'); // Añadir clase para identificar filas de criterios
            newCriterio.innerHTML = `
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-12 col-md-4">
                        <input type="text" class="form-control criterio-nombre" placeholder="Ej: Parciales" value="${criterio ? escapeHtml(criterio.nombre) : ''}">
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control criterio-cantidad" placeholder="Cant." min="1" value="${criterio ? criterio.cantidad_evaluaciones : '1'}">
                            <span class="input-group-text d-none d-lg-block" title="Cantidad de evaluaciones">#</span>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control criterio-porcentaje" placeholder="Valor" min="0" max="100" step="0.1" value="${criterio ? parseFloat(criterio.porcentaje) : ''}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-3 col-md-3">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control criterio-nota-maxima" placeholder="Máx" min="1" step="0.1" value="${criterio ? parseFloat(criterio.nota_maxima) : '100'}">
                            <span class="input-group-text d-none d-lg-block">Máx</span>
                        </div>
                    </div>
                    <div class="col-1 col-md-1 text-center">
                        <button type="button" class="btn btn-remove-criterio btn-sm text-danger p-0" title="Eliminar criterio">
                            <i class="fas fa-times-circle fs-5"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(newCriterio);
            
            // Agregar evento para eliminar
            newCriterio.querySelector('.btn-remove-criterio').addEventListener('click', function(e) {
                e.preventDefault();
                newCriterio.remove();
                actualizarVisibilidadBotonesEliminar();
                actualizarTotalPorcentaje();
            });
            
            // Evento para actualizar suma al cambiar porcentaje
            newCriterio.querySelector('.criterio-porcentaje').addEventListener('input', actualizarTotalPorcentaje);

            actualizarVisibilidadBotonesEliminar();
            actualizarTotalPorcentaje();
        }

        // Mostrar/ocultar botones de eliminar según cantidad de criterios
        function actualizarVisibilidadBotonesEliminar() {
            const criterios = document.querySelectorAll('.criterio-row');
            criterios.forEach(criterio => {
                const btnEliminar = criterio.querySelector('.btn-remove-criterio');
                btnEliminar.style.display = criterios.length > 1 ? 'block' : 'none';
            });
        }

        // Calcular y mostrar total de porcentajes
        function actualizarTotalPorcentaje() {
            let total = 0;
            document.querySelectorAll('.criterio-porcentaje').forEach(input => {
                const val = parseFloat(input.value);
                if (!isNaN(val)) total += val;
            });
            
            const badge = document.getElementById('totalPorcentajeBadge');
            badge.textContent = `Total: ${total.toFixed(1)}% / 100%`;
            
            if (Math.abs(total - 100) < 0.1) {
                badge.className = 'badge bg-success text-white border';
            } else if (total > 100) {
                badge.className = 'badge bg-danger text-white border';
            } else {
                badge.className = 'badge bg-warning text-dark border';
            }
        }

        // Función para mostrar Toast
        function showToast(message, type = 'info') {
            const toastEl = document.getElementById('liveToast');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');
            
            // Configurar iconos y colores según el tipo
            if (type === 'success') {
                toastIcon.className = 'fas fa-check-circle me-2 text-success';
                toastTitle.textContent = '¡Éxito!';
            } else if (type === 'error') {
                toastIcon.className = 'fas fa-exclamation-circle me-2 text-danger';
                toastTitle.textContent = 'Error';
            } else if (type === 'warning') {
                toastIcon.className = 'fas fa-exclamation-triangle me-2 text-warning';
                toastTitle.textContent = 'Atención';
            } else {
                toastIcon.className = 'fas fa-info-circle me-2 text-primary';
                toastTitle.textContent = 'Información';
            }
            
            toastMessage.textContent = message;
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        // Envío del formulario con AJAX
        document.getElementById('formMateria').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = document.getElementById('btnGuardarMateria');
            const materiaId = document.getElementById('materiaId').value;
            const originalButtonHTML = submitButton.innerHTML;

            // Recopilar datos de criterios
            const criterios = [];
            let formCriteriosValido = true;
            document.querySelectorAll('.criterio-row').forEach(row => {
                const nombre = row.querySelector('.criterio-nombre').value.trim();
                const cantidad = row.querySelector('.criterio-cantidad').value;
                const porcentaje = row.querySelector('.criterio-porcentaje').value;
                
                // Ignorar fila si está vacía (considerando que cantidad tiene valor por defecto 1)
                if (!nombre && !porcentaje && cantidad === '1') {
                    return;
                }

                 if (nombre || cantidad || porcentaje) {
                    if (!nombre || !cantidad || !porcentaje) {
                        formCriteriosValido = false;
                    }
                    criterios.push({
                        nombre: nombre,
                        cantidad: parseInt(cantidad, 10),
                        porcentaje: parseFloat(porcentaje),
                        nota_maxima: parseFloat(row.querySelector('.criterio-nota-maxima').value) || 100
                    });
                }
            });

            if (!formCriteriosValido) {
                showToast('Si inicias un criterio, todos sus campos (nombre, cantidad, porcentaje) son requeridos.', 'warning');
                return;
            }

            // Validar que porcentajes no excedan 100 si hay criterios
            if (criterios.length > 0) {
                const totalPorcentaje = criterios.reduce((sum, c) => sum + (c.porcentaje || 0), 0);
                if (totalPorcentaje > 100.01) {
                    showToast(`La suma de los porcentajes de los criterios no debe exceder el 100%. Actualmente suman ${totalPorcentaje.toFixed(2)}%.`, 'warning');
                    return;
                }
            }

            const data = {
                id: materiaId ? parseInt(materiaId, 10) : null,
                nombre: document.getElementById('nombreMateria').value.trim(),
                activa: document.getElementById('estadoMateria').value,
                descripcion: document.getElementById('descripcionMateria').value.trim(),
                criterios: criterios
            };

            if (!data.nombre) {
                showToast('El nombre de la materia es obligatorio.', 'warning');
                document.getElementById('nombreMateria').focus();
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...`;

            try {
                data.accion = 'guardar';
                const response = await fetch('../backend/materias/materias_controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    showToast(result.message || 'Materia guardada con éxito.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('agregarMateriaModal')).hide();
                    cargarMaterias(); // Recargar la lista
                } else {
                    showToast('Error: ' + (result.message || 'Ocurrió un problema al guardar.'), 'error');
                }

            } catch (error) {
                console.error('Error en la petición:', error);
                showToast('Error de conexión. No se pudo guardar la materia.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonHTML;
            }
        });

        // Variables globales para manejo de vista y datos
        let materiasCache = [];
        let currentView = localStorage.getItem('materiaViewMode') || 'grid';
        let currentSort = localStorage.getItem('materiaSortMode') || 'newest';
        let isSelectionMode = false;
        let selectedMateriaIds = new Set();

        // Inicializar botones de ordenamiento
        function initSortButtons() {
            const selectSort = document.getElementById('selectSort');
            
            // Establecer estado inicial
            selectSort.value = currentSort;

            selectSort.addEventListener('change', (e) => {
                cambiarOrden(e.target.value);
            });
        }

        function cambiarOrden(sortMode) {
            currentSort = sortMode;
            localStorage.setItem('materiaSortMode', sortMode);
            renderMaterias();
        }

        function aplicarOrden(materias) {
            const sorted = [...materias];
            switch (currentSort) {
                case 'alpha-asc':
                    return sorted.sort((a, b) => a.nombre.localeCompare(b.nombre));
                case 'alpha-desc':
                    return sorted.sort((a, b) => b.nombre.localeCompare(a.nombre));
                case 'oldest':
                    return sorted.sort((a, b) => a.id - b.id);
                case 'newest':
                default:
                    return sorted.sort((a, b) => b.id - a.id);
            }
        }

        // Función para escapar HTML y prevenir XSS
        function escapeHtml(text) {
            if (text == null) return '';
            return text
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Inicializar botones de vista
        function initViewButtons() {
            const btnGrid = document.getElementById('btnViewGrid');
            const btnList = document.getElementById('btnViewList');

            // Establecer estado inicial basado en la preferencia guardada
            if (currentView === 'list') {
                btnGrid.classList.remove('active');
                btnList.classList.add('active');
            } else {
                btnGrid.classList.add('active');
                btnList.classList.remove('active');
            }

            // Event Listeners
            btnGrid.addEventListener('click', () => cambiarVista('grid'));
            btnList.addEventListener('click', () => cambiarVista('list'));
        }

        function cambiarVista(vista) {
            currentView = vista;
            localStorage.setItem('materiaViewMode', vista);
            
            // Actualizar botones
            document.getElementById('btnViewGrid').classList.toggle('active', vista === 'grid');
            document.getElementById('btnViewList').classList.toggle('active', vista === 'list');
            
            // Renderizar de nuevo
            renderMaterias();
        }

        // Función para cargar materias desde el backend
        async function cargarMaterias() {
            try {
                const response = await fetch('../backend/materias/materias_controller.php?accion=listar');
                const result = await response.json();

                if (result.status === 'success') {
                    materiasCache = result.data; // Guardar en caché
                    renderMaterias();
                } else {
                    showToast('Error al cargar materias: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error de conexión al cargar materias', 'error');
            }
        }

        // --- FUNCIONALIDAD DE SELECCIÓN MÚLTIPLE ---
        function initSelectionMode() {
            const selectionBarHtml = `
                <div id="selectionBar" class="selection-bar px-4">
                    <div class="d-flex align-items-center gap-3">
                        <button id="btnCancelSelection" class="btn btn-link text-white p-0 fs-5"><i class="fas fa-times"></i></button>
                        <span id="selectionCount" class="fw-bold fs-5">0 seleccionadas</span>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                         <button id="btnDeleteSelected" class="btn btn-light text-danger btn-sm rounded-circle shadow-sm" style="width: 36px; height: 36px;" disabled title="Eliminar"><i class="fas fa-trash"></i></button>
                         <button id="btnSelectAll" class="btn btn-outline-light btn-sm rounded-pill px-3 ms-2">Todo</button>
                    </div>
                </div>`;
            document.body.insertAdjacentHTML('beforeend', selectionBarHtml);

            document.getElementById('btnCancelSelection').addEventListener('click', toggleSelectionMode);
            
            document.getElementById('btnSelectAll').addEventListener('click', () => {
                const allIds = materiasCache.map(m => m.id);
                if (selectedMateriaIds.size === allIds.length) {
                    selectedMateriaIds.clear();
                } else {
                    allIds.forEach(id => selectedMateriaIds.add(id));
                }
                updateSelectionUI();
            });

            document.getElementById('btnDeleteSelected').addEventListener('click', () => {
                if (selectedMateriaIds.size === 0) return;
                // Usar el modal existente para confirmar
                document.getElementById('nombreMateriaEliminar').textContent = `${selectedMateriaIds.size} materias seleccionadas`;
                document.getElementById('idMateriaEliminar').value = ''; // Vacío indica lote
                new bootstrap.Modal(document.getElementById('eliminarMateriaModal')).show();
            });
        }

        function toggleSelectionMode() {
            isSelectionMode = !isSelectionMode;
            const bar = document.getElementById('selectionBar');
            if (isSelectionMode) {
                bar.classList.add('show');
                document.getElementById('materiasContainer').classList.add('selection-active');
            } else {
                bar.classList.remove('show');
                document.getElementById('materiasContainer').classList.remove('selection-active');
                selectedMateriaIds.clear();
            }
            updateSelectionUI();
        }

        window.toggleMateriaSelection = function(id) {
            if (selectedMateriaIds.has(id)) {
                selectedMateriaIds.delete(id);
            } else {
                selectedMateriaIds.add(id);
            }

            if (selectedMateriaIds.size > 0 && !isSelectionMode) {
                isSelectionMode = true;
                document.getElementById('selectionBar').classList.add('show');
                document.getElementById('materiasContainer').classList.add('selection-active');
            } else if (selectedMateriaIds.size === 0 && isSelectionMode) {
                isSelectionMode = false;
                document.getElementById('selectionBar').classList.remove('show');
                document.getElementById('materiasContainer').classList.remove('selection-active');
            }
            updateSelectionUI();
        }

        function updateSelectionUI() {
            const count = selectedMateriaIds.size;
            document.getElementById('selectionCount').textContent = `${count} seleccionada${count !== 1 ? 's' : ''}`;
            document.getElementById('btnDeleteSelected').disabled = count === 0;

            // Actualizar visualmente las tarjetas sin re-renderizar para evitar el parpadeo
            document.querySelectorAll('.materia-card').forEach(card => {
                const id = parseInt(card.dataset.id);
                const checkbox = card.querySelector('.materia-select-btn');
                
                if (!checkbox || isNaN(id)) return;
                
                const icon = checkbox.querySelector('i');

                if (selectedMateriaIds.has(id)) {
                    card.classList.add('selected');
                    checkbox.classList.add('checked');
                    icon.className = 'fas fa-check-circle';
                } else {
                    card.classList.remove('selected');
                    checkbox.classList.remove('checked');
                    icon.className = 'far fa-circle';
                }
            });
        }
        // -------------------------------------------

        // Función para renderizar las materias (separada de la carga)
        function renderMaterias() {
            const container = document.getElementById('materiasContainer');
            const indiceLista = document.getElementById('indiceLista');
            
            if (materiasCache.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No hay materias registradas</p>
                            <small>Agrega tu primera materia para comenzar</small>
                        </div>
                    </div>`;
                indiceLista.innerHTML = '<div class="text-center text-muted p-3">No hay materias</div>';
                return;
            }

            let html = '';
            let indiceHtml = '';

            // Aplicar ordenamiento antes de renderizar
            const materiasOrdenadas = aplicarOrden(materiasCache);

            // Determinar clases según la vista actual
            // col-12 col-md-6 col-lg-4 col-xl-3 para un diseño más equilibrado (máx 4 por fila)
            const colClass = currentView === 'grid' ? 'col-12 col-md-6 col-lg-4 col-xl-3' : 'col-12';
            const cardClass = currentView === 'list' ? 'card-list-view' : '';

            materiasOrdenadas.forEach(materia => {
                const estadoBadge = materia.activa == 1 
                    ? '<span class="materia-card-badge">Activa</span>' 
                    : '<span class="materia-card-badge bg-secondary">Inactiva</span>';
                
                const criteriosCount = materia.criterios ? materia.criterios.length : 0;
                const descripcionCorta = materia.descripcion ? (materia.descripcion.length > 60 ? materia.descripcion.substring(0, 60) + '...' : materia.descripcion) : 'Sin descripción';

                const criteriosHtml = materia.criterios && materia.criterios.length > 0 
                    ? materia.criterios.slice(0, 3).map(c => `<span class="badge border me-1 mb-1" style="font-size: 0.7rem; background-color: var(--bg-light); color: var(--text-secondary);">${escapeHtml(c.nombre)}</span>`).join(' ') 
                    : '<span class="text-muted small">Sin criterios</span>';

                const isSelected = selectedMateriaIds.has(materia.id);
                const checkClass = isSelected ? 'checked' : '';
                const checkIcon = isSelected ? 'fas fa-check-circle' : 'far fa-circle';
                const cardSelectedClass = isSelected ? 'selected' : '';
                
                // Botón de selección HTML
                const selectBtnHtml = `
                    <div class="materia-select-btn ${checkClass}" onclick="event.stopPropagation(); toggleMateriaSelection(${materia.id})">
                        <i class="${checkIcon}"></i>
                    </div>`;

                if (currentView === 'list') {
                    html += `
                        <div class="col-12 mb-2" id="materia-${materia.id}">
                            <div class="materia-card card-list-view shadow-sm d-flex align-items-center ${cardSelectedClass}" data-id="${materia.id}">
                                ${selectBtnHtml}
                                <div class="card-list-sidebar d-flex flex-column justify-content-center px-3">
                                    <h3 class="h6 mb-2 fw-bold" style="color: white;" title="${escapeHtml(materia.nombre)}">${escapeHtml(materia.nombre)}</h3>
                                    <div class="d-flex">${estadoBadge}</div>
                                </div>
                                <div class="card-list-content flex-grow-1 px-4 d-none d-md-block">
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        ${criteriosHtml}
                                    </div>
                                    <div class="text-secondary small">
                                        ${escapeHtml(materia.descripcion || 'Sin descripción')}
                                    </div>
                                </div>
                                <div class="card-list-actions flex-shrink-0 ms-auto d-flex gap-2 align-items-center px-3">
                                    <a href="materia_detalle.php?id=${materia.id}" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm" title="Registrar Calificaciones">
                                        <i class="fas fa-plus"></i> <span class="ms-1">Calif.</span>
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="abrirModalEdicion(${materia.id})" title="Editar">
                                        <i class="fas fa-edit" style="font-size: 0.8rem;"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="abrirModalEliminar(${materia.id})" title="Eliminar">
                                        <i class="fas fa-trash" style="font-size: 0.8rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="${colClass} mb-2" id="materia-${materia.id}">
                            <div class="materia-card h-100 shadow-sm d-flex flex-column ${cardSelectedClass}" data-id="${materia.id}" style="transition: transform 0.2s; border-radius: 1rem; background-color: var(--secondary-color);">
                                ${selectBtnHtml}
                                <div class="materia-card-header p-2" style="background: var(--primary-gradient); color: white; min-height: 80px;">
                                    <div class="mb-1">
                                        ${estadoBadge}
                                    </div>
                                    <h3 class="h6 mb-0 fw-bold text-truncate-2" title="${escapeHtml(materia.nombre)}">${escapeHtml(materia.nombre)}</h3>
                                </div>
                                <div class="materia-card-body p-3 d-flex flex-column flex-grow-1">
                                    <div class="d-flex align-items-center text-muted small mb-2">
                                        <i class="fas fa-layer-group me-2 text-primary"></i> 
                                        <span><strong>${criteriosCount}</strong> Criterios</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        ${criteriosHtml}
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <div class="text-muted mb-3" style="font-size: 0.8rem; height: 2.4em; overflow: hidden; line-height: 1.2;">
                                            ${escapeHtml(materia.descripcion || 'Sin descripción')}
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                            <a href="materia_detalle.php?id=${materia.id}" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm d-flex align-items-center">
                                                <i class="fas fa-plus-circle me-1"></i> Registrar Calif.
                                            </a>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2" onclick="abrirModalEdicion(${materia.id})" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="abrirModalEliminar(${materia.id})" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Agregar elemento al índice (siempre igual)
                indiceHtml += `
                    <a href="#materia-${materia.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasIndice')).hide()">
                        ${escapeHtml(materia.nombre)}
                        ${materia.activa == 1 ? '<span class="badge bg-success rounded-pill">Activa</span>' : '<span class="badge bg-secondary rounded-pill">Inactiva</span>'}
                    </a>`;
            });
            
            container.innerHTML = html;
            indiceLista.innerHTML = indiceHtml;
        }

        // Función para abrir el modal en modo edición
        function abrirModalEdicion(id) {
            const materia = materiasCache.find(m => m.id == id);
            if (!materia) return;

            // Cambiar título y botón
            document.getElementById('agregarMateriaLabel').innerHTML = '<i class="fas fa-edit"></i> Editar Materia';
            document.getElementById('btnGuardarMateria').innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';

            // Llenar datos
            document.getElementById('materiaId').value = materia.id;
            document.getElementById('nombreMateria').value = materia.nombre;
            document.getElementById('estadoMateria').value = materia.activa;
            document.getElementById('descripcionMateria').value = materia.descripcion || '';

            // Limpiar y llenar criterios
            const criteriosContainer = document.getElementById('criteriosContainer');
            criteriosContainer.innerHTML = ''; // Limpiar

            if (materia.criterios && materia.criterios.length > 0) {
                materia.criterios.forEach(c => agregarCriterio(c));
            } else {
                agregarCriterio(); // Agregar uno vacío si no hay
            }

            // Mostrar modal
            new bootstrap.Modal(document.getElementById('agregarMateriaModal')).show();
        }

        // Función para abrir el modal de eliminación
        function abrirModalEliminar(id) {
            const materia = materiasCache.find(m => m.id == id);
            if (!materia) return;

            document.getElementById('nombreMateriaEliminar').textContent = `la materia "${materia.nombre}"`;
            document.getElementById('idMateriaEliminar').value = id;

            new bootstrap.Modal(document.getElementById('eliminarMateriaModal')).show();
        }

        // Función para confirmar y ejecutar la eliminación
        async function eliminarMateria() {
            const id = document.getElementById('idMateriaEliminar').value;
            
            let payload = {};
            
            // Si ID está vacío, es eliminación por lote
            if (!id && selectedMateriaIds.size > 0) {
                payload = { accion: 'eliminar_lote', ids: Array.from(selectedMateriaIds) };
            } else {
                payload = { accion: 'eliminar', id: id };
            }

            try {
                const response = await fetch('../backend/materias/materias_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('eliminarMateriaModal')).hide();
                    
                    if(payload.accion === 'eliminar_lote') {
                        toggleSelectionMode(); // Salir modo selección
                    }
                    cargarMaterias();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Error de conexión al eliminar la materia.', 'error');
            }
        }

        // Inicializar y limpiar modal
        document.addEventListener('DOMContentLoaded', function() {
            actualizarVisibilidadBotonesEliminar();
            initSelectionMode(); // Inicializar UI de selección
            initViewButtons(); // Inicializar botones de vista
            initSortButtons(); // Inicializar botones de ordenamiento
            cargarMaterias(); // Cargar materias al iniciar
            
            const modalElement = document.getElementById('agregarMateriaModal');
            modalElement.addEventListener('hidden.bs.modal', function () {
                document.getElementById('formMateria').reset();
                // Limpiar y restaurar estado inicial
                document.getElementById('criteriosContainer').innerHTML = '';
                agregarCriterio(); 
                document.getElementById('materiaId').value = '';
                document.getElementById('agregarMateriaLabel').innerHTML = '<i class="fas fa-plus-circle"></i> Agregar Nueva Materia';
                document.getElementById('btnGuardarMateria').innerHTML = '<i class="fas fa-save"></i> Guardar Materia';
                actualizarVisibilidadBotonesEliminar();
            });

            // Evento para el botón de confirmar eliminación
            document.getElementById('btnConfirmarEliminar').addEventListener('click', eliminarMateria);

            // Prevenir la letra 'e' en todos los inputs numéricos de la página (para el modal)
            document.addEventListener('keydown', function(e) {
                if (e.target.matches('input[type="number"]') && e.key.toLowerCase() === 'e') {
                    e.preventDefault();
                }
            });

            // Inicializar con un criterio vacío al cargar la página
            agregarCriterio();

            // Abrir modal si viene del dashboard (Quick Action)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('accion') === 'nueva') {
                const addModal = new bootstrap.Modal(document.getElementById('agregarMateriaModal'));
                addModal.show();
            }
        });
    </script>
</body>
</html>
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
    <title>Materias - Sistema de Notas</title>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/materias.css">
</head>
<body>
    <!-- Navbar -->
    <?php include '../components/navbar.php'; ?>
    
    <!-- Contenido Principal -->
    <main class="container py-4 pb-5">
        <!-- Header -->
        <div class="materias-header">
            <div class="row align-items-center">
                <div class="col-12 col-md-6">
                    <h1><i class="fas fa-book"></i> Mis Materias</h1>
                    <p>Administra tus materias y criterios de evaluación</p>
                </div>
                <div class="col-12 col-md-6 mt-3 mt-md-0">
                    <div class="d-flex justify-content-end gap-2">
                        <!-- Toggle de Vistas -->
                        <div class="btn-group me-2 d-none d-md-inline-flex" role="group" aria-label="Cambiar vista">
                            <button type="button" class="btn btn-outline-light btn-sm active" id="btnViewGrid" title="Vista Cuadrícula"><i class="fas fa-th-large"></i></button>
                            <button type="button" class="btn btn-outline-light btn-sm" id="btnViewList" title="Vista Lista"><i class="fas fa-list"></i></button>
                        </div>

                        <button class="btn btn-agregar flex-grow-1 flex-md-grow-0" data-bs-toggle="modal" data-bs-target="#agregarMateriaModal">
                            <i class="fas fa-plus me-2"></i><span class="d-none d-sm-inline">Agregar Materia</span><span class="d-inline d-sm-none">Agregar</span>
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
                                <h6 class="mb-0"><i class="fas fa-list-check"></i> Criterios de Evaluación</h6>
                                <small class="text-muted">El total debe sumar 100%</small>
                            </div>
                            
                            <div id="criteriosContainer">
                                <div class="criterio-row">
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6">
                                            <input type="text" class="form-control criterio-nombre" placeholder="Ej: Quiz">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <input type="number" class="form-control criterio-cantidad" placeholder="Cantidad" min="1" value="1">
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="input-group">
                                                <input type="number" class="form-control criterio-porcentaje" placeholder="%" min="0" max="100" step="0.1">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-criterio w-100" style="display: none;">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-criterio" id="btnAgregarCriterio">
                                <i class="fas fa-plus"></i> Agregar Criterio
                            </button>
                        </div>

                        <div class="alert alert-info mt-3 mb-0" role="alert">
                            <small><i class="fas fa-info-circle"></i> Solo el nombre de la materia es obligatorio. Los criterios pueden agregarse después.</small>
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
            newCriterio.className = 'criterio-row';
            newCriterio.innerHTML = `
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <input type="text" class="form-control criterio-nombre" placeholder="Ej: Quiz" value="${criterio ? escapeHtml(criterio.nombre) : ''}">
                    </div>
                    <div class="col-6 col-md-3">
                        <input type="number" class="form-control criterio-cantidad" placeholder="Cantidad" min="1" value="${criterio ? criterio.cantidad_evaluaciones : '1'}">
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="input-group">
                            <input type="number" class="form-control criterio-porcentaje" placeholder="%" min="0" max="100" step="0.1" value="${criterio ? parseFloat(criterio.porcentaje) : ''}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-criterio w-100">
                            <i class="fas fa-trash"></i> Eliminar
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
            });
            
            actualizarVisibilidadBotonesEliminar();
        }

        // Mostrar/ocultar botones de eliminar según cantidad de criterios
        function actualizarVisibilidadBotonesEliminar() {
            const criterios = document.querySelectorAll('.criterio-row');
            criterios.forEach(criterio => {
                const btnEliminar = criterio.querySelector('.btn-remove-criterio');
                btnEliminar.style.display = criterios.length > 1 ? 'block' : 'none';
            });
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
                        porcentaje: parseFloat(porcentaje)
                    });
                }
            });

            if (!formCriteriosValido) {
                showToast('Si inicias un criterio, todos sus campos (nombre, cantidad, porcentaje) son requeridos.', 'warning');
                return;
            }

            // Validar que porcentajes sumen 100 si hay criterios
            if (criterios.length > 0) {
                const totalPorcentaje = criterios.reduce((sum, c) => sum + (c.porcentaje || 0), 0);
                if (Math.abs(totalPorcentaje - 100) > 0.01) {
                    showToast(`La suma de los porcentajes de los criterios debe ser 100%. Actualmente suman ${totalPorcentaje.toFixed(2)}%.`, 'warning');
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
                const response = await fetch('../backend/materia_proceso.php', {
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

            // Establecer estado inicial
            if (currentView === 'list') {
                btnGrid.classList.remove('active');
                btnList.classList.add('active');
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
                const response = await fetch('../backend/obtener_materias.php');
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

            // Determinar clases según la vista actual
            const colClass = currentView === 'grid' ? 'col-12 col-md-6 col-lg-6 col-xl-4' : 'col-12';
            const cardClass = currentView === 'list' ? 'card-list-view' : '';

            materiasCache.forEach(materia => {
                const estadoBadge = materia.activa == 1 
                    ? '<span class="materia-card-badge">Activa</span>' 
                    : '<span class="materia-card-badge bg-secondary">Inactiva</span>';
                
                const criteriosCount = materia.criterios ? materia.criterios.length : 0;
                const descripcionCorta = materia.descripcion ? (materia.descripcion.length > 60 ? materia.descripcion.substring(0, 60) + '...' : materia.descripcion) : 'Sin descripción';

                html += `
                    <div class="${colClass} mb-3" id="materia-${materia.id}">
                        <div class="materia-card h-100 ${cardClass}">
                            <div class="materia-card-header" style="cursor: pointer;" onclick="window.location.href='materia_detalle.php?id=${materia.id}'">
                                <div class="materia-card-title">${escapeHtml(materia.nombre)}</div>
                                ${estadoBadge}
                            </div>
                            <div class="materia-card-body">
                                <p class="text-muted small mb-2" style="min-height: 20px;">${escapeHtml(descripcionCorta)}</p>
                                <div class="d-flex align-items-center text-primary small fw-bold">
                                    <i class="fas fa-list-check me-2"></i> ${criteriosCount} Criterios de evaluación
                                </div>
                            </div>
                            <div class="materia-card-footer">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalEdicion(${materia.id})">
                                    <i class="fas fa-edit"></i><span class="d-none d-sm-inline ms-1">Editar</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="abrirModalEliminar(${materia.id})">
                                    <i class="fas fa-trash"></i><span class="d-none d-sm-inline ms-1">Eliminar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

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

            document.getElementById('nombreMateriaEliminar').textContent = materia.nombre;
            document.getElementById('idMateriaEliminar').value = id;

            new bootstrap.Modal(document.getElementById('eliminarMateriaModal')).show();
        }

        // Función para confirmar y ejecutar la eliminación
        async function eliminarMateria() {
            const id = document.getElementById('idMateriaEliminar').value;
            try {
                const response = await fetch('../backend/eliminar_materia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('eliminarMateriaModal')).hide();
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
            initViewButtons(); // Inicializar botones de vista
            cargarMaterias(); // Cargar materias al iniciar
            
            const modalElement = document.getElementById('agregarMateriaModal');
            modalElement.addEventListener('hidden.bs.modal', function () {
                document.getElementById('formMateria').reset();
                document.querySelectorAll('.criterio-row:not(:first-child)').forEach(el => el.remove());
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
        });
    </script>
</body>
</html>
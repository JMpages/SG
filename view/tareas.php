<?php
require_once '../backend/config/config.php';
require_once '../backend/autologin.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tareas - Sistema de Notas</title>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/tareas.css">
</head>
<body>
    <!-- Navbar -->
    <?php include '../components/navbar.php'; ?>

    <main class="container py-4 pb-5">
        <!-- Header -->
        <div class="tareas-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1><i class="fas fa-tasks me-2"></i>Mis Tareas</h1>
                    <p class="mb-0 opacity-75">Organiza tus entregas y pendientes académicos</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group" role="group" aria-label="Vista">
                        <button type="button" class="btn btn-outline-light active" id="btnVistaLista" title="Vista de Lista"><i class="fas fa-list"></i></button>
                        <button type="button" class="btn btn-outline-light" id="btnVistaCalendario" title="Vista de Calendario"><i class="fas fa-calendar-alt"></i></button>
                    </div>
                    <button class="btn btn-agregar" data-bs-toggle="modal" data-bs-target="#modalTarea">
                        <i class="fas fa-plus me-2"></i>Nueva Tarea
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row g-3 mb-4" id="filtros-container">
            <div class="col-6 col-md-4">
                <label for="filtroMateria" class="form-label small fw-bold">Materia</label>
                <select class="form-select" id="filtroMateria">
                    <option value="">Todas las materias</option>
                    <!-- Se llena dinámicamente -->
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label for="filtroEstado" class="form-label small fw-bold">Estado</label>
                <select class="form-select" id="filtroEstado">
                    <option value="todas">Todas</option>
                    <option value="pendientes" selected>Pendientes</option>
                    <option value="completadas">Completadas</option>
                    <option value="vencidas">Vencidas</option>
                </select>
            </div>
        </div>

        <!-- Lista de Tareas -->
        <div id="lista-tareas" class="row g-3">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        </div>

        <!-- Vista Calendario -->
        <div id="calendar-view" class="d-none">
            <div class="calendar-controls d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h3 id="calendar-month-year" class="m-0 fw-bold h4"></h3>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" id="btnCalMes">Mes</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnCalSemana">Semana</button>
                    </div>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary btn-sm" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                    <button class="btn btn-outline-secondary btn-sm" id="todayBtn">Hoy</button>
                    <button class="btn btn-outline-secondary btn-sm" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="calendar-container">
                <div class="calendar-header">
                    <div>Dom</div><div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div>
                </div>
                <div class="calendar-grid" id="calendar-grid">
                    <!-- Días generados por JS -->
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Crear/Editar Tarea -->
    <div class="modal fade" id="modalTarea" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTareaTitle">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Tarea
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formTarea">
                        <input type="hidden" id="tareaId" name="id">
                        <div class="mb-3">
                            <label for="tareaTitulo" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tareaTitulo" name="titulo" required placeholder="Ej: Taller de Cálculo">
                        </div>
                        <div class="mb-3">
                            <label for="tareaMateria" class="form-label">Materia <span class="text-danger">*</span></label>
                            <select class="form-select" id="tareaMateria" name="materia_id" required>
                                <option value="">Selecciona una materia</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="checkVincular">
                                <label class="form-check-label fw-bold" for="checkVincular">¿Es una actividad con nota?</label>
                                <div class="form-text text-muted small mt-0" style="line-height: 1.3;">Actívalo para asociar esta tarea a una evaluación (ej. Examen 1) en tu registro.</div>
                            </div>
                            <div id="panelVinculacion" class="mt-2 p-3 border rounded d-none" style="background-color: var(--bg-light); border-color: var(--border-color) !important;">
                                <div class="mb-2">
                                    <label for="tareaCriterio" class="form-label small text-muted">Tipo de Evaluación</label>
                                    <select class="form-select" id="tareaCriterio">
                                        <option value="">Primero selecciona materia...</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="tareaEvaluacionNumero" class="form-label small text-muted">Número de Evaluación</label>
                                    <select class="form-select" id="tareaEvaluacionNumero">
                                        <option value="">-</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="tareaFecha" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tareaFecha" name="fecha_entrega" required>
                        </div>
                        <div class="mb-3">
                            <label for="tareaDescripcion" class="form-label">Descripción (Opcional)</label>
                            <textarea class="form-control" id="tareaDescripcion" name="descripcion" rows="3" placeholder="Detalles adicionales..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarTarea">
                        <i class="fas fa-save me-2"></i>Guardar Tarea
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Tarea -->
    <div class="modal fade" id="modalEliminarTarea" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar la tarea <strong id="nombreTareaEliminar"></strong>?</p>
                    <p class="text-danger small"><strong>Atención:</strong> Esta acción no se puede deshacer.</p>
                    <input type="hidden" id="idTareaEliminar">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Día -->
    <div class="modal fade" id="modalDetalleDia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-6 fw-bold" id="modalDetalleDiaTitle">Fecha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="detalleDiaLista" class="list-group list-group-flush">
                        <!-- Items generados por JS -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-primary" id="btnAgregarTareaDia">
                        <i class="fas fa-plus me-1"></i>Agregar Tarea
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast de notificaciones -->
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
  
    <!-- JS Personalizado -->
    <script src="../assets/js/tareas.js"></script>

    <!-- Script para manejo de vinculación de tareas (Mejora UX) -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectMateria = document.getElementById('tareaMateria');
        const checkVincular = document.getElementById('checkVincular');
        const panelVinculacion = document.getElementById('panelVinculacion');
        const selectCriterio = document.getElementById('tareaCriterio');
        const selectNumero = document.getElementById('tareaEvaluacionNumero');
        const btnGuardar = document.getElementById('btnGuardarTarea');
        const modalTarea = document.getElementById('modalTarea');

        let criteriosCache = [];

        // Función auxiliar para cargar criterios
        async function cargarCriterios(materiaId) {
            selectCriterio.innerHTML = '<option value="">Cargando...</option>';
            selectNumero.innerHTML = '<option value="">-</option>';
            criteriosCache = [];
            
            if(!materiaId) {
                selectCriterio.innerHTML = '<option value="">Primero selecciona materia...</option>';
                return false;
            }

            try {
                const response = await fetch(`../backend/obtener_detalle_materia.php?id=${materiaId}`);
                const result = await response.json();
                
                if(result.status === 'success' && result.data.criterios) {
                    criteriosCache = result.data.criterios;
                    let options = '<option value="">Selecciona tipo...</option>';
                    criteriosCache.forEach(c => {
                        options += `<option value="${c.id}">${c.nombre}</option>`;
                    });
                    selectCriterio.innerHTML = options;
                    return true;
                } else {
                    selectCriterio.innerHTML = '<option value="">Sin criterios definidos</option>';
                }
            } catch(e) {
                console.error("Error cargando criterios:", e);
                selectCriterio.innerHTML = '<option value="">Error al cargar</option>';
            }
            return false;
        }

        // 1. Eventos de Interfaz
        checkVincular.addEventListener('change', function() {
            if(this.checked) {
                panelVinculacion.classList.remove('d-none');
                // Si hay materia pero no criterios, cargar
                if(selectMateria.value && selectCriterio.options.length <= 1) {
                    cargarCriterios(selectMateria.value);
                }
            } else {
                panelVinculacion.classList.add('d-none');
            }
        });

        selectMateria.addEventListener('change', function() {
            if(checkVincular.checked) {
                cargarCriterios(this.value);
            } else {
                // Limpiar cache si cambia materia aunque esté oculto
                selectCriterio.innerHTML = '<option value="">Primero selecciona materia...</option>';
                criteriosCache = [];
            }
        });

        selectCriterio.addEventListener('change', function() {
            const criterioId = this.value;
            selectNumero.innerHTML = '<option value="">-</option>';
            
            const criterio = criteriosCache.find(c => c.id == criterioId);
            if(criterio) {
                let options = '';
                for(let i=1; i <= criterio.cantidad_evaluaciones; i++) {
                    options += `<option value="${i}">#${i}</option>`;
                }
                selectNumero.innerHTML = options;
            }
        });

        // 2. Manejo de Edición (Poblar datos al abrir modal)
        modalTarea.addEventListener('shown.bs.modal', async function() {
            const tareaId = document.getElementById('tareaId').value;
            
            // UX: Mejoras de velocidad (Foco, Fecha Hoy, Pre-selección Materia)
            document.getElementById('tareaTitulo').focus();
            if(!document.getElementById('tareaFecha').value) {
                document.getElementById('tareaFecha').value = new Date().toISOString().split('T')[0];
            }
            // Si ya estás filtrando por una materia, la seleccionamos automáticamente
            if(!tareaId && window.app && window.app.state.filtroMateria) {
                document.getElementById('tareaMateria').value = window.app.state.filtroMateria;
            }

            // Si es edición (tareaId tiene valor) y tenemos acceso a la app global
            if(tareaId && window.app) {
                const tarea = window.app.state.tareas.find(t => t.id == tareaId) || window.app.state.calendarTasks.find(t => t.id == tareaId);
                
                if(tarea && tarea.es_calificada == 1) {
                    checkVincular.checked = true;
                    panelVinculacion.classList.remove('d-none');
                    
                    // Cargar criterios y esperar
                    await cargarCriterios(tarea.materia_id);
                    
                    // Establecer valores
                    selectCriterio.value = tarea.criterio_id;
                    // Disparar evento change manualmente para llenar números
                    selectCriterio.dispatchEvent(new Event('change'));
                    selectNumero.value = tarea.numero_evaluacion;
                } else {
                    checkVincular.checked = false;
                    panelVinculacion.classList.add('d-none');
                }
            } else {
                // Modo crear: resetear visualmente
                checkVincular.checked = false;
                panelVinculacion.classList.add('d-none');
            }
        });

        // 2. Sobrescribir el guardado para incluir la vinculación
        // Clonamos el botón para eliminar listeners anteriores de tareas.js y usar este nuevo logic
        const newBtnGuardar = btnGuardar.cloneNode(true);
        btnGuardar.parentNode.replaceChild(newBtnGuardar, btnGuardar);

        newBtnGuardar.addEventListener('click', async function() {
            const form = document.getElementById('formTarea');
            const titulo = document.getElementById('tareaTitulo').value;
            const materiaId = document.getElementById('tareaMateria').value;
            const fecha = document.getElementById('tareaFecha').value;
            const descripcion = document.getElementById('tareaDescripcion').value;
            const id = document.getElementById('tareaId').value;

            if(!titulo || !materiaId || !fecha) {
                // Usamos el toast existente si es posible, o alert simple
                alert('Por favor completa los campos obligatorios (Título, Materia, Fecha)');
                return;
            }

            // Preparar datos para el backend
            let es_calificada = 0;
            let criterio_id = null;
            let numero_evaluacion = null;

            if(checkVincular.checked) {
                const critVal = selectCriterio.value;
                const numVal = selectNumero.value;
                
                if(!critVal || !numVal) {
                    alert('Si activas la vinculación, debes seleccionar el Tipo y el Número de evaluación.');
                    return;
                }
                es_calificada = 1;
                criterio_id = critVal;
                numero_evaluacion = numVal;
            }

            const data = {
                id: id || null,
                titulo: titulo,
                materia_id: materiaId,
                fecha_entrega: fecha,
                descripcion: descripcion,
                es_calificada: es_calificada,
                criterio_id: criterio_id,
                numero_evaluacion: numero_evaluacion
            };

            try {
                const response = await fetch('../backend/tareas_proceso.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if(result.status === 'success') {
                    // Recargar página o actualizar lista (asumimos recarga para simplificar integración)
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(error) {
                console.error(error);
                alert('Error de conexión al guardar la tarea');
            }
        });
    });
    </script>
</body>
</html>
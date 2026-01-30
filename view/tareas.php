<?php
require_once '../backend/config/config.php';

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
                <button class="btn btn-agregar" data-bs-toggle="modal" data-bs-target="#modalTarea">
                    <i class="fas fa-plus me-2"></i>Nueva Tarea
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row g-3 mb-4">
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
</body>
</html>
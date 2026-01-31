<?php
// Configuración y sesión
require_once '../backend/config/config.php';
require_once '../backend/autologin.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Validar que se reciba un ID de materia
$materia_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$materia_id) {
    header("Location: materias.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Materia - Sistema de Notas</title>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/materia_detalle.css">
</head>
<body>
    <!-- Navbar -->
    <?php include '../components/navbar.php'; ?>

    <!-- Contenido Principal -->
    <main class="container py-4 pb-5" id="app">
        <!-- Header de la materia (se llenará con JS) -->
        <div id="materia-header-placeholder"></div>

        <div class="row g-4">
            <!-- Columna principal de notas -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0"><i class="fas fa-edit me-2"></i>Registro de Calificaciones</h2>
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-sm btn-outline-secondary d-none" id="btn-sync-real" title="Reiniciar simulación con notas reales">
                            <i class="fas fa-sync-alt me-1"></i> Traer notas reales
                        </button>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="modoSimulacion">
                            <label class="form-check-label" for="modoSimulacion">Modo Simulación</label>
                        </div>
                    </div>
                </div>
                <div id="criterios-container" class="d-grid gap-4">
                    <!-- Las tarjetas de criterios se cargarán aquí -->
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna de resumen -->
            <div class="col-lg-4">
                <div class="summary-sidebar">
                    <h2 class="h4 mb-3"><i class="fas fa-chart-line me-2"></i>Resumen del Curso</h2>
                    
                    <div class="card-resumen-final mb-3">
                        <div class="label">Nota Final Actual</div>
                        <div class="nota" id="nota-final-total">0.00</div>
                        <div class="barra-progreso">
                            <div class="barra" id="barra-nota-final" style="width: 0%;"></div>
                        </div>
                    </div>

                    <div id="resumen-criterios-container" class="d-grid gap-2">
                        <!-- El resumen por criterio se cargará aquí -->
                    </div>

                    <hr class="my-4">

                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg" id="btn-guardar-notas">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirmar Acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro? Esto sobrescribirá los datos de la simulación con las notas reales actuales.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm-action">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JS de la página -->
    <script>
        // Pasar el ID de la materia a JavaScript
        const MATERIA_ID = <?php echo json_encode($materia_id); ?>;
    </script>
    <script src="../assets/js/materia_detalle.js"></script>
</body>
</html>
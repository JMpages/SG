<?php
// Configuración y sesión
require_once '../backend/config/config.php';
require_once '../backend/auth/autologin.php';

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
    <?php include 'components/navbar.php'; ?>

    <!-- Contenido Principal -->
    <main class="container py-4 pb-5" id="app">
        <!-- Header de la materia (se llenará con JS) -->
        <div id="materia-header-placeholder"></div>

        <div class="row g-4 align-items-stretch">
            <!-- Columna de resumen (Sticky a la Derecha en PC) -->
            <div class="col-lg-4 order-lg-2">
                <div class="summary-sidebar animate-slide-up">
                    <div class="card-resumen-final d-none d-lg-block">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="label mb-0">Nota Final</span>
                            <button class="btn btn-light btn-sm shadow-sm text-primary fw-bold" id="btn-guardar-notas">
                                <i class="fas fa-save me-1"></i> Guardar
                            </button>
                        </div>
                        <div class="nota" id="nota-final-total">0.00</div>
                        <div class="barra-progreso w-100 mt-2">
                            <div class="barra" id="barra-nota-final" style="width: 0%;"></div>
                        </div>
                    </div>
                    
                    <div class="sidebar-info-card border-0 p-0 mb-3">
                        <!-- Botón movido arriba para mejor UX en móvil -->
                        <div class="d-lg-none mb-2">
                            <button class="btn btn-link text-decoration-none w-100 d-flex justify-content-between align-items-center p-0" type="button" data-bs-toggle="collapse" data-bs-target="#resumen-criterios-collapse" aria-expanded="false" aria-controls="resumen-criterios-collapse">
                                <span class="fw-semibold text-body"><i class="fas fa-chart-pie me-2"></i>Ver Resumen</span>
                                <i class="fas fa-chevron-down transition-icon"></i>
                            </button>
                        </div>

                        <div class="collapse d-lg-block mt-2" id="resumen-criterios-collapse">
                            <div class="px-1 mb-2 d-lg-none">
                                <small class="text-muted fst-italic" style="font-size: 0.75rem;">Detalle por criterios:</small>
                            </div>
                            <div id="resumen-criterios-container" class="row g-2 mt-2">
                                <!-- Inyectado por JS -->
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-top border-light d-none d-lg-block">
                        <a href="materias.php" class="btn btn-sm btn-outline-secondary w-100 text-start">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>

            <!-- Columna principal de notas (Izquierda en PC) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4" style="background-color: var(--secondary-color); color: var(--text-primary);">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="h5 mb-0 fw-bold"><i class="fas fa-edit me-2 text-primary"></i>Registro de Calificaciones</h2>
                            <div class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i> Se guarda automáticamente
                            </div>
                        </div>
                        <div id="criterios-container" class="row">
                            <!-- JS Inject -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Resumen Flotante para Móvil -->
    <div class="floating-summary-mobile d-lg-none">
        <div class="top-progress-container">
            <div class="barra" id="barra-nota-final-mobile" style="width: 0%;"></div>
        </div>
        
        <div class="d-flex align-items-center justify-content-between w-100 px-2" style="height: 100%;">
            <div class="d-flex align-items-center gap-2">
                <span class="label mb-0 text-white-50 small text-uppercase fw-bold">Nota Final:</span>
                <div class="nota mb-0 text-white h2 fw-bold" id="nota-final-total-mobile">0.00</div>
            </div>
            
            <button class="btn btn-light shadow-sm d-flex align-items-center justify-content-center" id="btn-guardar-notas-mobile-floating" style="width: 40px; height: 40px; border-radius: 10px;">
                <i class="fas fa-save text-primary"></i>
            </button>
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
    
    <!-- JS de la página -->
    <script>
        // Pasar el ID de la materia a JavaScript
        const MATERIA_ID = <?php echo json_encode($materia_id); ?>;
    </script>
    <script src="../assets/js/materia_detalle.js"></script>
</body>
</html>
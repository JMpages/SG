<?php
require_once '../backend/config/config.php';
require_once '../backend/autologin.php';

// Verificar sesión
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
    <title>Mi Perfil - Sistema de Notas</title>
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos -->
    <link rel="stylesheet" href="../assets/css/materias.css">
    <link rel="stylesheet" href="../assets/css/perfil.css">
</head>
<body>
    
    <!-- Navbar -->
    <?php include '../components/navbar.php'; ?>

    <div class="container py-5" id="perfil-container">
        
        <div class="row g-4">
            <!-- Columna Izquierda: Tarjeta de Usuario y Resumen -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm perfil-card h-100">
                    <div class="card-body text-center p-4 d-flex flex-column align-items-center justify-content-center">
                        <div class="perfil-avatar-lg">
                            <i class="fas fa-user"></i>
                        </div>
                        
                        <h3 class="fw-bold mb-1" id="display-usuario">Cargando...</h3>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Pestañas de Configuración -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm perfil-content-card">
                    <!-- Header de Pestañas -->
                    <div class="card-header bg-transparent border-bottom-custom pt-4 px-4 pb-0">
                        <ul class="nav nav-pills card-header-pills mb-3 nav-justified" id="perfilTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab" aria-controls="tab-general" aria-selected="true">
                                    <i class="fas fa-user-edit"></i>General
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="seguridad-tab" data-bs-toggle="pill" data-bs-target="#tab-seguridad" type="button" role="tab" aria-controls="tab-seguridad" aria-selected="false">
                                    <i class="fas fa-shield-alt"></i>Seguridad
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="actividad-tab" data-bs-toggle="pill" data-bs-target="#tab-actividad" type="button" role="tab" aria-controls="tab-actividad" aria-selected="false">
                                    <i class="fas fa-history"></i>Actividad
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Contenido de Pestañas -->
                    <div class="card-body p-4">
                        <div class="tab-content" id="perfilTabsContent">
                            
                            <!-- Tab: General -->
                            <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="general-tab">
                                <h5 class="mb-4 text-primary fw-bold">Información Básica</h5>
                                <form id="form-perfil-datos">
                                    <div class="mb-4">
                                        <label for="perfil-nombre" class="form-label text-muted small fw-bold">NOMBRE DE USUARIO</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                                            <input type="text" class="form-control" id="perfil-nombre" required minlength="3">
                                        </div>
                                        <div class="form-text text-muted">Este nombre será visible en tu dashboard y barra de navegación.</div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-save me-2"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Tab: Seguridad -->
                            <div class="tab-pane fade" id="tab-seguridad" role="tabpanel" aria-labelledby="seguridad-tab">
                                <h5 class="mb-4 text-primary fw-bold">Cambiar Contraseña</h5>
                                <form id="form-perfil-password" class="mb-5">
                                    <div class="mb-3">
                                        <label for="pass-actual" class="form-label small text-muted fw-bold">CONTRASEÑA ACTUAL</label>
                                        <input type="password" class="form-control" id="pass-actual" required>
                                    </div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label for="pass-nueva" class="form-label small text-muted fw-bold">NUEVA CONTRASEÑA</label>
                                            <input type="password" class="form-control" id="pass-nueva" required minlength="6">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="pass-confirm" class="form-label small text-muted fw-bold">CONFIRMAR NUEVA</label>
                                            <input type="password" class="form-control" id="pass-confirm" required minlength="6">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <button type="submit" class="btn btn-outline-primary px-4">
                                            <i class="fas fa-key me-2"></i>Actualizar Contraseña
                                        </button>
                                    </div>
                                </form>

                                <hr class="my-4 border-secondary opacity-25">

                                <div class="rounded-3 p-3 danger-zone">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="fs-4 text-danger me-3"><i class="fas fa-exclamation-triangle"></i></div>
                                        <div>
                                            <h6 class="text-danger fw-bold mb-1">Zona de Peligro</h6>
                                            <p class="small text-muted mb-0">Acciones irreversibles para tu cuenta.</p>
                                        </div>
                                    </div>
                                    <button id="btn-eliminar-cuenta" class="btn btn-danger btn-sm w-100">
                                        <i class="fas fa-trash-alt me-2"></i>Eliminar mi cuenta permanentemente
                                    </button>
                                </div>
                            </div>

                            <!-- Tab: Actividad -->
                            <div class="tab-pane fade" id="tab-actividad" role="tabpanel" aria-labelledby="actividad-tab">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="text-primary fw-bold mb-0">Historial de Inicios de Sesión</h5>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="cargarActividad()">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div id="activity-container">
                                    <div class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                                        <p>Cargando actividad...</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Lógica del Perfil -->
    <script src="../assets/js/perfil.js"></script>
</body>
</html>
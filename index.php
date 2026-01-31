<?php
// Configuración y Autologin
require_once 'backend/config/config.php';
require_once 'backend/autologin.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido - Sistema de Notas</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/style.css">
    
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <i class="fas fa-graduation-cap fa-4x" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                    </div>
                    <h1 class="display-4 fw-bold mb-4" style="color: var(--text-primary);">Tus notas, bajo control</h1>
                    <p class="lead mb-5" style="color: var(--text-secondary);">
                        La herramienta definitiva para estudiantes. Gestiona tus materias, organiza tus tareas y simula tus calificaciones para alcanzar tus metas académicas.
                    </p>
                    
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <?php if(isset($_SESSION['usuario'])): ?>
                            <a href="view/dashboard.php" class="btn btn-primary btn-lg px-5 shadow-lg">
                                <i class="fas fa-tachometer-alt me-2"></i>Ir a mi Dashboard
                            </a>
                        <?php else: ?>
                            <a href="view/login.php" class="btn btn-primary btn-lg px-4 shadow-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Comenzar Ahora
                            </a>
                            <a href="view/registro.php" class="btn btn-outline-secondary btn-lg px-4" style="border-color: var(--border-color); color: var(--text-primary);">
                                Crear Cuenta
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3 class="h4 mb-3" style="color: var(--text-primary);">Gestión de Materias</h3>
                        <p style="color: var(--text-secondary);">
                            Crea materias personalizadas, define tus criterios de evaluación y porcentajes. Mantén todo organizado en un solo lugar.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h3 class="h4 mb-3" style="color: var(--text-primary);">Simulador de Notas</h3>
                        <p style="color: var(--text-secondary);">
                            ¿Cuánto necesitas sacar en el final? Simula calificaciones futuras sin afectar tus datos reales y planifica tu éxito.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="h4 mb-3" style="color: var(--text-primary);">Control de Tareas</h3>
                        <p style="color: var(--text-secondary);">
                            Nunca más olvides una entrega. Registra tus tareas pendientes, fechas de vencimiento y marca tu progreso.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section class="py-5" style="background-color: var(--bg-light);">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: var(--text-primary);">¿Cómo funciona?</h2>
                <p class="text-muted">Empieza a mejorar tus calificaciones en 3 simples pasos</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h4 class="mb-3" style="color: var(--text-primary);">Regístrate</h4>
                        <p style="color: var(--text-secondary);">Crea tu cuenta gratuita en segundos. Solo necesitas un nombre de usuario y contraseña.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h4 class="mb-3" style="color: var(--text-primary);">Agrega Materias</h4>
                        <p style="color: var(--text-secondary);">Configura tus asignaturas y define los porcentajes de evaluación (parciales, quices, etc.).</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h4 class="mb-3" style="color: var(--text-primary);">Simula y Gana</h4>
                        <p style="color: var(--text-secondary);">Registra tus notas reales o simula escenarios futuros para saber cuánto necesitas para pasar.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Madness Section -->
    <section class="py-5 text-white" style="background: var(--primary-dark); position: relative; overflow: hidden;">
        <!-- Background decoration -->
        <div style="position: absolute; top: -50%; left: -20%; width: 80%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); transform: rotate(30deg);"></div>
        
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-4 fw-bold mb-3">¿Sabías que...?</h2>
                    <p class="lead mb-4">El 99% de los estudiantes que organizan sus materias reducen su estrés en un 200% (estadística totalmente inventada, pero suena real, ¿verdad?).</p>
                    <p class="mb-4">No dejes que el caos controle tu semestre. Domina tus números.</p>
                    
                    <?php if(!isset($_SESSION['usuario'])): ?>
                        <a href="view/registro.php" class="btn btn-light btn-lg px-4 fw-bold text-primary shadow">
                            <i class="fas fa-rocket me-2"></i>Despegar Ahora
                        </a>
                    <?php else: ?>
                        <a href="view/dashboard.php" class="btn btn-light btn-lg px-4 fw-bold text-primary shadow">
                            <i class="fas fa-rocket me-2"></i>Ir al Panel
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-4 bg-white bg-opacity-10 rounded-3 text-center" style="backdrop-filter: blur(5px);">
                                <div class="fs-1 mb-2">∞</div>
                                <div class="small text-uppercase tracking-wider">Posibilidades</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-4 bg-white bg-opacity-10 rounded-3 text-center mt-4" style="backdrop-filter: blur(5px);">
                                <div class="fs-1 mb-2">0</div>
                                <div class="small text-uppercase tracking-wider">Excusas</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-4 bg-white bg-opacity-10 rounded-3 text-center" style="margin-top: -1.5rem; backdrop-filter: blur(5px);">
                                <div class="fs-1 mb-2">100%</div>
                                <div class="small text-uppercase tracking-wider">Control</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-4 bg-white bg-opacity-10 rounded-3 text-center mt-2" style="backdrop-filter: blur(5px);">
                                <div class="fs-1 mb-2">24/7</div>
                                <div class="small text-uppercase tracking-wider">Disponible</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="mb-4">
                <span class="fw-bold fs-4" style="color: var(--primary-color);">Notas</span>
            </div>
            <p class="mb-4">Simplificando la vida académica, una nota a la vez.</p>
            <div class="d-flex justify-content-center gap-4 mb-4">
                
            </div>
            <small>&copy; <?php echo date('Y'); ?> Sistema de Notas. Todos los derechos reservados.</small>
        </div>
    </footer>

    <!-- Bootstrap JS (Necesario para el navbar móvil) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Configuración y sesión
require_once '../backend/config/config.php';
require_once '../backend/auth/autologin.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$total_materias = 0;
$total_tareas = 0;
$total_vencidas = 0;
$proximas_tareas = [];
$materias_recientes = [];

try {
    // 1. Obtener contadores
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM materias WHERE usuario_id = ? AND activa = 1");
    $stmt->execute([$usuario_id]);
    $total_materias = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE m.usuario_id = ? AND t.completada = 0");
    $stmt->execute([$usuario_id]);
    $total_tareas = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE m.usuario_id = ? AND t.completada = 0 AND t.fecha_entrega < CURDATE()");
    $stmt->execute([$usuario_id]);
    $total_vencidas = $stmt->fetchColumn();

    // 2. Obtener próximas tareas (Top 5)
    $stmt = $pdo->prepare("SELECT t.*, m.nombre as materia_nombre FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE m.usuario_id = ? AND t.completada = 0 ORDER BY t.fecha_entrega ASC LIMIT 5");
    $stmt->execute([$usuario_id]);
    $proximas_tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener materias recientes (Top 4) con progreso
    $stmt = $pdo->prepare("
        SELECT m.*, 
        (SELECT COUNT(*) FROM criterios_evaluacion c WHERE c.materia_id = m.id) as total_criterios,
        (SELECT SUM(cantidad_evaluaciones) FROM criterios_evaluacion c WHERE c.materia_id = m.id) as total_evaluaciones,
        (SELECT COUNT(*) FROM notas n 
         JOIN criterios_evaluacion c ON n.criterio_id = c.id 
         WHERE c.materia_id = m.id AND n.es_simulacion = 0 AND n.calificacion IS NOT NULL) as evaluaciones_completadas
        FROM materias m 
        WHERE m.usuario_id = ? AND m.activa = 1 
        ORDER BY m.id DESC LIMIT 4
    ");
    $stmt->execute([$usuario_id]);
    $materias_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular progreso para cada materia
    foreach ($materias_recientes as &$materia) {
        $total = (int)$materia['total_evaluaciones'];
        $completadas = (int)$materia['evaluaciones_completadas'];
        $materia['progreso'] = $total > 0 ? round(($completadas / $total) * 100) : 0;
    }
    unset($materia); // Romper la referencia para evitar errores en el bucle de la vista.

}
catch (PDOException $e) {
    // En producción, loguear error
    $error_db = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Notas</title>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/materias.css">
    <link rel="stylesheet" href="../assets/css/tareas.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenido -->
    <main class="container py-4">
        <!-- Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-banner">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>¡Hola, <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</h2>
                            <p>Bienvenido a tu panel de control. Aquí tienes un resumen de tu actividad.</p>
                        </div>
                        <div class="col-md-4 text-end d-none d-md-block">
                            <i class="fas fa-graduation-cap fa-4x" style="opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5 animate-slide-up">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon shadow-sm" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(102, 126, 234, 0.05)); color: var(--primary-color);">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_materias; ?></h3>
                        <p>Materias Activas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon shadow-sm" style="background: linear-gradient(135deg, rgba(253, 203, 110, 0.15), rgba(253, 203, 110, 0.05)); color: var(--warning-vibrant);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_tareas; ?></h3>
                        <p>Tareas Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon shadow-sm" style="background: linear-gradient(135deg, rgba(255, 118, 117, 0.15), rgba(255, 118, 117, 0.05)); color: var(--danger-vibrant);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_vencidas; ?></h3>
                        <p>Tareas Vencidas</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Columna Izquierda: Materias y Tareas -->
            <div class="col-lg-9">
                <div class="row g-4">
                    <!-- Materias Recientes -->
                    <div class="col-12 col-md-6">
                        <div class="section-title">
                            <span><i class="fas fa-clock me-2 text-primary"></i>Materias Recientes</span>
                            <a href="materias.php" class="btn btn-link btn-sm p-0">Ver todas</a>
                        </div>
                        
                        <?php if (empty($materias_recientes)): ?>
                            <div class="glass rounded-4 p-5 text-center mb-4">
                                <i class="fas fa-book-open mb-3 fs-1 text-muted opacity-50"></i>
                                <p class="text-secondary">No tienes materias activas</p>
                                <a href="materias.php" class="btn btn-primary btn-sm mt-2">Crear Materia</a>
                            </div>
                        <?php
else: ?>
                            <div class="row g-3">
                                <?php foreach ($materias_recientes as $materia): ?>
                                    <div class="col-12">
                                        <div class="materia-card glass border-0 p-3" style="cursor: pointer;" onclick="window.location.href='../materia/<?php echo $materia['id']; ?>'">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <h5 class="mb-0 text-truncate" style="font-size: 1rem;"><?php echo htmlspecialchars($materia['nombre']); ?></h5>
                                                    <small class="text-muted d-block text-truncate"><?php echo $materia['total_criterios']; ?> criterios definidos</small>
                                                </div>
                                            </div>
                                            <div class="progress mb-2" style="height: 6px; border-radius: 10px; background: rgba(0,0,0,0.05);">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $materia['progreso']; ?>%; border-radius: 10px;"></div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Progreso académico</small>
                                                <small class="fw-bold text-primary"><?php echo $materia['progreso']; ?>%</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php
    endforeach; ?>
                            </div>
                        <?php
endif; ?>
                    </div>

                    <!-- Próximas Tareas -->
                    <div class="col-12 col-md-6">
                        <div class="section-title">
                            <span><i class="fas fa-calendar-alt me-2 text-primary"></i>Próximas Entregas</span>
                            <a href="tareas.php" class="btn btn-link btn-sm p-0">Ir a Tareas</a>
                        </div>

                        <?php if (empty($proximas_tareas)): ?>
                            <div class="glass rounded-4 p-5 text-center">
                                <i class="fas fa-check-double mb-3 fs-1 text-success opacity-50"></i>
                                <p class="text-secondary">¡Todo al día! No hay tareas pendientes.</p>
                                <a href="tareas.php" class="btn btn-primary btn-sm mt-2">Crear Tarea</a>
                            </div>
                        <?php
else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($proximas_tareas as $tarea): ?>
                                    <?php
        $fecha_entrega = new DateTime($tarea['fecha_entrega']);
        $hoy = new DateTime();
        $hoy->setTime(0, 0, 0);
        $fecha_entrega->setTime(0, 0, 0);

        $diff = $hoy->diff($fecha_entrega);
        $dias_restantes = (int)$diff->format("%r%a");

        $es_vencida = $dias_restantes < 0;
        $es_hoy = $dias_restantes == 0;
        $es_manana = $dias_restantes == 1;

        if ($es_vencida) {
            $status_class = 'bg-danger';
            $status_text = 'Vencida';
            $card_border = 'border-start border-danger border-4';
        }
        elseif ($es_hoy) {
            $status_class = 'bg-warning text-dark';
            $status_text = 'Hoy';
            $card_border = 'border-start border-warning border-4';
        }
        elseif ($es_manana) {
            $status_class = 'bg-info text-white';
            $status_text = 'Mañana';
            $card_border = 'border-start border-info border-4';
        }
        else {
            $status_class = 'bg-light text-muted border';
            $status_text = 'En ' . $dias_restantes . ' días';
            $card_border = '';
        }
?>
                                    <div class="tarea-card glass border-0 p-3 <?php echo $card_border; ?>" style="height: auto;">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge <?php echo $status_class; ?> rounded-pill px-3" style="font-size: 0.7rem;"><?php echo $status_text; ?></span>
                                            <span class="text-primary fw-bold" style="font-size: 0.7rem; opacity: 0.7;"><?php echo htmlspecialchars($tarea['materia_nombre']); ?></span>
                                        </div>
                                        <h5 class="mb-1" style="font-size: 1rem; font-weight: 700;"><?php echo htmlspecialchars($tarea['titulo']); ?></h5>
                                        <div class="d-flex align-items-center mt-2 text-muted" style="font-size: 0.8rem;">
                                            <i class="far fa-calendar-alt me-2"></i>
                                            <span><?php echo $fecha_entrega->format('d M, Y'); ?></span>
                                        </div>
                                    </div>
                                <?php
    endforeach; ?>
                            </div>
                        <?php
endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Acciones Rápidas -->
            <div class="col-lg-3">
                <div class="section-title">
                    <span><i class="fas fa-bolt me-2 text-primary"></i>Acciones Rápidas</span>
                </div>
                <div class="glass rounded-4 p-4 d-flex flex-column gap-3">
                    <button class="btn btn-primary d-flex align-items-center justify-content-between w-100 p-3 rounded-4 shadow-sm" onclick="window.location.href='materias.php?accion=nueva'">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-plus-circle me-3 fs-5"></i>
                            <span class="fw-bold">Nueva Materia</span>
                        </div>
                        <i class="fas fa-chevron-right opacity-50"></i>
                    </button>
                    
                    <button class="btn btn-light d-flex align-items-center justify-content-between w-100 p-3 rounded-4 border shadow-xs" onclick="window.location.href='tareas.php?accion=nueva'">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tasks me-3 fs-5 text-primary"></i>
                            <span class="fw-bold">Nueva Tarea</span>
                        </div>
                        <i class="fas fa-chevron-right opacity-50"></i>
                    </button>

                    <hr class="my-2 opacity-10">

                    <a href="materias.php" class="text-decoration-none p-2 d-flex align-items-center text-secondary small">
                        <i class="fas fa-list-ul me-3"></i> Listado completo
                    </a>
                </div>

                <div class="mt-4 p-4 rounded-4" style="background: linear-gradient(135deg, #193989ff 0%, #9041c8ff 100%); border: 1px solid rgba(12, 51, 227, 0.1);">
                    <h6 class="fw-bold text-primary mb-2">Tip del día</h6>
                    <p class="small text-muted mb-0">¡Quizás lo mejor es no estudiar!</p>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
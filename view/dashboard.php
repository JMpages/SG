<?php
// Configuración y sesión
require_once '../backend/config/config.php';
require_once '../backend/autologin.php';

// Verificar si el usuario está logueado
if(!isset($_SESSION['usuario'])){
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

    // 3. Obtener materias recientes (Top 3)
    $stmt = $pdo->prepare("SELECT * FROM materias WHERE usuario_id = ? AND activa = 1 ORDER BY id DESC LIMIT 3");
    $stmt->execute([$usuario_id]);
    $materias_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
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
    <?php include '../components/navbar.php'; ?>

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
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_materias; ?></h3>
                        <p>Materias Activas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_tareas; ?></h3>
                        <p>Tareas Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_vencidas; ?></h3>
                        <p>Tareas Vencidas</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Materias Recientes -->
            <div class="col-lg-6">
                <div class="section-title">
                    <span><i class="fas fa-clock me-2"></i>Materias Recientes</span>
                    <a href="materias.php">Ver todas <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                
                <?php if(empty($materias_recientes)): ?>
                    <div class="empty-state py-4">
                        <i class="fas fa-book-open mb-3"></i>
                        <p>No tienes materias activas</p>
                        <a href="materias.php" class="btn btn-sm btn-primary mt-2">Crear Materia</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach($materias_recientes as $materia): ?>
                            <div class="materia-card card-list-view" style="min-height: auto; cursor: pointer;" onclick="window.location.href='materia_detalle.php?id=<?php echo $materia['id']; ?>'">
                                <div class="materia-card-header py-3" style="flex: 0 0 60px; background: var(--primary-gradient); color: white; align-items: center; justify-content: center;">
                                    <i class="fas fa-book fa-lg"></i>
                                </div>
                                <div class="materia-card-body py-3 d-flex align-items-center justify-content-between">
                                    <div class="ms-3">
                                        <h5 class="materia-card-title mb-1" style="font-size: 1rem;"><?php echo htmlspecialchars($materia['nombre']); ?></h5>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($materia['descripcion'] ?? '', 0, 40)); ?></small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted me-3"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Próximas Tareas -->
            <div class="col-lg-6">
                <div class="section-title">
                    <span><i class="fas fa-calendar-alt me-2"></i>Próximas Entregas</span>
                    <a href="tareas.php">Ver todas <i class="fas fa-arrow-right ms-1"></i></a>
                </div>

                <?php if(empty($proximas_tareas)): ?>
                    <div class="empty-state py-4">
                        <i class="fas fa-check-circle mb-3"></i>
                        <p>¡Todo al día! No hay tareas pendientes.</p>
                        <a href="tareas.php" class="btn btn-sm btn-primary mt-2">Crear Tarea</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach($proximas_tareas as $tarea): ?>
                            <?php 
                                $fecha_entrega = new DateTime($tarea['fecha_entrega']);
                                $hoy = new DateTime();
                                $hoy->setTime(0,0,0); // Reset time to compare dates only
                                $fecha_entrega->setTime(0,0,0);
                                
                                $diff = $hoy->diff($fecha_entrega);
                                $dias_restantes = (int)$diff->format("%r%a");
                                
                                $es_vencida = $dias_restantes < 0;
                                $es_hoy = $dias_restantes == 0;
                                
                                $badge_class = $es_vencida ? 'bg-danger' : ($es_hoy ? 'bg-warning text-dark' : 'bg-info text-dark');
                                $texto_fecha = $es_vencida ? 'Vencida' : ($es_hoy ? 'Hoy' : $fecha_entrega->format('d/m/Y'));
                            ?>
                            <div class="tarea-card p-3 <?php echo $es_vencida ? 'vencida' : ''; ?>" style="height: auto;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="tarea-materia mb-1 d-inline-block"><?php echo htmlspecialchars($tarea['materia_nombre']); ?></span>
                                        <h5 class="tarea-titulo mb-1" style="font-size: 1rem;"><?php echo htmlspecialchars($tarea['titulo']); ?></h5>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i> <?php echo $fecha_entrega->format('d M Y'); ?></small>
                                    </div>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $texto_fecha; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
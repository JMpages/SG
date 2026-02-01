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
    <title>Anotaciones - Sistema de Notas</title>
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/anotaciones.css">
</head>
<body>
    <!-- Navbar -->
    <?php include '../components/navbar.php'; ?>
    
    <!-- Contenido Principal -->
    <main class="container py-5">
        <div class="row justify-content-center text-center mt-5">
            <div class="col-md-8">
                <div class="p-5 rounded-3 border shadow-sm" style="background-color: var(--secondary-color);">
                    <i class="fas fa-tools fa-4x text-muted mb-4 opacity-50"></i>
                    <h1 class="display-5 fw-bold text-muted">Próximamente</h1>
                    <p class="lead text-muted mb-4">Esta sección de Anotaciones estará disponible en futuras actualizaciones.</p>
                    <a href="dashboard.php" class="btn btn-primary btn-lg">Volver al Inicio</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
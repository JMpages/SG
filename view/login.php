<?php
// Configuración de la base de datos
require_once '../backend/config/config.php';
require_once '../backend/autologin.php';

// Si ya está logeado, se redirige
if(isset($_SESSION['usuario'])){
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Notas</title>
    <!-- Script para aplicar tema (claro/oscuro) -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
            }
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- iconos de google -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Hoja de estilos personalizada -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main>
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-5">
                    <div class="card register-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h3 class="fw-bold">Iniciar Sesión</h3>
                                <p class="text-muted">Accede a tu cuenta para gestionar tus notas</p>
                            </div>

                            <!-- Mostrar errores si existen -->
                            <?php if(isset($_SESSION['errores_login'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>¡Error!</strong><br>
                                    <?php foreach($_SESSION['errores_login'] as $error): ?>
                                        • <?php echo htmlspecialchars($error); ?><br>
                                    <?php endforeach; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['errores_login']); ?>
                            <?php endif; ?>

                            <!-- Mostrar mensajes de éxito -->
                            <?php if(isset($_SESSION['mensaje_exito'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>¡Éxito!</strong><br>
                                    <?php echo htmlspecialchars($_SESSION['mensaje_exito']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['mensaje_exito']); ?>
                            <?php endif; ?>

                            <form action="../backend/login_proceso.php" method="POST" novalidate>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="usuario" class="form-label">Nombre de usuario <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Tu nombre de usuario" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" id="recordar" name="recordar">
                                                <label class="form-check-label" for="recordar">Recuérdame</label>
                                            </div>
                                            <a href="recuperar.php" class="text-decoration-none small">¿Olvidaste tu contraseña?</a>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                                    </div>
                                </div>
                            </form>
                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <p class="small">¿No tienes cuenta? <a href="registro.php" class="text-decoration-none fw-bold">Regístrate aquí</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JS de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Validaciones y manejo de autenticación -->
    <script src="../assets/js/autenticacion.js"></script>
</body>
</html>

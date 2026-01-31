<?php
//configuración de la base de datos
require_once '../backend/config/config.php';

//si ya esta logeado, se redirige
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
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- iconos de google -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined&display=swap" rel="stylesheet">

    <!-- Bootstrap/ css -->
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
                                <h3 class="fw-bold">Crear Cuenta</h3>
                                <p class="text-muted">Registrate para gestionar tus notas</p>
                            </div>

                            <!-- Mostrar errores si existen -->
                            <?php if(isset($_SESSION['errores_registro'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>¡Error!</strong><br>
                                    <?php foreach($_SESSION['errores_registro'] as $error): ?>
                                        • <?php echo htmlspecialchars($error); ?><br>
                                    <?php endforeach; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['errores_registro']); ?>
                            <?php endif; ?>

                            <form action="../backend/registro_proceso.php" method="POST" novalidate>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="nombre" class="form-label">Nombre de usuario</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="password" class="form-label">Contraseña</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="" required>
                                        
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="" required>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">Registrarse</button>
                                    </div>
                                </div>
                            </form>
                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <p class="small">¿Ya tienes una cuenta? <a href="login.php" class="text-decoration-none fw-bold">Inicia sesión</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </main>
    <!-- js de bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- validaciones y manejo de autenticación -->
    <script src="../assets/js/autenticacion.js"></script>
</body>
</html>
<?php 
session_start(); 
$token = isset($_GET['token']) ? $_GET['token'] : '';
if(empty($token)) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Notas</title>
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
                        <h3>Nueva Contraseña</h3>
                        <p class="text-muted">Crea una contraseña segura</p>
                    </div>

                    <?php if(isset($_SESSION['errores_restablecer'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>¡Error!</strong><br>
                            <?php 
                                foreach($_SESSION['errores_restablecer'] as $error) echo "• $error<br>"; 
                                unset($_SESSION['errores_restablecer']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="../backend/recuperar_restablecer.php" method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="form-text">Mínimo 8 caracteres.</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary w-100">Cambiar Contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Notas</title>
    <!-- Script para aplicar tema (claro/oscuro) -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme) {
                document.documentElement.setAttribute('data-theme', theme);
            }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                        <h3>Recuperar Contraseña</h3>
                        <p class="text-muted">Ingresa tu correo para recibir instrucciones</p>
                        <div class="alert alert-info py-2 mt-3 mb-0 small">
                            <i class="fas fa-info-circle me-1"></i> Nota: Revisa tu carpeta de <strong>Spam</strong> o Correo no deseado si no recibes el email.
                        </div>
                    </div>

                    <?php if(isset($_SESSION['errores_recuperar'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>¡Error!</strong><br>
                            <?php 
                                foreach($_SESSION['errores_recuperar'] as $error) echo "• $error<br>"; 
                                unset($_SESSION['errores_recuperar']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="../backend/recuperar_solicitud.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="ejemplo@correo.com">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary w-100">Enviar Enlace</button>
                        </div>
                    </form>
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <p class="small"><a href="login.php" class="text-decoration-none fw-bold">Volver al Login</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
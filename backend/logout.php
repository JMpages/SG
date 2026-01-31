<?php
// Iniciar sesión
session_start();

// Eliminar cookie de autologin si existe
if (isset($_COOKIE['usuario_sesion'])) {
    setcookie('usuario_sesion', '', time() - 3600, '/', '', false, true);
    unset($_COOKIE['usuario_sesion']);
}

// Destruir la sesión
session_destroy();

// Redirigir a login
header("Location: ../view/login.php");
exit();
?>

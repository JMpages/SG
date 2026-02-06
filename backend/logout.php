<?php
require_once 'config/config.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Eliminar cookie de "Recordarme" si existe
if (isset($_COOKIE['usuario_sesion'])) {
    setcookie('usuario_sesion', '', time() - 3600, '/');
}

// Redirigir al login
header("Location: ../view/login.php");
exit();
?>
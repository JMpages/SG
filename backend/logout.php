<?php
// Iniciar sesión
session_start();

// Destruir la sesión
session_destroy();

// Redirigir a login
header("Location: ../view/login.php");
exit();
?>

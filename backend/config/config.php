<?php
$url = "mysql:host=localhost;dbname=sistema_notas;charset=utf8mb4";
$usuario = "root";
$contrasena = "";

try {
    $pdo = new PDO($url, $usuario, $contrasena);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos.");
}

// Clave secreta para firmar la cookie 'recuerdame'. Cambiar en producción.
$app_secret = getenv('APP_SECRET') ?: 'dev_change_this_secret_please_replace';

session_start();
?>

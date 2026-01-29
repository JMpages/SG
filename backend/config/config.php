<?php
// Configuración de la base de datos
$url = "mysql:host=localhost;dbname=sistema_notas;charset=utf8mb4";
$usuario = "root";
$contrasena = "";

try {
    $pdo = new PDO($url, $usuario, $contrasena);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Iniciar sesión
session_start();
?>
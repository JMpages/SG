<?php
require_once 'config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: ../view/login.php");
    exit();
}

$token = isset($_POST['token']) ? $_POST['token'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

$errores = [];

if(empty($token)) $errores[] = "Token inválido.";
if(strlen($password) < 8) $errores[] = "La contraseña debe tener al menos 8 caracteres.";
if($password !== $password_confirm) $errores[] = "Las contraseñas no coinciden.";

if(!empty($errores)){
    $_SESSION['errores_restablecer'] = $errores;
    header("Location: ../view/restablecer.php?token=" . urlencode($token));
    exit();
}

try {
    // Verificar token válido y no expirado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacion = ? AND token_expiracion > NOW()");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$usuario) {
        $_SESSION['errores_login'] = ["El enlace de recuperación es inválido o ha expirado."];
        header("Location: ../view/login.php");
        exit();
    }

    // Actualizar contraseña y limpiar token
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $update = $pdo->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE id = ?");
    $update->execute([$hash, $usuario['id']]);

    $_SESSION['mensaje_exito'] = "¡Contraseña restablecida! Ya puedes iniciar sesión.";
    header("Location: ../view/login.php");

} catch(PDOException $e) {
    $_SESSION['errores_restablecer'] = ["Error: " . $e->getMessage()];
    header("Location: ../view/restablecer.php?token=" . urlencode($token));
}
?>
<?php
// Configuración de la base de datos
require_once 'config/config.php';

// Si ya está logeado, se redirige
if(isset($_SESSION['usuario'])){
    header("Location: ../view/dashboard.php");
    exit();
}

// Validar que se recibió una petición POST
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: ../view/login.php");
    exit();
}

// Obtener y sanitizar datos
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$recordar = isset($_POST['recordar']) ? true : false;

// Array para almacenar errores
$errores = [];

// Validaciones básicas
if(empty($usuario)){
    $errores[] = "El nombre de usuario es requerido";
}

if(empty($password)){
    $errores[] = "La contraseña es requerida";
}

// Si hay errores, redirigir con mensaje
if(!empty($errores)){
    $_SESSION['errores_login'] = $errores;
    header("Location: ../view/login.php");
    exit();
}

// Buscar el usuario en la BD
try {
    $sql = "SELECT id, username, password FROM usuarios WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario]);
    
    $usuario_bd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si el usuario existe
    if(!$usuario_bd){
        $errores[] = "El nombre de usuario o contraseña es incorrecto";
    } else {
        // Verificar la contraseña con password_verify
        if(!password_verify($password, $usuario_bd['password'])){
            $errores[] = "El nombre de usuario o contraseña es incorrecto";
        }
    }
    
    // Si hay errores, redirigir
    if(!empty($errores)){
        $_SESSION['errores_login'] = $errores;
        header("Location: ../view/login.php");
        exit();
    }
    
    // Si todo es correcto, crear la sesión
    $_SESSION['usuario'] = $usuario_bd['username'];
    $_SESSION['usuario_id'] = $usuario_bd['id'];
    $_SESSION['mensaje_exito'] = "¡Bienvenido " . htmlspecialchars($usuario_bd['username']) . "!";
    
    // Si marcó "Recuérdame", crear cookies (opcional, más seguro sin esto)
    if($recordar){
        // Crear un token de sesión más seguro
        setcookie('usuario_sesion', $usuario_bd['id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }
    
    // Redirigir al dashboard
    header("Location: ../view/dashboard.php");
    exit();
    
} catch(PDOException $e){
    $errores[] = "Error en la base de datos: " . $e->getMessage();
    $_SESSION['errores_login'] = $errores;
    header("Location: ../view/login.php");
    exit();
}
?>

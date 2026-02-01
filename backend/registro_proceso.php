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
    header("Location: ../view/registro.php");
    exit();
}

// Obtener y sanitizar datos
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

// Array para almacenar errores
$errores = [];

// Validaciones
// 1. Validar que los campos no estén vacíos
if(empty($nombre)){
    $errores[] = "El nombre de usuario es requerido";
}

if(empty($password)){
    $errores[] = "La contraseña es requerida";
}

if(empty($password_confirm)){
    $errores[] = "La confirmación de contraseña es requerida";
}

// 2. Si no hay errores, continuar con validaciones más profundas
if(empty($errores)){
    
    // Validar longitud del nombre de usuario (3-50 caracteres)
    if(strlen($nombre) < 3){
        $errores[] = "El nombre de usuario debe tener al menos 3 caracteres";
    }
    
    if(strlen($nombre) > 50){
        $errores[] = "El nombre de usuario no puede exceder 50 caracteres";
    }
    
    // Validar que el nombre contiene solo caracteres alfanuméricos, guiones y guiones bajos
    if(!preg_match('/^[a-zA-Z0-9_-]+$/', $nombre)){
        $errores[] = "El nombre de usuario solo puede contener letras, números, guiones y guiones bajos";
    }
    
    // Validar longitud de la contraseña (mínimo 6 caracteres)
    if(strlen($password) < 6){
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Validar que las contraseñas coinciden
    if($password !== $password_confirm){
        $errores[] = "Las contraseñas no coinciden";
    }
    
    // Validar que el nombre de usuario no exista en la BD
    if(empty($errores)){
        try {
            $sql = "SELECT id FROM usuarios WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre]);
            
            if($stmt->rowCount() > 0){
                $errores[] = "El nombre de usuario ya está registrado";
            }
        } catch(PDOException $e){
            $errores[] = "Error al validar el usuario: " . $e->getMessage();
        }
    }
}

// Si hay errores, redirigir con mensaje
if(!empty($errores)){
    $_SESSION['errores_registro'] = $errores;
    header("Location: ../view/registro.php");
    exit();
}

// Si no hay errores, proceder con el registro
try {
    // Hashear la contraseña
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Insertar el nuevo usuario
    $sql = "INSERT INTO usuarios (username, password) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $password_hash]);
    
    $usuario_id = $pdo->lastInsertId();

    // Si el registro fue exitoso, establecer sesión y redirigir
    $_SESSION['usuario'] = $nombre;
    $_SESSION['usuario_id'] = $usuario_id;
    $_SESSION['mensaje_exito'] = "¡Registro exitoso! Bienvenido " . htmlspecialchars($nombre);
    
    // Guardar log de registro en la tabla registro_logs
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Detectar navegador
        $navegador = 'Otro';
        if (strpos($ua, 'Edg') !== false) $navegador = 'Microsoft Edge';
        elseif (strpos($ua, 'Chrome') !== false) $navegador = 'Google Chrome';
        elseif (strpos($ua, 'Firefox') !== false) $navegador = 'Mozilla Firefox';
        elseif (strpos($ua, 'Safari') !== false) $navegador = 'Safari';
        elseif (strpos($ua, 'OPR') !== false || strpos($ua, 'Opera') !== false) $navegador = 'Opera';
        
        // Detectar sistema operativo
        $so = 'Otro';
        if (preg_match('/windows/i', $ua)) $so = 'Windows';
        elseif (preg_match('/macintosh|mac os x/i', $ua)) $so = 'Mac OS';
        elseif (preg_match('/linux/i', $ua)) $so = 'Linux';
        elseif (preg_match('/android/i', $ua)) $so = 'Android';
        elseif (preg_match('/iphone|ipad/i', $ua)) $so = 'iOS';
        
        $dispositivo = preg_match('/mobile|tablet|android|iphone|ipad/i', $ua) ? 'Móvil/Tablet' : 'Escritorio';
        
        $sqlLog = "INSERT INTO registro_logs (usuario_id, username, ip, navegador, sistema_operativo, dispositivo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtLog = $pdo->prepare($sqlLog);
        $stmtLog->execute([$usuario_id, $nombre, $ip, $navegador, $so, $dispositivo]);
    } catch (Exception $e) {
        // Continuar aunque falle el log para no interrumpir el registro del usuario
    }

    header("Location: ../view/dashboard.php");
    exit();
    
} catch(PDOException $e){
    $errores[] = "Error al registrar el usuario: " . $e->getMessage();
    $_SESSION['errores_registro'] = $errores;
    header("Location: ../view/registro.php");
    exit();
}
?>

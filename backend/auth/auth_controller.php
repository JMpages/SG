<?php
/**
 * Controlador Único de Autenticación y Perfil
 * Gestiona: Login, Registro, Logout, Gestión de Perfil y Recuperación
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once '../config/config.php';

header('Content-Type: application/json');

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    $accion = $input['accion'] ?? '';
} else {
    $accion = $_GET['accion'] ?? '';
}

try {
    switch ($accion) {
        case 'login':
            $usernameOrEmail = $_POST['usuario'] ?? '';
            $password = $_POST['password'] ?? '';
            $recuerdame = isset($_POST['recuerdame']);

            if (!$usernameOrEmail || !$password) {
                $_SESSION['error_login'] = "Datos incompletos";
                header("Location: ../../pages/login.php");
                exit;
            }

            // Buscar por username o email
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ? OR email = ?");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario'] = $user['username'];
                
                if ($recuerdame) {
                    $exp_time = time() + (86400 * 30);
                    // Si hay $app_secret (definido en config), crear cookie firmada que no requiere DB
                    if (!empty($app_secret)) {
                        $payload = $user['id'] . '|' . $exp_time;
                        $hmac = hash_hmac('sha256', $payload, $app_secret);
                        $cookie_value = base64_encode($payload . '|' . $hmac);
                        setcookie('recuerdame', $cookie_value, $exp_time, "/", "", false, true);
                    } else {
                        // Fallback: token aleatorio + intentar guardar en DB (por compatibilidad)
                        $token = bin2hex(random_bytes(32));
                        setcookie('recuerdame', $token, $exp_time, "/", "", false, true);
                        try {
                            $expiracion = date('Y-m-d H:i:s', $exp_time);
                            $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = ?, remember_expiracion = ? WHERE id = ?");
                            $stmt->execute([$token, $expiracion, $user['id']]);
                        } catch (Exception $e) {
                            // Ignorar si la columna no existe
                        }
                    }
                }
                
                header("Location: ../../pages/dashboard.php");
                exit;
            } else {
                $_SESSION['error_login'] = "Usuario o contraseña incorrectos";
                header("Location: ../../pages/login.php");
                exit;
            }
            break;

        case 'registro':
            $username = trim($_POST['nombre'] ?? '');
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (!$username || !$password || !$password_confirm) {
                $_SESSION['errores_registro'] = ["Datos obligatorios incompletos"];
                header("Location: ../../pages/registro.php");
                exit;
            }

            if ($password !== $password_confirm) {
                $_SESSION['errores_registro'] = ["Las contraseñas no coinciden"];
                header("Location: ../../pages/registro.php");
                exit;
            }

            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? OR (email IS NOT NULL AND email = ?)");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $_SESSION['errores_registro'] = ["El usuario o email ya está registrado"];
                header("Location: ../../pages/registro.php");
                exit;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email ?: null, $hash]);

            $_SESSION['usuario_id'] = $pdo->lastInsertId();
            $_SESSION['usuario'] = $username;

            header("Location: ../../pages/dashboard.php");
            exit;
            break;

        case 'logout':
            // Limpiar sesiones y cookies (incluyendo legacy)
            if (isset($_SESSION['usuario_id'])) {
                try {
                    $pdo->prepare("UPDATE usuarios SET remember_token = NULL, remember_expiracion = NULL WHERE id = ?")->execute([$_SESSION['usuario_id']]);
                } catch (Exception $e) {
                    // ignorar
                }
            }
            session_destroy();
            setcookie('recuerdame', '', time() - 3600, '/');
            setcookie('usuario_sesion', '', time() - 3600, '/');
            header("Location: ../../pages/login.php");
            exit;
            break;

        case 'perfil_ver':
            if (!isset($_SESSION['usuario_id'])) throw new Exception('No autorizado', 401);
            $stmt = $pdo->prepare("SELECT username, email FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => ['username' => $user['username'], 'email' => $user['email'] ?? '']]);
            break;

        case 'perfil_actualizar':
            if (!isset($_SESSION['usuario_id'])) throw new Exception('No autorizado', 401);
            $username = trim($input['nombre'] ?? '');
            if (!$username) throw new Exception('El nombre de usuario es requerido', 400);

            $stmt = $pdo->prepare("UPDATE usuarios SET username = ? WHERE id = ?");
            $stmt->execute([$username, $_SESSION['usuario_id']]);
            $_SESSION['usuario'] = $username;
            echo json_encode(['status' => 'success', 'message' => 'Perfil actualizado']);
            break;

        case 'perfil_cambiar_password':
            if (!isset($_SESSION['usuario_id'])) throw new Exception('No autorizado', 401);
            $actual = $input['password_actual'] ?? '';
            $nueva = $input['password_nueva'] ?? '';
            
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $user = $stmt->fetch();
            
            if (!password_verify($actual, $user['password'])) throw new Exception('Contraseña actual incorrecta', 400);
            
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['usuario_id']]);
            echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada']);
            break;

        case 'perfil_eliminar_cuenta':
            if (!isset($_SESSION['usuario_id'])) throw new Exception('No autorizado', 401);
            $password = $input['password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $user = $stmt->fetch();
            
            if (!password_verify($password, $user['password'])) throw new Exception('Contraseña incorrecta', 400);
            
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$_SESSION['usuario_id']]);
            session_destroy();
            echo json_encode(['status' => 'success', 'message' => 'Cuenta eliminada']);
            break;

        case 'obtener_actividad':
            // Esta funcionalidad parece requerir una tabla de logs que podrías no tener o ser simulada
            // Por ahora retornaremos un arreglo vacío para no romper perfil.js
            echo json_encode(['status' => 'success', 'data' => []]);
            break;

        default:
            throw new Exception('Acción no permitida');
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

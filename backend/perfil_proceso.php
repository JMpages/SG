<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once 'config/config.php';
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$input = json_decode(file_get_contents('php://input'), true);
$accion = $input['accion'] ?? '';

try {
    // 1. Obtener datos del perfil
    if ($accion === 'obtener_datos') {
        $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $user]);
    } 
    // 2. Actualizar información básica (Nombre de usuario)
    elseif ($accion === 'actualizar_perfil') {
        $nuevo_nombre = trim($input['nombre'] ?? '');
        
        if (empty($nuevo_nombre) || strlen($nuevo_nombre) < 3 || strlen($nuevo_nombre) > 50) {
            throw new Exception('El nombre debe tener entre 3 y 50 caracteres.');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $nuevo_nombre)) {
            throw new Exception('El nombre solo puede contener letras, números, guiones y guiones bajos.');
        }

        // Verificar que el nombre no esté en uso por otro usuario
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $stmtCheck->execute([$nuevo_nombre, $usuario_id]);
        if ($stmtCheck->rowCount() > 0) {
            throw new Exception('El nombre de usuario ya está registrado.');
        }

        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET username = ? WHERE id = ?");
        $stmtUpdate->execute([$nuevo_nombre, $usuario_id]);
        
        $_SESSION['usuario'] = $nuevo_nombre; // Actualizar sesión
        
        echo json_encode(['status' => 'success', 'message' => 'Perfil actualizado correctamente.']);
    } 
    // 3. Cambiar contraseña
    elseif ($accion === 'cambiar_password') {
        $pass_actual = $input['password_actual'] ?? '';
        $pass_nueva = $input['password_nueva'] ?? '';
        $pass_confirm = $input['password_confirm'] ?? '';

        if (empty($pass_actual) || empty($pass_nueva)) {
            throw new Exception('Todos los campos son obligatorios.');
        }

        if (strlen($pass_nueva) < 6) {
            throw new Exception('La nueva contraseña debe tener al menos 6 caracteres.');
        }

        if ($pass_nueva !== $pass_confirm) {
            throw new Exception('Las nuevas contraseñas no coinciden.');
        }

        // Verificar contraseña actual
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($pass_actual, $user['password'])) {
            throw new Exception('La contraseña actual es incorrecta.');
        }

        // Actualizar contraseña
        $hash = password_hash($pass_nueva, PASSWORD_BCRYPT);
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmtUpdate->execute([$hash, $usuario_id]);

        echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada correctamente.']);
    } 
    // 4. Eliminar cuenta
    elseif ($accion === 'eliminar_cuenta') {
        $password = $input['password'] ?? '';
        
        // Verificar contraseña antes de eliminar
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($password, $user['password'])) {
            throw new Exception('Contraseña incorrecta. No se pudo eliminar la cuenta.');
        }

        $pdo->beginTransaction();
        // Eliminar datos relacionados (Materias, Logs, Usuario)
        // Se asume que las tablas hijas de materias (notas, criterios) tienen ON DELETE CASCADE, 
        // pero eliminamos materias explícitamente para asegurar limpieza.
        $pdo->prepare("DELETE FROM materias WHERE usuario_id = ?")->execute([$usuario_id]);
        $pdo->prepare("DELETE FROM registro_logs WHERE usuario_id = ?")->execute([$usuario_id]);
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$usuario_id]);
        $pdo->commit();
        
        session_destroy(); // Destruir sesión
        echo json_encode(['status' => 'success', 'message' => 'Cuenta eliminada permanentemente.']);
    }
    // 5. Obtener historial de actividad
    elseif ($accion === 'obtener_actividad') {
        // Intentamos obtener los últimos 10 registros. 
        // Asumimos que existe una columna de fecha (created_at o fecha), si no, ordenamos por ID.
        try {
            $stmt = $pdo->prepare("SELECT ip, navegador, sistema_operativo, dispositivo, fecha FROM registro_logs WHERE usuario_id = ? ORDER BY id DESC LIMIT 10");
            $stmt->execute([$usuario_id]);
        } catch (PDOException $e) {
            // Si falla (por ejemplo si la columna fecha no existe), intentamos sin fecha explícita ordenando por ID
            $stmt = $pdo->prepare("SELECT ip, navegador, sistema_operativo, dispositivo FROM registro_logs WHERE usuario_id = ? ORDER BY id DESC LIMIT 10");
            $stmt->execute([$usuario_id]);
        }
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $logs]);
    }
    // 6. Exportar datos (GDPR)
    elseif ($accion === 'exportar_datos') {
        // Recopilar toda la info del usuario
        $export = [];
        
        // Usuario
        $stmtUser = $pdo->prepare("SELECT username, created_at FROM usuarios WHERE id = ?");
        $stmtUser->execute([$usuario_id]);
        $export['perfil'] = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        // Materias y Criterios
        $stmtMat = $pdo->prepare("SELECT * FROM materias WHERE usuario_id = ?");
        $stmtMat->execute([$usuario_id]);
        $materias = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
        
        // Para un export simple, enviamos las materias. 
        // En un sistema real, haríamos un loop para sacar notas de cada materia, 
        // pero esto sirve como base profesional.
        $export['materias'] = $materias;
        
        echo json_encode(['status' => 'success', 'data' => $export]);
    } else {
        throw new Exception('Acción no válida.');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
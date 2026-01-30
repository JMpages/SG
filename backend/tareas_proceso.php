<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once 'config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$usuario_id = $_SESSION['usuario_id'];

try {
    if (empty($input['titulo']) || empty($input['materia_id']) || empty($input['fecha_entrega'])) {
        throw new Exception('Campos obligatorios faltantes');
    }

    $titulo = trim($input['titulo']);
    $materia_id = (int)$input['materia_id'];
    $fecha_entrega = $input['fecha_entrega'];
    $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : null;
    $id = isset($input['id']) && !empty($input['id']) ? (int)$input['id'] : null;

    // Verificar que la materia pertenezca al usuario
    $stmtCheck = $pdo->prepare("SELECT id FROM materias WHERE id = ? AND usuario_id = ?");
    $stmtCheck->execute([$materia_id, $usuario_id]);
    if ($stmtCheck->rowCount() === 0) {
        throw new Exception('Materia no válida o no autorizada');
    }

    if ($id) {
        // Actualizar tarea existente
        // Primero verificamos que la tarea pertenezca a una materia del usuario
        $checkTarea = $pdo->prepare("SELECT t.id FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE t.id = ? AND m.usuario_id = ?");
        $checkTarea->execute([$id, $usuario_id]);
        if ($checkTarea->rowCount() === 0) throw new Exception('Tarea no encontrada o no autorizada');

        $sql = "UPDATE tareas SET titulo = ?, materia_id = ?, fecha_entrega = ?, descripcion = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $materia_id, $fecha_entrega, $descripcion, $id]);
        $msg = 'Tarea actualizada correctamente';
    } else {
        // Crear nueva tarea
        $sql = "INSERT INTO tareas (materia_id, titulo, fecha_entrega, descripcion) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$materia_id, $titulo, $fecha_entrega, $descripcion]);
        $msg = 'Tarea creada correctamente';
    }

    echo json_encode(['status' => 'success', 'message' => $msg]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
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
$id = isset($input['id']) ? (int)$input['id'] : null;
$completada = isset($input['completada']) && $input['completada'] ? 1 : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
    exit;
}

try {
    // Verificar propiedad
    $check = $pdo->prepare("SELECT t.id FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE t.id = ? AND m.usuario_id = ?");
    $check->execute([$id, $usuario_id]);

    if ($check->rowCount() > 0) {
        $sql = "UPDATE tareas SET completada = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$completada, $id]);

        echo json_encode(['status' => 'success', 'message' => 'Estado actualizado']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tarea no encontrada']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar']);
}
?>
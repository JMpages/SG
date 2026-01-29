<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config/config.php';

header('Content-Type: application/json');

// if (!isset($_SESSION['usuario_id'])) {
//     http_response_code(403);
//     echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de materia no proporcionado.']);
    exit;
}

$materia_id = filter_var($data['id'], FILTER_VALIDATE_INT);
$usuario_id = $_SESSION['usuario_id'];

if (!$materia_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de materia inválido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Eliminar la materia. Se asume que la base de datos tiene configurado
    // 'ON DELETE CASCADE' en las claves foráneas de 'criterios_evaluacion' y 'notas'
    // para que se borren en cadena.
    $sqlDelete = "DELETE FROM materias WHERE id = ? AND usuario_id = ?";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([$materia_id, $usuario_id]);

    if ($stmtDelete->rowCount() === 0) {
        throw new Exception('Materia no encontrada o no tienes permiso para eliminarla.', 404);
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Materia eliminada correctamente.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
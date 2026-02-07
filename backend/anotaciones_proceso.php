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

$usuario_id = $_SESSION['usuario_id'];
$input = json_decode(file_get_contents('php://input'), true);
$accion = $input['accion'] ?? '';

try {
    switch ($accion) {
        case 'obtener':
            // Obtener todas las notas del usuario
            $sql = "SELECT * FROM anotaciones WHERE usuario_id = ? ORDER BY fecha_actualizacion DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario_id]);
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $notas]);
            break;

        case 'guardar':
            // Crear o Actualizar nota
            $id = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : null;
            $titulo = trim($input['titulo'] ?? '');
            $contenido = $input['contenido'] ?? '';
            $texto = $input['texto'] ?? '';
            $materia_id = !empty($input['materia_id']) ? (int)$input['materia_id'] : null;
            $color = $input['color'] ?? 'white';

            if (empty($titulo) && empty($texto)) {
                throw new Exception('La nota debe tener al menos un título o contenido.');
            }

            if ($id) {
                $sql = "UPDATE anotaciones SET titulo = ?, contenido = ?, texto = ?, materia_id = ?, color = ? WHERE id = ? AND usuario_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titulo, $contenido, $texto, $materia_id, $color, $id, $usuario_id]);
                $msg = 'Nota actualizada correctamente';
            } else {
                $sql = "INSERT INTO anotaciones (usuario_id, titulo, contenido, texto, materia_id, color) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$usuario_id, $titulo, $contenido, $texto, $materia_id, $color]);
                $msg = 'Nota creada correctamente';
            }
            echo json_encode(['status' => 'success', 'message' => $msg]);
            break;

        case 'eliminar':
            // Eliminar nota
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            if (!$id) throw new Exception('ID de nota inválido');

            $sql = "DELETE FROM anotaciones WHERE id = ? AND usuario_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $usuario_id]);
            echo json_encode(['status' => 'success', 'message' => 'Nota eliminada correctamente']);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
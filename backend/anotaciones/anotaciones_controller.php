<?php
/**
 * Controlador de Anotaciones
 * Gestiona: Notas personales (texto rico y dibujos)
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
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
        case 'listar':
            $stmt = $pdo->prepare("SELECT * FROM anotaciones WHERE usuario_id = ? ORDER BY fecha_actualizacion DESC");
            $stmt->execute([$usuario_id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'guardar':
            $id = isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : null;
            $titulo = trim($input['titulo'] ?? '');
            $contenido = $input['contenido'] ?? '';
            $texto = $input['texto'] ?? '';
            $materia_id = !empty($input['materia_id']) ? (int)$input['materia_id'] : null;
            $color = $input['color'] ?? 'white';

            if (empty($titulo) && empty($texto)) throw new Exception('La nota debe tener título o contenido');

            if ($id) {
                $stmt = $pdo->prepare("UPDATE anotaciones SET titulo = ?, contenido = ?, texto = ?, materia_id = ?, color = ? WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$titulo, $contenido, $texto, $materia_id, $color, $id, $usuario_id]);
                echo json_encode(['status' => 'success', 'message' => 'Anotación actualizada', 'id' => $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO anotaciones (usuario_id, titulo, contenido, texto, materia_id, color) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$usuario_id, $titulo, $contenido, $texto, $materia_id, $color]);
                echo json_encode(['status' => 'success', 'message' => 'Anotación creada', 'id' => $pdo->lastInsertId()]);
            }
            break;

        case 'eliminar':
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            $stmt = $pdo->prepare("DELETE FROM anotaciones WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $usuario_id]);
            echo json_encode(['status' => 'success', 'message' => 'Anotación eliminada']);
            break;

        case 'eliminar_lote':
            $ids = $input['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                throw new Exception('No se seleccionaron notas para eliminar');
            }
            
            // Validar y sanear IDs
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge($ids, [$usuario_id]);
            
            $stmt = $pdo->prepare("DELETE FROM anotaciones WHERE id IN ($placeholders) AND usuario_id = ?");
            $stmt->execute($params);
            
            echo json_encode(['status' => 'success', 'message' => 'Notas eliminadas correctamente']);
            break;

        default:
            throw new Exception('Acción no permitida en este controlador');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

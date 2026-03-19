<?php
/**
 * Controlador Único de Tareas
 * Gestiona: Obtener, Guardar (Crear/Editar), Eliminar y Marcar tareas
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

// Obtener datos según el método
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
            $materia_id = filter_input(INPUT_GET, 'materia_id', FILTER_VALIDATE_INT);
            $estado = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_STRING); // 'pendientes', 'completadas', 'vencidas', 'todas'

            $sql = "SELECT t.*, 
                           m.nombre as materia_nombre,
                           ce.nombre as criterio_nombre
                    FROM tareas t 
                    JOIN materias m ON t.materia_id = m.id 
                    LEFT JOIN criterios_evaluacion ce ON t.criterio_id = ce.id
                    WHERE m.usuario_id = ?";
            
            $params = [$usuario_id];

            if ($materia_id) {
                $sql .= " AND t.materia_id = ?";
                $params[] = $materia_id;
            }

            if ($estado === 'pendientes') {
                $sql .= " AND t.completada = 0";
            } elseif ($estado === 'completadas') {
                $sql .= " AND t.completada = 1";
            } elseif ($estado === 'vencidas') {
                $sql .= " AND t.completada = 0 AND t.fecha_entrega < CURDATE()";
            }

            $sql .= " ORDER BY t.completada ASC, t.fecha_entrega ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $tareas]);
            break;

        case 'guardar':
            if (empty($input['titulo']) || empty($input['materia_id']) || empty($input['fecha_entrega'])) {
                throw new Exception('Campos obligatorios faltantes');
            }

            $titulo = trim($input['titulo']);
            $materia_id = (int)$input['materia_id'];
            $fecha_entrega = $input['fecha_entrega'];
            $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : null;
            $id = isset($input['id']) && !empty($input['id']) ? (int)$input['id'] : null;
            
            $es_calificada = !empty($input['es_calificada']) ? 1 : 0;
            $criterio_id = !empty($input['criterio_id']) ? (int)$input['criterio_id'] : null;
            $numero_evaluacion = !empty($input['numero_evaluacion']) ? (int)$input['numero_evaluacion'] : null;

            // Verificar que la materia pertenezca al usuario
            $stmtCheck = $pdo->prepare("SELECT id FROM materias WHERE id = ? AND usuario_id = ?");
            $stmtCheck->execute([$materia_id, $usuario_id]);
            if ($stmtCheck->rowCount() === 0) {
                throw new Exception('Materia no válida o no autorizada');
            }

            // Validar criterio si es calificada
            if ($es_calificada && $criterio_id) {
                $stmtCrit = $pdo->prepare("SELECT id FROM criterios_evaluacion WHERE id = ? AND materia_id = ?");
                $stmtCrit->execute([$criterio_id, $materia_id]);
                if ($stmtCrit->rowCount() === 0) {
                    throw new Exception("El criterio seleccionado no es válido para esta materia.");
                }
            } else {
                $criterio_id = null;
                $numero_evaluacion = null;
            }

            if ($id) {
                // Actualizar
                $checkTarea = $pdo->prepare("SELECT t.id FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE t.id = ? AND m.usuario_id = ?");
                $checkTarea->execute([$id, $usuario_id]);
                if ($checkTarea->rowCount() === 0) throw new Exception('Tarea no encontrada o no autorizada');

                $sql = "UPDATE tareas SET titulo = ?, materia_id = ?, fecha_entrega = ?, descripcion = ?, es_calificada = ?, criterio_id = ?, numero_evaluacion = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titulo, $materia_id, $fecha_entrega, $descripcion, $es_calificada, $criterio_id, $numero_evaluacion, $id]);
                $msg = 'Tarea actualizada correctamente';
            } else {
                // Crear
                $sql = "INSERT INTO tareas (materia_id, titulo, fecha_entrega, descripcion, es_calificada, criterio_id, numero_evaluacion) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$materia_id, $titulo, $fecha_entrega, $descripcion, $es_calificada, $criterio_id, $numero_evaluacion]);
                $msg = 'Tarea creada correctamente';
            }

            echo json_encode(['status' => 'success', 'message' => $msg]);
            break;

        case 'eliminar':
            $id = isset($input['id']) ? (int)$input['id'] : null;
            if (!$id) throw new Exception('ID de tarea no proporcionado');

            $check = $pdo->prepare("SELECT t.id FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE t.id = ? AND m.usuario_id = ?");
            $check->execute([$id, $usuario_id]);
            
            if ($check->rowCount() > 0) {
                $stmt = $pdo->prepare("DELETE FROM tareas WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'success', 'message' => 'Tarea eliminada']);
            } else {
                throw new Exception('Tarea no encontrada o no autorizada');
            }
            break;

        case 'marcar':
            $id = isset($input['id']) ? (int)$input['id'] : null;
            $completada = isset($input['completada']) && $input['completada'] ? 1 : 0;
            if (!$id) throw new Exception('ID de tarea no proporcionado');

            $check = $pdo->prepare("SELECT t.id FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE t.id = ? AND m.usuario_id = ?");
            $check->execute([$id, $usuario_id]);

            if ($check->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE tareas SET completada = ? WHERE id = ?");
                $stmt->execute([$completada, $id]);
                echo json_encode(['status' => 'success', 'message' => 'Estado actualizado']);
            } else {
                throw new Exception('Tarea no encontrada o no autorizada');
            }
            break;

        default:
            throw new Exception('Acción "' . $accion . '" no reconocida');
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

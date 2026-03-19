<?php
/**
 * Controlador de Calificaciones Académicas
 * Gestiona exclusivamente: Notas numéricas de evaluaciones
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
        case 'guardar_calificaciones':
            if (!isset($input['materia_id']) || !isset($input['notas'])) throw new Exception('Datos inválidos');
            
            $materia_id = (int)$input['materia_id'];
            $notas_a_guardar = $input['notas'];
            $es_simulacion = isset($input['es_simulacion']) && $input['es_simulacion'] ? 1 : 0;

            $pdo->beginTransaction();
            $stmtCheck = $pdo->prepare("SELECT m.id FROM materias m JOIN criterios_evaluacion ce ON m.id = ce.materia_id WHERE m.usuario_id = ? AND ce.id = ?");
            $sql = "INSERT INTO notas (criterio_id, numero_evaluacion, calificacion, es_simulacion) 
                    VALUES (:criterio_id, :numero_evaluacion, :calificacion, :es_simulacion)
                    ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion)";
            $stmt = $pdo->prepare($sql);

            $max_evaluaciones = [];
            foreach ($notas_a_guardar as $nota) {
                $criterio_id = (int)$nota['criterio_id'];
                $num_eval = (int)$nota['numero_evaluacion'];
                $calif = $nota['calificacion'] === '' || $nota['calificacion'] === null ? null : (float)$nota['calificacion'];

                $stmtCheck->execute([$usuario_id, $criterio_id]);
                if ($stmtCheck->rowCount() === 0) throw new Exception("Permiso denegado para criterio $criterio_id");

                $stmt->execute([':criterio_id' => $criterio_id, ':numero_evaluacion' => $num_eval, ':calificacion' => $calif, ':es_simulacion' => $es_simulacion]);
                $max_evaluaciones[$criterio_id] = max($max_evaluaciones[$criterio_id] ?? 0, $num_eval);
            }

            $stmtUpdateStruct = $pdo->prepare("UPDATE criterios_evaluacion SET cantidad_evaluaciones = GREATEST(cantidad_evaluaciones, ?) WHERE id = ?");
            foreach ($max_evaluaciones as $cid => $max) {
                $stmtUpdateStruct->execute([$max, $cid]);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Calificaciones guardadas']);
            break;

        default:
            throw new Exception('Acción no permitida en este controlador');
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

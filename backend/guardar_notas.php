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

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['materia_id']) || !isset($data['notas'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

$materia_id = filter_var($data['materia_id'], FILTER_VALIDATE_INT);
$notas_a_guardar = $data['notas'];
$es_simulacion = isset($data['es_simulacion']) && $data['es_simulacion'] ? 1 : 0;
$usuario_id = $_SESSION['usuario_id'];

try {
    // Verificar que el usuario es dueño de la materia
    $sqlCheck = "SELECT m.id FROM materias m JOIN criterios_evaluacion ce ON m.id = ce.materia_id WHERE m.usuario_id = ? AND ce.id = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);

    $pdo->beginTransaction();

    // Usaremos INSERT ... ON DUPLICATE KEY UPDATE para eficiencia
    $sql = "INSERT INTO notas (criterio_id, numero_evaluacion, calificacion, es_simulacion) 
            VALUES (:criterio_id, :numero_evaluacion, :calificacion, :es_simulacion)
            ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion)";
    $stmt = $pdo->prepare($sql);

    foreach ($notas_a_guardar as $nota) {
        $criterio_id = filter_var($nota['criterio_id'], FILTER_VALIDATE_INT);
        $numero_evaluacion = filter_var($nota['numero_evaluacion'], FILTER_VALIDATE_INT);
        // Permitir NULL, así que no validamos como float si está vacío
        $calificacion = $nota['calificacion'] === '' || $nota['calificacion'] === null ? null : filter_var($nota['calificacion'], FILTER_VALIDATE_FLOAT);

        if ($criterio_id === false || $numero_evaluacion === false || $calificacion === false) {
             // Si la calificación es null, no es un error de filtro
            if ($calificacion !== null) {
                throw new Exception("Dato de nota inválido para criterio {$criterio_id}, evaluación {$numero_evaluacion}.");
            }
        }

        // Chequeo de seguridad extra
        $stmtCheck->execute([$usuario_id, $criterio_id]);
        if ($stmtCheck->rowCount() === 0) {
            throw new Exception("Permiso denegado para el criterio con ID {$criterio_id}.");
        }

        $stmt->execute([
            ':criterio_id' => $criterio_id,
            ':numero_evaluacion' => $numero_evaluacion,
            ':calificacion' => $calificacion,
            ':es_simulacion' => $es_simulacion
        ]);
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Calificaciones guardadas correctamente.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
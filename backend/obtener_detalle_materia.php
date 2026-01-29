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

$materia_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$materia_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de materia no válido']);
    exit;
}

try {
    $usuario_id = $_SESSION['usuario_id'];

    // 1. Verificar que la materia pertenece al usuario y obtener sus datos
    $sqlMateria = "SELECT * FROM materias WHERE id = ? AND usuario_id = ?";
    $stmtMateria = $pdo->prepare($sqlMateria);
    $stmtMateria->execute([$materia_id, $usuario_id]);
    $materia = $stmtMateria->fetch(PDO::FETCH_ASSOC);

    if (!$materia) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Materia no encontrada o no tienes permiso para verla.']);
        exit;
    }

    // 2. Obtener los criterios de evaluación de la materia
    $sqlCriterios = "SELECT * FROM criterios_evaluacion WHERE materia_id = ? ORDER BY id ASC";
    $stmtCriterios = $pdo->prepare($sqlCriterios);
    $stmtCriterios->execute([$materia_id]);
    $criterios = $stmtCriterios->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener todas las notas asociadas a esos criterios
    $criterio_ids = array_map(fn($c) => $c['id'], $criterios);
    $notas = [];

    if (!empty($criterio_ids)) {
        // Crear placeholders para la consulta IN (...)
        $placeholders = implode(',', array_fill(0, count($criterio_ids), '?'));
        
        $sqlNotas = "SELECT * FROM notas WHERE criterio_id IN ($placeholders)";
        $stmtNotas = $pdo->prepare($sqlNotas);
        $stmtNotas->execute($criterio_ids);
        $notasResult = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

        // Organizar las notas para un acceso fácil
        foreach ($notasResult as $nota) {
            $key = $nota['es_simulacion'] == 1 ? 'simulacion' : 'real';
            $notas[$nota['criterio_id']][$nota['numero_evaluacion']][$key] = $nota['calificacion'];
        }
    }

    // 4. Combinar todo en una sola estructura
    foreach ($criterios as &$criterio) {
        $criterio['notas'] = [];
        for ($i = 1; $i <= $criterio['cantidad_evaluaciones']; $i++) {
            $criterio['notas'][$i] = [
                'real' => $notas[$criterio['id']][$i]['real'] ?? null,
                'simulacion' => $notas[$criterio['id']][$i]['simulacion'] ?? null,
            ];
        }
    }
    
    $materia['criterios'] = $criterios;

    echo json_encode(['status' => 'success', 'data' => $materia]);

} catch (PDOException $e) {
    http_response_code(500);
    // En producción, loguear el error en lugar de mostrarlo
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
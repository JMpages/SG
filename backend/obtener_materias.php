<?php
// Habilitar reporte de errores pero no mostrarlos en el output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config/config.php';

header('Content-Type: application/json');

// if (!isset($_SESSION['usuario_id'])) {
//     http_response_code(403);
//     echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
//     exit;
// }

try {
    $usuario_id = $_SESSION['usuario_id'];
    
    // Obtener materias del usuario, ordenadas por la más reciente
    $sql = "SELECT * FROM materias WHERE usuario_id = ? ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener criterios para cada materia
    foreach ($materias as &$materia) {
        $sqlCriterios = "SELECT * FROM criterios_evaluacion WHERE materia_id = ? ORDER BY id ASC";
        $stmtCriterios = $pdo->prepare($sqlCriterios);
        $stmtCriterios->execute([$materia['id']]);
        $materia['criterios'] = $stmtCriterios->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['status' => 'success', 'data' => $materias]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al cargar datos: ' . $e->getMessage()]);
}
?>
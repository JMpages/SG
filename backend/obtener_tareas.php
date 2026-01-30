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
$materia_id = filter_input(INPUT_GET, 'materia_id', FILTER_VALIDATE_INT);
$estado = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_STRING); // 'pendientes', 'completadas', 'vencidas', 'todas'

try {
    $sql = "SELECT t.*, m.nombre as materia_nombre 
            FROM tareas t 
            JOIN materias m ON t.materia_id = m.id 
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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al cargar tareas: ' . $e->getMessage()]);
}
?>
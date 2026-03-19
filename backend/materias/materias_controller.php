<?php
/**
 * Controlador Único de Materias
 * Gestiona: Obtener Todas, Obtener Detalle, Guardar (Crear/Editar) y Eliminar materias
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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;
    $accion = $data['accion'] ?? '';
} else {
    $accion = $_GET['accion'] ?? '';
}

try {
    switch ($accion) {
        case 'listar':
            // Lógica de obtener_materias.php
            $sql = "SELECT * FROM materias WHERE usuario_id = ? ORDER BY id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario_id]);
            $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($materias as &$materia) {
                $sqlCriterios = "SELECT * FROM criterios_evaluacion WHERE materia_id = ? ORDER BY id ASC";
                $stmtCriterios = $pdo->prepare($sqlCriterios);
                $stmtCriterios->execute([$materia['id']]);
                $materia['criterios'] = $stmtCriterios->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode(['status' => 'success', 'data' => $materias]);
            break;

        case 'detalle':
            // Lógica de obtener_detalle_materia.php
            $materia_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$materia_id) throw new Exception('ID de materia no válido', 400);

            $sqlMateria = "SELECT * FROM materias WHERE id = ? AND usuario_id = ?";
            $stmtMateria = $pdo->prepare($sqlMateria);
            $stmtMateria->execute([$materia_id, $usuario_id]);
            $materia = $stmtMateria->fetch(PDO::FETCH_ASSOC);

            if (!$materia) throw new Exception('Materia no encontrada', 404);

            $sqlCriterios = "SELECT * FROM criterios_evaluacion WHERE materia_id = ? ORDER BY id ASC";
            $stmtCriterios = $pdo->prepare($sqlCriterios);
            $stmtCriterios->execute([$materia_id]);
            $criterios = $stmtCriterios->fetchAll(PDO::FETCH_ASSOC);

            $criterio_ids = array_map(fn($c) => $c['id'], $criterios);
            $notas = [];
            if (!empty($criterio_ids)) {
                $placeholders = implode(',', array_fill(0, count($criterio_ids), '?'));
                $sqlNotas = "SELECT * FROM notas WHERE criterio_id IN ($placeholders)";
                $stmtNotas = $pdo->prepare($sqlNotas);
                $stmtNotas->execute($criterio_ids);
                $notasResult = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

                foreach ($notasResult as $nota) {
                    $key = $nota['es_simulacion'] == 1 ? 'simulacion' : 'real';
                    $notas[$nota['criterio_id']][$nota['numero_evaluacion']][$key] = $nota['calificacion'];
                }
            }

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

            // Obtener tareas relacionadas para mostrar en el registro de notas
            $sqlTareas = "SELECT id, titulo, fecha_entrega, completada, criterio_id, numero_evaluacion 
                          FROM tareas WHERE materia_id = ? AND es_calificada = 1";
            $stmtTareas = $pdo->prepare($sqlTareas);
            $stmtTareas->execute([$materia_id]);
            $tareas = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);

            // Agrupar tareas por criterio y número
            $tareasAgrupadas = [];
            foreach ($tareas as $t) {
                $tareasAgrupadas[$t['criterio_id']][$t['numero_evaluacion']] = $t;
            }
            $materia['tareas_vinculadas'] = $tareasAgrupadas;

            echo json_encode(['status' => 'success', 'data' => $materia]);
            break;

        case 'guardar':
            // Lógica de materia_proceso.php
            if (empty($data['nombre'])) throw new Exception('El nombre es obligatorio', 400);

            $materia_id_edicion = isset($data['id']) && !empty($data['id']) ? (int)$data['id'] : null;
            $nombre = trim($data['nombre']);
            $activa = isset($data['activa']) ? (int)$data['activa'] : 1;
            $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
            $criterios = isset($data['criterios']) ? $data['criterios'] : [];

            // Validar porcentajes
            $totalPorcentaje = 0;
            foreach ($criterios as $c) {
                $totalPorcentaje += (float)$c['porcentaje'];
            }
            if (!empty($criterios) && $totalPorcentaje > 100.01) {
                throw new Exception("La suma de porcentajes no debe exceder el 100%. Actual: $totalPorcentaje%", 400);
            }

            $pdo->beginTransaction();
            if ($materia_id_edicion) {
                $stmtCheck = $pdo->prepare("SELECT id FROM materias WHERE id = ? AND usuario_id = ?");
                $stmtCheck->execute([$materia_id_edicion, $usuario_id]);
                if ($stmtCheck->rowCount() === 0) throw new Exception('Materia no encontrada', 404);

                $stmt = $pdo->prepare("UPDATE materias SET nombre = ?, descripcion = ?, activa = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $activa, $materia_id_edicion]);
                $materia_id_actual = $materia_id_edicion;

                $pdo->prepare("DELETE FROM criterios_evaluacion WHERE materia_id = ?")->execute([$materia_id_actual]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO materias (usuario_id, nombre, descripcion, activa) VALUES (?, ?, ?, ?)");
                $stmt->execute([$usuario_id, $nombre, $descripcion, $activa]);
                $materia_id_actual = $pdo->lastInsertId();
            }

            if (!empty($criterios)) {
                $stmtCrit = $pdo->prepare("INSERT INTO criterios_evaluacion (materia_id, nombre, cantidad_evaluaciones, porcentaje, nota_maxima) VALUES (?, ?, ?, ?, ?)");
                foreach ($criterios as $c) {
                    $notaMax = isset($c['nota_maxima']) ? (float)$c['nota_maxima'] : 100.00;
                    $stmtCrit->execute([$materia_id_actual, trim($c['nombre']), (int)$c['cantidad'], (float)$c['porcentaje'], $notaMax]);
                }
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Materia guardada correctamente']);
            break;

        case 'eliminar':
            $materia_id = isset($data['id']) ? (int)$data['id'] : null;
            if (!$materia_id) throw new Exception('ID no proporcionado', 400);

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM materias WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$materia_id, $usuario_id]);
            if ($stmt->rowCount() === 0) throw new Exception('Materia no encontrada', 404);
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Materia eliminada']);
            break;

        case 'eliminar_lote':
            $ids = isset($data['ids']) ? $data['ids'] : [];
            if (!is_array($ids) || empty($ids)) {
                throw new Exception('No se seleccionaron materias para eliminar', 400);
            }

            $pdo->beginTransaction();
            
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge($ids, [$usuario_id]);
            
            $stmt = $pdo->prepare("DELETE FROM materias WHERE id IN ($placeholders) AND usuario_id = ?");
            $stmt->execute($params);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Materias eliminadas correctamente']);
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

<?php
// Habilitar reporte de errores pero no mostrarlos en el output (para no romper el JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Configuración y sesión
require_once 'config/config.php';

// Establecer el tipo de contenido a JSON
header('Content-Type: application/json');

try {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Acceso denegado. Debes iniciar sesión.', 403);
    }

    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Se recibió: ' . $_SERVER['REQUEST_METHOD'], 405);
    }

    // Obtener los datos del cuerpo de la petición (JSON)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido.', 400);
    }

    // Validar datos básicos
    if (empty($data['nombre'])) {
        throw new Exception('El nombre de la materia es obligatorio.', 400);
    }

// Sanitizar y preparar datos
$usuario_id = $_SESSION['usuario_id'];
$materia_id_edicion = isset($data['id']) && !empty($data['id']) ? (int)$data['id'] : null;
$nombre = trim($data['nombre']);
$activa = isset($data['activa']) ? (int)$data['activa'] : 1;
$descripcion = isset($data['descripcion']) && !empty($data['descripcion']) ? trim($data['descripcion']) : null;
$criterios = isset($data['criterios']) ? $data['criterios'] : [];

// Validar suma de porcentajes de criterios
$totalPorcentaje = 0;
if (!empty($criterios)) {
    foreach ($criterios as $criterio) {
        if (empty($criterio['nombre']) || !isset($criterio['cantidad']) || !isset($criterio['porcentaje'])) {
            throw new Exception('Cada criterio debe tener nombre, cantidad y porcentaje.', 400);
        }
        $totalPorcentaje += (float)$criterio['porcentaje'];
    }

    if (abs($totalPorcentaje - 100) > 0.01) {
        throw new Exception('La suma de los porcentajes de los criterios debe ser 100%. Total actual: ' . $totalPorcentaje . '%', 400);
    }
}

// Iniciar transacción
$pdo->beginTransaction();

    $materia_id_actual = null;
    $message = '';
    $responseCode = 200;

    if ($materia_id_edicion) {
        // --- MODO EDICIÓN ---
        $materia_id_actual = $materia_id_edicion;
        $message = 'Materia actualizada correctamente.';
        $responseCode = 200; // OK

        // 1. Verificar que la materia pertenece al usuario
        $sqlCheck = "SELECT id FROM materias WHERE id = ? AND usuario_id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$materia_id_actual, $usuario_id]);
        if ($stmtCheck->rowCount() === 0) {
            throw new Exception('Materia no encontrada o sin permisos para editar.', 404);
        }

        // 2. Actualizar la materia
        $sqlMateria = "UPDATE materias SET nombre = ?, descripcion = ?, activa = ? WHERE id = ?";
        $stmtMateria = $pdo->prepare($sqlMateria);
        $stmtMateria->execute([$nombre, $descripcion, $activa, $materia_id_actual]);

        // 3. Eliminar criterios antiguos (y notas asociadas si hay ON DELETE CASCADE)
        $sqlDeleteCriterios = "DELETE FROM criterios_evaluacion WHERE materia_id = ?";
        $stmtDeleteCriterios = $pdo->prepare($sqlDeleteCriterios);
        $stmtDeleteCriterios->execute([$materia_id_actual]);

    } else {
        // --- MODO CREACIÓN ---
        $message = 'Materia creada correctamente.';
        $responseCode = 201; // Created

        // 1. Insertar la materia
        $sqlMateria = "INSERT INTO materias (usuario_id, nombre, descripcion, activa) VALUES (?, ?, ?, ?)";
        $stmtMateria = $pdo->prepare($sqlMateria);
        $stmtMateria->execute([$usuario_id, $nombre, $descripcion, $activa]);
        $materia_id_actual = $pdo->lastInsertId();
    }

    // Insertar los nuevos criterios (aplica para ambos modos)
    if ($materia_id_actual && !empty($criterios)) {
        $sqlCriterio = "INSERT INTO criterios_evaluacion (materia_id, nombre, cantidad_evaluaciones, porcentaje) VALUES (?, ?, ?, ?)";
        $stmtCriterio = $pdo->prepare($sqlCriterio);

        foreach ($criterios as $criterio) {
            $stmtCriterio->execute([
                $materia_id_actual,
                trim($criterio['nombre']),
                (int)$criterio['cantidad'],
                (float)$criterio['porcentaje']
            ]);
        }
    }

    $pdo->commit();

    http_response_code($responseCode);
    echo json_encode(['status' => 'success', 'message' => $message]);

} catch (Throwable $e) {
    // Si hay una transacción activa, revertirla
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $code = $e->getCode();
    $httpCode = (is_int($code) && $code >= 400 && $code < 600) ? $code : 500;
    
    http_response_code($httpCode);
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'debug' => 'Error en ' . basename($e->getFile()) . ':' . $e->getLine()
    ]);
}
?>
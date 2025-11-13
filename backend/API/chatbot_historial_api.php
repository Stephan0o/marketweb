<?php
session_start();
require_once __DIR__ . '/../../init-light.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Obtener acción
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

// ========== ACCIONES ==========

switch ($action) {
    case 'obtener_historial':
        obtenerHistorial($conn, $usuario_id);
        break;
    
    case 'obtener_conversacion':
        $fecha = $input['fecha'] ?? '';
        obtenerConversacion($conn, $usuario_id, $fecha);
        break;
    
    case 'eliminar_conversacion':
        $fecha = $input['fecha'] ?? '';
        eliminarConversacion($conn, $usuario_id, $fecha);
        break;
    
    case 'buscar':
        $termino = $input['termino'] ?? '';
        buscarEnHistorial($conn, $usuario_id, $termino);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

// ========== FUNCIONES ==========

/**
 * Obtener historial agrupado por fecha
 */
function obtenerHistorial($conn, $usuario_id) {
    $sql = "SELECT 
                DATE(timestamp) as fecha,
                COUNT(*) as total_mensajes,
                MIN(mensaje) as primer_mensaje,
                MAX(timestamp) as ultima_hora
            FROM chatbot_conversaciones
            WHERE usuario_id = ? AND rol = 'user'
            GROUP BY DATE(timestamp)
            ORDER BY fecha DESC
            LIMIT 30";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $historial = [];
    while ($row = $result->fetch_assoc()) {
        $historial[] = [
            'fecha' => $row['fecha'],
            'total_mensajes' => (int)$row['total_mensajes'],
            'preview' => mb_substr($row['primer_mensaje'], 0, 100),
            'ultima_hora' => date('H:i', strtotime($row['ultima_hora']))
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'historial' => $historial
    ]);
}

/**
 * Obtener conversación completa de una fecha
 */
function obtenerConversacion($conn, $usuario_id, $fecha) {
    if (empty($fecha)) {
        http_response_code(400);
        echo json_encode(['error' => 'Fecha no proporcionada']);
        return;
    }
    
    $sql = "SELECT mensaje, rol, timestamp
            FROM chatbot_conversaciones
            WHERE usuario_id = ? 
            AND DATE(timestamp) = ?
            ORDER BY timestamp ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mensajes = [];
    while ($row = $result->fetch_assoc()) {
        $mensajes[] = [
            'mensaje' => $row['mensaje'],
            'rol' => $row['rol'],
            'hora' => date('H:i', strtotime($row['timestamp']))
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'mensajes' => $mensajes,
        'fecha' => $fecha
    ]);
}

/**
 * Eliminar conversación de una fecha
 */
function eliminarConversacion($conn, $usuario_id, $fecha) {
    if (empty($fecha)) {
        http_response_code(400);
        echo json_encode(['error' => 'Fecha no proporcionada']);
        return;
    }
    
    $sql = "DELETE FROM chatbot_conversaciones
            WHERE usuario_id = ? 
            AND DATE(timestamp) = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $fecha);
    $success = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    if ($success && $affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Conversación eliminada correctamente',
            'deleted' => $affected
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró la conversación'
        ]);
    }
}

/**
 * Buscar en el historial
 */
function buscarEnHistorial($conn, $usuario_id, $termino) {
    if (empty($termino)) {
        http_response_code(400);
        echo json_encode(['error' => 'Término de búsqueda vacío']);
        return;
    }
    
    $termino_busqueda = '%' . $termino . '%';
    
    $sql = "SELECT 
                DATE(timestamp) as fecha,
                mensaje,
                rol,
                TIME(timestamp) as hora
            FROM chatbot_conversaciones
            WHERE usuario_id = ? 
            AND mensaje LIKE ?
            ORDER BY timestamp DESC
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $termino_busqueda);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $resultados = [];
    while ($row = $result->fetch_assoc()) {
        $resultados[] = [
            'fecha' => $row['fecha'],
            'mensaje' => $row['mensaje'],
            'rol' => $row['rol'],
            'hora' => $row['hora']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'resultados' => $resultados,
        'total' => count($resultados)
    ]);
}
?>
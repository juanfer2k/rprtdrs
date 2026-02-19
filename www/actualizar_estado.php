<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_log.txt');

// --- Lógica de Entorno (Local vs. Producción) ---
if (file_exists(__DIR__ . '/conex.local.php')) {
    require_once 'conex.local.php';
} else {
    require_once 'conex.php';
}

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$raw_data = file_get_contents("php://input");
$data = json_decode($raw_data, true);

if (!$data || !isset($data['id_repartidor']) || !isset($data['estado'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
    exit;
}

$id_repartidor = $data['id_repartidor'];
$estado = $data['estado'];
$latitud = $data['latitud'] ?? null;
$longitud = $data['longitud'] ?? null;

try {
    $sql = "UPDATE repartidores SET estado = :estado, ultima_actualizacion = NOW()";
    $params = [':estado' => $estado];

    if ($latitud !== null && $longitud !== null) {
        $sql .= ", latitud = :latitud, longitud = :longitud";
        $params[':latitud'] = $latitud;
        $params[':longitud'] = $longitud;
    }

    // Incrementar el contador de pedidos entregados
    if ($estado === 'Pedido Entregado') {
        $sql .= ", pedidos_entregados = pedidos_entregados + 1";
    }

    $sql .= " WHERE id_repartidor = :id_repartidor";
    $params[':id_repartidor'] = $id_repartidor;

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Estado actualizado."]);
        } else {
            echo json_encode(["status" => "warning", "message" => "No se encontró el repartidor o el estado ya era el mismo."]);
        }
    } else {
        throw new Exception("Falló la ejecución de la consulta.");
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD en actualizar_estado: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Error en la base de datos."]);
}

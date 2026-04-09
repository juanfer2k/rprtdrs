<?php
// --- Archivo: www/api/track.php ---
// API Unificada para Seguimiento y Actualización de Estado

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../conex-switch.php';

function getRequestHeadersSafe() {
    if (function_exists('apache_request_headers')) {
        return apache_request_headers();
    }

    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$headerName] = $value;
        }
    }
    return $headers;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!is_array($data)) {
    $data = [];
}

$headers = getRequestHeadersSafe();
$authorization = $headers['Authorization'] ?? $headers['authorization'] ?? null;
$token = null;

if ($authorization) {
    if (stripos($authorization, 'Bearer ') === 0) {
        $token = trim(substr($authorization, 7));
    } else {
        $token = trim($authorization);
    }
}

if (!$token) {
    $token = trim((string)($data['token'] ?? ''));
}

if (!$token) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No autorizado: Token faltante"]);
    exit;
}

try {
    // Buscar usuario activo por token
    $stmt = $pdo->prepare("SELECT id, rol FROM usuarios WHERE api_token = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token inválido"]);
        exit;
    }

    if (($user['rol'] ?? '') !== 'repartidor') {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token no autorizado para seguimiento"]);
        exit;
    }

    $id_repartidor = (int)$user['id'];

    $stmtRep = $pdo->prepare("SELECT id_repartidor FROM repartidores WHERE id_repartidor = ? AND activo = 1 LIMIT 1");
    $stmtRep->execute([$id_repartidor]);
    if (!$stmtRep->fetch()) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Perfil de repartidor no vinculado o inactivo"]);
        exit;
    }

    $lat = $data['lat'] ?? $data['latitud'] ?? null;
    $lng = $data['lng'] ?? $data['longitud'] ?? null;
    $estado = $data['estado'] ?? null;

    if ($lat === null || $lng === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Coordenadas lat/lng requeridas"]);
        exit;
    }

    // 1. Actualizar repartidores (Ubicación y opcionalmente Estado)
    if ($estado) {
        $sqlUpd = "UPDATE repartidores SET latitud = ?, longitud = ?, estado = ?, ultima_actualizacion = NOW() WHERE id_repartidor = ?";
        $pdo->prepare($sqlUpd)->execute([$lat, $lng, $estado, $id_repartidor]);
    } else {
        $sqlUpd = "UPDATE repartidores SET latitud = ?, longitud = ?, ultima_actualizacion = NOW() WHERE id_repartidor = ?";
        $pdo->prepare($sqlUpd)->execute([$lat, $lng, $id_repartidor]);
    }

    // 2. Guardar en historial de posiciones
    $sqlHist = "INSERT INTO posiciones_historial (id_repartidor, latitud, longitud) VALUES (?, ?, ?)";
    $pdo->prepare($sqlHist)->execute([$id_repartidor, $lat, $lng]);

    echo json_encode(["status" => "success", "message" => "Sincronizado correctamente"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error DB: " . $e->getMessage()]);
}
?>

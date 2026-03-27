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

require_once __DIR__ . '/../../conex-switch.php';

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

if (empty($data) && !empty($_POST)) {
    $data = $_POST;
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
    // Buscar usuario por token
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE api_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token inválido"]);
        exit;
    }

    $id_repartidor = $user['id'];
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

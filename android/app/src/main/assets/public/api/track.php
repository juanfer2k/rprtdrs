<?php
ini_set('display_errors', 0);
error_reporting(0);
// --- Archivo: www/api/track.php ---
// API Unificada para Seguimiento y Actualización de Estado

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../conex-switch.php';

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

    $uid = (int)$user['id'];

    $lat    = isset($data['lat'])    ? $data['lat']    : (isset($data['latitud'])  ? $data['latitud']  : null);
    $lng    = isset($data['lng'])    ? $data['lng']    : (isset($data['longitud']) ? $data['longitud'] : null);
    $estado = isset($data['estado']) ? $data['estado'] : null;

    if ($lat === null || $lng === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Coordenadas lat/lng requeridas"]);
        exit;
    }

    // Actualizar ubicación y estado directamente en usuarios
    $estadosValidos = ['Disponible','No disponible','En camino a recoger','En camino a entrega','Pedido Entregado','libre','ocupado','desconectado'];
    if ($estado && in_array($estado, $estadosValidos)) {
        $pdo->prepare("UPDATE usuarios SET latitud=?, longitud=?, estado=?, ultima_actualizacion=NOW() WHERE id=?")
            ->execute([$lat, $lng, $estado, $uid]);
    } else {
        $pdo->prepare("UPDATE usuarios SET latitud=?, longitud=?, ultima_actualizacion=NOW() WHERE id=?")
            ->execute([$lat, $lng, $uid]);
    }

    // Historial de posiciones (usa usuarios.id como id_repartidor)
    $pdo->prepare("INSERT INTO posiciones_historial (id_repartidor, latitud, longitud) VALUES (?,?,?)")
        ->execute([$uid, $lat, $lng]);

    echo json_encode(["status" => "success", "message" => "Sincronizado correctamente"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error DB: " . $e->getMessage()]);
}
?>

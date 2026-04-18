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

    // Buscar el id_repartidor correcto: primero por ID directo, luego por username
    // (cubre el caso donde usuarios.id != repartidores.id_repartidor en datos legacy)
    $stmtRep = $pdo->prepare("SELECT id_repartidor FROM repartidores WHERE id_repartidor = ? AND activo = 1 LIMIT 1");
    $stmtRep->execute([$id_repartidor]);
    $rep = $stmtRep->fetch();

    if (!$rep) {
        // Fallback: buscar por username en join
        $stmtFb = $pdo->prepare("
            SELECT r.id_repartidor FROM repartidores r
            JOIN usuarios u ON u.username = (SELECT username FROM usuarios WHERE id = ? LIMIT 1)
            WHERE r.activo = 1
            ORDER BY ABS(r.id_repartidor - ?) ASC
            LIMIT 1
        ");
        $stmtFb->execute([$id_repartidor, $id_repartidor]);
        $rep = $stmtFb->fetch();
    }

    if (!$rep) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Perfil no encontrado. Pide al admin que verifique tu cuenta."]);
        exit;
    }

    $id_repartidor = (int)$rep['id_repartidor'];

    $lat    = isset($data['lat'])     ? $data['lat']     : (isset($data['latitud'])   ? $data['latitud']   : null);
    $lng    = isset($data['lng'])     ? $data['lng']     : (isset($data['longitud'])  ? $data['longitud']  : null);
    $estado = isset($data['estado']) ? $data['estado']  : null;

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

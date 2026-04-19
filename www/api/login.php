<?php
ini_set('display_errors', 0);
error_reporting(0);
// --- Archivo: www/api/login.php ---
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../conex-switch.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Usuario y contraseña requeridos"]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password_hash, rol, api_token FROM usuarios WHERE username = ? AND activo = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        echo json_encode([
            "status"        => "success",
            "token"         => $user['api_token'],
            "rol"           => $user['rol'],
            "id"            => (int)$user['id'],
            "id_repartidor" => (int)$user['id'],
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Credenciales inválidas"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error en el servidor"]);
}
?>

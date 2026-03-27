<?php
// --- Archivo: www/api/login.php ---
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../conex-switch.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!is_array($data)) {
    $data = [];
}

if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

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
        // En una app real, generarías un JWT. Aquí usamos el api_token de la DB para simplicidad.
        echo json_encode([
            "status" => "success",
            "token" => $user['api_token'],
            "rol" => $user['rol'],
            "id" => $user['id']
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

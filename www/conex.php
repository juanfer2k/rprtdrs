<?php
// --- Archivo: www/conex.php (CONFIGURACIÓN DINÁMICA) ---

$host = getenv('DB_HOST') ?: "127.0.0.1";
$db   = getenv('DB_NAME') ?: "logistica_db";
$user = getenv('DB_USER') ?: "user_reparto";
$pass = getenv('DB_PASS') ?: "pass_reparto";

$dsn = "mysql:host=$host;dbname=$db;charset=utf8";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de conexión a la BD: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Error de conexión interna."]);
    exit;
}
?>

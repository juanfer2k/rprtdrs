<?php
// --- Archivo: www/conex.php (CONFIGURACIÓN DINÁMICA) ---

$host = getenv('DB_HOST') ?: "192.99.84.34";
$db   = getenv('DB_NAME') ?: "elcerrit_rprtdrs";
$user = getenv('DB_USER') ?: "elcerrit_rprtdrs";
$pass = getenv('DB_PASS') ?: ']EzCPlz+I%i4';

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

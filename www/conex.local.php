<?php
// --- CONFIGURACIÓN PARA ENTORNO LOCAL (DOCKER) ---
$host = "host.docker.internal"; // Usar host.docker.internal para conectar al host desde el contenedor
$port = "3307";                // El puerto que has mapeado para Docker en tu host
$db   = "elcerrit_rprtdrs";
$user = "elcerrit_rprtdrs";
$pass = ']EzCPlz+I%i4';     // Tu contraseña de Docker

// --- NO MODIFICAR DEBAJO DE ESTA LÍNEA ---
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de conexión LOCAL: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos LOCAL. Revisa conex.local.php."]);
    exit;
}

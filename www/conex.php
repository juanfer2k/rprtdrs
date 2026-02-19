<?php
// --- CONFIGURACIÓN PARA SERVIDOR REMOTO (PRODUCCIÓN) ---
$host = "localhost";         // El host de tu servidor remoto
$db   = "elcerrit_rprtdrs";
$user = "elcerrit_rprtdrs";
$pass = "]EzCPlz+I%i4"; // <-- USA COMILLAS SIMPLES

// --- NO MODIFICAR DEBAJO DE ESTA LÍNEA ---
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
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos. Revisa el log del servidor."]);
    exit;
}

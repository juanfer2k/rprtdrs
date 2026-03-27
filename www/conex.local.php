<?php
// --- CONFIGURACIÓN PARA ENTORNO LOCAL (XAMPP) ---
$host = "127.0.0.1";
$port = "3306";
$db   = "elcerrit_rprtdrs";
$user = "root";
$pass = ""; // En XAMPP por defecto root no tiene contraseña

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
    error_log("Error de conexión LOCAL (XAMPP): " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos local (XAMPP). Revisa www/conex.local.php."]);
    exit;
}
?>

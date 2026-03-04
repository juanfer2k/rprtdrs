<?php
// --- Archivo: www/conex.php (VERSIÓN FINAL CON SOPORTE LOCAL) ---

// SI EXISTE UN ARCHIVO LOCAL, USARLO Y SALIR
if (file_exists(__DIR__ . '/conex.local.php')) {
    require_once __DIR__ . '/conex.local.php';
    return;
}

// --- CONFIGURACIÓN PARA SERVIDOR REMOTO (PRODUCCIÓN) ---
$host = "127.0.0.1";         // Usar 127.0.0.1 en lugar de localhost para forzar conexión de red
$db   = "elcerrit_rprtdrs";
$user = "elcerrit_rprtdrs";
$pass = ']EzCPlz+I%i4';     // Tu contraseña de producción

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
    // Este error SÓLO se verá en el error_log del servidor, nunca al usuario final.
    error_log("Error CRÍTICO de conexión a la BD: " . $e->getMessage());
    // Respuesta genérica para el frontend para no exponer detalles.
    echo json_encode(["status" => "error", "message" => "Error de conexión interna."]);
    exit;
}
?>

<?php
// --- Archivo: www/obtener_ubicaciones.php (VERSIÓN CORREGIDA) ---

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// FORMA ROBUSTA Y DIRECTA DE INCLUIR LA CONEXIÓN
require_once __DIR__ . '/conex.php';

try {
    // La consulta ya está correcta con el alias
    $stmt = $pdo->query("SELECT id_repartidor, nombre_completo, latitud, longitud, estado, ultima_actualizacion AS ultimo_update, activo FROM repartidores");
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    // Log del error para depuración en el servidor
    error_log("Error en obtener_ubicaciones.php: " . $e->getMessage());
    // Mensaje genérico para el frontend
    echo json_encode(["status" => "error", "message" => "Error al consultar la base de datos."]);
}
?>

<?php
// --- Archivo: www/get_repartidores.php ---

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once 'conex.php';

try {
    $stmt = $pdo->query("SELECT id_repartidor, nombre_completo, telefono, latitud, longitud, estado, ultima_actualizacion FROM repartidores WHERE activo = 1");
    $repartidores = $stmt->fetchAll();
    echo json_encode($repartidores);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

<?php
// --- Archivo: www/api/get_admin_data.php ---
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../../conex-switch.php';

try {
    // 1. Obtener todos los repartidores
    $stmtRep = $pdo->query("SELECT id_repartidor, nombre_completo, latitud, longitud, estado, ultima_actualizacion FROM repartidores");
    $repartidores = $stmtRep->fetchAll();

    // 2. Obtener pedidos activos (no entregados)
    $stmtPed = $pdo->query("SELECT id_pedido, cliente_nombre, direccion_entrega, estado, id_repartidor FROM pedidos WHERE estado != 'entregado' ORDER BY fecha_creacion DESC");
    $pedidos = $stmtPed->fetchAll();

    echo json_encode([
        "status" => "success",
        "repartidores" => $repartidores,
        "pedidos" => $pedidos
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

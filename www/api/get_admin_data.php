<?php
// --- Archivo: www/api/get_admin_data.php ---
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../conex-switch.php';

try {
    // Repartidores: nombre desde usuarios, ubicación desde repartidores con fallback a usuarios
    $repartidores = $pdo->query("
        SELECT
            r.id_repartidor,
            COALESCE(u.nombre_completo, u.username, r.nombre_completo) AS nombre_completo,
            COALESCE(r.latitud,  u.latitud)  AS latitud,
            COALESCE(r.longitud, u.longitud) AS longitud,
            COALESCE(r.estado,   u.estado)   AS estado,
            COALESCE(r.ultima_actualizacion, u.ultima_actualizacion) AS ultima_actualizacion,
            r.activo
        FROM repartidores r
        LEFT JOIN usuarios u ON u.id = r.id_repartidor
        WHERE r.activo = 1
        ORDER BY r.id_repartidor
    ")->fetchAll();

    $pedidos = $pdo->query("
        SELECT id_pedido, cliente_nombre, direccion_entrega, estado, id_repartidor
        FROM pedidos
        WHERE estado != 'entregado'
        ORDER BY fecha_creacion DESC
    ")->fetchAll();

    echo json_encode(array(
        'status'       => 'success',
        'repartidores' => $repartidores,
        'pedidos'      => $pedidos
    ));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

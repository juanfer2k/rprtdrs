<?php
ini_set('display_errors', 0);
error_reporting(0);
// --- Archivo: www/api/get_admin_data.php ---
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../conex-switch.php';

try {
    $repartidores = $pdo->query("
        SELECT
            id AS id_repartidor,
            COALESCE(nombre_completo, username) AS nombre_completo,
            latitud, longitud, estado, ultima_actualizacion, activo
        FROM usuarios
        WHERE rol = 'repartidor' AND activo = 1
        ORDER BY id
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

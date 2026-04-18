<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

try {
    require_once __DIR__ . '/../conex-switch.php';

    $repartidores = $pdo->query("
        SELECT
            id              AS id_repartidor,
            COALESCE(nombre_completo, username) AS nombre_completo,
            latitud, longitud,
            COALESCE(estado, 'No disponible') AS estado,
            ultima_actualizacion, activo
        FROM usuarios
        WHERE rol = 'repartidor' AND activo = 1
        ORDER BY id
    ")->fetchAll();

    // Pedidos activos — tabla opcional, no falla si no existe
    $pedidos = array();
    try {
        $pedidos = $pdo->query("
            SELECT id_pedido, cliente_nombre, direccion_entrega, estado, id_repartidor
            FROM pedidos
            WHERE estado != 'entregado'
            ORDER BY fecha_creacion DESC
        ")->fetchAll();
    } catch (PDOException $ep) {
        // Tabla pedidos no existe todavía — devolver array vacío
    }

    echo json_encode(array(
        'status'       => 'success',
        'repartidores' => $repartidores,
        'pedidos'      => $pedidos
    ));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'status'  => 'error',
        'message' => 'DB: ' . $e->getMessage()
    ));
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode(array(
        'status'  => 'error',
        'message' => $t->getMessage()
    ));
}

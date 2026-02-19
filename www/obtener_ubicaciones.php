<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// --- Lógica de Entorno (Local vs. Producción) ---
if (file_exists(__DIR__ . '/conex.local.php')) {
    require_once 'conex.local.php';
} else {
    require_once 'conex.php';
}

try {
    $stmt = $pdo->query("
        SELECT
            id_repartidor,
            nombre_completo,
            latitud,
            longitud,
            estado,
            ultima_actualizacion,
            pedidos_entregados, // <-- AÑADIDO
            activo
        FROM repartidores
        WHERE activo = 1
    ");

    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en obtener_ubicaciones: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Error al consultar la base de datos."]);
}

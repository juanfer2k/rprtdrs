<?php
header('Content-Type: application/json');

// --- Lógica de Entorno (Local vs. Producción) ---
if (file_exists(__DIR__ . '/conex.local.php')) {
    require_once 'conex.local.php';
} else {
    require_once 'conex.php';
}

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'read':
        $stmt = $pdo->query("SELECT id_repartidor, nombre_completo, email, telefono, activo FROM repartidores ORDER BY nombre_completo");
        echo json_encode($stmt->fetchAll());
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

<?php
// --- Archivo: www/gestionar_repartidores.php (VERSIÓN FINAL CORREGIDA) ---

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Incluir la conexión a la base de datos de forma robusta
require_once __DIR__ . '/conex.php';

$action = $_GET['action'] ?? null;

switch ($action) {

    case 'read':
        // Esta acción devuelve la lista de usuarios para la tabla de "Gestión"
        $stmt = $pdo->query("SELECT id_repartidor, nombre_completo, email, telefono, foto_url, activo FROM repartidores ORDER BY nombre_completo");
        echo json_encode($stmt->fetchAll());
        break;

    case 'get_map_data':
        // Esta acción es para el dashboard, pero la hemos delegado a obtener_ubicaciones.php
        // La mantenemos funcional por si se usa en el futuro.
        $stmt = $pdo->query("
            SELECT
                id_repartidor,
                nombre_completo,
                latitud,
                longitud,
                estado,
                ultima_actualizacion AS ultimo_update,
                activo
            FROM repartidores
            WHERE activo = 1
        ");
        echo json_encode($stmt->fetchAll());
        break;

    default:
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida o no especificada.']);
        break;
}
?>

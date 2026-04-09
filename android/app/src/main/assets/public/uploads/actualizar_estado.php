<?php
// --- Archivo: www/actualizar_estado.php ---
// Este script recibe las actualizaciones de la App del Repartidor.

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Permite peticiones desde cualquier origen.
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Métodos permitidos.
header("Access-Control-Allow-Headers: Content-Type");

// Manejo de la petición pre-vuelo (preflight) OPTIONS.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Incluir la conexión a la base de datos.
require_once __DIR__ . '/conex.php';
// Leer el cuerpo de la petición (que viene en formato JSON).
$json_data = file_get_contents('php://input');
$data = json_decode($json_data);

// Validar que los datos necesarios están presentes.
if (!isset($data->id_repartidor) || !isset($data->latitud) || !isset($data->longitud) || !isset($data->estado)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos requeridos.']);
    exit;
}

// Limpiar los datos para seguridad.
$id_repartidor = intval($data->id_repartidor);
$latitud = floatval($data->latitud);
$longitud = floatval($data->longitud);
$estado = htmlspecialchars(strip_tags($data->estado));

// Preparar la sentencia SQL para actualizar los datos del repartidor.
// Usamos sentencias preparadas para prevenir inyección SQL.
$sql = "UPDATE repartidores SET
            latitud = :latitud,
            longitud = :longitud,
            estado = :estado,
            ultima_actualizacion = NOW() -- Usamos la función de la BD para la hora del servidor
        WHERE id_repartidor = :id_repartidor";

try {
    $stmt = $pdo->prepare($sql);

    // Vincular los parámetros.
    $stmt->bindParam(':latitud', $latitud, PDO::PARAM_STR);
    $stmt->bindParam(':longitud', $longitud, PDO::PARAM_STR);
    $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
    $stmt->bindParam(':id_repartidor', $id_repartidor, PDO::PARAM_INT);

    // Ejecutar la consulta.
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            // Se actualizó la fila correctamente.
            echo json_encode(['status' => 'success', 'message' => 'Ubicación y estado actualizados.']);
        } else {
            // No se encontró un repartidor con ese ID.
            http_response_code(404); // Not Found
            echo json_encode(['status' => 'warning', 'message' => 'Repartidor no encontrado.']);
        }
    } else {
        throw new Exception("Error en la ejecución de la consulta.");
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error en actualizar_estado.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor al actualizar los datos.']);
}

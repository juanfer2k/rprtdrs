<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// --- Lógica de Entorno (Local vs. Producción) ---
if (file_exists(__DIR__ . '/conex.local.php')) {
    require_once 'conex.local.php';
} else {
    require_once 'conex.php';
}

$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nombre_completo']) && isset($_POST['email'])) {
        $nombre_completo = $_POST['nombre_completo'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'] ?? null;
        $tipo_vehiculo = $_POST['tipo_vehiculo'] ?? null;
        $placa_vehiculo = $_POST['placa_vehiculo'] ?? null;
        $foto_path = null;

        // --- Lógica de Subida de Foto ---
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $response['message'] = 'Error: No se pudo crear el directorio de subidas. Verifique los permisos.';
                    echo json_encode($response);
                    exit;
                }
            }
            $tmp_name = $_FILES['foto']['tmp_name'];
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $file_name = 'perfil_' . uniqid() . '.' . $file_extension;
            $destination = $upload_dir . $file_name;

            if (move_uploaded_file($tmp_name, $destination)) {
                $foto_path = 'uploads/' . $file_name;
            } else {
                $response['message'] = 'Error al mover el archivo subido.';
                echo json_encode($response);
                exit;
            }
        }

        // --- Inserción en la Base de Datos ---
        try {
            $id_repartidor = 'REP-' . strtoupper(substr(uniqid(), -5));
            $sql = "INSERT INTO repartidores (id_repartidor, nombre_completo, email, telefono, tipo_vehiculo, placa_vehiculo, foto_path, activo, estado, pedidos_entregados) VALUES (:id_repartidor, :nombre_completo, :email, :telefono, :tipo_vehiculo, :placa_vehiculo, :foto_path, 1, 'No disponible', 0)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_repartidor' => $id_repartidor,
                ':nombre_completo' => $nombre_completo,
                ':email' => $email,
                ':telefono' => $telefono,
                ':tipo_vehiculo' => $tipo_vehiculo,
                ':placa_vehiculo' => $placa_vehiculo,
                ':foto_path' => $foto_path
            ]);

            $response['status'] = 'success';
            $response['message'] = '¡Repartidor creado con éxito! ID: ' . $id_repartidor;

        } catch (PDOException $e) {
            $response['message'] = 'Error de base de datos: ' . $e->getMessage();
            http_response_code(500);
        }
    } else {
        $response['message'] = 'Faltan datos obligatorios (nombre y email).';
        http_response_code(400);
    }
} else {
    $response['message'] = 'Método no permitido.';
    http_response_code(405);
}

echo json_encode($response);

<?php
// --- Archivo: www/crear_perfil.php (VERSIÓN FINAL Y CORREGIDA) ---

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once __DIR__ . '/conex.php';
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nombre_completo']) && isset($_POST['email'])) {
        $nombre_completo = $_POST['nombre_completo'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'] ?? null;
        $foto_url = null; // Coincide con la columna 'foto_url' de la BD

        // Lógica de subida de foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!file_exists($upload_dir)) { mkdir($upload_dir, 0755, true); }

            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $file_name = 'perfil_' . uniqid() . '.' . $file_extension;
            $destination = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
                $foto_url = 'uploads/' . $file_name; // Guardamos la ruta en la variable correcta
            }
        }

        // Inserción en la Base de Datos
        try {
            // No insertamos id_repartidor, dejamos que la BD lo genere con AUTO_INCREMENT
            $sql = "INSERT INTO repartidores (nombre_completo, email, telefono, foto_url, activo, estado)
                    VALUES (:nombre_completo, :email, :telefono, :foto_url, 1, 'No disponible')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre_completo' => $nombre_completo,
                ':email' => $email,
                ':telefono' => $telefono,
                ':foto_url' => $foto_url // Usamos la variable correcta
            ]);

            $last_id = $pdo->lastInsertId(); // Obtenemos el ID numérico real
            $response['status'] = 'success';
            $response['message'] = '¡Repartidor creado con éxito! ID numérico: ' . $last_id;

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
?>

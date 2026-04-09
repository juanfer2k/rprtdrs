<?php
// --- Archivo: www/crear_perfil.php (VERSIÓN FINAL Y CORREGIDA) ---

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/conex-switch.php';
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

function normalizeUsername(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9._-]/', '', $value);
    return $value;
}

function generateTokenSafe(): string {
    try {
        return bin2hex(random_bytes(24));
    } catch (Exception $e) {
        return bin2hex(openssl_random_pseudo_bytes(24));
    }
}

function generatePasswordSafe(int $length = 10): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $max = strlen($alphabet) - 1;
    $plain = '';
    for ($i = 0; $i < $length; $i++) {
        $plain .= $alphabet[random_int(0, $max)];
    }
    return $plain;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nombre_completo']) && isset($_POST['email'])) {
        $nombre_completo = trim((string)$_POST['nombre_completo']);
        $email = trim((string)$_POST['email']);
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $telefono = $telefono === '' ? null : $telefono;
        $requestedUsername = normalizeUsername((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $foto_url = null; // Coincide con la columna 'foto_url' de la BD

        if ($nombre_completo === '' || $email === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Nombre completo y email son obligatorios.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'El email no es válido.']);
            exit;
        }

        $usernameBase = $requestedUsername;
        if ($usernameBase === '') {
            $emailPrefix = explode('@', $email)[0] ?? '';
            $usernameBase = normalizeUsername($emailPrefix);
        }

        if ($usernameBase === '') {
            $usernameBase = 'rep';
        }

        $generatedPassword = false;
        if ($password === '') {
            $password = generatePasswordSafe(10);
            $generatedPassword = true;
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
            exit;
        }

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
            $pdo->beginTransaction();

            $stmtEmail = $pdo->prepare("SELECT id_repartidor FROM repartidores WHERE email = ? LIMIT 1");
            $stmtEmail->execute([$email]);
            if ($stmtEmail->fetch()) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'Ya existe un repartidor con ese email.']);
                exit;
            }

            $username = $usernameBase;
            $counter = 0;
            do {
                $candidate = $counter === 0 ? $username : $usernameBase . $counter;
                $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? LIMIT 1");
                $stmtUser->execute([$candidate]);
                $exists = $stmtUser->fetch();
                if (!$exists) {
                    $username = $candidate;
                    break;
                }
                $counter++;
            } while ($counter < 1000);

            if ($counter >= 1000) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'No fue posible generar un usuario único.']);
                exit;
            }

            $token = generateTokenSafe();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sqlUser = "INSERT INTO usuarios (username, password_hash, api_token, rol, activo) VALUES (?, ?, ?, 'repartidor', 1)";
            $pdo->prepare($sqlUser)->execute([$username, $passwordHash, $token]);
            $userId = (int)$pdo->lastInsertId();

            // Mantener id_repartidor = id de usuario para compatibilidad con tracking y panel.
            $sql = "INSERT INTO repartidores (id_repartidor, nombre_completo, email, telefono, foto_url, activo, estado)
                    VALUES (:id_repartidor, :nombre_completo, :email, :telefono, :foto_url, 1, 'No disponible')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_repartidor' => $userId,
                ':nombre_completo' => $nombre_completo,
                ':email' => $email,
                ':telefono' => $telefono,
                ':foto_url' => $foto_url // Usamos la variable correcta
            ]);

            $pdo->commit();

            $response['status'] = 'success';
            $response['message'] = '¡Repartidor creado con éxito!';
            $response['id_repartidor'] = $userId;
            $response['username'] = $username;
            $response['generated_password'] = $generatedPassword;
            if ($generatedPassword) {
                $response['password_temporal'] = $password;
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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

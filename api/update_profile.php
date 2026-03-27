<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

require_once '../conex-switch.php';

function getRequestHeadersSafe() {
    if (function_exists('apache_request_headers')) {
        return apache_request_headers();
    }

    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$headerName] = $value;
        }
    }
    return $headers;
}

$headers = getRequestHeadersSafe();
$authorization = $headers['Authorization'] ?? $headers['authorization'] ?? null;
$token = null;

if ($authorization) {
    if (stripos($authorization, 'Bearer ') === 0) {
        $token = trim(substr($authorization, 7));
    } else {
        $token = trim($authorization);
    }
}

if (!$token) {
    $token = trim((string)($_POST['token'] ?? ''));
}

if (!$token) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No autorizado: Token faltante"]);
    exit;
}

try {
    $stmtUser = $pdo->prepare("SELECT id, password_hash, foto_url FROM usuarios WHERE api_token = ? AND activo = 1 LIMIT 1");
    $stmtUser->execute([$token]);
    $user = $stmtUser->fetch();

    if (!$user) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token inválido o usuario inactivo"]);
        exit;
    }

    $userId = (int)$user['id'];

    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');

    $wantsPasswordChange = ($currentPassword !== '' || $newPassword !== '');
    $newPhotoPath = null;
    $updatedPasswordHash = null;

    if ($wantsPasswordChange) {
        if ($currentPassword === '' || $newPassword === '') {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Para cambiar contraseña debes enviar contraseña actual y nueva"]);
            exit;
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "La contraseña actual es incorrecta"]);
            exit;
        }

        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "La nueva contraseña debe tener al menos 8 caracteres"]);
            exit;
        }

        $updatedPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Error subiendo imagen"]);
            exit;
        }

        $maxSize = 2 * 1024 * 1024;
        if ((int)$_FILES['foto']['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "La imagen no debe superar 2MB"]);
            exit;
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['foto']['tmp_name']);

        if (!in_array($mime, $allowedMime, true)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Formato no permitido. Usa JPG, PNG o WEBP"]);
            exit;
        }

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];
        $ext = $extMap[$mime];

        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'perfil_user_' . $userId . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "No se pudo guardar la imagen"]);
            exit;
        }

        $newPhotoPath = 'uploads/' . $filename;
    }

    if (!$wantsPasswordChange && $newPhotoPath === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "No hay cambios para actualizar"]);
        exit;
    }

    $fields = [];
    $params = [];

    if ($updatedPasswordHash !== null) {
        $fields[] = "password_hash = ?";
        $params[] = $updatedPasswordHash;
    }

    if ($newPhotoPath !== null) {
        $fields[] = "foto_url = ?";
        $params[] = $newPhotoPath;
    }

    $params[] = $userId;

    $sql = "UPDATE usuarios SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmtUpdate = $pdo->prepare($sql);
    $stmtUpdate->execute($params);

    echo json_encode([
        "status" => "success",
        "message" => "Perfil actualizado correctamente",
        "foto_url" => $newPhotoPath ?? $user['foto_url']
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno actualizando perfil"]);
}
?>

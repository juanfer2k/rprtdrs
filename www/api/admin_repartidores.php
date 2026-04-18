<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../conex.php';

// ── Autenticación: solo admins ────────────────────────────────────────────────
function requireAdmin($pdo) {
    $headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
    foreach ($_SERVER as $k => $v) {
        if (strpos($k, 'HTTP_') === 0) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
            if (!isset($headers[$name])) $headers[$name] = $v;
        }
    }
    $auth  = isset($headers['Authorization']) ? $headers['Authorization']
           : (isset($headers['authorization']) ? $headers['authorization'] : '');
    $token = stripos($auth, 'Bearer ') === 0 ? trim(substr($auth, 7)) : trim($auth);

    if (!$token) {
        http_response_code(401);
        echo json_encode(array('status' => 'error', 'message' => 'Token requerido'));
        exit;
    }

    $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE api_token = ? AND activo = 1");
    $stmt->execute(array($token));
    $user = $stmt->fetch();

    if (!$user || $user['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(array('status' => 'error', 'message' => 'Acceso denegado'));
        exit;
    }
}

requireAdmin($pdo);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$body   = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = array();

function jsonOk($data = array()) {
    echo json_encode(array_merge(array('status' => 'success'), $data));
    exit;
}

function jsonErr($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(array('status' => 'error', 'message' => $msg));
    exit;
}

switch ($action) {

    // ── Listar ────────────────────────────────────────────────────────────────
    case 'list':
        $rows = $pdo->query("
            SELECT r.id_repartidor, r.nombre_completo, r.telefono, r.email,
                   r.estado, r.activo, r.ultima_actualizacion,
                   u.username
            FROM repartidores r
            LEFT JOIN usuarios u ON u.id = r.id_repartidor
            ORDER BY r.nombre_completo
        ")->fetchAll();
        jsonOk(array('repartidores' => $rows));

    // ── Crear ─────────────────────────────────────────────────────────────────
    case 'create':
        $nombre   = trim(isset($body['nombre_completo']) ? $body['nombre_completo'] : '');
        $username = trim(isset($body['username'])        ? $body['username']        : '');
        $password = isset($body['password'])             ? $body['password']        : '';
        $telefono = trim(isset($body['telefono'])        ? $body['telefono']        : '') ?: null;
        $email    = trim(isset($body['email'])           ? $body['email']           : '') ?: null;

        if (!$nombre || !$username || !$password) {
            jsonErr('nombre_completo, username y password son obligatorios');
        }
        if (strlen($password) < 6) {
            jsonErr('La contraseña debe tener al menos 6 caracteres');
        }

        $check = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $check->execute(array($username));
        if ($check->fetch()) jsonErr("El usuario '$username' ya existe");

        $hash  = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        try {
            $pdo->beginTransaction();

            $pdo->prepare("
                INSERT INTO repartidores (nombre_completo, telefono, email, activo, estado)
                VALUES (?, ?, ?, 1, 'No disponible')
            ")->execute(array($nombre, $telefono, $email));

            $newId = (int) $pdo->lastInsertId();

            $pdo->prepare("
                INSERT INTO usuarios (id, username, password_hash, rol, api_token, activo)
                VALUES (?, ?, ?, 'repartidor', ?, 1)
            ")->execute(array($newId, $username, $hash, $token));

            $pdo->commit();
            jsonOk(array('id_repartidor' => $newId));

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() === '23000') jsonErr("El usuario '$username' ya existe");
            jsonErr('Error al crear repartidor: ' . $e->getMessage(), 500);
        }

    // ── Toggle activo ─────────────────────────────────────────────────────────
    case 'toggle':
        $id     = (int)(isset($body['id_repartidor']) ? $body['id_repartidor'] : 0);
        $activo = (int)(isset($body['activo'])        ? $body['activo']        : 0);
        if (!$id) jsonErr('id_repartidor requerido');

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE repartidores SET activo = ? WHERE id_repartidor = ?")->execute(array($activo, $id));
        $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute(array($activo, $id));
        $pdo->commit();
        jsonOk();

    // ── Cambiar contraseña ────────────────────────────────────────────────────
    case 'change_password':
        $id       = (int)(isset($body['id_repartidor']) ? $body['id_repartidor'] : 0);
        $password = isset($body['password'])            ? $body['password']       : '';
        if (!$id || !$password) jsonErr('id_repartidor y password requeridos');
        if (strlen($password) < 6) jsonErr('La contraseña debe tener al menos 6 caracteres');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $stmt->execute(array($hash, $id));
        if (!$stmt->rowCount()) jsonErr('Repartidor no encontrado', 404);
        jsonOk();

    // ── Eliminar ──────────────────────────────────────────────────────────────
    case 'delete':
        $id = (int)(isset($body['id_repartidor']) ? $body['id_repartidor'] : 0);
        if (!$id) jsonErr('id_repartidor requerido');

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM posiciones_historial WHERE id_repartidor = ?")->execute(array($id));
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute(array($id));
        $pdo->prepare("DELETE FROM repartidores WHERE id_repartidor = ?")->execute(array($id));
        $pdo->commit();
        jsonOk();

    default:
        jsonErr('Acción no válida');
}

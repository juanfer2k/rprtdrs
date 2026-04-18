<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../conex-switch.php';

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
        // uid = id real en tabla usuarios (puede diferir de id_repartidor en datos legacy)
        $rows = $pdo->query("
            SELECT r.id_repartidor,
                   COALESCE(u.nombre_completo, r.nombre_completo) AS nombre_completo,
                   r.telefono, r.email, r.estado, r.activo, r.ultima_actualizacion,
                   u.username,
                   u.id AS uid
            FROM repartidores r
            LEFT JOIN usuarios u ON u.id = r.id_repartidor
               OR (u.username = r.nombre_completo AND u.rol = 'repartidor')
            WHERE r.activo IS NOT NULL
            GROUP BY r.id_repartidor
            ORDER BY r.id_repartidor
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
        $id_rep = (int)(isset($body['id_repartidor']) ? $body['id_repartidor'] : 0);
        $uid    = (int)(isset($body['uid'])           ? $body['uid']           : 0);
        $activo = (int)(isset($body['activo'])        ? $body['activo']        : 0);
        if (!$id_rep) jsonErr('id_repartidor requerido');

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE repartidores SET activo = ? WHERE id_repartidor = ?")->execute(array($activo, $id_rep));
        if ($uid) {
            $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute(array($activo, $uid));
        } else {
            // Fallback: actualizar por nombre_completo = username
            $pdo->prepare("UPDATE usuarios u JOIN repartidores r ON r.id_repartidor = ?
                           SET u.activo = ? WHERE u.rol = 'repartidor' AND u.id = r.id_repartidor")
                ->execute(array($id_rep, $activo));
        }
        $pdo->commit();
        jsonOk();

    // ── Cambiar contraseña ────────────────────────────────────────────────────
    case 'change_password':
        $id_rep   = (int)(isset($body['id_repartidor']) ? $body['id_repartidor'] : 0);
        $uid      = (int)(isset($body['uid'])           ? $body['uid']           : 0);
        $password = isset($body['password'])            ? $body['password']       : '';
        if (!$id_rep || !$password) jsonErr('id_repartidor y password requeridos');
        if (strlen($password) < 6) jsonErr('La contraseña debe tener al menos 6 caracteres');

        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Intentar con uid directo primero, luego con id_repartidor, luego búsqueda por nombre
        $updated = 0;
        if ($uid) {
            $s = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ? AND rol = 'repartidor'");
            $s->execute(array($hash, $uid));
            $updated = $s->rowCount();
        }
        if (!$updated) {
            $s = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ? AND rol = 'repartidor'");
            $s->execute(array($hash, $id_rep));
            $updated = $s->rowCount();
        }
        if (!$updated) {
            // Último recurso: buscar el usuario cuyo username coincide con nombre_completo en repartidores
            $s = $pdo->prepare("
                UPDATE usuarios u
                JOIN repartidores r ON u.username = r.nombre_completo
                SET u.password_hash = ?
                WHERE r.id_repartidor = ? AND u.rol = 'repartidor'
            ");
            $s->execute(array($hash, $id_rep));
            $updated = $s->rowCount();
        }
        if (!$updated) jsonErr('Usuario no encontrado. Verifica que el repartidor tiene cuenta en la tabla usuarios.');
        jsonOk();

    // ── Eliminar ──────────────────────────────────────────────────────────────
    case 'delete':
        $id_rep = (int)(isset($body['id_repartidor']) ? $body['id_repartidor'] : 0);
        $uid    = (int)(isset($body['uid'])           ? $body['uid']           : 0);
        if (!$id_rep) jsonErr('id_repartidor requerido');

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM posiciones_historial WHERE id_repartidor = ?")->execute(array($id_rep));
        if ($uid) {
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute(array($uid));
        } else {
            $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND rol = 'repartidor'")->execute(array($id_rep));
        }
        $pdo->prepare("DELETE FROM repartidores WHERE id_repartidor = ?")->execute(array($id_rep));
        $pdo->commit();
        jsonOk();

    default:
        jsonErr('Acción no válida');
}

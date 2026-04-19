<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/../conex-switch.php';

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
    if (!$token) { http_response_code(401); echo json_encode(array('status'=>'error','message'=>'Token requerido')); exit; }
    $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE api_token = ? AND activo = 1 LIMIT 1");
    $stmt->execute(array($token));
    $user = $stmt->fetch();
    if (!$user || $user['rol'] !== 'admin') { http_response_code(403); echo json_encode(array('status'=>'error','message'=>'Acceso denegado')); exit; }
}

requireAdmin($pdo);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$body   = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = array();

function jsonOk($data = array()) { echo json_encode(array_merge(array('status'=>'success'), $data)); exit; }
function jsonErr($msg, $code = 400) { http_response_code($code); echo json_encode(array('status'=>'error','message'=>$msg)); exit; }

switch ($action) {

    // ── Listar ────────────────────────────────────────────────────────────────
    case 'list':
        $rows = $pdo->query("
            SELECT
                id                                      AS uid,
                id                                      AS id_repartidor,
                username,
                COALESCE(nombre_completo, username)     AS nombre_completo,
                COALESCE(telefono, '')                  AS telefono,
                COALESCE(email, '')                     AS email,
                COALESCE(estado, 'No disponible')       AS estado,
                activo,
                ultima_actualizacion,
                latitud,
                longitud,
                COALESCE(foto_url, '')                  AS foto_url
            FROM usuarios
            WHERE rol = 'repartidor'
            ORDER BY id
        ")->fetchAll();
        jsonOk(array('repartidores' => $rows));

    // ── Crear ─────────────────────────────────────────────────────────────────
    case 'create':
        $nombre   = trim(isset($body['nombre_completo']) ? $body['nombre_completo'] : '');
        $username = trim(isset($body['username'])        ? $body['username']        : '');
        $password = isset($body['password'])             ? $body['password']        : '';
        $telefono = trim(isset($body['telefono'])        ? $body['telefono']        : '') ?: null;
        $email    = trim(isset($body['email'])           ? $body['email']           : '') ?: null;

        if (!$nombre || !$username || !$password) jsonErr('nombre_completo, username y password son obligatorios');
        if (strlen($password) < 6) jsonErr('La contraseña debe tener al menos 6 caracteres');

        $check = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? LIMIT 1");
        $check->execute(array($username));
        if ($check->fetch()) jsonErr("El usuario '$username' ya existe");

        $hash  = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        try {
            $pdo->prepare("
                INSERT INTO usuarios (username, password_hash, rol, api_token, activo, nombre_completo, telefono, email, estado)
                VALUES (?, ?, 'repartidor', ?, 1, ?, ?, ?, 'No disponible')
            ")->execute(array($username, $hash, $token, $nombre, $telefono, $email));

            jsonOk(array('id' => (int)$pdo->lastInsertId()));
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') jsonErr("El usuario '$username' ya existe");
            jsonErr('Error al crear: ' . $e->getMessage(), 500);
        }

    // ── Editar perfil completo ────────────────────────────────────────────────
    case 'update_user':
        $uid      = (int)(isset($body['uid']) ? $body['uid'] : 0);
        $nombre   = trim(isset($body['nombre_completo']) ? $body['nombre_completo'] : '') ?: null;
        $username = trim(isset($body['username'])        ? $body['username']        : '');
        $telefono = trim(isset($body['telefono'])        ? $body['telefono']        : '') ?: null;
        $email    = trim(isset($body['email'])           ? $body['email']           : '') ?: null;
        if (!$uid) jsonErr('uid requerido');
        // Verificar que el username no lo use otro
        if ($username) {
            $chk = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ? LIMIT 1");
            $chk->execute(array($username, $uid));
            if ($chk->fetch()) jsonErr("El usuario '$username' ya está en uso");
        }
        $pdo->prepare("UPDATE usuarios SET nombre_completo=?, telefono=?, email=?, username=COALESCE(NULLIF(?,''), username) WHERE id=? AND rol='repartidor'")
            ->execute(array($nombre, $telefono, $email, $username, $uid));
        jsonOk();

    // ── Toggle activo ─────────────────────────────────────────────────────────
    case 'toggle':
        $uid    = (int)(isset($body['uid']) ? $body['uid'] : (isset($body['id_repartidor']) ? $body['id_repartidor'] : 0));
        $activo = (int)(isset($body['activo']) ? $body['activo'] : 0);
        if (!$uid) jsonErr('uid requerido');
        $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ? AND rol = 'repartidor'")->execute(array($activo, $uid));
        jsonOk();

    // ── Cambiar contraseña ────────────────────────────────────────────────────
    case 'change_password':
        $uid      = (int)(isset($body['uid']) ? $body['uid'] : (isset($body['id_repartidor']) ? $body['id_repartidor'] : 0));
        $password = isset($body['password']) ? $body['password'] : '';
        if (!$uid || !$password) jsonErr('uid y password requeridos');
        if (strlen($password) < 6) jsonErr('Mínimo 6 caracteres');
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $stmt->execute(array($hash, $uid));
        if (!$stmt->rowCount()) jsonErr('Usuario no encontrado');
        jsonOk();

    // ── Eliminar ──────────────────────────────────────────────────────────────
    case 'delete':
        $uid = (int)(isset($body['uid']) ? $body['uid'] : (isset($body['id_repartidor']) ? $body['id_repartidor'] : 0));
        if (!$uid) jsonErr('uid requerido');
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM posiciones_historial WHERE id_repartidor = ?")->execute(array($uid));
        $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND rol = 'repartidor'")->execute(array($uid));
        $pdo->commit();
        jsonOk();

    default:
        jsonErr('Acción no válida');
}

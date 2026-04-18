<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../conex-switch.php';

function getUser($pdo) {
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
    if (!$token) $token = isset($_POST['token']) ? trim($_POST['token']) : (isset($_GET['token']) ? trim($_GET['token']) : '');
    if (!$token) return null;
    $stmt = $pdo->prepare("SELECT id, username, rol FROM usuarios WHERE api_token = ? AND activo = 1 LIMIT 1");
    $stmt->execute(array($token));
    return $stmt->fetch();
}

$user = getUser($pdo);
if (!$user) { http_response_code(401); echo json_encode(array('status'=>'error','message'=>'No autorizado')); exit; }

$uid    = (int)$user['id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

function jsonOk($data = array()) { echo json_encode(array_merge(array('status'=>'success'), $data)); exit; }
function jsonErr($msg, $code = 400) { http_response_code($code); echo json_encode(array('status'=>'error','message'=>$msg)); exit; }

switch ($action) {

    case 'get':
        $stmt = $pdo->prepare("SELECT id, username, nombre_completo, telefono, email, foto_url, estado FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute(array($uid));
        $perfil = $stmt->fetch();
        if (!$perfil) jsonErr('Perfil no encontrado', 404);
        jsonOk(array('perfil' => $perfil));

    case 'update_profile':
        $body     = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) $body = array();
        $telefono = trim(isset($body['telefono']) ? $body['telefono'] : '') ?: null;
        $email    = trim(isset($body['email'])    ? $body['email']    : '') ?: null;
        $pdo->prepare("UPDATE usuarios SET telefono = ?, email = ? WHERE id = ?")->execute(array($telefono, $email, $uid));
        jsonOk();

    case 'change_password':
        $body       = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) $body = array();
        $pwd_actual = isset($body['password_actual']) ? $body['password_actual'] : '';
        $pwd_nueva  = isset($body['password_nueva'])  ? $body['password_nueva']  : '';
        if (!$pwd_actual || !$pwd_nueva) jsonErr('Faltan campos');
        if (strlen($pwd_nueva) < 6) jsonErr('Mínimo 6 caracteres');
        $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute(array($uid));
        $row = $stmt->fetch();
        if (!$row || !password_verify($pwd_actual, $row['password_hash'])) jsonErr('Contraseña actual incorrecta', 403);
        $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?")->execute(array(password_hash($pwd_nueva, PASSWORD_DEFAULT), $uid));
        jsonOk();

    case 'upload_photo':
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) jsonErr('No se recibió foto válida');
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('jpg','jpeg','png','webp','gif'))) jsonErr('Formato no permitido');
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fileName = 'perfil_user_' . $uid . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $fileName)) jsonErr('Error al guardar la imagen', 500);
        $fotoUrl = 'uploads/' . $fileName;
        // Borrar foto anterior
        $old = $pdo->prepare("SELECT foto_url FROM usuarios WHERE id = ? LIMIT 1");
        $old->execute(array($uid));
        $oldRow = $old->fetch();
        if ($oldRow && $oldRow['foto_url']) { $p = __DIR__ . '/../' . $oldRow['foto_url']; if (file_exists($p)) @unlink($p); }
        $pdo->prepare("UPDATE usuarios SET foto_url = ? WHERE id = ?")->execute(array($fotoUrl, $uid));
        jsonOk(array('foto_url' => $fotoUrl));

    default:
        jsonErr('Acción no válida');
}

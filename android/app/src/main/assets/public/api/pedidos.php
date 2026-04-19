<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/../conex-switch.php';

// Auth: cualquier usuario activo
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
    if (!$token) return null;
    $stmt = $pdo->prepare("SELECT id, rol FROM usuarios WHERE api_token = ? AND activo = 1 LIMIT 1");
    $stmt->execute(array($token));
    return $stmt->fetch();
}

$user = getUser($pdo);
if (!$user) { http_response_code(401); echo json_encode(array('status'=>'error','message'=>'No autorizado')); exit; }

$uid    = (int)$user['id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'mis_pedidos';
$body   = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = array();

function jsonOk($data = array()) { echo json_encode(array_merge(array('status'=>'success'), $data)); exit; }
function jsonErr($msg, $code = 400) { http_response_code($code); echo json_encode(array('status'=>'error','message'=>$msg)); exit; }

switch ($action) {

    // Pedidos asignados al repartidor logueado
    case 'mis_pedidos':
        $stmt = $pdo->prepare("
            SELECT id_pedido, cliente_nombre, direccion_entrega, estado, fecha_creacion
            FROM pedidos
            WHERE id_repartidor = ?
            ORDER BY FIELD(estado,'en camino','pendiente','entregado'), fecha_creacion DESC
            LIMIT 20
        ");
        $stmt->execute(array($uid));
        jsonOk(array('pedidos' => $stmt->fetchAll()));

    // Admin: todos los pedidos activos con nombre del repartidor
    case 'todos':
        if ($user['rol'] !== 'admin') jsonErr('Solo admins', 403);
        $pedidos = $pdo->query("
            SELECT p.id_pedido, p.cliente_nombre, p.direccion_entrega, p.estado,
                   p.fecha_creacion, p.id_repartidor,
                   COALESCE(u.nombre_completo, u.username) AS repartidor_nombre
            FROM pedidos p
            LEFT JOIN usuarios u ON u.id = p.id_repartidor
            WHERE p.estado != 'entregado'
            ORDER BY p.fecha_creacion DESC
        ")->fetchAll();
        jsonOk(array('pedidos' => $pedidos));

    // Actualizar estado de un pedido (repartidor solo puede actualizar los suyos)
    case 'update_estado':
        $id_pedido    = (int)(isset($body['id_pedido'])  ? $body['id_pedido']  : 0);
        $nuevo_estado = trim(isset($body['estado'])      ? $body['estado']     : '');
        $estados_ok   = array('pendiente','en camino','entregado','cancelado');
        if (!$id_pedido || !$nuevo_estado) jsonErr('id_pedido y estado requeridos');
        if (!in_array($nuevo_estado, $estados_ok)) jsonErr('Estado no válido');

        if ($user['rol'] === 'admin') {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
            $stmt->execute(array($nuevo_estado, $id_pedido));
        } else {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ? AND id_repartidor = ?");
            $stmt->execute(array($nuevo_estado, $id_pedido, $uid));
        }
        if (!$stmt->rowCount()) jsonErr('Pedido no encontrado o sin permiso', 404);
        jsonOk();

    // Asignar pedido a repartidor (admin)
    case 'asignar':
        if ($user['rol'] !== 'admin') jsonErr('Solo admins', 403);
        $id_pedido    = (int)(isset($body['id_pedido'])     ? $body['id_pedido']     : 0);
        $id_rep       = (int)(isset($body['id_repartidor']) ? $body['id_repartidor'] : 0);
        if (!$id_pedido) jsonErr('id_pedido requerido');
        $pdo->prepare("UPDATE pedidos SET id_repartidor = ? WHERE id_pedido = ?")
            ->execute(array($id_rep ?: null, $id_pedido));
        jsonOk();

    default:
        jsonErr('Acción no válida');
}

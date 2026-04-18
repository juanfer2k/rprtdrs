<?php
// --- Archivo: www/gestionar_repartidores.php (VERSIÓN FINAL CORREGIDA) ---

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Incluir la conexión a la base de datos de forma robusta
require_once __DIR__ . '/conex-switch.php';

function readInputData(): array {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }

    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }

    return $data;
}

function normalizeUsername(string $value): string {
    $value = strtolower(trim($value));
    return preg_replace('/[^a-z0-9._-]/', '', $value);
}

function generateTokenSafe(): string {
    try {
        return bin2hex(random_bytes(24));
    } catch (Exception $e) {
        return bin2hex(openssl_random_pseudo_bytes(24));
    }
}

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
$data = readInputData();

switch ($action) {

    case 'read':
        // Esta acción devuelve la lista de usuarios para la tabla de "Gestión"
        $stmt = $pdo->query("SELECT r.id_repartidor, r.nombre_completo, r.email, r.telefono, r.foto_url, r.activo, r.estado, u.username, u.rol
                             FROM repartidores r
                             LEFT JOIN usuarios u ON u.id = r.id_repartidor
                             ORDER BY r.nombre_completo");
        echo json_encode($stmt->fetchAll());
        break;

    case 'create':
        $nombre = trim((string)($data['nombre_completo'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $telefono = trim((string)($data['telefono'] ?? ''));
        $usernameInput = normalizeUsername((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($nombre === '' || $email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'nombre_completo, email y password son obligatorios']);
            break;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Email inválido']);
            break;
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres']);
            break;
        }

        $usernameBase = $usernameInput !== '' ? $usernameInput : normalizeUsername((string)explode('@', $email)[0]);
        if ($usernameBase === '') {
            $usernameBase = 'rep';
        }

        try {
            $pdo->beginTransaction();

            $stmtEmail = $pdo->prepare("SELECT id_repartidor FROM repartidores WHERE email = ? LIMIT 1");
            $stmtEmail->execute([$email]);
            if ($stmtEmail->fetch()) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'Ya existe un repartidor con ese email']);
                break;
            }

            $username = $usernameBase;
            $counter = 0;
            do {
                $candidate = $counter === 0 ? $usernameBase : $usernameBase . $counter;
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
                echo json_encode(['status' => 'error', 'message' => 'No fue posible generar username único']);
                break;
            }

            $token = generateTokenSafe();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $pdo->prepare("INSERT INTO usuarios (username, password_hash, api_token, rol, activo) VALUES (?, ?, ?, 'repartidor', 1)")
                ->execute([$username, $passwordHash, $token]);

            $idRepartidor = (int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO repartidores (id_repartidor, nombre_completo, email, telefono, activo, estado) VALUES (?, ?, ?, ?, 1, 'No disponible')")
                ->execute([$idRepartidor, $nombre, $email, $telefono !== '' ? $telefono : null]);

            $pdo->commit();
            echo json_encode([
                'status' => 'success',
                'message' => 'Repartidor creado correctamente',
                'id_repartidor' => $idRepartidor,
                'username' => $username
            ]);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al crear repartidor: ' . $e->getMessage()]);
        }
        break;

    case 'update':
        $idRepartidor = (int)($data['id_repartidor'] ?? 0);
        if ($idRepartidor <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'id_repartidor inválido']);
            break;
        }

        $nombre = trim((string)($data['nombre_completo'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $telefono = trim((string)($data['telefono'] ?? ''));
        $estado = trim((string)($data['estado'] ?? ''));
        $username = normalizeUsername((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');

        try {
            $pdo->beginTransaction();

            if ($nombre !== '' || $email !== '' || $telefono !== '' || $estado !== '') {
                $fields = [];
                $params = [];

                if ($nombre !== '') {
                    $fields[] = 'nombre_completo = ?';
                    $params[] = $nombre;
                }
                if ($email !== '') {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new RuntimeException('Email inválido');
                    }
                    $fields[] = 'email = ?';
                    $params[] = $email;
                }
                if ($telefono !== '') {
                    $fields[] = 'telefono = ?';
                    $params[] = $telefono;
                }
                if ($estado !== '') {
                    $fields[] = 'estado = ?';
                    $params[] = $estado;
                }

                if (!empty($fields)) {
                    $params[] = $idRepartidor;
                    $sql = 'UPDATE repartidores SET ' . implode(', ', $fields) . ' WHERE id_repartidor = ?';
                    $pdo->prepare($sql)->execute($params);
                }
            }

            if ($username !== '' || $password !== '') {
                $fields = [];
                $params = [];

                if ($username !== '') {
                    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id <> ? LIMIT 1");
                    $stmt->execute([$username, $idRepartidor]);
                    if ($stmt->fetch()) {
                        throw new RuntimeException('El username ya está en uso');
                    }
                    $fields[] = 'username = ?';
                    $params[] = $username;
                }

                if ($password !== '') {
                    if (strlen($password) < 8) {
                        throw new RuntimeException('La contraseña debe tener al menos 8 caracteres');
                    }
                    $fields[] = 'password_hash = ?';
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                if (!empty($fields)) {
                    $params[] = $idRepartidor;
                    $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = ?';
                    $pdo->prepare($sql)->execute($params);
                }
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Repartidor actualizado']);
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        }
        break;

    case 'toggle_active':
        $idRepartidor = (int)($data['id_repartidor'] ?? 0);
        $activo = (int)($data['activo'] ?? 0) === 1 ? 1 : 0;

        if ($idRepartidor <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'id_repartidor inválido']);
            break;
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE repartidores SET activo = ? WHERE id_repartidor = ?")->execute([$activo, $idRepartidor]);
            $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute([$activo, $idRepartidor]);
            $pdo->commit();

            echo json_encode(['status' => 'success', 'message' => $activo ? 'Repartidor activado' : 'Repartidor desactivado']);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar estado: ' . $e->getMessage()]);
        }
        break;

    case 'get_map_data':
        // Esta acción es para el dashboard, pero la hemos delegado a obtener_ubicaciones.php
        // La mantenemos funcional por si se usa en el futuro.
        $stmt = $pdo->query("
            SELECT
                id_repartidor,
                nombre_completo,
                latitud,
                longitud,
                estado,
                ultima_actualizacion AS ultimo_update,
                activo
            FROM repartidores
            WHERE activo = 1
        ");
        echo json_encode($stmt->fetchAll());
        break;

    default:
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida o no especificada.']);
        break;
}
?>

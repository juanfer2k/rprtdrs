<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'conex.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['id_repartidor'], $data['lat'], $data['lng'], $data['estado'])) {
    try {
        $sql = "
            UPDATE repartidores
            SET latitud = :lat,
                longitud = :lng,
                estado = :estado,
                ultima_actualizacion = NOW()
            WHERE id_repartidor = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'     => $data['id_repartidor'],
            ':lat'    => $data['lat'],
            ':lng'    => $data['lng'],
            ':estado' => $data['estado']
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Repartidor no encontrado"]);
            exit;
        }

        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error al ejecutar la consulta."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
}
<?php
require_once __DIR__ . '/conex.php';
try {
    $stmt = $pdo->query("DESCRIBE repartidores");
    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

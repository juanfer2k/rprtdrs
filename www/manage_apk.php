<?php
$file_path = __DIR__ . '/repartidores.apk';
$message = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            $message = "El archivo repartidores.apk ha sido eliminado exitosamente.";
        } else {
            $message = "Error: No se pudo eliminar el archivo. Verifique los permisos.";
        }
    } else {
        $message = "El archivo repartidores.apk no existe en el servidor.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de APK</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { text-align: center; padding-top: 50px; }
        .message { margin-bottom: 20px; font-size: 1.2em; }
        a { color: #00796b; }
    </style>
</head>
<body>
    <div class="message"><?php echo $message; ?></div>
    <a href="admin.php">Volver al Panel de Administración</a>
</body>
</html>

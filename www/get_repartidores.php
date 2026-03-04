<?php
// --- Archivo: www/get_repartidores.php ---

// 1. Incluir nuestro archivo de conexión.
//    Esto nos da acceso a la variable $conexion.
require_once 'conexion.php';

// 2. Preparar la consulta SQL para obtener los datos.
//    Seleccionamos las columnas que necesita el frontend.
//    Asumo que tu tabla se llama 'repartidores' y las columnas tienen nombres similares.
//    Ajusta los nombres si son diferentes en tu base de datos.
$sql = "SELECT id, nombre, latitud, longitud, estado, ultimo_update FROM repartidores";

// 3. Ejecutar la consulta.
$resultado = $conexion->query($sql);

// 4. Preparar un array para almacenar los resultados.
$repartidores = [];

// 5. Verificar si la consulta fue exitosa y si devolvió filas.
if ($resultado && $resultado->num_rows > 0) {
    // Recorrer cada fila del resultado y añadirla a nuestro array.
    // fetch_assoc() devuelve cada fila como un array asociativo (clave => valor).
    while ($fila = $resultado->fetch_assoc()) {
        $repartidores[] = $fila;
    }
}

// 6. Cerrar la conexión a la base de datos.
$conexion->close();

// 7. Enviar la respuesta en formato JSON.
//    Esto es lo que el JavaScript del panel de administrador espera recibir.
//    Si no se encontraron repartidores, se enviará un array vacío: [].
header('Content-Type: application/json');
echo json_encode($repartidores);
?>

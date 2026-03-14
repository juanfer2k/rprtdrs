-- Initial Schema for Delivery Logistics App

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    rol ENUM('admin', 'repartidor') NOT NULL,
    api_token VARCHAR(255) UNIQUE NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS repartidores (
    id_repartidor INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(20),
    foto_url VARCHAR(255),
    latitud DECIMAL(10, 8),
    longitud DECIMAL(11, 8),
    estado ENUM('Disponible', 'No disponible', 'En camino a recoger', 'En camino a entrega', 'Pedido Entregado', 'libre', 'ocupado', 'desconectado') DEFAULT 'No disponible',
    activo BOOLEAN DEFAULT TRUE,
    ultima_actualizacion DATETIME,
    FOREIGN KEY (id_repartidor) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_repartidor INT,
    cliente_nombre VARCHAR(100) NOT NULL,
    direccion_entrega TEXT NOT NULL,
    lat_destino DECIMAL(10, 8),
    lng_destino DECIMAL(11, 8),
    estado ENUM('disponible', 'asignado', 'en_ruta', 'entregado', 'incidencia') DEFAULT 'disponible',
    notas TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega DATETIME,
    FOREIGN KEY (id_repartidor) REFERENCES repartidores(id_repartidor)
);

CREATE TABLE IF NOT EXISTS posiciones_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_repartidor INT NOT NULL,
    id_pedido INT,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (id_repartidor, fecha_registro),
    FOREIGN KEY (id_repartidor) REFERENCES repartidores(id_repartidor)
);

CREATE TABLE IF NOT EXISTS historial_estados_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50),
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
);

-- Initial Mock Data (Optional for testing)
INSERT INTO usuarios (username, password_hash, rol, api_token) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'token_admin_test'),
('repartidor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'repartidor', 'token_repartidor_test');

INSERT INTO repartidores (id_repartidor, nombre_completo, telefono, estado) VALUES 
(2, 'Juan Perez', '123456789', 'desconectado');

-- =============================================
-- Script de插入 datos iniciales - Repartidores
-- =============================================
-- Ejecutar en DBeaver después de crear las tablas
-- Base de datos: elcerrit_rprtdrs
-- Usuario: elcerrit_rprtdrs

USE elcerrit_rprtdrs;

-- Insertar Admin (usuario: admin, contraseña: Senseii)
INSERT INTO usuarios (username, password_hash, email, rol, api_token, activo) 
VALUES ('admin', '$2b$12$yc5IJ0Qt4dElLhzl2fzoQewI/nV3tZfFZp1PfygR5RhGlK5QrRFJS', 'admin@elcerritovalle.org', 'admin', 'token_admin_senseii', 1);

-- Insertar Repartidor 1 (usuario: repartidor1, contraseña: Pizza)
INSERT INTO usuarios (username, password_hash, email, rol, api_token, activo) 
VALUES ('repartidor1', '$2b$12$6lrGpBdCeFa7QBz.c8.Oo.i076UvdfLyNLumvEmGRKBhexUfRjUcW', 'repartidor1@elcerritovalle.org', 'repartidor', 'token_repartidor1_pizza', 1);

-- Insertar Repartidor 2 (usuario: repartidor2, contraseña: Pizza)
INSERT INTO usuarios (username, password_hash, email, rol, api_token, activo) 
VALUES ('repartidor2', '$2b$12$6lrGpBdCeFa7QBz.c8.Oo.i076UvdfLyNLumvEmGRKBhexUfRjUcW', 'repartidor2@elcerritovalle.org', 'repartidor', 'token_repartidor2_pizza', 1);

-- Crear registros de repartidores vinculados a los usuarios
INSERT INTO repartidores (id_repartidor, nombre_completo, email, telefono, estado, activo) 
VALUES 
(2, 'Juan Pérez', 'repartidor1@elcerritovalle.org', '3001234567', 'desconectado', 1),
(3, 'Carlos García', 'repartidor2@elcerritovalle.org', '3007654321', 'desconectado', 1);

-- Verificar inserción
SELECT id, username, email, rol, activo FROM usuarios;
SELECT id_repartidor, nombre_completo, email, estado, activo FROM repartidores;
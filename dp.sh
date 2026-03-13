#!/bin/bash

# --- Script de Despliegue Automático (dp.sh) ---
# Este script actualiza el repositorio y asegura los permisos correctos.

echo "🚀 Iniciando actualización de rprtdrs..."

# 1. Obtener cambios del repositorio
echo "📥 Tirando de los últimos cambios desde GitHub..."
git pull origin master

# 2. Configurar permisos
# Aseguramos que el servidor web pueda leer los archivos y escribir en 'uploads'
echo "🔐 Ajustando permisos de archivos..."
chmod -R 755 www/
if [ -d "www/uploads" ]; then
    chmod -R 777 www/uploads/
fi

# 3. Sincronización de Capacitor (Si se han hecho cambios en la web)
if command -v npx &> /dev/null; then
    echo "📲 Sincronizando cambios con Capacitor..."
    npx cap copy android
fi

# 4. Docker (Opcional: Descomenta si quieres que el contenedor se reinicie)
# echo "🐳 Reiniciando contenedores Docker..."
# docker-compose up -d --build

echo "✅ WebApp actualizada con éxito."

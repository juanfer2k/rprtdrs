#!/bin/bash

# --- Script de Despliegue Automático (dp.sh) ---
# Copia el contenido de www/ a la raíz de la instancia

echo "🚀 Iniciando actualización en el servidor..."

# 1. Pull de cambios (dentro del repo)
echo "📥 Obteniendo cambios de GitHub..."
git pull origin master

# 2. Copiar contenido de www/ a la raíz (.)
echo "📂 Sincronizando carpeta www con la raíz..."
cp -r www/* .

# 3. Permisos
echo "🔐 Ajustando permisos..."
chmod -R 755 .
if [ -d "uploads" ]; then
    chmod -R 777 uploads/
fi

# 4. Limpieza
if [ -f "error_log" ]; then
    rm error_log
    touch error_log
    chmod 666 error_log
fi

echo "✅ WebApp actualizada y desplegada en la raíz."

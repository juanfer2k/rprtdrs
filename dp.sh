#!/bin/bash

# --- Script de Despliegue Automático (dp.sh) ---
# Copia el contenido de www/ a la raíz de la instancia

# Configuración
DEPLOY_DIR="/home2/rprtdrs"
REPO_URL="git@github.com:juanfer2k/rprtdrs.git"
BRANCH="master"

echo "🚀 Iniciando actualización en el servidor..."

# 1. Verificar si es un repositorio git, si no, clonar
if [ ! -d ".git" ]; then
    echo "📦 No se detectó repositorio Git. Clonando..."
    if [ -d "$DEPLOY_DIR" ]; then
        echo "⚠️  El directorio ya existe. Respaldando..."
        mv "$DEPLOY_DIR" "${DEPLOY_DIR}_backup_$(date +%s)"
    fi
    git clone -b $BRANCH $REPO_URL $DEPLOY_DIR
    cd $DEPLOY_DIR
fi

# 2. Pull de cambios
echo "📥 Obteniendo cambios de GitHub..."
git pull origin $BRANCH

# 3. Copiar contenido de www/ a la raíz
echo "📂 Sincronizando carpeta www con la raíz..."
cp -r www/* .

# 4. Permisos
echo "🔐 Ajustando permisos..."
chmod -R 755 .
if [ -d "uploads" ]; then
    chmod -R 777 uploads/
fi

# 5. Limpieza
if [ -f "error_log" ]; then
    rm error_log
    touch error_log
    chmod 666 error_log
fi

echo "✅ WebApp actualizada y desplegada en la raíz."

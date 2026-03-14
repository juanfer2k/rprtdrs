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

# 2. Verificar que existe la carpeta www
if [ ! -d "www" ]; then
    echo "❌ Error: No se encontró la carpeta www/"
    exit 1
fi

# 3. Pull de cambios
echo "📥 Obteniendo cambios de GitHub..."
git pull origin $BRANCH

# 4. Copiar contenido de www/ a la raíz
echo "📂 Sincronizando carpeta www con la raíz..."
rm -rf ./* 2>/dev/null
cp -r www/* .
cp -r www/.* . 2>/dev/null

# 5. Permisos
echo "🔐 Ajustando permisos..."
chmod -R 755 .
if [ -d "uploads" ]; then
    chmod -R 777 uploads/
fi

# 6. Limpieza
if [ -f "error_log" ]; then
    rm error_log
    touch error_log
    chmod 666 error_log
fi

echo "✅ WebApp actualizada y desplegada en la raíz."
echo "📍 Accede a: https://elcerritovalle.org/rprtdrs"

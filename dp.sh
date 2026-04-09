#!/bin/bash
set -euo pipefail

# --- Script de Despliegue Automático (dp.sh) ---
# Copia el contenido de www/ a la raíz de la instancia

# Configuración.
DEPLOY_DIR="/home2/elcerrit/elcerritovalle.org/rprtdrs"
REPO_URL="git@github.com:juanfer2k/rprtdrs.git"
BRANCH="master"

echo "🚀 Iniciando actualización en el servidor..."
echo "📍 Directorio: $DEPLOY_DIR"

# 1. Asegurar que estamos en el directorio correcto
if [ -d "$DEPLOY_DIR" ]; then
    cd "$DEPLOY_DIR"
fi

# 2. Verificar si es un repositorio git, si no, clonar
if [ ! -d ".git" ]; then
    echo "📦 No se detectó repositorio Git. Clonando..."
    if [ "$(ls -A .)" ]; then
        echo "⚠️ El directorio contiene archivos pero no es git. Respaldando..."
        BACKUP_NAME="../rprtdrs_backup_$(date +%s)"
        mv "$DEPLOY_DIR" "$BACKUP_NAME"
        mkdir -p "$DEPLOY_DIR"
        cd "$DEPLOY_DIR"
    fi
    git clone -b $BRANCH $REPO_URL .
fi

# 3. Pull de cambios
echo "📥 Obteniendo cambios de GitHub..."
# Intentar stash si hay cambios locales para evitar abortos
HAS_CHANGES=$(git status --porcelain | wc -l)
if [ "$HAS_CHANGES" -gt 0 ]; then
    echo "⚠️ Se detectaron cambios locales. Guardando en stash..."
    git stash
fi

if ! git pull origin $BRANCH; then
    echo "❌ Error: git pull falló. Verifica conflictos manualmente."
    exit 1
fi

# Aplicar stash si se guardó algo
if [ "$HAS_CHANGES" -gt 0 ]; then
    echo "📥 Restaurando cambios locales desde stash..."
    git stash pop || echo "⚠️ Conflictos al restaurar stash, por favor revisa."
fi

# 4. Verificar que existe la carpeta www o usar archivos locales
echo "📂 Preparando archivos para despliegue..."

# Check if www/ exists in repo, if not use local www/
if [ -d "www" ]; then
    echo "📁 Carpeta www/ encontrada en repositorio"
    SOURCE_DIR="www"
elif [ -d "/home/j/proyectos/repartidores/www" ]; then
    echo "📁 Usando carpeta www/ local del proyecto"
    # Copy local www to deploy dir first
    cp -r /home/j/proyectos/repartidores/www "$DEPLOY_DIR/"
    SOURCE_DIR="www"
else
    # Check if files are already in root (already deployed)
    if [ -f "conex.php" ] || [ -f "index.php" ]; then
        echo "📁 Archivos ya están en la raíz, sincronizando desde local..."
        SOURCE_DIR=""
    else
        echo "❌ Error: No se encontró la carpeta www/ ni archivos en la raíz."
        echo "📋 Contenido actual:"
        ls -la
        exit 1
    fi
fi

# 5. Sincronizar contenido a la raíz (FORMA SEGURA)
if [ -n "$SOURCE_DIR" ]; then
    echo "📂 Sincronizando $SOURCE_DIR/ con la raíz..."
    if command -v rsync >/dev/null 2>&1; then
        rsync -av --delete --exclude='.git' --exclude='www' --exclude='dp.sh' --exclude='error_log' --exclude='uploads' "$SOURCE_DIR/" .
    else
        echo "⚠️ rsync no encontrado, usando cp (menos seguro)..."
        cp -r "$SOURCE_DIR"/* .
        cp -r "$SOURCE_DIR"/.* . 2>/dev/null || true
    fi
else
    # Direct sync from local
    echo "📂 Sincronizando archivos locales..."
    rsync -av --delete --exclude='.git' --exclude='www' --exclude='dp.sh' --exclude='error_log' --exclude='uploads' /home/j/proyectos/repartidores/www/ .
fi

# 6. Permisos
echo "🔐 Ajustando permisos..."
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \; || true
if [ -d "uploads" ]; then
    chmod -R 777 uploads/
fi

# 7. Limpieza
if [ -f "error_log" ]; then
    chmod 666 error_log
fi

echo "✅ WebApp actualizada y desplegada en la raíz."
echo "📍 Accede a: https://elcerritovalle.org/rprtdrs"
